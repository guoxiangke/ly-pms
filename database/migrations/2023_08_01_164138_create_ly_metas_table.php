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
            $table->string('code')->unique();
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('begin_at')->nullable();
            $table->timestamp('stop_at')->nullable();
            $table->foreignId('maker_id')->nullable(); //Not NULL!
            $table->text('remark')->nullable()->comment('备注');
            // $table->string('begin_at')->nullable()->comment('跳过片头 00:30');
            $table->softDeletes();
            $table->timestamps();
            // $table->string('cron')->default("0 0 * * *"); // schedule_cron
            // $table->string('fields')->nullable();
            //custom_fields:brief,email,sms_keyword,phone_open,cbox_uri,
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
