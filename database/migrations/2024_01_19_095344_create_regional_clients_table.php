<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionalClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regional_clients', function (Blueprint $table) {
            $table->id();
            $table->string('base_client_id');
            $table->string('regional_client_name');
            $table->string('state');
            $table->string('gst_no');
            $table->text('address');
            $table->text('remarks')->nullable();
            $table->string('status')->default(1);
            $table->string('saved_by_id');
            $table->string('saved_by_name');
            $table->string('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('regional_clients');
    }
}
