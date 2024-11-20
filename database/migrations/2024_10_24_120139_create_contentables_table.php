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
        Schema::create('contentables', function (Blueprint $table) {
            $table->id();
            // Generates contentable_id and contentable_type
            $table->morphs('contentable'); // 多态关联字段
            $table->foreignId('content_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contentables');
    }
};
