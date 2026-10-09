<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 32)->unique();
            $table->dateTime('created_at')->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('product', 150)->nullable();
            $table->unsignedInteger('budget_uah')->nullable();
            $table->string('status', 50)->nullable()->index();
            $table->string('manager', 100)->nullable()->index();
            $table->text('comment')->nullable();
            $table->dateTime('next_contact_at')->nullable()->index();
            $table->dateTime('updated_at')->nullable();

            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
