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
            $table->string('farmer_id');
            $table->string('gender');
            $table->string('income');
            $table->string('education_level');
            $table->string('date_of_birth');
            $table->string('wedding_anniversary');
            $table->string('attitude');
            $table->string('lifestyle');
            $table->string('professional_info');
            $table->string('influence');
            $table->string('hobbies');
            $table->string('favourite_activities');
            $table->string('intrests');
            $table->string('mobile_phone_used');
            $table->string('social_media_platform');
            $table->string('tech_proficiency');
            $table->string('prferred_communication');
            $table->string('email_id');
            $table->string('ratings');
            $table->text('suggestion_for_improvement');
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
