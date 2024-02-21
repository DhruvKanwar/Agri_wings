<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFarmDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('farm_details', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id');
            $table->string('field_area');
            $table->string('sub_district');
            $table->string('village');
            $table->string('district');
            $table->string('state');
            $table->string('pin_code');
            $table->string('location_coordinates')->nullable();
            $table->text('address');
            $table->string('acerage');
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id');
            $table->string('updated_by_name');
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
        Schema::dropIfExists('farm_details');
    }
}
