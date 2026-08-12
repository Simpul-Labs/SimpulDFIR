<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id');
            $table->timestamp('timestamp')->useCurrent();
            $table->string('source_ip')->nullable()->index();
            $table->text('log_message');
            $table->string('threat_level')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_logs');
    }
};
