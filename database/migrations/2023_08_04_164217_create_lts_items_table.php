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
        Schema::create('lts_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lts_meta_id')->index()->nullable(); //Not NULL!
            $table->string('alias')->unique(); // mavam101-mavam124
            $table->timestamp('play_at')->nullable();
            $table->text('description')->nullable()->comment('一句话描述');
            $table->string('mp3')->nullable()->comment('attachment_mp3 覆盖上传的音频mp3，用于更正音频');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lts_items');
    }
};
