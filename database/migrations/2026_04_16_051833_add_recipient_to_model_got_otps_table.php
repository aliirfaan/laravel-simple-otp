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
        Schema::table('model_got_otps', function (Blueprint $table) {
            $table->string('model_id')->nullable()->change();
            $table->string('model_type')->nullable()->change();

            // use it for guests and where model_id and type is not available
            $table->string('recipient')->index('recipient_index')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_got_otps', function (Blueprint $table) {
            // write the rollback code here
            $table->string('model_id')->nullable(false)->change();
            $table->string('model_type')->nullable(false)->change();
            $table->dropColumn('recipient');
        });
    }
};
