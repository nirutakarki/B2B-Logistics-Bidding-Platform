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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('raised_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open');  
            $table->string('priority')->default('medium'); 
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
