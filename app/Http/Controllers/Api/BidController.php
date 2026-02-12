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


// app/Http/Controllers/Api/BusinessController.php

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        $business = Business::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => 'pending' // important
        ]);

        return response()->json([
            'message' => 'Business registration submitted. Awaiting approval.',
            'business' => $business
        ], 201);
    }
}

public function approve($id)
{
    $business = Business::findOrFail($id);

    $business->update([
        'status' => 'approved'
    ]);

    return response()->json([
        'message' => 'Business approved successfully.',
        'business' => $business
    ]);
}

use App\Models\Driver;

class DriverController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'business_name' => 'nullable|string'
        ]);

        $driver = Driver::create([
            'user_id' => $validated['user_id'],
            'business_name' => $validated['business_name'] ?? null,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Driver registration submitted.',
            'driver' => $driver
        ], 201);
    }

    public function approve($id)
    {
        $driver = Driver::findOrFail($id);

        $driver->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Driver approved successfully.',
            'driver' => $driver
        ]);
    }
}
