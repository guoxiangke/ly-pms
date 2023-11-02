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
        Schema::create('announcers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->unique();
            $table->string('avatar')->nullable();
            $table->date('birthday')->nullable();
            $table->text('description')->nullable();
            // $table->text('favlor')->nullable();
            // $table->text('testimony')->nullable();
            // $table->text('ministry')->nullable();
            $table->timestamp('begin_at')->nullable()->comment('加入时间');
            $table->timestamp('stop_at')->nullable()->comment('停止显示');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcers');
    }
};
