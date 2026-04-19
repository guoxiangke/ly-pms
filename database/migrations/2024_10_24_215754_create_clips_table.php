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
        Schema::create('clips', function (Blueprint $table) {
            $table->id();
            // 00:00=>0
            // 01:00=>60
            $table->unsignedInteger('begin_at')->default(0)->comment('单位s');
            $table->unsignedInteger('length')->default(0)->comment('单位s');
            $table->text('title')->nullable()->comment('不可为空');
            $table->text('ars_summary')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('album_id')->nullable()
                  ->constrained('albums')->nullOnDelete();
            $table->integer('sort_order')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clips');
    }
};
