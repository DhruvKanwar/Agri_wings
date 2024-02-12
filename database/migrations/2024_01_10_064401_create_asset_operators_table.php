<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetOperatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_operators', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('phone');
            $table->string('rpc_no')->nullable();
            $table->string('dl_no')->nullable();
            $table->string('aadhaar_no')->nullable();
            $table->string('dl_img')->nullable();
            $table->string('aadhaar_img')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('user_id')->nullable();
            $table->string('user_password')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->string('asset_id')->nullable();
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
        Schema::dropIfExists('asset_operators');
    }
}
