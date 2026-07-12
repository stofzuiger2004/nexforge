<?php

declare(strict_types=1);
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

            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();

            $table->string('short_description')->nullable();
            $table->text('description')->nullable();

            $table->string('status', 32)->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_configurable')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at'], 'systems_publication_idx');
            $table->index(['is_featured', 'sort_order'], 'systems_featured_sort_idx');
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
