<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LocationData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('location_data', function (Blueprint $table) {
            $table->id();
            $table->string('state_name');
            $table->string('state_code');
            $table->string('district_name');
            $table->string('district_code');
            $table->string('subdistrict_name');
            $table->string('subdistrict_code');
            $table->string('vil_town_name');
            $table->string('vil_town_code');
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
        //
    }
}
