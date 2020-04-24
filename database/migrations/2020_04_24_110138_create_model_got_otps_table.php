<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelGotOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_got_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id')->index();
            $table->string('model_type')->index();
            $table->string('otp_code');
            $table->boolean('otp_was_validated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_got_otps');
    }
}
