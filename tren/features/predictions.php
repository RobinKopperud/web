<?php
include_once '../../../db.php';

function getPredictedMeasurements($user_id, $future_days = 30) {
    global $pdo;

    // Fetch historical measurements
    $stmt = $pdo->prepare("SELECT date, weight FROM tren_measurements WHERE user_id = :user_id ORDER BY date ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($measurements) < 2) {
        return []; // Not enough data for prediction
    }

    // Linear regression for predictions
    $dates = [];
    $weights = [];
    foreach ($measurements as $m) {
        $dates[] = strtotime($m['date']); // Convert dates to timestamps
        $weights[] = $m['weight'];
    }

    // Calculate slope and intercept for y = mx + c
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

    // Predict future measurements
    $predicted = [];
    $last_date = end($dates);
    for ($i = 1; $i <= $future_days; $i++) {
        $future_date = $last_date + ($i * 86400); // Add days in seconds
        $predicted[] = [
            'date' => date('Y-m-d', $future_date),
            'weight' => $slope * $future_date + $intercept
        ];
    }

    return $predicted;
}
?>
