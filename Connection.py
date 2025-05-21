import paho.mqtt.client as mqtt
import firebase_admin
from firebase_admin import credentials, db
import json
from datetime import datetime

# Inisialisasi Firebase
cred = credentials.Certificate("C:\\Users\\HP\\Downloads\\iotboxdatabase-firebase-adminsdk-fbsvc-d340373f18.json")  # Ganti nama file sesuai milikmu
firebase_admin.initialize_app(cred, {
    "databaseURL": "https://your-project-id.firebaseio.com/"  # Ganti dengan URL Firebase kamu
})

# Fungsi ketika berhasil terhubung ke broker MQTT
def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker with result code " + str(rc))
    client.subscribe("events/sensor_data")
    client.subscribe("events/machine_runtime")

# Fungsi ketika menerima pesan MQTT
def on_message(client, userdata, msg):
    topic = msg.topic
    payload = msg.payload.decode()

    if topic == "events/sensor_data":
        try:
            data = json.loads(payload)
            fire = data.get("fire")
            motion = data.get("motion")
            temperature = data.get("temperature")
            vibration = data.get("vibration")

            if fire is not None and motion is not None and temperature is not None and vibration is not None:
                timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

                sensor_log = {
                    "fire": fire,
                    "motion": motion,
                    "temperature": temperature,
                    "vibration": vibration,
                    "timestamp": timestamp
                }

                db.reference("sensor_data").push(sensor_log)
                print(f"[SENSOR] Data ditambahkan: {sensor_log}")
            else:
                print("[SENSOR] Data tidak lengkap:", data)

        except Exception as e:
            print("Error parsing sensor_data:", e)

    elif topic == "events/machine_runtime":
        try:
            data = json.loads(payload)
            runtime = data.get("runtime")  # format: 00:05:41
            status = data.get("status")    # format: "on" atau "off"

            if runtime is not None and status is not None:
                timestamp = datetime.now().strftime("%Y-%m-%d %H:%M")

                log_data = {
                    "Machine On": status,
                    "Runtime": runtime,
                    "Timestamp": timestamp
                }

                ref = db.reference("machine")
                existing_data = ref.get()

                if existing_data:
                    existing_ids = [int(key) for key in existing_data.keys() if key.isdigit()]
                    new_id = max(existing_ids) + 1
                else:
                    new_id = 1

                ref.child(str(new_id)).set(log_data)
                print(f"[MACHINE] Data disimpan dengan ID {new_id}: {log_data}")

            else:
                print("[MACHINE] Data tidak lengkap:", data)

        except Exception as e:
            print("Error parsing machine_runtime:", e)

# Inisialisasi client MQTT
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

# Koneksi ke broker MQTT
client.connect("broker.hivemq.com", 1883, 60)

# Loop forever
client.loop_forever()
