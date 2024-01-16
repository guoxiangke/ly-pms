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
        Schema::create('ly_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ly_meta_id')->index()->nullable(); //Not NULL!
            $table->foreignId('announcer_id')->nullable()->comment('本次节目的主持人');
            $table->string('alias')->unique(); // mavam101
            $table->text('description')->nullable()->comment('title:节目一句话描述，简体中文');
            // Not NULL! compute from alias. But need query by date!
            $table->timestamp('play_at')->nullable();
            $table->string('mp3')->nullable()->comment('attachment_mp3 覆盖上传的音频mp3，用于更正音频');// versions-able:with Uploader Uid
            $table->unsignedInteger('filesize')->nullable();
            $table->string('playtime_string')->nullable();
            // TODO 
                // warning: 请务必上传核听完成的节目
                // 上传之后，队列处理ID3（logo 版权）（假设1h之后完成处理）&& （纠正错误功能）临时返回tempUrl以供当天当前1h内播放！
            // 但最终处理后的路径，依然是old&new
                // New: /ly/audio/ttb/2023/ttb230726.mp3
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ly_items');
    }
};
