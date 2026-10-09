<?php

namespace Tests\Feature;

use App\Services\LeadImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeadImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_csv_rows_and_normalizes_budget(): void
    {
        $path = storage_path('framework/testing/leads.csv');
        @mkdir(dirname($path), 0777, true);

        $handle = fopen($path, 'wb');
        fputcsv($handle, [
            'external_id', 'created_at', 'first_name', 'last_name', 'phone', 'email', 'city',
            'source', 'utm_campaign', 'product', 'budget_uah', 'status', 'manager', 'comment', 'next_contact_at',
        ]);
        fputcsv($handle, [
            'LD-000001', '2025-07-10 05:06:54', 'Олександр', 'Поліщук', '380672341057',
            'test@example.com', 'Івано-Франківськ', 'Facebook Ads', 'catalog_2026',
            'Контекстна реклама', '23 700', 'in_progress', 'Литвиненко Н.', '',
            '2025-07-23 05:06:54',
        ]);
        fclose($handle);

        $file = new UploadedFile($path, 'leads.csv', 'text/csv', null, true);
        $count = app(LeadImportService::class)->import($file);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('leads', [
            'external_id' => 'LD-000001',
            'budget_uah' => 23700,
        ]);
    }
}
