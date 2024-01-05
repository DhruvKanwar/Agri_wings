<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFarmerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('farmer_details', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_code');//yes
            $table->string('farmer_name');
            $table->string('farmer_mobile_no');
            $table->string('farmer_pincode');
            $table->string('farmer_sub_district'); //yes
            $table->string('farmer_village'); //yes
            $table->string('farmer_district');
            $table->string('farmer_state');
            $table->text('farmer_address');
            $table->string('status')->default(1);
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id');
            $table->string('updated_by_name');
            // Add more columns as needed
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
        Schema::dropIfExists('farmer_details');
    }
}
