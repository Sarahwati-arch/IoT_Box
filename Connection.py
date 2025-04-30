import paho.mqtt.client as mqtt
import mysql.connector
from datetime import datetime

# MQTT settings
broker = "broker.emqx.io"
port = 1883
fire = "fire_sensor"  # Topic for fire sensor
water = "water_sensor"  # Topic for water sensor
motion = "motion_sensor"  # Topic for motion sensor


# Called when connected to the broker
def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker!")
    client.subscribe([(fire, 0), (water, 0), (motion, 0)])

# Called when a message is received
def on_message(client, userdata, msg):
    message = msg.payload.decode()
    print(f"Received: {message} on topic {msg.topic}")

    try:
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="iotboxtest"
        )
        cursor = db.cursor()

        # Fire sensor data insertion
        if msg.topic == fire:
            sql = "INSERT INTO fire_sensor (fire_status) VALUES (%s)"
            val = (message,)
            cursor.execute(sql, val)
            db.commit()
            print("Saved to fire_sensor")

        # Water sensor data insertion
        elif msg.topic == water:
            sql = "INSERT INTO water_sensor (water_status) VALUES (%s)"
            val = (message,)
            cursor.execute(sql, val)
            db.commit()
            print("Saved to water_sensor")

        # Motion sensor data insertion
        elif msg.topic == motion:
            sql = "INSERT INTO motion_sensor (motion_status) VALUES (%s)"
            val = (message,)
            cursor.execute(sql, val)
            db.commit()
            print("Saved to motion_sensor")

    except Exception as e:
        print(" Error:", e)

    finally:
        if db.is_connected():
            cursor.close()
            db.close()
            print("Database connection closed.")

# MQTT setup
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

try:
    client.connect(broker, port, 60)
    client.loop_forever()

except KeyboardInterrupt:
    print("Disconnected manually.")
