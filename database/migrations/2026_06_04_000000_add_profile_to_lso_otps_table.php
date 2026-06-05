<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileToLsoOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lso_otps', function (Blueprint $table) {
            $table->string('profile')->nullable()->index('profile_index');
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
            $table->dropIndex('profile_index');
            $table->dropColumn('profile');
        });
    }
}
