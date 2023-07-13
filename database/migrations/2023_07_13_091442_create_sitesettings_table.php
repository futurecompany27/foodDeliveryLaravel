<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sitesettings', function (Blueprint $table) {
            $table->id();
            $table->string('phone_one')->nullable();
            $table->string('phone_two')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('copyright')->nullable();
            $table->string('facebook')->nullable();
            $table->string('facebookIcon')->nullable();
            $table->string('instagram')->nullable();
            $table->string('instagramIcon')->nullable();
            $table->string('twitter')->nullable();
            $table->string('twitterIcon')->nullable();
            $table->string('youtube')->nullable();
            $table->string('youtubeIcon')->nullable();
            $table->string('created_by_company_link')->nullable();
            $table->string('created_by_company')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitesettings');
    }
};
