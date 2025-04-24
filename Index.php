<?php
$host = "localhost";
$user = "root";
$pass = ""; // leave empty if no password
$dbname = "IoT_Box";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, fire_status, timestamp FROM fire_sensor ORDER BY timestamp DESC limit 100";
$sql = "SELECT id, water_status, timestamp FROM water_sensor ORDER BY timestamp DESC limit 100";
$sql = "SELECT id, motion_status, timestamp FROM motion_sensor ORDER BY timestamp DESC limit 100";
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
            <th>Motion Status</th>
            <th>Timestamp</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $fireStatus = $row["fire_status"] ?? "No Fire"; // Default if not detected
                $waterStatus = $row["water_status"] ?? "No Water";
                $motionStatus = $row["motion_status"] ?? "No Motion";

                // Apply class for fire status
                $fireClass = ($fireStatus == "Fire Detected!") ? "fire" : "safe";

                // Apply class for water status
                $waterClass = (strpos($waterStatus, "No") !== false) ? "nowater" : "safe";

                // Apply class for motion status
                $motionClass = (strpos($motionStatus, "No") !== false) ? "nomotion" : "moving";

                echo "<tr>
                    <td>" . ($row["id"] ?? "-") . "</td>
                    <td class='$fireClass'>" . $fireStatus . "</td>
                    <td class='$waterClass'>" . $waterStatus . "</td>
                    <td class='$motionClass'>" . $motionStatus . "</td>
                    <td>" . ($row["timestamp"] ?? "-") . "</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No data found</td></tr>";
        }
        $conn->close();
        ?>

    </table>
</body>
</html>
