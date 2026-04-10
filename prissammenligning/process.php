<?php
// process.php
// Tar imot to PDF-filer, forsøker å hente ut tilbudslinjer, lagrer i DB og sender videre til resultatside.

require_once __DIR__ . '/db.php';

// Vi bruker smalot/pdfparser hvis tilgjengelig via Composer.
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
}
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$store1 = isset($_POST['store_1']) ? (int) $_POST['store_1'] : 0;
$store2 = isset($_POST['store_2']) ? (int) $_POST['store_2'] : 0;

if ($store1 <= 0 || $store2 <= 0 || $store1 === $store2) {
    die('Velg to ulike butikker.');
}

if (!isset($_FILES['pdf_1'], $_FILES['pdf_2'])) {
    die('Begge PDF-filer må lastes opp.');
}

if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0775, true);
}

$mysqli->begin_transaction();

try {
    // Opprett ett import-batch-id som knytter begge filer til samme kjøring.
    $mysqli->query('INSERT INTO imports (created_at) VALUES (NOW())');
    $importId = (int) $mysqli->insert_id;

    $offersByFile = [];

    // Håndter fil 1 og fil 2 likt for å holde logikken enkel.
    $fileConfigs = [
        ['field' => 'pdf_1', 'store_id' => $store1],
        ['field' => 'pdf_2', 'store_id' => $store2],
    ];

    foreach ($fileConfigs as $config) {
        $uploadInfo = $_FILES[$config['field']];
        validateUploadedPdf($uploadInfo);

        $savedPath = saveUploadedFile($uploadInfo, $config['store_id']);

        $stmtFile = $mysqli->prepare('INSERT INTO import_files (import_id, store_id, file_path) VALUES (?, ?, ?)');
        $stmtFile->bind_param('iis', $importId, $config['store_id'], $savedPath);
        $stmtFile->execute();
        $importFileId = (int) $stmtFile->insert_id;
        $stmtFile->close();

        $text = extractPdfText($savedPath);
        $parsedOffers = parseOffersFromText($text);

        $offersByFile[] = [
            'import_file_id' => $importFileId,
            'store_id' => $config['store_id'],
            'offers' => $parsedOffers,
        ];
    }

    // Bygg match mellom butikkene basert på normalisert navn.
    applyMatchingAndSaveOffers($mysqli, $offersByFile, $importId);

    $mysqli->commit();

    header('Location: result.php?import_id=' . $importId);
    exit;
} catch (Throwable $e) {
    $mysqli->rollback();
    die('Feil under prosessering: ' . htmlspecialchars($e->getMessage()));
}

/**
 * Validerer grunnleggende upload-feil + at filen ser ut som PDF.
 */
function validateUploadedPdf(array $uploadInfo): void
{
    if (($uploadInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('En av filene kunne ikke lastes opp.');
    }

    $name = strtolower($uploadInfo['name'] ?? '');
    if (!str_ends_with($name, '.pdf')) {
        throw new RuntimeException('Kun PDF-filer er tillatt.');
    }
}

/**
 * Lagrer fil i /uploads med unikt navn.
 */
function saveUploadedFile(array $uploadInfo, int $storeId): string
{
    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $uploadInfo['name']);
    $filename = date('Ymd_His') . '_store' . $storeId . '_' . uniqid() . '_' . $safeName;
    $targetPath = __DIR__ . '/uploads/' . $filename;

    if (!move_uploaded_file($uploadInfo['tmp_name'], $targetPath)) {
        throw new RuntimeException('Kunne ikke lagre opplastet fil.');
    }

    // Vi lagrer relativ sti i DB for enkel bruk i appen.
    return 'uploads/' . $filename;
}

/**
 * Leser tekst fra PDF.
 * Prioriterer smalot/pdfparser. Fallback: pdftotext hvis installert i systemet.
 */
function extractPdfText(string $relativePath): string
{
    $fullPath = __DIR__ . '/' . $relativePath;

    // Smalot-parser via Composer.
    if (class_exists('Smalot\\PdfParser\\Parser')) {
        $parser = new Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($fullPath);
        return $pdf->getText();
    }

    // Enkel fallback for miljøer der vendor ikke finnes.
    $cmd = 'pdftotext ' . escapeshellarg($fullPath) . ' -';
    $text = shell_exec($cmd);

    if (is_string($text) && trim($text) !== '') {
        return $text;
    }

    throw new RuntimeException('Fant ingen PDF-parser. Installer f.eks. smalot/pdfparser via Composer.');
}

/**
 * Enkel linjebasert parsing.
 * Matcher linjer som typisk inneholder både navn og pris, f.eks: "Rødløk 24,90".
 */
function parseOffersFromText(string $text): array
{
    $offers = [];
    $lines = preg_split('/\R/u', $text) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // Prisformat støttet: 24,90 eller 24.90
        if (!preg_match('/(.+?)\s+(\d{1,4}[\.,]\d{2})(?:\s*kr)?$/ui', $line, $matches)) {
            continue;
        }

        $rawName = trim($matches[1]);
        $priceRaw = str_replace(',', '.', $matches[2]);
        $price = (float) $priceRaw;

        if ($price <= 0) {
            continue;
        }

        $normalized = normalizeName($rawName);
        if ($normalized === '') {
            continue;
        }

        $offers[] = [
            'raw_name' => $rawName,
            'normalized_name' => $normalized,
            'price' => $price,
            'unit' => null,
        ];
    }

    return $offers;
}

