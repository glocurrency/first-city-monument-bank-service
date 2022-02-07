<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFcmbRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcmb_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('mobile')->nullable();
            $table->char('country_code', 3);
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->date('id_expiry')->nullable();

            $table->string('account_number')->nullable();
            $table->string('bank_code')->nullable();

            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcmb_recipients');
    }
}
