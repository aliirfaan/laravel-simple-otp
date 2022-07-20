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
            $table->string('model_id')->index('model_id_index');
            $table->string('model_type')->index('model_type_index');
            $table->string('otp_intent')->nullable()->index('otp_intent_index');
            $table->string('otp_code');
            $table->dateTime('otp_generated_at', $precision = 0)->index('generated_at_index');
            $table->boolean('otp_was_validated')->nullable()->index('was_validated_index');
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
