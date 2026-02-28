<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rated_by_business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('rated_business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('load_id')->constrained('loads')->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); 
            $table->text('review_text')->nullable();
            $table->timestamps();
            
            $table->unique(['rated_by_business_id', 'rated_business_id', 'load_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
