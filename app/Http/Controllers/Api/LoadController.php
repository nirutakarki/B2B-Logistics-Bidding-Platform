<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoadController extends Controller
{
    public function index()
    {
        return Load::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pickup_location' => 'required',
            'delivery_location' => 'required',
            'business_id' => 'required|exists:businesses,id'
        ]);

        $load = Load::create($validated);

        return response()->json($load, 201);
    }
}

class LoadController extends Controller
{
    public function index()
    {
        return Load::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pickup_location' => 'required',
            'delivery_location' => 'required',
            'business_id' => 'required|exists:businesses,id'
        ]);

        $load = Load::create($validated);

        return response()->json($load, 201);
    }
}

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
}

// app/Models/Business.php

class Business extends Model
{
    protected $fillable = [
        'name',
        'status',
        'category',
        'address'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function loads()
    {
        return $this->hasMany(Load::class);
    }
}

public function up()
{
    Schema::create('loads', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->cascadeOnDelete();
        $table->string('pickup_location');
        $table->string('delivery_location');
        $table->integer('weight')->nullable();
        $table->string('required_vehicle_type')->nullable();
        $table->string('status')->default('draft');
        $table->timestamps();
    });
}

