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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->nullable();

            $table->string('short_description')->nullable();
            $table->string('description')->nullable();

            $table->string('status', 32)->default('draft');
            $table->boolean('is_configurable')->default(true);

            $table->timestamp('published_at')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at'], 'products_publication_idx');

            $table->index(['category_id', 'status'], 'products_category_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
