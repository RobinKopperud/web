<?php
function fetchEmployees($conn) {
    $employees = [];
    $sql = "SELECT username FROM brukere";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row['username'];
        }
    }
    return $employees;
}
?>
