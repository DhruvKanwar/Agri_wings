<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorReimbursementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operator_reimbursement_details', function (Blueprint $table) {
            $table->id();
            $table->string('unid')->nullable();
            $table->string('category');
            $table->string('bill_no');
            $table->date('from_date');
            $table->date('to_date');
            $table->string('bill_amount');
            $table->string('claimed_amount');
            $table->text('remarks');
            $table->string('attachment');
            $table->string('status')->default(1);
            $table->string('user_id');
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
        Schema::dropIfExists('operator_reimbursement_details');
    }
}
