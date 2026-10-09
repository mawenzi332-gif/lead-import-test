<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lead Import</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 60px auto; padding: 0 20px; color: #111827; }
        .card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 28px; box-shadow: 0 8px 28px rgba(0,0,0,.06); }
        h1 { margin-top: 0; }
        input[type=file] { display: block; width: 100%; margin: 18px 0; }
        button { border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
        .msg { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; background: #f3f4f6; }
        .error { background: #fee2e2; }
        .success { background: #dcfce7; }
        .hint { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Import leads</h1>
    <p class="hint">Upload the provided XLSX or CSV file. Rows are streamed and written to the database in batches.</p>

    @if (session('success'))
        <div class="msg success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="msg error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="msg error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".xlsx,.csv" required>
        <button type="submit">Import</button>
    </form>
</div>
</body>
</html>
