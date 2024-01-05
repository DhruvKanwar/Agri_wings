<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDroneDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drone_details', function (Blueprint $table) {
            $table->id();
            $table->string('drone_id');
            $table->string('model');
            $table->string('uin');
            $table->string('capacity')->nullable();
            $table->string('mfg_year');
            $table->string('status')->default(1);
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
        Schema::dropIfExists('drone_details');
    }
}
