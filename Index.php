<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IoT Box Dashboard</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-database-compat.js"></script>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <h1 class="dashboard-title">
            <img src="logo.png" alt="IoT Icon"> IoT Box Dashboard
        </h1>
    </nav>

    <!-- Tipe Machine Scroll -->
    <div class="main-content">
        <div class="left-box">
            <h2>Machine Type</h2>
            <div class="machine-scroll">
                <a href="#" class="machine-card">Rooting</a>
                <a href="#" class="machine-card">Hair Styling</a>
                <a href="#" class="machine-card">Makeup</a>
                <a href="#" class="machine-card">Body</a>
                <a href="#" class="machine-card">Outfit</a>
            </div>
        </div>

        <!-- Status Mesin -->
        <div class="right-box">
            <h2>Machine Status</h2>
            <div class="machine-container" id="machineStatusContainer">
                <!-- Akan diisi otomatis dari Firebase -->
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
                    <th>Timestamp</th>
                </tr>
            </table>
        </div>
    </div>

    <!-- Firebase Realtime Update -->
<script>
    const firebaseConfig = {
        apiKey: "AIzaSyCW_41f54-PLz5BUeuQaFTLW1gRQ52bNzE",
        authDomain: "iotboxdatabase.firebaseapp.com",
        databaseURL: "https://iotboxdatabase-default-rtdb.asia-southeast1.firebasedatabase.app",
        projectId: "iotboxdatabase",
        storageBucket: "iotboxdatabase.appspot.com",
        messagingSenderId: "990335288553",
        appId: "1:990335288553:web:39bfddbe65f6ab80919c78",
        measurementId: "G-VRSYECDQF3"
    };

    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

    const sensorTable = document.getElementById("sensorDataTable");
    let rowDataMap = {};
    let rowCount = 0;

    function updateRow(timestamp, type, value) {
        if (!rowDataMap[timestamp]) {
            // Buat baris baru jika belum ada
            rowCount++;
            const row = sensorTable.insertRow(1);
            const idCell = row.insertCell(0);
            const fireCell = row.insertCell(1);
            const tempCell = row.insertCell(2);
            const motionCell = row.insertCell(3);
            const vibrationCell = row.insertCell(4);
            const timeCell = row.insertCell(5);

            idCell.textContent = rowCount;
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
                    vibration: vibrationCell
                }
            };

            if (sensorTable.rows.length > 101) {
                sensorTable.deleteRow(sensorTable.rows.length - 1);
                rowCount--;
            }
        }

        if (rowDataMap[timestamp]) {
            const cells = rowDataMap[timestamp].cells;
            if (type === "fire") cells.fire.textContent = value;
            if (type === "temperature") cells.temperature.textContent = value;
            if (type === "motion") cells.motion.textContent = value;
            if (type === "vibration") cells.vibration.textContent = value;
        }
    }

    // ✅ Sensor Data Gabungan
    const sensorRef = database.ref("sensor_data");
    sensorRef.on("child_added", (snapshot) => {
        const data = snapshot.val();
        updateRow(data.timestamp, "fire", data.fire);
        updateRow(data.timestamp, "temperature", data.temperature);
        updateRow(data.timestamp, "motion", data.motion);
        updateRow(data.timestamp, "vibration", data.vibration);
    });

    // ✅ Runtime Tetap Sama
    const runtimeRef = database.ref("machine");
    const runtimeTable = document.getElementById("runtimeTable");
    let runtimeCount = 0;

    runtimeRef.on("child_added", (snapshot) => {
        const data = snapshot.val();
        runtimeCount++;
        const row = runtimeTable.insertRow(1);
        row.innerHTML = `
            <td>${runtimeCount}</td>
            <td>${data.on}</td>
            <td>${data.off}</td>
            <td>${data.runtime}</td>
            <td>${data.timestamp}</td>
        `;

        if (runtimeTable.rows.length > 101) {
            runtimeTable.deleteRow(runtimeTable.rows.length - 1);
            runtimeCount--;
        }
    });
</script>

</body>
</html>
