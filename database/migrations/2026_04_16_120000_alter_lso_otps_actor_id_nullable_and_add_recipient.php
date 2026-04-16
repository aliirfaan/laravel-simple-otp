<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterLsoOtpsActorIdNullableAndAddRecipient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lso_otps', function (Blueprint $table) {
            $table->string('actor_id')->nullable()->change();
        });

        Schema::table('lso_otps', function (Blueprint $table) {
            $table->string('recipient')->nullable()->index('recipient_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lso_otps', function (Blueprint $table) {
            $table->dropIndex('recipient_index');
            $table->dropColumn('recipient');
        });

        DB::table('lso_otps')->whereNull('actor_id')->update(['actor_id' => '']);

        Schema::table('lso_otps', function (Blueprint $table) {
            $table->string('actor_id')->nullable(false)->change();
        });
    }
}
