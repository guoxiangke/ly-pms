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
            $table->string('alias')->unique(); // cc210503
            // $table->timestamp('play_at')->nullable();// Not NULL! compute from alias
            $table->text('description')->nullable()->comment('节目一句话描述，简体中文');

            // $table->string('begin_at')->default('00:00'); // 跳过片头 copy from metadata
            // $table->string('ad_at')->default('01:00'); // 跳过广告
            // $table->integer('ad_length')->default('40'); // 广告长度
            // //多个广告？
            
            // contentAble =》节目文本内容 html / markdown no pdf!
            // markAble =》 节目打点 数据

            // $table->foreignId('content_id')->nullable()->comment('节目文本，如果有的话');
            // 0：draft 1：published null：
            // 导入成功->核听完成->已发布
            // 请上传核听完成的节目
            // $table->unsignedTinyInteger('status')->nullable()->comment('节目状态');

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
