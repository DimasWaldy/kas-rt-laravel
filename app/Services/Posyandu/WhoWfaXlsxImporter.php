<?php

namespace App\Services\Posyandu;

use App\Models\Balita;
use App\Models\WhoGrowthStandard;
use Illuminate\Support\Facades\DB;
use PharData;
use RuntimeException;
use SimpleXMLElement;

class WhoWfaXlsxImporter
{
    private const XML_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const EXPECTED_HEADERS = [
        'A' => 'Month',
        'B' => 'L',
        'C' => 'M',
        'D' => 'S',
        'E' => 'SD3neg',
        'F' => 'SD2neg',
        'G' => 'SD1neg',
        'H' => 'SD0',
        'I' => 'SD1',
        'J' => 'SD2',
        'K' => 'SD3',
    ];

    public function import(string $path, string $jenisKelamin): int
    {
        if (! array_key_exists($jenisKelamin, Balita::JENIS_KELAMIN)) {
            throw new RuntimeException("Jenis kelamin {$jenisKelamin} tidak didukung.");
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("File standar WHO tidak dapat dibaca: {$path}");
        }

        $records = $this->readRecords($path);
        $checksum = hash_file('sha256', $path);

        DB::transaction(function () use ($records, $jenisKelamin, $path, $checksum) {
            foreach ($records as $record) {
                WhoGrowthStandard::updateOrCreate(
                    [
                        'indicator' => WhoGrowthStandard::INDICATOR_WEIGHT_FOR_AGE,
                        'jenis_kelamin' => $jenisKelamin,
                        'usia_bulan' => $record['usia_bulan'],
                    ],
                    [
                        ...$record,
                        'versi_standar' => WhoGrowthStandard::VERSION,
                        'source_file' => basename($path),
                        'source_checksum' => $checksum,
                    ]
                );
            }
        });

        return count($records);
    }

    private function readRecords(string $path): array
    {
        try {
            $archive = new PharData($path);
            $sharedStringsXml = $archive['xl/sharedStrings.xml']->getContent();
            $worksheetXml = $archive['xl/worksheets/sheet1.xml']->getContent();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                "Gagal membuka XLSX {$path}: {$exception->getMessage()}",
                previous: $exception
            );
        }

        $sharedStrings = $this->parseSharedStrings($sharedStringsXml);
        $rows = $this->parseWorksheetRows($worksheetXml, $sharedStrings);

        if ($rows === []) {
            throw new RuntimeException('Worksheet standar WHO kosong.');
        }

        $headers = array_shift($rows);
        foreach (self::EXPECTED_HEADERS as $column => $expectedHeader) {
            if (($headers[$column] ?? null) !== $expectedHeader) {
                throw new RuntimeException(
                    "Header kolom {$column} harus {$expectedHeader}."
                );
            }
        }

        $records = array_map(fn (array $row) => $this->normalizeRow($row), $rows);
        $months = array_column($records, 'usia_bulan');
        sort($months);

        if (count($records) !== 61 || $months !== range(0, 60)) {
            throw new RuntimeException(
                'Dataset WFA wajib berisi tepat satu record untuk setiap bulan 0 sampai 60.'
            );
        }

        return $records;
    }

    private function parseSharedStrings(string $xml): array
    {
        $document = $this->loadXml($xml, 'sharedStrings.xml');
        $document->registerXPathNamespace('x', self::XML_NAMESPACE);
        $strings = [];

        foreach ($document->xpath('//x:si') ?: [] as $item) {
            $item->registerXPathNamespace('x', self::XML_NAMESPACE);
            $parts = array_map(
                fn (SimpleXMLElement $text) => (string) $text,
                $item->xpath('.//x:t') ?: []
            );
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function parseWorksheetRows(string $xml, array $sharedStrings): array
    {
        $document = $this->loadXml($xml, 'sheet1.xml');
        $document->registerXPathNamespace('x', self::XML_NAMESPACE);
        $rows = [];

        foreach ($document->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $row->registerXPathNamespace('x', self::XML_NAMESPACE);
            $values = [];

            foreach ($row->xpath('x:c') ?: [] as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) $attributes['r'];
                $column = preg_replace('/\d+/', '', $reference);
                $type = (string) $attributes['t'];
                $cell->registerXPathNamespace('x', self::XML_NAMESPACE);
                $valueNodes = $cell->xpath('x:v') ?: [];
                $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

                if ($type === 's' && $value !== '') {
                    $index = (int) $value;
                    if (! array_key_exists($index, $sharedStrings)) {
                        throw new RuntimeException("Shared string index {$index} tidak ditemukan.");
                    }
                    $value = $sharedStrings[$index];
                }

                $values[$column] = $value;
            }

            $rows[] = $values;
        }

        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (array_keys(self::EXPECTED_HEADERS) as $column) {
            if (! isset($row[$column]) || ! is_numeric($row[$column])) {
                throw new RuntimeException("Nilai kolom {$column} harus numerik.");
            }
        }

        $month = (int) $row['A'];
        if ((string) $month !== (string) $row['A'] || $month < 0 || $month > 60) {
            throw new RuntimeException("Nilai bulan {$row['A']} tidak valid.");
        }

        return [
            'usia_bulan' => $month,
            'l' => (float) $row['B'],
            'm' => (float) $row['C'],
            's' => (float) $row['D'],
            'sd3neg' => (float) $row['E'],
            'sd2neg' => (float) $row['F'],
            'sd1neg' => (float) $row['G'],
            'sd0' => (float) $row['H'],
            'sd1' => (float) $row['I'],
            'sd2' => (float) $row['J'],
            'sd3' => (float) $row['K'],
        ];
    }

    private function loadXml(string $xml, string $source): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $document = simplexml_load_string($xml);
            if ($document === false) {
                $message = collect(libxml_get_errors())
                    ->map(fn (\LibXMLError $error) => trim($error->message))
                    ->implode('; ');
                throw new RuntimeException("XML {$source} tidak valid: {$message}");
            }

            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
