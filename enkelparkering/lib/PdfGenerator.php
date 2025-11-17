<?php
/**
 * Minimal PDF generator tailored for standard kontraktstilbud.
 */
class PdfGenerator
{
    /**
     * Create a PDF offer document with the provided data.
     *
     * @param string $filePath
     * @param array $data
     * @throws RuntimeException When the PDF cannot be written
     */
    public static function createContractOffer(string $filePath, array $data): void
    {
        $typeLabel = ucfirst($data['type'] ?? '');
        $monthly = self::formatCurrency($data['monthly'] ?? 0);
        $deposit = self::formatCurrency($data['deposit'] ?? 0);
        $placeLine = sprintf('Tilbudet gjelder plass %s ved %s (%s).', $data['place'], $data['facility'], $typeLabel);

        $vandalisme = 'All hærverk eller skader på parkeringsområdet skal meldes umiddelbart til styret. '
            . 'Leietaker kan bli holdt økonomisk ansvarlig for skader som skyldes uaktsom bruk.';

        $ladeinfo = 'Det er installert ladeinfrastruktur på anlegget. '
            . 'Eventuell ladeboks må likevel bekostes og vedlikeholdes av leietaker.';

        $priceLines = sprintf('Depositum: %s   |   Månedlig leie: %s', $deposit, $monthly);

        $lines = [];
        $lines[] = ['text' => 'Tilbud om parkeringsplass', 'size' => 18, 'spacing' => 28];
        $lines[] = ['text' => '', 'size' => 12, 'spacing' => 12];
        $lines[] = ['text' => 'Navn: ' . $data['name'], 'size' => 12];
        $lines[] = ['text' => $placeLine, 'size' => 12];
        $lines[] = ['text' => 'Tilbud sendt: ' . $data['date'], 'size' => 12];
        $lines[] = ['text' => '', 'size' => 12, 'spacing' => 18];
        $lines[] = ['text' => 'Priser og betaling', 'size' => 14, 'spacing' => 22];
        $lines[] = ['text' => $priceLines, 'size' => 12];
        $lines[] = ['text' => 'Depositum betales før overtakelse. Leie faktureres månedlig.', 'size' => 12];
        $lines[] = ['text' => '', 'size' => 12, 'spacing' => 18];
        $lines[] = ['text' => 'Vilkår', 'size' => 14, 'spacing' => 22];
        $lines = array_merge($lines, self::paragraphLines($vandalisme));
        $lines = array_merge($lines, self::paragraphLines($ladeinfo));
        $lines[] = ['text' => 'Plassen skal holdes ryddig. Eventuelle skader rapporteres straks.', 'size' => 12];
        $lines[] = ['text' => '', 'size' => 12, 'spacing' => 24];
        $lines[] = ['text' => 'Signatur', 'size' => 14, 'spacing' => 22];
        $lines[] = ['text' => 'Leietaker: ________________________________', 'size' => 12, 'spacing' => 20];
        $lines[] = ['text' => 'Dato: _____________________________________', 'size' => 12];

        self::writePdf($filePath, $lines);
    }

    private static function paragraphLines(string $text): array
    {
        $wrapped = [];
        foreach (preg_split('/\r?\n/', trim($text)) as $line) {
            if ($line === '') {
                continue;
            }
            $parts = explode("\n", wordwrap($line, 90, "\n"));
            foreach ($parts as $part) {
                $wrapped[] = ['text' => trim($part), 'size' => 12];
            }
            $wrapped[] = ['text' => '', 'size' => 12];
        }
        return $wrapped;
    }

    private static function writePdf(string $filePath, array $lines): void
    {
        $content = "BT\n";
        $currentSize = 0;
        $y = 800;
        foreach ($lines as $line) {
            $size = $line['size'] ?? 12;
            $spacing = $line['spacing'] ?? 18;
            $text = $line['text'] ?? '';

            if ($size !== $currentSize) {
                $content .= sprintf("/F1 %d Tf\n", $size);
                $currentSize = $size;
            }

            if ($text === '') {
                $y -= $spacing;
                continue;
            }

            $parts = explode("\n", wordwrap($text, 90, "\n"));
            foreach ($parts as $part) {
                $content .= sprintf("1 0 0 1 50 %.2f Tm\n(%s) Tj\n", $y, self::escape($part));
                $y -= $spacing;
            }
        }
        $content .= "ET";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $obj) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 $count\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPosition\n%%EOF";

        if (file_put_contents($filePath, $pdf) === false) {
            throw new RuntimeException('Kunne ikke skrive PDF-fil.');
        }
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private static function formatCurrency(int $value): string
    {
        return number_format($value, 0, ',', ' ') . ' kr';
    }
}
