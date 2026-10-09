<?php

namespace App\Http\Controllers;

use App\Services\LeadImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class LeadImportController extends Controller
{
    public function index(): View
    {
        return view('leads.import');
    }

    public function store(Request $request, LeadImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:15360'],
        ]);

        $startedAt = microtime(true);

        try {
            $count = $importer->import($validated['file']);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        $seconds = round(microtime(true) - $startedAt, 2);

        return back()->with(
            'success',
            "Imported {$count} rows in {$seconds} seconds."
        );
    }
}
