import paho.mqtt.client as mqtt
from datetime import datetime, timedelta
import firebase_admin
from firebase_admin import credentials, db

broker = "broker.emqx.io"
port = 1883

topics = {
    "fire": "events/fire_events",
    "machine": "events/machine_runtime",
    "motion": "events/motion_events",
    "temperature": "events/temperature_events",
    "vibration": "events/vibration_events"
}

machine_on_time = None

# Inisialisasi Firebase
cred = credentials.Certificate("C:\\Users\\HP\\Downloads\\iotboxdatabase-firebase-adminsdk-fbsvc-d340373f18.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://iotboxdatabase-default-rtdb.asia-southeast1.firebasedatabase.app'
})

# Buffer data sensor dan waktu penerimaan
sensor_data = {
    "fire": None,
    "motion": None,
    "temperature": None,
    "vibration": None
}
sensor_time = {
    "fire": None,
    "motion": None,
    "temperature": None,
    "vibration": None
}

THRESHOLD_SECONDS = 5  # toleransi waktu supaya data dianggap "berkaitan"

def try_push_combined_data():
    # Pastikan semua sensor sudah punya data
    if all(sensor_data[s] is not None for s in sensor_data):
        times = [sensor_time[s] for s in sensor_time]
        # Cek apakah waktu data semua sensor beda kurang dari threshold
        if max(times) - min(times) <= timedelta(seconds=THRESHOLD_SECONDS):
            now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            data = {
                "fire": sensor_data["fire"],
                "motion": sensor_data["motion"],
                "temperature": sensor_data["temperature"],
                "vibration": sensor_data["vibration"],
                "timestamp": now
            }
            db.reference("sensor_data").push(data)
            print(f"[DATA PUSHED] {data}")

def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker")
    for t in topics.values():
        client.subscribe(t)

def on_message(client, userdata, msg):
    global machine_on_time
    topic = msg.topic
    message = msg.payload.decode()
    now = datetime.now()

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
        elif message.lower() == 'off' and machine_on_time:
            machine_off_time = now
            runtime = machine_off_time - machine_on_time
            runtime_str = str(runtime).split('.')[0]

            db.reference("machine").push({
                'on': machine_on_time.strftime('%Y-%m-%d %H:%M:%S'),
                'off': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                'runtime': runtime_str,
                'timestamp': now.strftime('%Y-%m-%d %H:%M:%S')
            })
            machine_on_time = None
            print(f"[MACHINE RUNTIME] {runtime_str}")

client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message
client.connect(broker, port, 60)
client.loop_forever()
