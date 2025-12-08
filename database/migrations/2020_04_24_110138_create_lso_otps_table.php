<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLsoOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lso_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor_id')->index('actor_id_index');
            $table->string('actor_type')->nullable()->index('actor_type_index');
            $table->string('device_id')->nullable()->index('device_id_index');
            $table->string('otp_intent')->nullable()->index('otp_intent_index');
            $table->string('otp_code_hash');
            $table->dateTime('otp_generated_at')->index('otp_generated_at_index');
            $table->dateTime('otp_verified_at')->nullable()->index('otp_verified_at_index');
            $table->dateTime('otp_expired_at')->index('otp_expired_at_index');
            $table->string('correlation_id')->nullable()->index('correlation_id_index');
            $table->json('otp_meta')->nullable();

            $table->index(['actor_id', 'actor_type', 'otp_intent', 'device_id', 'otp_verified_at', 'otp_expired_at'], 'idx_otp_actor_intent_verification');
            
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
        Schema::dropIfExists('lso_otps');
    }
}
