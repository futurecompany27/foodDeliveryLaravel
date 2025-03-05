<?php

use App\Models\Driver;
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
        Schema::create('driver_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Driver::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('related_to');
            $table->text('message');
            $table->string('sample_pic')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_suggestions');
    }
};
