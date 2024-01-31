<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();;
            $table->string('applicability')->nullable();;
            $table->string('scheme_code');
            $table->string('scheme_name');
            $table->string('crop_id');
            $table->string('period_from');
            $table->string('period_to');
            $table->string('crop_base_price');
            $table->string('discount_price')->nullable();
            $table->string('min_acreage')->nullable();
            $table->string('max_acreage')->nullable();
            $table->string('client_id');
            $table->string('status')->default(1);
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schemes');
    }
}
