<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Function to calculate predictions
function getPredictedMeasurements($conn, $user_id, $days) {
    // Fetch historical measurements
    $stmt = $conn->prepare("SELECT date, weight FROM tren_measurements WHERE user_id = ? ORDER BY date ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $measurements = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($measurements) < 2) {
        return []; // Not enough data for predictions
    }

    // Perform simple logic for predictions
    $dates = [];
    $weights = [];
    foreach ($measurements as $m) {
        $dates[] = strtotime($m['date']);
        $weights[] = $m['weight'];
    }

    // Calculate slope and intercept (linear regression)
    $n = count($dates);
    $x_mean = array_sum($dates) / $n;
    $y_mean = array_sum($weights) / $n;

    $numerator = 0;
    $denominator = 0;
    for ($i = 0; $i < $n; $i++) {
        $numerator += ($dates[$i] - $x_mean) * ($weights[$i] - $y_mean);
        $denominator += pow($dates[$i] - $x_mean, 2);
    }
    $slope = $numerator / $denominator;
    $intercept = $y_mean - $slope * $x_mean;

    // Generate predictions
    $predictions = [];
    $last_date = end($dates);
    for ($i = 1; $i <= $days; $i++) {
        $future_date = $last_date + ($i * 86400); // Add 1 day in seconds
        $predicted_weight = $slope * $future_date + $intercept;

        $predictions[] = [
            'date' => date('Y-m-d', $future_date),
            'weight' => round($predicted_weight, 1)
        ];
    }

    return $predictions;
}
?>
