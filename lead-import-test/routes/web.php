<?php

use App\Http\Controllers\LeadImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadImportController::class, 'index'])->name('leads.import');
Route::post('/import', [LeadImportController::class, 'store'])->name('leads.import.store');
