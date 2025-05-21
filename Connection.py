import paho.mqtt.client as mqtt
from datetime import datetime, timedelta
import firebase_admin
from firebase_admin import credentials, db
import threading

# MQTT Broker Config
broker = "broker.emqx.io"
port = 1883

# MQTT Topics
topics = {
    "fire": "events/fire_events",
    "machine": "events/machine_runtime",
    "motion": "events/motion_events",
    "temperature": "events/temperature_events",
    "vibration": "events/vibration_events"
}

# Variabel global untuk track waktu mesin menyala
machine_on_time = None

# Firebase initialization
cred = credentials.Certificate("C:\\Users\\HP\\Downloads\\iotboxdatabase-firebase-adminsdk-fbsvc-d340373f18.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://iotboxdatabase-default-rtdb.asia-southeast1.firebasedatabase.app'
})

# Buffer data sensor dan waktu
sensor_data = {
    "fire": None,
    "motion": None,
    "temperature": None,
    "vibration": None,
    "machine": None
}
sensor_time = {
    "fire": None,
    "motion": None,
    "temperature": None,
    "vibration": None,
    "machine": None
}

THRESHOLD_SECONDS = 5  # toleransi waktu data
MACHINE_TIMEOUT = 15   # timeout untuk auto-off

# Status mesin untuk timeout handling
machine_status = {
    'last_on_time': None,
    'off_reported': True
}

# Fungsi cek apakah semua sensor punya data terbaru, lalu push gabungan ke Firebase
def try_push_combined_data():
    if all(sensor_data[s] is not None for s in sensor_data):
        times = [sensor_time[s] for s in sensor_time]
        if max(times) - min(times) <= timedelta(seconds=THRESHOLD_SECONDS):
            now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            data = {
                "fire": sensor_data["fire"],
                "motion": sensor_data["motion"],
                "temperature": sensor_data["temperature"],
                "vibration": sensor_data["vibration"],
                "machine": sensor_data["machine"],
                "timestamp": now
            }
            db.reference("sensor_data").push(data)
            print(f"[DATA PUSHED] {data}")

# Fungsi cek timeout mesin nyala
def check_machine_timeout():
    global machine_on_time
    if machine_status['last_on_time'] and not machine_status['off_reported']:
        elapsed = datetime.now() - machine_status['last_on_time']
        if elapsed.total_seconds() > MACHINE_TIMEOUT:
            now = datetime.now()
            db.reference("machine").push({
                'on': machine_status['last_on_time'].strftime('%Y-%m-%d %H:%M:%S'),
                'off': now.strftime('%Y-%m-%d %H:%M:%S'),
                'runtime': str(now - machine_status['last_on_time']).split('.')[0],
                'timestamp': now.strftime('%Y-%m-%d %H:%M:%S')
            })
            print(f"[MACHINE TIMEOUT - auto off] runtime: {str(now - machine_status['last_on_time']).split('.')[0]}")
            machine_status['off_reported'] = True
            machine_on_time = None

    threading.Timer(5, check_machine_timeout).start()

# Callback saat MQTT terhubung
def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker")
    for t in topics.values():
        print(f"Subscribing to: {t}")
        client.subscribe(t)
    # Wildcard subscription to all under "events/"
    client.subscribe("events/#")
    print("Subscribed to wildcard topic: events/#")


# Callback saat pesan MQTT diterima
def on_message(client, userdata, msg):
    global machine_on_time
    topic = msg.topic
    message = msg.payload.decode()
    now = datetime.now()

    # Universal debug log
    print(f"[ALL MQTT] Topic: {topic} | Message: {message}")

    if topic == topics["machine"]:
        print(f"[DEBUG] Received machine message: {message}")

    print(f"[{topic}] {message} @ {now.strftime('%Y-%m-%d %H:%M:%S')}")


    if topic == topics["fire"]:
        sensor_data["fire"] = message
        sensor_time["fire"] = now
        try_push_combined_data()

    elif topic == topics["motion"]:
        sensor_data["motion"] = message
        sensor_time["motion"] = now
        try_push_combined_data()

    elif topic == topics["temperature"]:
        sensor_data["temperature"] = message
        sensor_time["temperature"] = now
        try_push_combined_data()

    elif topic == topics["vibration"]:
        sensor_data["vibration"] = message
        sensor_time["vibration"] = now
        try_push_combined_data()

   elif topic == topics["machine"]:
        if message.lower() == 'on':
        machine_on_time = now
        machine_status['last_on_time'] = now
        machine_status['off_reported'] = False

        # Simpan data "Mesin Menyala"
        try:
            db.reference("machine_log").push({
                'status': "On",
                'timestamp': now.strftime('%Y-%m-%d %H:%M:%S'),
                'runtime': "-",
                'keterangan': "Mesin Menyala"
            })
            print("[FIREBASE] Machine ON logged")
        except Exception as e:
            print(f"[ERROR] Logging machine ON: {e}")

        sensor_data["machine"] = "on"
        sensor_time["machine"] = now
        try_push_combined_data()

    elif message.lower() == 'off' and machine_on_time:
        machine_off_time = now
        runtime = machine_off_time - machine_on_time
        runtime_str = str(runtime).split('.')[0]  # Format jadi string tanpa mikrodetik

        try:
            db.reference("machine_log").push({
                'status': "Off",
                'timestamp': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                'runtime': runtime_str,
                'keterangan': "Mesin Dimatikan"
            })
            print("[FIREBASE] Machine OFF logged")
        except Exception as e:
            print(f"[ERROR] Logging machine OFF: {e}")

        sensor_data["machine"] = "off"
        sensor_time["machine"] = now
        try_push_combined_data()

        machine_on_time = None
        machine_status['off_reported'] = True
        print(f"[MACHINE RUNTIME] {runtime_str}")


# Inisialisasi MQTT Client dan koneksi
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message
client.connect(broker, port, 60)

# Mulai cek timeout mesin secara periodik
check_machine_timeout()

# Start loop MQTT
client.loop_forever()
