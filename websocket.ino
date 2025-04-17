#include <WiFi.h>
#include <PubSubClient.h>

#define FLAME_SENSOR_PIN 23  // Digital pin for flame sensor
#define WATER_SENSOR_PIN 32  // Analog pin for water sensor (e.g. YL-69)
#define LED_PIN 22           // LED for fire warning

char *SSID = "no";
char *PASS = "peepeepoopoo";

char *MQTT_SERVER = "broker.emqx.io";
int MQTT_PORT = 1883;

WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);

void connectToWiFi() {
  Serial.print("Connecting to: ");
  Serial.println(SSID);
  WiFi.begin(SSID, PASS);
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }
  Serial.print("\nConnected to address: ");
  Serial.println(WiFi.localIP());
}

void connectToBroker(String clientName) {
  mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
  Serial.println("Connecting to MQTT Broker...");
  String clientId = "ESP32Client-" + clientName;

  while (!mqttClient.connected()) {
    if (mqttClient.connect(clientId.c_str())) {
      Serial.print("Connected to broker as ");
      Serial.println(clientName);
    } else {
      Serial.print("Failed, state: ");
      Serial.println(mqttClient.state());
      Serial.println("Retrying in 2 seconds...");
      delay(2000);
    }
  }
}

void doPublish(String topic, String payload) {
  mqttClient.publish(topic.c_str(), payload.c_str());
  Serial.print(topic);
  Serial.print(" ==> ");
  Serial.println(payload);
}

void setup() {
  Serial.begin(115200);
  pinMode(FLAME_SENSOR_PIN, INPUT);  // Flame sensor digital
  pinMode(WATER_SENSOR_PIN, INPUT);  // Water sensor analog (no need for pinMode technically)
  pinMode(LED_PIN, OUTPUT);          // LED

  connectToWiFi();
  connectToBroker("flame-water-monitor");
}

void loop() {
  // --- Fire Sensor Check ---
  int fireStatus = digitalRead(FLAME_SENSOR_PIN);
  if (fireStatus == LOW) {  // Fire detected
    Serial.println("🔥 Fire Detected!");
    doPublish("fire", "🔥 Fire Detected!");
    digitalWrite(LED_PIN, HIGH);
  } else {
    Serial.println("✅ No Fire.");
    doPublish("fire", "✅ No Fire.");
    digitalWrite(LED_PIN, LOW);
  }

  // --- Water Sensor Check ---
  int waterValue = analogRead(WATER_SENSOR_PIN);  // Reads 0-4095
  Serial.print("🌊 Water Sensor Value: ");
  Serial.println(waterValue);

  // Optional: you can categorize it
  String waterMessage;
  if (waterValue < 1000) {
    waterMessage = " no water";
  } else {
    waterMessage = "water detected";
  }

  doPublish("water", waterMessage);

  mqttClient.loop();  // Keep MQTT connection alive
  delay(5000);        // Delay 5 seconds between readings
}
