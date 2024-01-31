<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCropPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crop_prices', function (Blueprint $table) {
            $table->id();
            $table->string('crop_id');
            $table->string('crop_name');
            $table->string('state')->nullable();
            $table->string('state_price')->nullable();
            $table->string('base_price')->nullable();
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->string('status')->default(1);
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
        Schema::dropIfExists('crop_prices');
    }
}
