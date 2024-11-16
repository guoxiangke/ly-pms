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
        Schema::create('ly_metas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('节目标题')->default('');//不可为空，但此处为了导入方便 @see SyncFromOpenQueue Program::chunk
            $table->string('supervisor')->nullable()->comment('节目监制');
            $table->string('code')->comment('节目网络用代号')->unique();
            $table->text('description')->comment('节目简介')->nullable();
            $table->string('rrule_by_day')->default("MO,TU,WE,TH,FR,SA,SU")->comment('每周播出日期');
            $table->timestamp('begin_at')->nullable()->comment('节目启播日期');
            $table->timestamp('end_at')->nullable()->comment('节目停播日期');
            $table->timestamp('unpublished_at')->nullable()->comment('播放列表下线日期');
            $table->unsignedTinyInteger('counts_max_list')->default(30)->comment('每集节目显示天数');//max_show_counts 播放列表最多显示天数，Publish duration：31-255
            $table->foreignId('make_id')->comment('制作中心')->nullable();
            $table->string('avatar')->comment('节目图')->nullable();
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedInteger('wx_index')->unique()->nullable()->comment('微信编号');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ly_metas');
    }
};
