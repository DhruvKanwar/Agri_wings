<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTimelineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services_timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->date('order_date')->nullable();
            $table->unsignedBigInteger('assign_created_by_id')->nullable();
            $table->string('assign_created_by')->nullable();
            $table->date('assign_date')->nullable();
            $table->unsignedBigInteger('aknowledged_created_by_id')->nullable();
            $table->string('aknowledged_created_by')->nullable();
            $table->date('aknowledged_date')->nullable();
            $table->unsignedBigInteger('spray_started_created_by_id')->nullable();
            $table->string('spray_started_created_by')->nullable();
            $table->date('spray_started_date')->nullable();
            $table->text('noc_image')->nullable();  // Change to text data type
            $table->text('payment_proof_image')->nullable();  // Change to text data type
            $table->text('farmer_signature')->nullable();  // Change to text data type
            $table->text('farmer_image')->nullable();  // Change to text data type
            $table->unsignedBigInteger('payment_received_created_by_id')->nullable();
            $table->string('payment_received_created_by')->nullable();
            $table->date('payment_received_date')->nullable();
            $table->unsignedBigInteger('delivered_created_by_id')->nullable();
            $table->string('delivered_created_by')->nullable();
            $table->date('delivered_date')->nullable();
            $table->unsignedBigInteger('cancel_created_by_id')->nullable();
            $table->string('cancel_created_by')->nullable();
            $table->date('cancel_date')->nullable();
            $table->string('chemical_used_ids')->nullable();
            $table->string('farmer_available')->nullable();
            $table->string('fresh_water')->nullable();
            $table->string('refund_image')->nullable();
            $table->text('farmer_refund_signature')->nullable();




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
        Schema::dropIfExists('services_timeline');
    }
}
