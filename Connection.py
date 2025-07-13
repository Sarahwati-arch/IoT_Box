import paho.mqtt.client as mqtt
from datetime import datetime, timedelta
import firebase_admin
from firebase_admin import credentials, db
import threading
import time

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
machine_off_recorded = True
last_sensor_activity_time = None

# Inisialisasi Firebase
cred = credentials.Certificate("C:\\Users\\HP\\Downloads\\iot-box-new-firebase-adminsdk-fbsvc-374ebd4ba3.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://iot-box-new-default-rtdb.asia-southeast1.firebasedatabase.app/'
})

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

THRESHOLD_SECONDS = 5
MACHINE_TIMEOUT_SECONDS = 30

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
                "timestamp": now
            }
            db.reference("sensor_data").push(data)
            print(f"[DATA PUSHED] {data}")

def machine_timeout_checker():
    global machine_on_time, machine_off_recorded
    while True:
        if machine_on_time and not machine_off_recorded:
            now = datetime.now()
            if last_sensor_activity_time and (now - last_sensor_activity_time).total_seconds() > MACHINE_TIMEOUT_SECONDS:
                machine_off_time = now
                runtime = machine_off_time - machine_on_time
                runtime_seconds = int(runtime.total_seconds())

                # Update machine status to OFF
                db.reference("status/machine").set({
                    'status': 'OFF',
                    'timestamp': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                    'runtime': runtime_seconds
                })

                # Update machine runtime record
                ref = db.reference("machine")
                all_records = ref.order_by_key().limit_to_last(1).get()
                if all_records:
                    last_key = list(all_records.keys())[0]
                    last_record = all_records[last_key]
                    if last_record.get('off') is None:
                        ref.child(last_key).update({
                            'off': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                            'runtime': str(runtime).split('.')[0]
                        })
                        print(f"[TIMEOUT OFF] Machine off by timeout. Runtime: {runtime_seconds} seconds")

                machine_on_time = None
                machine_off_recorded = True
        time.sleep(1)

def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker")
    for t in topics.values():
        client.subscribe(t)

def on_message(client, userdata, msg):
    global machine_on_time, machine_off_recorded, last_sensor_activity_time
    topic = msg.topic
    message = msg.payload.decode()
    now = datetime.now()

    print(f"[{topic}] {message} @ {now.strftime('%Y-%m-%d %H:%M:%S')}")

    if topic in [topics["fire"], topics["motion"], topics["temperature"], topics["vibration"]]:
        sensor_type = list(topics.keys())[list(topics.values()).index(topic)]
        sensor_data[sensor_type] = message
        sensor_time[sensor_type] = now
        last_sensor_activity_time = now
        try_push_combined_data()

    elif topic == topics["machine"]:
        try:
            status, timestr = message.split("|")
            time_event = datetime.strptime(timestr, "%Y-%m-%dT%H:%M:%S")
        except Exception as e:
            print(f"[ERROR parsing machine_runtime] {e}")
            return

        if status == 'on':
            if machine_on_time is None:
                machine_on_time = time_event
                machine_off_recorded = False
                last_sensor_activity_time = time_event

                # Update status machine (real-time) - FIXED PATH
                db.reference("status/machine").set({
                    'status': 'ON',
                    'timestamp': machine_on_time.strftime('%Y-%m-%d %H:%M:%S'),
                    'runtime': 0
                })

                # CREATE initial machine runtime record
                db.reference("machine").push({
                    'on': machine_on_time.strftime('%Y-%m-%d %H:%M:%S'),
                    'off': None,
                    'runtime': None
                })

                print(f"[MACHINE ON] {machine_on_time}")
            else:
                print("[IGNORED] Machine already ON")

        elif status == 'off' and machine_on_time:
            machine_off_time = time_event
            runtime = machine_off_time - machine_on_time
            runtime_seconds = int(runtime.total_seconds())
            runtime_str = str(runtime).split('.')[0]

            # Update machine status to OFF
            db.reference("status/machine").set({
                'status': 'OFF',
                'timestamp': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                'runtime': runtime_seconds
            })

            # Update machine runtime record
            ref = db.reference("machine")
            all_records = ref.order_by_key().limit_to_last(1).get()
            if all_records:
                last_key = list(all_records.keys())[0]
                last_record = all_records[last_key]
                if last_record.get('off') is None:
                    ref.child(last_key).update({
                        'off': machine_off_time.strftime('%Y-%m-%d %H:%M:%S'),
                        'runtime': runtime_str
                    })
                    print(f"[MACHINE OFF] {machine_off_time}, Runtime: {runtime_str}")

            machine_on_time = None
            machine_off_recorded = True

client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message
client.connect(broker, port, 60)

threading.Thread(target=machine_timeout_checker, daemon=True).start()
client.loop_forever()