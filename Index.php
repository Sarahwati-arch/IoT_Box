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
    background-color: #fbbbbb ;
    color: white;
    font-weight: bold;
    border: 2px solid #f99090 !important;
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
            apiKey: "AIzaSyCW_41f54-PLz5BUeuQaFTLW1gRQ52bNzE",
            authDomain: "iotboxdatabase.firebaseapp.com",
            databaseURL: "https://iotboxdatabase-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "iotboxdatabase",
            storageBucket: "iotboxdatabase.appspot.com",
            messagingSenderId: "990335288553",
            appId: "1:990335288553:web:39bfddbe65f6ab80919c78",
            measurementId: "G-VRSYECDQF3",
        };

        firebase.initializeApp(firebaseConfig);
        var database = firebase.database();

        // --- SENSOR DATA ---
        const sensorTable = document.getElementById("sensorDataTable");
        let rowDataMap = {};
        let sensorRowCount = 0;

        function updateRow(timestamp, type, value) {
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


        // --- MACHINE STATUS DISPLAY ---
        const machineStatusBox = document.getElementById("machineStatusBox");
        const statusTextDiv = document.getElementById("statusText");

        // Referensi ke node machine
        var machineStatusRef = database.ref("machine/ID001");

        // Listener realtime
        machineStatusRef.on("value", (snapshot) => {
        const data = snapshot.val();
        console.log("Data Firebase:", data);

        const machineStatusBox = document.getElementById("machineStatusBox");
        const statusTextDiv = document.getElementById("statusText");
        const runtimeTextDiv = document.getElementById("runtimeText");
        const timestampTextDiv = document.getElementById("timestampText");

        if (data) {
            // Periksa boolean (benar-benar boolean, bukan string)
            const isOn = data["Machine On"] === true;
            const runtime = data["Runtime"] || 0;
            const timestamp = data["Timestamp"] || "-";

            const statusClass = isOn ? "on" : "off";
            const statusText = isOn ? "ON" : "OFF";

            machineStatusBox.className = `machine-box ${statusClass}`;
            statusTextDiv.textContent = `Status: ${statusText}`;
            runtimeTextDiv.textContent = `Runtime: ${runtime} detik`;
            timestampTextDiv.textContent = `Waktu: ${timestamp}`;
        } else {
            machineStatusBox.className = "machine-box unknown";
            statusTextDiv.textContent = "Status: UNKNOWN";
            runtimeTextDiv.textContent = "Runtime: -";
            timestampTextDiv.textContent = "Waktu: -";
        }
        });
    </script>
</body>
</html>