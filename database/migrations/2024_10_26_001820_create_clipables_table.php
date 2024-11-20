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
        Schema::create('clipables', function (Blueprint $table) {
            $table->id();
            // Generates clipable_id and clipable_type
            $table->morphs('clipable'); // 多态关联字段
            $table->foreignId('clip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clipables');
    }
};
