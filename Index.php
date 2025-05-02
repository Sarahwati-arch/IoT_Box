<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "iotboxtest";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil data sensor
$fireResult = $conn->query("SELECT fire_status, timestamp FROM fire_sensor ORDER BY timestamp DESC LIMIT 100");
$waterResult = $conn->query("SELECT water_status FROM water_sensor ORDER BY timestamp DESC LIMIT 100");
$motionResult = $conn->query("SELECT motion_status FROM motion_sensor ORDER BY timestamp DESC LIMIT 100");

// Ambil data mesin
$machineResult = $conn->query("SELECT machine_on, machine_off, timestamp FROM machine_runtime ORDER BY timestamp DESC LIMIT 100");
$latestMachineResult = $conn->query("SELECT machine_on, machine_off FROM machine_runtime ORDER BY timestamp DESC LIMIT 1");
$latestMachine = $latestMachineResult->fetch_assoc();

// Gabungkan data sensor
$rows = [];
$i = 0;
while ($f = $fireResult->fetch_assoc()) {
    $rows[$i]['fire'] = $f['fire_status'];
    $rows[$i]['timestamp'] = $f['timestamp'];
    $i++;
}
$i = 0;
while ($w = $waterResult->fetch_assoc()) {
    $rows[$i]['water'] = $w['water_status'];
    $i++;
}
$i = 0;
while ($m = $motionResult->fetch_assoc()) {
    $rows[$i]['motion'] = $m['motion_status'];
    $i++;
}

// Gabungkan data runtime mesin
$machines = [];
while ($mc = $machineResult->fetch_assoc()) {
    $machines[] = $mc;
}

// Tentukan status mesin terbaru
$machineStatus = "Unknown";
$boxClass = "unknown";

if ($latestMachine) {
    if (!empty($latestMachine['machine_on']) && empty($latestMachine['machine_off'])) {
        $machineStatus = "Machine ON";
        $boxClass = "on";
    } elseif (!empty($latestMachine['machine_off'])) {
        $machineStatus = "Machine OFF";
        $boxClass = "off";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IoT Box Dashboard</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <h1 class="dashboard-title">
            <img src="logo.png" alt="IoT Icon"> IoT Box Dashboard
        </h1>
    </nav>

    <!-- ATAS: Tipe Machine Scroll + Status Mesin -->
    <div class="main-content">
        <!-- KIRI -->
        <div class="left-box">
            <h2>Machine Type</h2>
            <div class="machine-scroll">
                <a href="machine_page.php?id=a" class="machine-card">Rooting</a>
                <a href="machine_page.php?id=a" class="machine-card">Hair Styling</a>
                <a href="machine_page.php?id=b" class="machine-card">Makeup</a>
                <a href="machine_page.php?id=c" class="machine-card">Body</a>
                <a href="machine_page.php?id=c" class="machine-card">Outfit</a>
                <!-- Tambahkan lebih banyak jika perlu -->
            </div>
        </div>

        <!-- KANAN -->
        <div class="right-box">
            <h2>Machine Status</h2>
            <div class="machine-container">
                <?php
                // Loop untuk menampilkan status mesin A, B, C
                foreach ($machines as $status) {  // Use $machines instead of $machineStatuses
                    // Tentukan status mesin berdasarkan data database
                    $machineType = $status['machine_type'];
                    $statusClass = '';
                    $statusText = '';

                    if (!empty($status['machine_on']) && empty($status['machine_off'])) {
                        $statusClass = 'on';
                        $statusText = 'Machine ON';
                    } elseif (!empty($status['machine_off'])) {
                        $statusClass = 'off';
                        $statusText = 'Machine OFF';
                    } else {
                        $statusClass = 'unknown';
                        $statusText = 'Unknown';
                    }

                    // Tampilkan box dengan status mesin
                    echo "<div class='machine-box {$statusClass}'>{$machineType}: {$statusText}</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- BAWAH: Data Sensor & Runtime -->
    <div class="main-content">
        <!-- KIRI: Data Sensor -->
        <div class="left-box">
            <h2>Sensor Data</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Fire Status</th>
                    <th>Water Status</th>
                    <th>Motion Status</th>
                    <th>Timestamp</th>
                </tr>
                <?php foreach ($rows as $index => $row): 
                    $id = $index + 1;
                    $fire = $row['fire'] ?? 'N/A';
                    $water = $row['water'] ?? 'N/A';
                    $motion = $row['motion'] ?? 'N/A';
                    $timestamp = $row['timestamp'] ?? 'N/A';

                    $fireClass = ($fire == "Fire Detected!") ? "fire" : "safe";
                    $waterClass = (strpos(strtolower($water), "no") !== false) ? "nowater" : "safe";
                    $motionClass = ($motion == "yes") ? "moving" : "nomotion";
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td class="<?= $fireClass ?>"><?= $fire ?></td>
                    <td class="<?= $waterClass ?>"><?= $water ?></td>
                    <td class="<?= $motionClass ?>"><?= $motion ?></td>
                    <td><?= $timestamp ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- KANAN: Runtime Mesin -->
        <div class="right-box">
            <h2>Machine Runtime</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Machine On</th>
                    <th>Machine Off</th>
                    <th>Runtime</th>
                    <th>Timestamp</th>
                </tr>
                <?php foreach ($machines as $index => $mc): 
                    $id = $index + 1;
                    $on = $mc['machine_on'] ?? '-';
                    $off = $mc['machine_off'] ?? '-';
                    $runtime = $mc['runtime'] ?? '-'; // Now it holds the calculated runtime
                    $timestamp = $mc['timestamp'] ?? '-';
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= $on ?></td>
                    <td><?= $off ?></td>
                    <td><?= $runtime ?></td> <!-- Display the calculated runtime -->
                    <td><?= $timestamp ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>

</body>
</html>

<?php $conn->close(); ?>
