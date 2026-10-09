<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Fast imports beat 100,000 individual INSERTs.');
})->purpose('Display a short message');
