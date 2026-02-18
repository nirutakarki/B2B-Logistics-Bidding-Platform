<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Shared\Enums\LoadStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loads', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('pickup_location');
            $table->string('delivery_location');
            $table->date('pickup_date');
            $table->date('delivery_deadline');
            $table->decimal('weight', 10, 2);
            $table->text('cargo_description');
            $table->text('special_requirements')->nullable();
            $table->decimal('budget_amount', 10, 2)->nullable();
            $table->string('status')->default(LoadStatus::Draft->value);
            $table->foreignId('assigned_driver_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loads');
    }
};
