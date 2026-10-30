<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the user_groups table, the pivot table for the many-to-many
     * "secondary groups" relation, and the primary_group_id foreign key on
     * users for the belongs-to "primary group" relation.
     */
    public function up(): void
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_user_group', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'user_group_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('primary_group_id')
                ->nullable()
                ->after('id')
                ->constrained('user_groups')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_group_id');
        });

        Schema::dropIfExists('user_user_group');
        Schema::dropIfExists('user_groups');
    }
};
