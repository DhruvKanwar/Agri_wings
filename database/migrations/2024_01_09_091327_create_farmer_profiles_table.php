<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFarmerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id')->nullable();
            $table->string('gender')->nullable();
            $table->string('income')->nullable();
            $table->string('education_level')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('wedding_anniversary')->nullable();
            $table->string('attitude')->nullable();
            $table->string('lifestyle')->nullable();
            $table->string('professional_info')->nullable();
            $table->string('influence')->nullable();
            $table->string('hobbies')->nullable();
            $table->string('favourite_activities')->nullable();
            $table->string('intrests')->nullable();
            $table->string('mobile_phone_used')->nullable();
            $table->string('social_media_platform')->nullable();
            $table->string('tech_proficiency')->nullable();
            $table->string('prferred_communication')->nullable();
            $table->string('email_id')->nullable();
            $table->string('ratings')->nullable();
            $table->text('suggestion_for_improvement')->nullable();
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
        Schema::dropIfExists('farmer_profiles');
    }
}
