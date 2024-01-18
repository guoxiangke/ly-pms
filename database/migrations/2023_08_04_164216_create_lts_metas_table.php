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
            $table->string('avatar')->nullable()->comment('课程封面，如果有的话，@see cover');;
            $table->string('author')->nullable()->comment('授课老师、分割');
            $table->string('code')->comment('课程代码')->unique();
            $table->unsignedTinyInteger('count')->comment('课程数量');
            $table->unsignedInteger('index')->unique()->nullable()->comment('微信编号');

            $table->timestamp('begin_at')->nullable()->comment('上架时间');
            $table->timestamp('stop_at')->nullable()->comment('课程下架时间');
            $table->timestamp('made_at')->nullable()->comment('制作日期');
            $table->foreignId('ly_meta_id')->index()->nullable()->comment('上次上架分类hp、dp');

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
