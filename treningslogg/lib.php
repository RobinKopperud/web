<?php
function fetch_measurements(mysqli $conn, int $user_id): array
{
    $stmt = $conn->prepare('SELECT id, name FROM treningslogg_measurements WHERE user_id = ? ORDER BY name ASC');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function fetch_measurement(mysqli $conn, int $measurement_id, int $user_id): ?array
{
    $stmt = $conn->prepare('SELECT id, name FROM treningslogg_measurements WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $measurement_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function fetch_last_entry(mysqli $conn, int $measurement_id): ?array
{
    $stmt = $conn->prepare('SELECT entry_date, value FROM treningslogg_entries WHERE measurement_id = ? ORDER BY entry_date DESC LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $measurement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function fetch_entries(mysqli $conn, int $measurement_id, int $limit = 12): array
{
    $stmt = $conn->prepare('SELECT id, entry_date, value FROM treningslogg_entries WHERE measurement_id = ? ORDER BY entry_date DESC LIMIT ?');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ii', $measurement_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $entries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return array_reverse($entries);
}

function fetch_delta_30_days(mysqli $conn, int $measurement_id): ?array
{
    $since = date('Y-m-d', strtotime('-30 days'));

    $stmt = $conn->prepare('SELECT entry_date, value FROM treningslogg_entries WHERE measurement_id = ? AND entry_date >= ? ORDER BY entry_date ASC LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('is', $measurement_id, $since);
    $stmt->execute();
    $result = $stmt->get_result();
    $first = $result ? $result->fetch_assoc() : null;

    $stmt = $conn->prepare('SELECT entry_date, value FROM treningslogg_entries WHERE measurement_id = ? AND entry_date >= ? ORDER BY entry_date DESC LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('is', $measurement_id, $since);
    $stmt->execute();
    $result = $stmt->get_result();
    $last = $result ? $result->fetch_assoc() : null;

    if (!$first || !$last) {
        return null;
    }

    $delta = (float) $last['value'] - (float) $first['value'];

    return [
        'delta' => $delta,
        'first_date' => $first['entry_date'],
        'last_date' => $last['entry_date'],
    ];
}

function format_delta(?float $delta): string
{
    if ($delta === null) {
        return '–';
    }

    $formatted = number_format(abs($delta), 1, ',', '');
    $sign = $delta > 0 ? '+' : ($delta < 0 ? '-' : '');
    return $sign . $formatted;
}

function build_chart_path(array $entries, int $width = 220, int $height = 80, int $padding = 10): array
{
    if (count($entries) === 0) {
        return [
            'path' => '',
            'last' => null,
            'points' => [],
            'min' => null,
            'max' => null,
        ];
    }

    $values = array_map(static fn(array $entry) => (float) $entry['value'], $entries);
    $min = min($values);
    $max = max($values);
    $range = $max - $min;
    if ($range == 0.0) {
        $range = 1.0;
    }

    $usable_width = $width - ($padding * 2);
    $usable_height = $height - ($padding * 2);
    $step = count($entries) > 1 ? $usable_width / (count($entries) - 1) : 0;

    $points = [];
    foreach ($values as $index => $value) {
        $x = $padding + ($index * $step);
        $normalized = ($value - $min) / $range;
        $y = $height - $padding - ($normalized * $usable_height);
        $points[] = ['x' => $x, 'y' => $y];
    }

    $path = '';
    foreach ($points as $index => $point) {
        $command = $index === 0 ? 'M' : 'L';
        $path .= sprintf('%s%.1f %.1f ', $command, $point['x'], $point['y']);
    }

    return [
        'path' => trim($path),
        'last' => end($points),
        'points' => $points,
        'min' => $min,
        'max' => $max,
    ];
}
?>
