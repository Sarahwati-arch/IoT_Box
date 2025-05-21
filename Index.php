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
                    <th>Machine off</th>
                    <th>Runtime</th>
                </tr>
            </table>
        </div>
    </div>

    <!-- Firebase Realtime Update -->
    <script>
        const firebaseConfig = {
            apiKey: "AIzaSyCW_41f54-PLz5BUeuQaFTLW1gRQ52bNzE",
            authDomain: "iotboxdatabase.firebaseapp.com",
            databaseURL:
                "https://iotboxdatabase-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "iotboxdatabase",
            storageBucket: "iotboxdatabase.appspot.com",
            messagingSenderId: "990335288553",
            appId: "1:990335288553:web:39bfddbe65f6ab80919c78",
            measurementId: "G-VRSYECDQF3",
        };

        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const database = firebase.database();

        // --- SENSOR DATA ---
        const sensorTable = document.getElementById("sensorDataTable");
        let rowDataMap = {}; // Simpan baris berdasarkan timestamp untuk update data
        let sensorRowCount = 0; // Counter ID yang hanya bertambah

function updateRow(timestamp, type, value) {
    if (!rowDataMap[timestamp]) {
        // Buat baris baru jika belum ada data dengan timestamp ini
        sensorRowCount++;
        const row = sensorTable.insertRow(1); // Insert di bawah header
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

        // Batasi maksimal 100 baris (header baris 0)
        if (sensorTable.rows.length > 101) {
            // Dapatkan row paling bawah (index terakhir)
            const lastRowIndex = sensorTable.rows.length - 1;
            // Ambil timestamp dari kolom waktu baris terakhir (kolom 5)
            const lastTimestamp = sensorTable.rows[lastRowIndex].cells[5].textContent;

            // Hapus baris paling bawah dari tabel
            sensorTable.deleteRow(lastRowIndex);

            // Hapus data di rowDataMap yang sesuai dengan lastTimestamp
            delete rowDataMap[lastTimestamp];
        }
    }

    // Update data sensor pada baris yang sudah ada
    const cells = rowDataMap[timestamp].cells;
    if (type === "fire") cells.fire.textContent = value;
    if (type === "temperature") cells.temperature.textContent = value;
    if (type === "motion") cells.motion.textContent = value;
    if (type === "vibration") cells.vibration.textContent = value;
}

        // Listen perubahan data sensor di Firebase (sensor_data)
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
        let runtimeRowCount = 0; // Counter ID runtime

        // Listen perubahan data runtime di Firebase (machine)
        const runtimeRef = database.ref("machine");
        runtimeRef.on("child_added", (snapshot) => {
            const data = snapshot.val();
            runtimeRowCount++;
            const row = runtimeTable.insertRow(1); // Insert di bawah header
            row.innerHTML = `
                <td>${runtimeRowCount}</td>
                <td>${data.on}</td>
                <td>${data.off}</td>
                <td>${data.runtime}</td>
                
            `;

            // Batasi maksimal 100 baris
            if (runtimeTable.rows.length > 101) {
                runtimeTable.deleteRow(runtimeTable.rows.length - 1);
                // Jangan kurangi runtimeRowCount supaya ID tetap naik terus
            }
        });
    </script>
</body>
</html>