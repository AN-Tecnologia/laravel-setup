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
        Schema::create('server_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method')->nullable();
            $table->string('url')->nullable();
            $table->integer('status_code')->nullable();
            $table->longText('body')->nullable();
            $table->string('client_request_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_responses');
    }
};
