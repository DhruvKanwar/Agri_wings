<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        // Insert the default admin user
        // DB::table('users')->insert([
        //     'name' => 'Super Admin',
        //     'email' => 'superadmin@agriwings.com',
        //     'role' => 'super admin',
        //     'password' => bcrypt('123456'), // Change 'password' to the desired password
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
