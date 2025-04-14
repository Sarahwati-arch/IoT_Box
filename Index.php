<?php
$host = "localhost";
$user = "root";
$pass = ""; // leave empty if no password
$dbname = "iotbox";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM fire_events ORDER BY timestamp DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>IoT Sensor Dashboard</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 30px; }
        table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 0 10px #ccc; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #333; color: white; }
        .fire { color: red; font-weight: bold; }
        .safe { color: green; font-weight: bold; }
        .nowater { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📡 IoT Sensor Dashboard</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>🔥 Fire Status</th>
            <th>💧 Water Status</th>
            <th>📏 Distance (cm)</th>
            <th>Topic</th>
            <th>Timestamp</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $fireClass = ($row["status"] == "🔥 Fire Detected! 🔥") ? "fire" : "safe";
                $waterClass = (strpos($row["water_status"], "No") !== false) ? "nowater" : "safe";
                echo "<tr>
                    <td>" . $row["id"] . "</td>
                    <td class='$fireClass'>" . $row["status"] . "</td>
                    <td class='$waterClass'>" . $row["water_status"] . "</td>
                    <td>" . $row["distance_cm"] . "</td>
                    <td>" . $row["topic"] . "</td>
                    <td>" . $row["timestamp"] . "</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No data found</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</body>
</html>
