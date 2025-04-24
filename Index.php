<?php
$host = "localhost";
$user = "root";
$pass = ""; // leave empty if no password
$dbname = "iotboxx";

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
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="dashboard-title">
        <img src="logo.png" alt="IoT Icon"> IoT Sensor Dashboard
    </h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Fire Status</th>
            <th>Water Status</th>
            <th>Distance (cm)</th>
            <th>Topic</th>
            <th>Timestamp</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $status = $row["status"] ?? "Unknown";
                $waterStatus = $row["water_status"] ?? "N/A";
                $distance = $row["distance_cm"] ?? "N/A";

                $fireClass = ($status == " Fire Detected! ") ? "fire" : "safe";
                $waterClass = (strpos($waterStatus, "No") !== false) ? "nowater" : "safe";

                echo "<tr>
                    <td>" . ($row["id"] ?? "-") . "</td>
                    <td class='$fireClass'>" . $status . "</td>
                    <td class='$waterClass'>" . $waterStatus . "</td>
                    <td>" . $distance . "</td>
                    <td>" . ($row["topic"] ?? "-") . "</td>
                    <td>" . ($row["timestamp"] ?? "-") . "</td>
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
