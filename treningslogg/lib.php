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

function fetch_user_entry_count(mysqli $conn, int $user_id): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM treningslogg_entries e
         JOIN treningslogg_measurements m ON e.measurement_id = m.id
         WHERE m.user_id = ?'
    );
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? (int) $row['total'] : 0;
}

function fetch_user_entry_dates(mysqli $conn, int $user_id): array
{
    $stmt = $conn->prepare(
        'SELECT DISTINCT e.entry_date
         FROM treningslogg_entries e
         JOIN treningslogg_measurements m ON e.measurement_id = m.id
         WHERE m.user_id = ?
         ORDER BY e.entry_date DESC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return array_map(static fn(array $row) => $row['entry_date'], $rows);
}

function fetch_user_entry_streak(mysqli $conn, int $user_id): int
{
    $dates = fetch_user_entry_dates($conn, $user_id);
    if (!$dates) {
        return 0;
    }

    $streak = 1;
    $previous = new DateTime($dates[0]);

    for ($i = 1; $i < count($dates); $i++) {
        $current = new DateTime($dates[$i]);
        $previous->modify('-1 day');
        if ($current->format('Y-m-d') !== $previous->format('Y-m-d')) {
            break;
        }
        $streak++;
        $previous = $current;
    }

    return $streak;
}

function get_openai_api_key(): ?string
{
    $api_key = $GLOBALS['OPENAI_API_KEY'] ?? null;
    if ($api_key) {
        return $api_key;
    }

    $api_key_path = $_SERVER['DOCUMENT_ROOT'] . '/api_keys.php';
    if (file_exists($api_key_path)) {
        include_once $api_key_path;
        return $GLOBALS['OPENAI_API_KEY'] ?? null;
    }

    return null;
}

function analyze_measurement_with_ai(string $measurement_name, array $entries): array
{
    if (count($entries) < 2) {
        return [
            'summary' => 'Trenger flere målinger for å analysere trend.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
    }

    $api_key = get_openai_api_key();
    if (!$api_key) {
        return [
            'summary' => 'Ingen API-nøkkel tilgjengelig for trendanalyse.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
    }

    $payload = [
        'model' => 'gpt-5-nano',
        'service_tier' => 'flex',
        'temperature' => 0.2,
        'max_tokens' => 240,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Du er en treningscoach som lager korte, nøytrale trendinnsikter. '
                    . 'Returner kun JSON med feltene summary (streng), trend (går opp/går ned/har flatet ut/–), '
                    . 'stability (stabil/varierende/–), anomaly (boolean). Skriv på norsk.',
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'måling' => $measurement_name,
                    'enhet' => 'cm',
                    'observasjoner' => array_map(static function (array $entry): array {
                        return [
                            'dato' => $entry['entry_date'],
                            'verdi' => (float) $entry['value'],
                        ];
                    }, $entries),
                ], JSON_UNESCAPED_UNICODE),
            ],
        ],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $status < 200 || $status >= 300) {
        return [
            'summary' => 'AI-analysen er midlertidig utilgjengelig.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
    }

    $decoded = json_decode($response, true);
    $content = $decoded['choices'][0]['message']['content'] ?? '';
    $analysis = json_decode($content, true);

    if (!is_array($analysis)) {
        return [
            'summary' => 'Kunne ikke tolke AI-responsen.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
    }

    return [
        'summary' => (string) ($analysis['summary'] ?? 'Ingen oppsummering tilgjengelig.'),
        'trend' => (string) ($analysis['trend'] ?? '–'),
        'stability' => (string) ($analysis['stability'] ?? '–'),
        'anomaly' => (bool) ($analysis['anomaly'] ?? false),
    ];
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
