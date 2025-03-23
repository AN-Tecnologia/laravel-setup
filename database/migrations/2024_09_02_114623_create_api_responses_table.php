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
        Schema::create('api_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method')->nullable();
            $table->string('url')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->nullable();
            $table->string('api_request_id')->nullable();
            $table->string('api')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_responses');
    }
};
