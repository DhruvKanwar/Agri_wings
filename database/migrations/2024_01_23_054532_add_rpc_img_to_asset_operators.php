<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRpcImgToAssetOperators extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asset_operators', function (Blueprint $table) {
            //
            $table->string('rpc_img')->nullable()->after('rpc_no');
            $table->softDeletes()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_operators', function (Blueprint $table) {
            //
            $table->dropColumn('rpc_img');
            $table->dropSoftDeletes();
        });
    }
}
