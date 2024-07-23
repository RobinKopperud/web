<?php
include_once '../../db.php'; // Adjust the path as needed

$sql = "
    SELECT brukere.username, 
           SUM(CASE WHEN logs.log_type = 'inn' THEN 1 ELSE 0 END) -
           SUM(CASE WHEN logs.log_type = 'ut' THEN 1 ELSE 0 END) AS flex_time
    FROM brukere
    JOIN logs ON brukere.id = logs.user_id
    GROUP BY brukere.id
    ORDER BY flex_time DESC
    LIMIT 3";

$result = $conn->query($sql);

$topUsers = array();
while ($row = $result->fetch_assoc()) {
    $topUsers[] = $row;
}

$conn->close();

echo json_encode($topUsers);
?>
