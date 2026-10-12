<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Лайки тем і скарги на теми спільноти. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_topics', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('comments_count');
        });

        Schema::create('community_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['community_topic_id', 'license_id']);
        });

        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            $table->index('is_processed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_likes');
        Schema::table('community_topics', fn (Blueprint $table) => $table->dropColumn('likes_count'));
    }
};
