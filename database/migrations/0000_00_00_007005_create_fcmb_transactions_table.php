<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFcmbTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcmb_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id')->index();
            $table->uuid('processing_item_id')->index();
            $table->uuid('fcmb_sender_id')->unique()->index();
            $table->uuid('fcmb_recipient_id')->unique()->index();

            $table->string('state_code');
            $table->longText('state_code_reason')->nullable();

            $table->string('error_code')->nullable();
            $table->longText('error_code_description')->nullable();

            $table->string('operation');
            $table->string('reference')->unique();
            $table->string('linking_reference')->nullable();

            $table->unsignedDouble('amount');
            $table->char('currency_code', 3);
            $table->char('country_code', 3);
            $table->string('reason')->nullable();
            $table->string('description')->nullable();
            $table->string('secret_question')->nullable();
            $table->string('secret_answer')->nullable();

            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);

            $table->foreign('fcmb_sender_id')->references('id')->on('fcmb_senders');
            $table->foreign('fcmb_recipient_id')->references('id')->on('fcmb_recipients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcmb_transactions');
    }
}
