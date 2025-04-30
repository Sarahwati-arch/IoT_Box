import paho.mqtt.client as mqtt
import mysql.connector
from datetime import datetime

broker = "broker.emqx.io"
port = 1883

topics = {
    "fire": "sensor/fire",
    "machine": "sensor/machine",
    "water": "sensor/water",
    "motion": "sensor/motion"
}

machine_on_time = None

def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker!")
    for t in topics.values():
        client.subscribe(t)

def on_message(client, userdata, msg):
    global machine_on_time
    message = msg.payload.decode().lower()
    topic = msg.topic
    now = datetime.now()

    try:
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="iotboxtest"
        )
        cursor = db.cursor()

        if topic == topics["fire"]:
            sql = "INSERT INTO fire_sensor (fire_status) VALUES (%s)"
            cursor.execute(sql, (message,))

        elif topic == topics["machine"]:
            if message == "on":
                machine_on_time = now
            elif message == "off" and machine_on_time:
                runtime = now - machine_on_time
                runtime_str = str(runtime).split('.')[0]
                sql = "INSERT INTO runtime (machine_on, machine_off, runtime) VALUES (%s, %s, %s)"
                cursor.execute(sql, (machine_on_time, now, runtime_str))
                machine_on_time = None

        elif topic == topics["water"]:
            sql = "INSERT INTO water_sensor (water_status) VALUES (%s)"
            cursor.execute(sql, (message,))

        elif topic == topics["motion"]:
            sql = "INSERT INTO motion_sensor (motion_status) VALUES (%s)"
            cursor.execute(sql, (message,))

        db.commit()

    except Exception as e:
        print("Database error:", e)
    finally:
        if db.is_connected():
            cursor.close()
            db.close()

# MQTT setup
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

try:
    client.connect(broker, port, 60)
    client.loop_forever()
except KeyboardInterrupt:
    print("Disconnected.")
