import paho.mqtt.client as mqtt
import mysql.connector
from datetime import datetime

# MQTT settings
broker = "broker.emqx.io"
port = 1883
topic = "TestforIoTBox"  # <-- match this to your ESP32 topic

# MySQL settings
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  # replace if you set a password in XAMPP
    database="iotbox"
)
cursor = db.cursor()

# Called when connected to broker
def on_connect(client, userdata, flags, rc):
    print("Connected to MQTT Broker!")
    client.subscribe(topic)

# Called when a message is received
def on_message(client, userdata, msg):
    message = msg.payload.decode()
    print(f"Received: {message} on topic {msg.topic}")

    # Insert into database
    sql = "INSERT INTO fire_events (status, topic) VALUES (%s, %s)"
    val = (message, msg.topic)
    cursor.execute(sql, val)
    db.commit()

    print("Saved to database ✅")

# MQTT setup
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

client.connect(broker, port, 60)
client.loop_forever()
