<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/api_keys.php';
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
    return $GLOBALS['OPENAI_API_KEY'] ?? null;
}

function normalize_openai_message_content($content): string
{
    if (is_array($content)) {
        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && isset($part['text'])) {
                $parts[] = $part['text'];
                continue;
            }
            if (is_string($part)) {
                $parts[] = $part;
            }
        }

        return implode('', $parts);
    }

    return (string) $content;
}

function extract_openai_response_text(array $decoded): string
{
    if (isset($decoded['output_text']) && is_string($decoded['output_text'])) {
        return $decoded['output_text'];
    }

    if (isset($decoded['output']) && is_array($decoded['output'])) {
        $parts = [];
        foreach ($decoded['output'] as $output) {
            if (!is_array($output)) {
                continue;
            }
            $content = $output['content'] ?? null;
            if ($content !== null) {
                $parts[] = normalize_openai_message_content($content);
            }
        }
        $combined = trim(implode('', $parts));
        if ($combined !== '') {
            return $combined;
        }
    }

    return normalize_openai_message_content($decoded['choices'][0]['message']['content'] ?? '');
}

function decode_openai_json_content(string $content): ?array
{
    $content = trim($content);
    if ($content === '') {
        return null;
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $stripped = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $stripped = preg_replace('/\s*```$/', '', $stripped);
    $stripped = trim($stripped);

    if ($stripped !== $content) {
        $decoded = json_decode($stripped, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $start = strpos($content, '{');
    $end = strrpos($content, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $snippet = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($snippet, true);
        if (is_array($decoded)) {
            return $decoded;
        }
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
        'max_output_tokens' => 240,
        'input' => [
            [
                'role' => 'system',
                'content' => 'Du er en treningscoach som lager korte, nøytrale trendinnsikter. '
                    . 'Returner kun ren tekst (ikke JSON) som inneholder kommentar på trend, stabilitet og avvik. '
                    . 'Skriv på norsk.',
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

    $ch = curl_init('https://api.openai.com/v1/responses');
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
    $content = extract_openai_response_text($decoded);
    $analysis_text = trim($content);

    if ($analysis_text === '') {
        return [
            'summary' => 'Kunne ikke tolke AI-responsen.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
    }

    return [
        'summary' => $analysis_text,
        'trend' => '–',
        'stability' => '–',
        'anomaly' => false,
    ];
}

function fetch_latest_entry_date_for_user(mysqli $conn, int $user_id): ?string
{
    $stmt = $conn->prepare(
        'SELECT MAX(e.entry_date) AS last_entry_date
         FROM treningslogg_entries e
         INNER JOIN treningslogg_measurements m ON m.id = e.measurement_id
         WHERE m.user_id = ?'
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    return $row['last_entry_date'] ?? null;
}

function fetch_recent_entries_for_user(mysqli $conn, int $user_id, int $days = 10): array
{
    $since = date('Y-m-d', strtotime("-{$days} days"));

    $stmt = $conn->prepare(
        'SELECT m.name AS measurement_name, e.entry_date, e.value
         FROM treningslogg_entries e
         INNER JOIN treningslogg_measurements m ON m.id = e.measurement_id
         WHERE m.user_id = ? AND e.entry_date >= ?
         ORDER BY e.entry_date ASC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('is', $user_id, $since);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function fetch_ai_trend_cache(mysqli $conn, int $user_id): ?array
{
    $stmt = $conn->prepare(
        'SELECT summary, trend, stability, anomaly, updated_at, last_entry_date
         FROM treningslogg_ai_trend_cache
         WHERE user_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if (!$row) {
        return null;
    }

    return [
        'summary' => (string) ($row['summary'] ?? ''),
        'trend' => (string) ($row['trend'] ?? '–'),
        'stability' => (string) ($row['stability'] ?? '–'),
        'anomaly' => (bool) ($row['anomaly'] ?? false),
        'updated_at' => $row['updated_at'] ?? null,
        'last_entry_date' => $row['last_entry_date'] ?? null,
    ];
}

function upsert_ai_trend_cache(mysqli $conn, int $user_id, array $analysis, ?string $last_entry_date): void
{
    $stmt = $conn->prepare(
        'INSERT INTO treningslogg_ai_trend_cache (user_id, summary, trend, stability, anomaly, last_entry_date)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           summary = VALUES(summary),
           trend = VALUES(trend),
           stability = VALUES(stability),
           anomaly = VALUES(anomaly),
           last_entry_date = VALUES(last_entry_date),
           updated_at = CURRENT_TIMESTAMP'
    );
    if (!$stmt) {
        return;
    }

    $summary = (string) ($analysis['summary'] ?? '');
    $trend = (string) ($analysis['trend'] ?? '–');
    $stability = (string) ($analysis['stability'] ?? '–');
    $anomaly = (int) (($analysis['anomaly'] ?? false) ? 1 : 0);
    $stmt->bind_param('isssis', $user_id, $summary, $trend, $stability, $anomaly, $last_entry_date);
    $stmt->execute();
}

function analyze_recent_trends_with_ai(mysqli $conn, int $user_id, int $days = 10, bool $debug_mode = false): array
{
    $entries = fetch_recent_entries_for_user($conn, $user_id, $days);
    if (count($entries) < 2) {
        return [
            'summary' => 'Legg inn flere målinger for å få en samlet trendanalyse.',
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

    $grouped = [];
    foreach ($entries as $entry) {
        $name = $entry['measurement_name'];
        if (!isset($grouped[$name])) {
            $grouped[$name] = [];
        }
        $grouped[$name][] = [
            'dato' => $entry['entry_date'],
            'verdi' => (float) $entry['value'],
        ];
    }

    $lines = ["Periode: siste {$days} dager", 'Målinger:'];
    foreach ($grouped as $name => $observations) {
        $formatted = array_map(
            static function (array $observation): string {
                return sprintf('%s: %s', $observation['dato'], number_format((float) $observation['verdi'], 1, '.', ''));
            },
            $observations
        );
        $lines[] = sprintf('- %s: %s', $name, implode(', ', $formatted));
    }
    $summary_input = implode("\n", $lines);

    $payload = [
        'model' => 'gpt-5-nano',
        'service_tier' => 'flex',
        'max_output_tokens' => 240,
        'input' => [
            [
                'role' => 'system',
                'content' => 'Du er en treningscoach som lager korte, nøytrale trendinnsikter. '
                    . 'Du skal gi en samlet trendanalyse for alle måletyper de siste 10 dagene. '
                    . 'Returner kun ren tekst (ikke JSON) som inneholder kommentar på trend, stabilitet og avvik. '
                    . 'Skriv på norsk.',
            ],
            [
                'role' => 'user',
                'content' => $summary_input,
            ],
        ],
    ];
    $debug_details = null;
    if ($debug_mode) {
        $debug_details = [
            'endpoint' => 'https://api.openai.com/v1/responses',
            'request_payload' => $payload,
        ];
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
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
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($debug_details !== null) {
        $body = is_string($response) ? trim($response) : '';
        if (strlen($body) > 2000) {
            $body = substr($body, 0, 2000) . '…';
        }
        $debug_details['http_status'] = $status ?: 'ukjent';
        $debug_details['curl_error'] = $curl_errno ? $curl_errno . ' (' . $curl_error . ')' : null;
        $debug_details['response_body'] = $body !== '' ? $body : null;
    }

    if (!$response || $status < 200 || $status >= 300) {
        $result = [
            'summary' => 'AI-analysen er midlertidig utilgjengelig.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
        if ($debug_details !== null) {
            $result['debug'] = $debug_details;
        }
        return $result;
    }

    $decoded = json_decode($response, true);
    $content = extract_openai_response_text($decoded);
    $analysis_text = trim($content);

    if ($analysis_text === '') {
        $result = [
            'summary' => 'Kunne ikke tolke AI-responsen.',
            'trend' => '–',
            'stability' => '–',
            'anomaly' => false,
        ];
        if ($debug_details !== null) {
            $debug_details['decoded_preview'] = $decoded;
            $result['debug'] = $debug_details;
        }
        return $result;
    }

    $result = [
        'summary' => $analysis_text,
        'trend' => '–',
        'stability' => '–',
        'anomaly' => false,
    ];
    if ($debug_details !== null) {
        $debug_details['decoded_preview'] = $decoded;
        $result['debug'] = $debug_details;
    }

    return $result;
}

function get_recent_trend_analysis(
    mysqli $conn,
    int $user_id,
    int $days = 10,
    int $ttl_seconds = 86400,
    bool $debug_mode = false
): array
{
    $cache = fetch_ai_trend_cache($conn, $user_id);
    $latest_entry_date = fetch_latest_entry_date_for_user($conn, $user_id);
    $api_key = get_openai_api_key();
    $cache_fresh = $cache && $cache['updated_at']
        ? strtotime($cache['updated_at']) >= (time() - $ttl_seconds)
        : false;
    $no_new_data = $cache && !$latest_entry_date
        ? true
        : ($cache && $cache['last_entry_date'] && $latest_entry_date
            ? $latest_entry_date <= $cache['last_entry_date']
            : false);
    $cache_missing_key = $cache
        && ($cache['summary'] ?? '') === 'Ingen API-nøkkel tilgjengelig for trendanalyse.'
        && $api_key;

    if (!$debug_mode && $cache && ($cache_fresh || $no_new_data) && !$cache_missing_key) {
        return $cache;
    }

    $analysis = analyze_recent_trends_with_ai($conn, $user_id, $days, $debug_mode);
    if ($latest_entry_date) {
        upsert_ai_trend_cache($conn, $user_id, $analysis, $latest_entry_date);
    }

    if ($analysis['summary'] === 'AI-analysen er midlertidig utilgjengelig.' && $cache) {
        return $cache;
    }

    return $analysis;
}

function test_openai_connection(): array
{
    $api_key = get_openai_api_key();
    $endpoint = 'https://api.openai.com/v1/responses';
    if (!$api_key) {
        return [
            'ok' => false,
            'message' => 'Ingen API-nøkkel tilgjengelig for testkall.',
            'details' => [
                'API-nøkkel funnet: nei',
                'Endepunkt: ' . $endpoint,
            ],
        ];
    }

    $payload = [
        'model' => 'gpt-5-nano',
        'service_tier' => 'flex',
        'max_output_tokens' => 8,
        'input' => [
            [
                'role' => 'system',
                'content' => 'Svar kun med OK.',
            ],
            [
                'role' => 'user',
                'content' => 'Ping.',
            ],
        ],
    ];

    $ch = curl_init($endpoint);
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
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);

    if (!$response || $status < 200 || $status >= 300) {
        $body = is_string($response) ? trim($response) : '';
        if (strlen($body) > 300) {
            $body = substr($body, 0, 300) . '…';
        }
        return [
            'ok' => false,
            'message' => 'Testkallet feilet. HTTP-status: ' . ($status ?: 'ukjent') . '.',
            'details' => array_filter([
                'API-nøkkel funnet: ja',
                'Endepunkt: ' . $endpoint,
                'HTTP-status: ' . ($status ?: 'ukjent'),
                $curl_errno ? 'cURL-feil: ' . $curl_errno . ' (' . $curl_error . ')' : null,
                $total_time ? 'Svartid: ' . number_format($total_time, 2, ',', '') . 's' : null,
                $body !== '' ? 'Respons: ' . $body : null,
            ]),
        ];
    }

    $decoded = json_decode($response, true);
    $content = trim(extract_openai_response_text($decoded));

    return [
        'ok' => true,
        'message' => $content ? 'Testkall OK: ' . $content : 'Testkall OK.',
        'details' => array_filter([
            'API-nøkkel funnet: ja',
            'Endepunkt: ' . $endpoint,
            'HTTP-status: ' . ($status ?: 'ukjent'),
            $total_time ? 'Svartid: ' . number_format($total_time, 2, ',', '') . 's' : null,
        ]),
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
