<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBaseClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('base_clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('pan_no')->nullable();
            $table->string('cin')->nullable();
            $table->text('registration_address')->nullable();
            $table->string('account_no')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('gst_nature')->nullable();
            $table->string('signature_name')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('sign_img')->nullable();
            $table->string('logo_img')->nullable();
            $table->string('phone_number')->nullable();
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
        Schema::dropIfExists('base_clients');
    }
}
