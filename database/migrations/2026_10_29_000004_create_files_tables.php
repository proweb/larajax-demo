<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates October Rain's file attachment tables, used by the file upload
     * fields: files holds the attachments and deferred_bindings links them to
     * records that have not been saved yet.
     */
    public function up(): void
    {
        Schema::create('deferred_bindings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('master_type');
            $table->string('master_field');
            $table->string('slave_type');
            $table->integer('slave_id');
            $table->string('session_key');
            $table->mediumText('pivot_data')->nullable();
            $table->boolean('is_bind')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('disk_name');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('content_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->nullable();
            $table->integer('attachment_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->index(['attachment_type', 'attachment_id', 'field'], 'files_master_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
        Schema::dropIfExists('deferred_bindings');
    }
};
