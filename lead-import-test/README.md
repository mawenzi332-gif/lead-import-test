# Lead Import Test Task

A small Laravel application that imports the provided leads file into MySQL.

The input contains 100,000 data rows. The implementation is designed to finish under the standard PHP `max_execution_time=30` on a normal local MySQL setup by avoiding per-row Eloquent operations.

## Approach

- **OpenSpout** streams XLSX/CSV rows instead of loading the whole workbook into memory.
- Rows are accumulated in **chunks of 1,000**.
- Each chunk is written using one database **upsert**, instead of 1,000 individual INSERT statements.
- `external_id` is unique, so importing the same file again is idempotent.
- Nullable source fields are preserved as `NULL`.
- `budget_uah` values such as `23 700` / `23 700` are normalized to integers.
- The importer validates the expected 15-column header before writing data.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+
- Laravel 12

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and configure `.env`, then run:

```bash
php artisan migrate
php artisan serve
```

Open the URL printed by `php artisan serve`, select the provided `.xlsx` or `.csv` file and click **Import**.

## Database structure

The database schema is included as a Laravel migration:

`database/migrations/2026_10_09_000000_create_leads_table.php`

Main indexes:

- unique: `external_id`
- indexes: `status`, `manager`, `next_contact_at`, `email`, `phone`

## Why no queue?

The acceptance criterion explicitly requires all rows to be written while PHP runs with the standard 30-second execution limit. Moving the work to a queue would only move the long-running operation to another process and would make the web request finish before the import itself is complete.

For this task, streaming + bulk SQL is simpler and directly demonstrates that 100,000 rows can be imported efficiently within one request.

## Performance notes

The critical performance difference is query count:

- naive `Model::create()` loop: approximately **100,000 INSERT queries**;
- this solution with a chunk size of 1,000: approximately **100 UPSERT queries**.

Actual timing depends on CPU, disk and MySQL configuration, so the UI displays the measured import duration after completion.

## Trade-offs

For a production system with much larger files, multi-user concurrent imports, retries or long-running enrichment, I would move imports to background jobs and track import status separately. That extra complexity is intentionally avoided here because it is not required by the task.
