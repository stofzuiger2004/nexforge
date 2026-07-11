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
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique;
            $table->text('description')->nullable();
            $table->string('processor');
            $table->string('graphics_card');
            $table->string('memory');
            $table->string('storage');
            $table->unsignedInteger('price_in_cents');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(false);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
