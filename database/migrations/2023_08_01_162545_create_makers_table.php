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
        Schema::create('makers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('avatar')->nullable();
            $table->text('descripton')->nullable();
            $table->timestamp('begin_at')->nullable()->comment('合作时间');
            $table->timestamp('stop_at')->nullable()->comment('停止合作/下架时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('makers');
    }
};
