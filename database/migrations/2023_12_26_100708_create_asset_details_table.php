<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_details', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('asset_id');
            $table->string('model');
            $table->string('uin');
            $table->string('asset_capacity')->nullable();
            $table->string('asset_spray_capacity')->nullable();
            $table->string('mfg_year');
            $table->string('battery_ids')->nullable();
            $table->string('assigned_status')->default(0);
            $table->date('assigned_date')->nullable();
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
