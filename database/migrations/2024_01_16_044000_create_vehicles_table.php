<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('operator_id')->nullable();
            $table->string('owned_by')->nullable();
            $table->string('type');
            $table->string('registration_no');
            $table->string('chassis_no')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('manufacturer');
            $table->year('year_of_make')->nullable();
            $table->string('assigned_to_operator')->nullable();
            $table->string('status')->default(1);
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
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
        Schema::dropIfExists('vehicles');
    }
}
