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
            $table->string('name');
            $table->string('supervisor')->nullable()->comment('节目监制');
            $table->string('code')->unique();
            // $table->string('fields')->nullable();
            // custom_fields:brief,email,sms_keyword,phone_open,
            $table->text('description')->nullable();
            // RRULE:FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=MO,TU,WE,TH,FR,SA,SU
            // rule.toText() =>  every day
            $table->string('rrule_by_day')->default("MO,TU,WE,TH,FR,SA,SU")->comment('播放规则rrule Not NULL!');
            $table->timestamp('begin_at')->nullable();
            $table->timestamp('end_at')->nullable()->comment('停播日期');
            $table->timestamp('unpublished_at')->nullable()->comment('下架日期，强制不显示');
            $table->foreignId('make_id')->nullable();
            $table->string('avatar')->nullable();
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
        Schema::dropIfExists('ly_metas');
    }
};
