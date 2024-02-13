<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('order_id',100);
            $table->string('order_type',100)->comment("order_type 1=> general,2=>Client , 3=> Subvention, 4=> R & D,5=> Demo");
            $table->string('client_id',200)->nullable();
            $table->string('farmer_name',250);
            $table->string('farmer_id',100);
            $table->date('spray_date');
            $table->string('crop_name',200);
            $table->string('crop_id',100);
            $table->string('requested_acreage');
            $table->string('sprayed_acreage')->nullable();
            $table->string('farm_location');
            $table->string('scheme_ids',200);
            $table->string('total_discount');
            $table->string('extra_discount')->nullable();;
            $table->string('remarks')->nullable();
            $table->string('amount_received')->nullable();
            $table->string('total_amount');
            $table->string('refund_amount')->nullable();
            $table->string('total_payable_amount');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('order_status',100)->default(1)->nullable();
            $table->string('payment_status',100)->nullable();
            $table->string('spray_status',100)->nullable();
            $table->string('agriwings_discount')->nullable();
            $table->string('client_discount')->nullable();
            $table->string('added_amount')->nullable();
            $table->string('asset_operator_id',150)->nullable();
            $table->string('assigned_status')->default(0);
            $table->date('assigned_date')->nullable();
            $table->string('asset_id', 150)->nullable();
            $table->string('battery_ids', 150)->nullable();
            $table->string('order_accepted', 150)->nullable()->default(0);
            $table->string('order_details_id', 150)->nullable();
            $table->string('cancel_remarks')->nullable();
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
        Schema::dropIfExists('service');
    }
}
