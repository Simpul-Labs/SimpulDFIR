<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forensic_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('hash');
            $table->string('status');
            $table->longText('pdf_data')->nullable(); // Can store base64 or path
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forensic_reports');
    }
};
