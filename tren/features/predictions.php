<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

function getPredictedMeasurements($conn, $user_id, $days = 30) {
    // Fetch historical measurements
    $stmt = $conn->prepare("SELECT date, weight, waist, widest FROM tren_measurements WHERE user_id = ? ORDER BY date ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $measurements = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($measurements) < 2) {
        return []; // Not enough data for predictions
    }

    // Prepare arrays for regression
    $dates = [];
    $weights = [];
    $waists = [];
    $widests = [];
    foreach ($measurements as $m) {
        $dates[] = strtotime($m['date']);
        $weights[] = $m['weight'];
        $waists[] = $m['waist'];
        $widests[] = $m['widest'];
    }

    // Function for linear regression
    function linearRegression($x, $y) {
        $n = count($x);
        $x_mean = array_sum($x) / $n;
        $y_mean = array_sum($y) / $n;

        $numerator = 0;
        $denominator = 0;
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $x_mean) * ($y[$i] - $y_mean);
            $denominator += pow($x[$i] - $x_mean, 2);
        }

        $slope = $numerator / $denominator;

        // Adjust slope to make predictions less extreme
        $slope *= 0.5; // Weakens the slope by 50%

        $intercept = $y_mean - $slope * $x_mean;
        return [$slope, $intercept];
    }

    // Calculate regression for each measurement
    [$weight_slope, $weight_intercept] = linearRegression($dates, $weights);
    [$waist_slope, $waist_intercept] = linearRegression($dates, $waists);
    [$widest_slope, $widest_intercept] = linearRegression($dates, $widests);

    // Predict future measurements
    $predictions = [];
    $last_date = end($dates);
    for ($i = 1; $i <= $days; $i++) {
        $future_date = $last_date + ($i * 86400); // Add 1 day in seconds
        $predictions[] = [
            'date' => date('Y-m-d', $future_date),
            'weight' => round($weight_slope * $future_date + $weight_intercept, 1),
            'waist' => round($waist_slope * $future_date + $waist_intercept, 1),
            'widest' => round($widest_slope * $future_date + $widest_intercept, 1),
        ];
    }

    return $predictions;
}
?>
