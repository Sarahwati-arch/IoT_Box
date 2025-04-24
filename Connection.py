import paho.mqtt.client as mqtt
import mysql.connector
from datetime import datetime

# MQTT settings
broker = "broker.emqx.io"
port = 1883
fire = "fire"  # Topic for fire events
water = "water"  # Topic for water events


# MySQL settings
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  # Replace if you set a password in XAMPP
    database="iotboxx"
)
cursor = db.cursor()

# Called when connected to the broker
def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker!")
    client.subscribe([(fire, 0), (water, 0),])

# Called when a message is received
def on_message(client, userdata, msg):
    message = msg.payload.decode()
    print(f"Received: {message} on topic {msg.topic}")

    if msg.topic == fire:
        # Insert into fire_events table
        sql = "INSERT INTO fire_events (status, topic) VALUES (%s, %s)"
        val = (message, msg.topic)
        cursor.execute(sql, val)
        db.commit()
        print("Saved to fire_events ")

    elif msg.topic == water:
        # Insert into water_events table
        sql = "INSERT INTO water_events (status, topic) VALUES (%s, %s)"
        val = (message, msg.topic)
        cursor.execute(sql, val)
        db.commit()
        print("Saved to water_events ")


# MQTT setup
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

try:
    client.connect(broker, port, 60)
    client.loop_forever()

except KeyboardInterrupt:
    print("Disconnected manually.")

finally:
    if db.is_connected():
        cursor.close()
        db.close()
        print("Database connection closed.")