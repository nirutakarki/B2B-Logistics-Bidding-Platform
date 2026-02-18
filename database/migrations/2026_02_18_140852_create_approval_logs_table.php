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
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            
            $table->string('approvable_type'); // polymorphic relationship
            $table->unsignedBigInteger('approvable_id');
            $table->string('old_status');
            $table->string('new_status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            
            $table->index(['approvable_type', 'approvable_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};
