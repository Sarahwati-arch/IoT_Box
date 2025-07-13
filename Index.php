<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>IoT Box Dashboard</title>
    <link rel="icon" href="logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="style.css?v=1.0"/>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-database-compat.js"></script>
    
<style>
/* General Styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
}

.navbar {
    background-color: #2196F3;
    padding: 1rem;
    color: white;
}

.dashboard-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dashboard-title img {
    width: 32px;
    height: 32px;
}

.main-content {
    display: flex;
    gap: 20px;
    padding: 20px;
    flex-wrap: wrap;
}

.left-box, .right-box {
    flex: 1;
    min-width: 300px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Machine scroll list */
.machine-scroll {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.machine-card {
    flex: 0 0 auto;
    padding: 12px 18px;
    background-color: #e3f2fd;
    color: #d21919;
    text-decoration: none;
    border-radius: 8px;
    border: 1px solid #f99090;
    transition: background-color 0.3s;
}

.machine-card:hover {
    background-color: #fbbbbb;
}

.machine-card.active-button {
    background-color: #fbbbbb;
    color: white;
    font-weight: bold;
    border: 2px solid #f99090 !important;
}

/* Machine Status */
.machine-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.machine-box {
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    color: white;
    min-width: 200px;
}

.machine-box.on {
    background-color: #4CAF50;
}

.machine-box.off {
    background-color: #f44336;
}

.machine-box.unknown {
    background-color: #9E9E9E;
}

.status-detail {
    font-size: 16px;
    margin-top: 10px;
    font-weight: normal;
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

tr:hover {
    background-color: #f5f5f5;
}
</style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <h1 class="dashboard-title">
            <img src="logo.png" alt="IoT Icon"/> IoT Box Dashboard
        </h1>
    </nav>

    <!-- Tipe Machine Scroll -->
    <div class="main-content">
        <div class="left-box">
            <h2>Machine Type</h2>
            <div class="machine-scroll">
                <a href="" class="machine-card" data-type="injection">Injection Molding</a>
                <a href="#" class="machine-card" data-type="painting">Painting & Detailing</a>
                <a href="#" class="machine-card" data-type="rooting">Rooting</a>
                <a href="#" class="machine-card" data-type="hair">Hair Styling</a>
                <a href="#" class="machine-card" data-type="makeup">Makeup</a>
                <a href="#" class="machine-card" data-type="body">Body Assembly</a>
                <a href="#" class="machine-card" data-type="sewing">Sewing Machine (Outfit)</a>
                <a href="#" class="machine-card" data-type="assembly">Assembly Machine</a>
                <a href="#" class="machine-card" data-type="quality">Quality Inspection</a>
                <a href="#" class="machine-card" data-type="packaging">Packaging</a>
            </div>
        </div>

        <!-- Status Mesin -->
        <div class="right-box">
            <h2>Machine Status</h2>
            <div class="machine-container" id="machineStatusContainer">
                <div class="machine-box unknown" id="machineStatusBox">
                    <div id="statusText">UNKNOWN</div>
                    <div class="status-detail" id="runtimeText">Runtime: -</div>
                    <div class="status-detail" id="timestampText">Time: -</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sensor & Runtime -->
    <div class="main-content">
        <!-- Sensor -->
        <div class="left-box">
            <h2>Sensor Data</h2>
            <table id="sensorDataTable">
                <tr>
                    <th>ID</th>
                    <th>Fire Status</th>
                    <th>Temperature</th>
                    <th>Motion Status</th>
                    <th>Vibration</th>
                    <th>Timestamp</th>
                </tr>
            </table>
        </div>

        <!-- Runtime -->
        <div class="right-box">
            <h2>Machine Runtime</h2>
            <table id="runtimeTable">
                <tr>
                    <th>ID</th>
                    <th>Machine On</th>
                    <th>Machine Off</th>
                    <th>Runtime</th>
                </tr>
            </table>
        </div>
    </div>

    <!-- Firebase Realtime Update -->
    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const firebaseConfig = {
                apiKey: "AIzaSyASpfGbTacYnBfGC0ZGNf9Y1EsmV_E0VhI",
                authDomain: "iot-box-new.firebaseapp.com",
                databaseURL: "https://iot-box-new-default-rtdb.asia-southeast1.firebasedatabase.app",
                projectId: "iot-box-new",
                storageBucket: "iot-box-new.firebasestorage.app",
                messagingSenderId: "55670829693",
                appId: "1:55670829693:web:163596347e75da28b9f714",
                measurementId: "G-D3D23B7TGV"
            };

            firebase.initializeApp(firebaseConfig);
            var database = firebase.database();

            // Machine status tracking variables
            let machineStatus = "OFF";
            let machineStartTime = null;
            let lastSensorTime = null;
            let currentRuntimeKey = null;
            let statusCheckInterval = null;

            // Status detection timeout (in milliseconds)
            const SENSOR_TIMEOUT = 15000; // 15 seconds (reduced for testing)

            // --- MACHINE STATUS FUNCTIONS ---
            function updateMachineStatus(status, timestamp) {
                const machineStatusBox = document.getElementById("machineStatusBox");
                const statusTextDiv = document.getElementById("statusText");
                const runtimeTextDiv = document.getElementById("runtimeText");
                const timestampTextDiv = document.getElementById("timestampText");

                machineStatus = status;
                const isOn = status === "ON";
                const statusClass = isOn ? "on" : "off";

                let runtimeDisplay = "-";
                if (isOn && machineStartTime) {
                    const currentTime = Date.now();
                    const runtime = Math.floor((currentTime - machineStartTime) / 1000);
                    
                    if (runtime === 0) {
                        runtimeDisplay = "Just started";
                    } else {
                        const hours = Math.floor(runtime / 3600);
                        const minutes = Math.floor((runtime % 3600) / 60);
                        const seconds = runtime % 60;
                        
                        if (hours > 0) {
                            runtimeDisplay = `${hours}h ${minutes}m ${seconds}s`;
                        } else if (minutes > 0) {
                            runtimeDisplay = `${minutes}m ${seconds}s`;
                        } else {
                            runtimeDisplay = `${seconds}s`;
                        }
                    }
                }

                machineStatusBox.className = `machine-box ${statusClass}`;
                statusTextDiv.textContent = `Status: ${status}`;
                runtimeTextDiv.textContent = `Runtime: ${runtimeDisplay}`;
                timestampTextDiv.textContent = `Time: ${timestamp}`;

                // Update status in Firebase
                database.ref("status/machine").set({
                    status: status,
                    runtime: isOn && machineStartTime ? Math.floor((Date.now() - machineStartTime) / 1000) : 0,
                    timestamp: timestamp
                });
            }

            function startMachine(timestamp) {
                if (machineStatus === "OFF") {
                    machineStartTime = Date.now();
                    updateMachineStatus("ON", timestamp);
                    
                    // Create new runtime entry
                    const runtimeRef = database.ref("machine").push();
                    currentRuntimeKey = runtimeRef.key;
                    runtimeRef.set({
                        on: timestamp,
                        off: null,
                        runtime: null
                    });
                }
            }

            function stopMachine(timestamp) {
                if (machineStatus === "ON") {
                    const offTime = Date.now();
                    const runtime = Math.floor((offTime - machineStartTime) / 1000);
                    
                    console.log("Stopping machine. Runtime:", runtime, "seconds");
                    
                    updateMachineStatus("OFF", timestamp);
                    
                    // Update runtime entry
                    if (currentRuntimeKey) {
                        database.ref(`machine/${currentRuntimeKey}`).update({
                            off: timestamp,
                            runtime: formatRuntime(runtime)
                        });
                        console.log("Updated runtime entry:", currentRuntimeKey);
                    }
                    
                    machineStartTime = null;
                    currentRuntimeKey = null;
                }
            }

            function formatRuntime(seconds) {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                
                if (hours > 0) {
                    return `${hours}h ${minutes}m ${secs}s`;
                } else if (minutes > 0) {
                    return `${minutes}m ${secs}s`;
                } else {
                    return `${secs}s`;
                }
            }

            function checkSensorTimeout() {
                const currentTime = Date.now();
                console.log("Checking timeout - Current time:", new Date().toLocaleString());
                console.log("Last sensor time:", lastSensorTime ? new Date(lastSensorTime).toLocaleString() : "Never");
                console.log("Machine status:", machineStatus);
                
                if (lastSensorTime && currentTime - lastSensorTime > SENSOR_TIMEOUT) {
                    console.log("TIMEOUT DETECTED! Stopping machine...");
                    if (machineStatus === "ON") {
                        const timestamp = new Date().toLocaleString();
                        stopMachine(timestamp);
                    }
                } else if (lastSensorTime) {
                    const timeLeft = SENSOR_TIMEOUT - (currentTime - lastSensorTime);
                    console.log("Time left before timeout:", Math.floor(timeLeft / 1000), "seconds");
                }
            }

            // Start periodic check for sensor timeout
            statusCheckInterval = setInterval(checkSensorTimeout, 2000); // Check every 2 seconds

            // --- SENSOR DATA ---
            const sensorTable = document.getElementById("sensorDataTable");
            let rowDataMap = {};
            let sensorRowCount = 0;

            function updateRow(timestamp, type, value) {
                // Update last sensor time
                lastSensorTime = Date.now();
                console.log("Sensor data received at:", new Date().toLocaleString(), "Type:", type, "Value:", value);
                
                // Start machine if not already started
                if (machineStatus === "OFF") {
                    startMachine(timestamp);
                }

                if (!rowDataMap[timestamp]) {
                    sensorRowCount++;
                    const row = sensorTable.insertRow(1);
                    const idCell = row.insertCell(0);
                    const fireCell = row.insertCell(1);
                    const tempCell = row.insertCell(2);
                    const motionCell = row.insertCell(3);
                    const vibrationCell = row.insertCell(4);
                    const timeCell = row.insertCell(5);

                    idCell.textContent = sensorRowCount;
                    fireCell.textContent = "-";
                    tempCell.textContent = "-";
                    motionCell.textContent = "-";
                    vibrationCell.textContent = "-";
                    timeCell.textContent = timestamp;

                    rowDataMap[timestamp] = {
                        rowElement: row,
                        cells: {
                            fire: fireCell,
                            temperature: tempCell,
                            motion: motionCell,
                            vibration: vibrationCell,
                        },
                    };

                    if (sensorTable.rows.length > 101) {
                        const lastRowIndex = sensorTable.rows.length - 1;
                        const lastTimestamp = sensorTable.rows[lastRowIndex].cells[5].textContent;
                        sensorTable.deleteRow(lastRowIndex);
                        delete rowDataMap[lastTimestamp];
                    }
                }

                const cells = rowDataMap[timestamp].cells;
                if (type === "fire") cells.fire.textContent = value;
                if (type === "temperature") cells.temperature.textContent = value;
                if (type === "motion") cells.motion.textContent = value;
                if (type === "vibration") cells.vibration.textContent = value;
            }

            const sensorRef = database.ref("sensor_data");
            sensorRef.on("child_added", (snapshot) => {
                const data = snapshot.val();
                updateRow(data.timestamp, "fire", data.fire);
                updateRow(data.timestamp, "temperature", data.temperature);
                updateRow(data.timestamp, "motion", data.motion);
                updateRow(data.timestamp, "vibration", data.vibration);
            });

            // --- MACHINE RUNTIME ---
            const runtimeTable = document.getElementById("runtimeTable");
            let runtimeRowCount = 0;

            const runtimeRef = database.ref("machine");
            runtimeRef.on("child_added", addRuntimeRow);
            runtimeRef.on("child_changed", updateRuntimeRow);

            function addRuntimeRow(snapshot) {
                const data = snapshot.val();
                runtimeRowCount++;

                const row = runtimeTable.insertRow(1);
                row.setAttribute("data-key", snapshot.key);

                const offDisplay = data.off || "-";
                const runtimeDisplay = data.runtime || "-";

                row.innerHTML = `
                    <td>${runtimeRowCount}</td>
                    <td>${data.on}</td>
                    <td>${offDisplay}</td>
                    <td>${runtimeDisplay}</td>
                `;
            }

            function updateRuntimeRow(snapshot) {
                const key = snapshot.key;
                const updatedData = snapshot.val();
                const rows = runtimeTable.rows;

                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    if (row.getAttribute("data-key") === key) {
                        const offDisplay = updatedData.off || "-";
                        const runtimeDisplay = updatedData.runtime || "-";

                        row.cells[2].textContent = offDisplay;
                        row.cells[3].textContent = runtimeDisplay;
                        break;
                    }
                }
            }

            // Initialize with OFF status
            updateMachineStatus("OFF", new Date().toLocaleString());

            // Update runtime display every second when machine is ON
            setInterval(() => {
                if (machineStatus === "ON" && machineStartTime) {
                    updateMachineStatus("ON", new Date().toLocaleString());
                }
            }, 1000);

            // Cleanup interval on page unload
            window.addEventListener('beforeunload', () => {
                if (statusCheckInterval) {
                    clearInterval(statusCheckInterval);
                }
            });
        });
    </script>
</body>
</html>