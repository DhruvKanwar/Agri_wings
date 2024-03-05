<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ter', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->string('da_amount');
            $table->string('total_attendance');
            $table->string('total_claimed_amount');
            $table->string('total_category_amount');
            $table->string('da_limit');
            $table->string('category_ids');
            $table->date('submit_date');
            $table->date('hr_updated_date')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('ter');
    }
}
