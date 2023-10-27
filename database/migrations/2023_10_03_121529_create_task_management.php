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
        Schema::create('task_management', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('order_id');
            $table->string('assigned_to');
            $table->string('priority');
            $table->string('status');
            $table->date('start_date');
            $table->date('deadline');
            $table->string('description');
            $table->string('created_by');
            $table->string('overview')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_management');
    }
};
