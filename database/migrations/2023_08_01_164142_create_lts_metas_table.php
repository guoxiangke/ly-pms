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
        Schema::create('lts_metas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('课程名字');
            $table->string('description')->nullable()->comment('课程描述');
            $table->string('avatar')->nullable();
            $table->string('author')->nullable()->comment('授课老师、分割');
            $table->string('code')->comment('课程命名前缀vfe0')->unique();
            $table->unsignedTinyInteger('count')->comment('课程数量');
            $table->unsignedInteger('index')->nullable()->comment('微信编号&课程排序');
            $table->timestamp('stop_at')->nullable();
            $table->text('remark')->nullable()->comment('备注');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lts_metas');
    }
};
