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
        // Storage::disk('s3')->url($this->path)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            //"下载讲义"
            $table->string('name');
            // '/rly/attachments/3974_aw_prog_260_20181021.pdf'
            $table->string('path');
            // $table->string('disk')->default('s3');
            $table->string('description')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('user_id')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