/**
 * Normaliserer produktnavn:
 * - lowercase
 * - fjern spesialtegn
 * - fjern enkle stoppord (stk/kg/g)
 */
function normalizeName(string $name): string
{
    $name = mb_strtolower($name, 'UTF-8');
    $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name);
    $name = preg_replace('/\s+/u', ' ', trim($name));

    if ($name === '') {
        return '';
    }

    $stopWords = ['stk', 'kg', 'g'];
    $words = explode(' ', $name);
    $filtered = [];

    foreach ($words as $word) {
        if ($word === '' || in_array($word, $stopWords, true)) {
            continue;
        }
        $filtered[] = $word;
    }

    return trim(implode(' ', $filtered));
}

/**
 * Matcher og lagrer tilbud i offers-tabellen.
 * match_group = normalized_name ved eksakt match.
 * confidence:
 * - 1.0 ved identisk normalized_name
 * - 0.7 ved delvis ord-overlapp
 * - 0.0 hvis ingen match
 */
function applyMatchingAndSaveOffers(mysqli $mysqli, array $offersByFile, int $importId): void
{
    if (count($offersByFile) !== 2) {
        throw new RuntimeException('Forventet nøyaktig to filer i importen.');
    }

    $first = $offersByFile[0];
    $second = $offersByFile[1];

    $secondByNormalized = [];
    foreach ($second['offers'] as $offer) {
        $secondByNormalized[$offer['normalized_name']][] = $offer;
    }

    $rowsToInsert = [];

    // Først: gå gjennom tilbud i butikk 1 og prøv å finne match i butikk 2.
    foreach ($first['offers'] as $offerA) {
        $normalizedA = $offerA['normalized_name'];
        $matchGroup = null;
        $confidence = 0.0;

        if (isset($secondByNormalized[$normalizedA]) && count($secondByNormalized[$normalizedA]) > 0) {
            // Identisk normalisert navn gir sikker match.
            $matchedOffer = array_shift($secondByNormalized[$normalizedA]);
            $matchGroup = $normalizedA;
            $confidence = 1.0;

            $rowsToInsert[] = buildOfferRow($first['import_file_id'], $first['store_id'], $offerA, $matchGroup, $confidence);
            $rowsToInsert[] = buildOfferRow($second['import_file_id'], $second['store_id'], $matchedOffer, $matchGroup, $confidence);
            continue;
        }

        // Delvis match: enkel ord-overlapp mot alle gjenstående i butikk 2.
        $partialIndex = findPartialMatchIndex($normalizedA, $secondByNormalized);
        if ($partialIndex !== null) {
            $candidate = array_shift($secondByNormalized[$partialIndex]);
            $matchGroup = $normalizedA;
            $confidence = 0.7;

            if (empty($secondByNormalized[$partialIndex])) {
                unset($secondByNormalized[$partialIndex]);
            }

            $rowsToInsert[] = buildOfferRow($first['import_file_id'], $first['store_id'], $offerA, $matchGroup, $confidence);
            $rowsToInsert[] = buildOfferRow($second['import_file_id'], $second['store_id'], $candidate, $matchGroup, $confidence);
            continue;
        }

        // Ingen match: behold raden uten match_group/confidence.
        $rowsToInsert[] = buildOfferRow($first['import_file_id'], $first['store_id'], $offerA, null, 0.0);
    }

    // Resten av varer fra butikk 2 uten match.
    foreach ($secondByNormalized as $leftovers) {
        foreach ($leftovers as $offerB) {
            $rowsToInsert[] = buildOfferRow($second['import_file_id'], $second['store_id'], $offerB, null, 0.0);
        }
    }

    $sql = 'INSERT INTO offers
        (import_file_id, store_id, raw_name, normalized_name, price, unit, match_group, confidence)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    foreach ($rowsToInsert as $row) {
        $stmt->bind_param(
            'iissdssd',
            $row['import_file_id'],
            $row['store_id'],
            $row['raw_name'],
            $row['normalized_name'],
            $row['price'],
            $row['unit'],
            $row['match_group'],
            $row['confidence']
        );
        $stmt->execute();
    }
    $stmt->close();
}

/**
 * Lager struktur for én rad som skal settes inn i offers.
 */
function buildOfferRow(int $importFileId, int $storeId, array $offer, ?string $matchGroup, float $confidence): array
{
    return [
        'import_file_id' => $importFileId,
        'store_id' => $storeId,
        'raw_name' => $offer['raw_name'],
        'normalized_name' => $offer['normalized_name'],
        'price' => $offer['price'],
        'unit' => $offer['unit'],
        'match_group' => $matchGroup,
        'confidence' => $confidence,
    ];
}

/**
 * Returnerer normalized_name-nøkkel fra butikk 2 ved enkel delvis match.
 */
function findPartialMatchIndex(string $normalizedA, array $secondByNormalized): ?string
{
    $wordsA = array_filter(explode(' ', $normalizedA));

    foreach ($secondByNormalized as $normalizedB => $offersB) {
        if (empty($offersB)) {
            continue;
        }

        $wordsB = array_filter(explode(' ', $normalizedB));
        $overlap = array_intersect($wordsA, $wordsB);

        if (count($overlap) > 0) {
            return $normalizedB;
        }
    }

    return null;
}
