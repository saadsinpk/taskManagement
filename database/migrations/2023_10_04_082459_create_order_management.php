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
        Schema::create('order_management', function (Blueprint $table) {
            $table->id();
            $table->string('order_name');
            $table->string('order_status');
            $table->string('overview')->nullable();
            $table->string('created_by');
            $table->date('date_created');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_management');
    }
};
