<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')
                ->constrained('review_sources')
                ->restrictOnDelete();
            $table->string('review_id');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('rating', 5, 2)->nullable();
            $table->string('review_title')->nullable();
            $table->longText('review_text')->nullable();
            $table->dateTime('review_date')->nullable();
            $table->dateTime('response_date')->nullable();
            $table->unique(['source_id', 'review_id']);
            $table->timestamps();
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'], true)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->fullText(
                    ['customer_name', 'customer_email', 'review_title', 'review_text'],
                    'reviews_customer_content_fulltext',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
