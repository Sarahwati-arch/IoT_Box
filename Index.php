<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "iotboxtest";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT 
            f.id,
            f.fire_status,
            w.water_status,
            m.motion_status,
            r.status AS machine_status,
            f.timestamp
        FROM fire_sensor f
        JOIN water_sensor w ON f.id = w.id
        JOIN motion_sensor m ON f.id = m.id
        JOIN runtime r ON f.id = r.id
        ORDER BY f.timestamp DESC
        LIMIT 100";

// Ambil data sensor
$fireResult = $conn->query("SELECT fire_status, timestamp FROM fire_sensor ORDER BY timestamp DESC LIMIT 100");
$waterResult = $conn->query("SELECT water_status FROM water_sensor ORDER BY timestamp DESC LIMIT 100");
$motionResult = $conn->query("SELECT motion_status FROM motion_sensor ORDER BY timestamp DESC LIMIT 100");

// Ambil data machine
$machineResult = $conn->query("SELECT machine_on, machine_off, runtime, timestamp FROM machine_runtime ORDER BY timestamp DESC LIMIT 100");




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

// Gabungkan data machine
$machines = [];
while ($mc = $machineResult->fetch_assoc()) {
    $machines[] = $mc;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>IoT Dashboard</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <h1 class="dashboard-title">
        <img src="logo.png" alt="IoT Icon"> IoT Box Dashboard
    </h1>
    <!-- Sensor Data -->
    <div>
        <h2>Sensor Data</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Fire Status</th>
                <th>Water Status</th>
                <th>Motion Status</th>
                <th>Timestamp</th>
            </tr>
            <?php
            foreach ($rows as $index => $row) {
                $id = $index + 1;
                $fire = $row['fire'] ?? 'N/A';
                $water = $row['water'] ?? 'N/A';
                $motion = $row['motion'] ?? 'N/A';
                $timestamp = $row['timestamp'] ?? 'N/A';

                $fireClass = ($fire == "Fire Detected!") ? "fire" : "safe";
                $waterClass = (strpos(strtolower($water), "no") !== false) ? "nowater" : "safe";
                $motionClass = ($motion == "yes") ? "moving" : "nomotion";

                echo "<tr>
                    <td>{$id}</td>
                    <td class='$fireClass'>{$fire}</td>
                    <td class='$waterClass'>{$water}</td>
                    <td class='$motionClass'>{$motion}</td>
                    <td>{$timestamp}</td>
                </tr>";
            }
            ?>
        </table>
    </div>

    <!-- Machine Runtime Data -->
<div>
    <h2>Machine Runtime</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Machine On</th>
            <th>Machine Off</th>
            <th>Runtime</th>
            <th>Timestamp</th>
        </tr>
        <?php
        foreach ($machines as $index => $mc) {
            $id = $index + 1;
            $on = $mc['machine_on'] ?? '-';
            $off = $mc['machine_off'] ?? '-';
            $runtime = $mc['runtime'] ?? '-';
            $timestamp = $mc['timestamp'] ?? '-';

            echo "<tr>
                <td>{$id}</td>
                <td>{$on}</td>
                <td>{$off}</td>
                <td>{$runtime}</td>
                <td>{$timestamp}</td>
            </tr>";
        }
        ?>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
