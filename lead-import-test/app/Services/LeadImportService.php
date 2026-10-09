<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

class LeadImportService
{
    private const CHUNK_SIZE = 1000;

    private const EXPECTED_HEADERS = [
        'external_id',
        'created_at',
        'first_name',
        'last_name',
        'phone',
        'email',
        'city',
        'source',
        'utm_campaign',
        'product',
        'budget_uah',
        'status',
        'manager',
        'comment',
        'next_contact_at',
    ];

    public function import(UploadedFile $file): int
    {
        $reader = $this->makeReader($file);
        $reader->open($file->getRealPath());

        $buffer = [];
        $imported = 0;
        $headersValidated = false;
        $now = now();

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();

                    if (! $headersValidated) {
                        $this->validateHeaders($values);
                        $headersValidated = true;
                        continue;
                    }

                    if ($this->rowIsEmpty($values)) {
                        continue;
                    }

                    $buffer[] = $this->mapRow($values, $now);

                    if (count($buffer) >= self::CHUNK_SIZE) {
                        $this->insertChunk($buffer);
                        $imported += count($buffer);
                        $buffer = [];
                    }
                }

                // The provided import file contains one data sheet.
                break;
            }

            if (! $headersValidated) {
                throw new RuntimeException('The uploaded file does not contain a header row.');
            }

            if ($buffer !== []) {
                $this->insertChunk($buffer);
                $imported += count($buffer);
            }
        } finally {
            $reader->close();
        }

        return $imported;
    }

    private function makeReader(UploadedFile $file): CsvReader|XlsxReader
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'xlsx' => new XlsxReader(),
            'csv' => new CsvReader(),
            default => throw new RuntimeException('Only XLSX and CSV files are supported.'),
        };
    }

    private function validateHeaders(array $headers): void
    {
        $normalized = array_map(
            static fn ($value) => trim((string) $value),
            array_slice($headers, 0, count(self::EXPECTED_HEADERS))
        );

        if ($normalized !== self::EXPECTED_HEADERS) {
            throw new RuntimeException('Unexpected file structure. Please use the provided import template.');
        }
    }

    private function mapRow(array $values, $now): array
    {
        $values = array_pad($values, count(self::EXPECTED_HEADERS), null);

        return [
            'external_id' => $this->stringOrNull($values[0]),
            'created_at' => $this->dateTimeOrNull($values[1]),
            'first_name' => $this->stringOrNull($values[2]),
            'last_name' => $this->stringOrNull($values[3]),
            'phone' => $this->stringOrNull($values[4]),
            'email' => $this->stringOrNull($values[5]),
            'city' => $this->stringOrNull($values[6]),
            'source' => $this->stringOrNull($values[7]),
            'utm_campaign' => $this->stringOrNull($values[8]),
            'product' => $this->stringOrNull($values[9]),
            'budget_uah' => $this->integerOrNull($values[10]),
            'status' => $this->stringOrNull($values[11]),
            'manager' => $this->stringOrNull($values[12]),
            'comment' => $this->stringOrNull($values[13]),
            'next_contact_at' => $this->dateTimeOrNull($values[14]),
            'updated_at' => $now,
        ];
    }

    private function insertChunk(array $rows): void
    {
        // external_id is unique, so rerunning the same file is safe and cheap.
        DB::table('leads')->upsert(
            $rows,
            ['external_id'],
            [
                'created_at', 'first_name', 'last_name', 'phone', 'email', 'city',
                'source', 'utm_campaign', 'product', 'budget_uah', 'status',
                'manager', 'comment', 'next_contact_at', 'updated_at',
            ]
        );
    }

    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9-]/u', '', (string) $value);

        return $normalized === '' ? null : (int) $normalized;
    }

    private function dateTimeOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', strtotime((string) $value));
    }
}
