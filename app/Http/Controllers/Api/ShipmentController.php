<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShipmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/shipments",
     *     operationId="getShipments",
     *     tags={"Shipments"},
     *     summary="Get shipments with filters",
     *     description="Retrieve shipments with optional filters and pagination",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"draft","published","matched","in_transit","delivered","cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filter by pickup or delivery city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter by pickup date from",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="max_weight",
     *         in="query",
     *         description="Filter by maximum weight",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipments", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['user', 'photos', 'primaryPhoto'])
            ->published()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where(function($q) use ($request) {
                $q->where('pickup_city', 'like', '%' . $request->city . '%')
                  ->orWhere('delivery_city', 'like', '%' . $request->city . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->where('pickup_date_from', '>=', $request->date_from);
        }

        if ($request->filled('max_weight')) {
            $query->where('weight', '<=', $request->max_weight);
        }

        // Add distance filter if lat/lng provided
        if ($request->filled(['latitude', 'longitude'])) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->get('radius', 50); // default 50km

            $query->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) * cos(radians(pickup_longitude) - radians(?)) + sin(radians(?)) * sin(radians(pickup_latitude)))) <= ?",
                [$lat, $lng, $lat, $radius]
            );
        }

        $shipments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Shipments retrieved successfully',
            'data' => [
                'shipments' => $shipments->items(),
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'total_pages' => $shipments->lastPage(),
                    'total_items' => $shipments->total(),
                    'per_page' => $shipments->perPage(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/shipments",
     *     operationId="createShipment",
     *     tags={"Shipments"},
     *     summary="Create new shipment",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","description","weight","pickup_address","pickup_city","pickup_country","delivery_address","delivery_city","delivery_country","pickup_date_from","pickup_date_to","delivery_date_limit","budget_min","budget_max"},
     *             @OA\Property(property="title", type="string", example="Colis fragile - Electronique"),
     *             @OA\Property(property="description", type="string", example="Ordinateur portable dans son emballage"),
     *             @OA\Property(property="weight", type="number", format="float", example=2.5),
     *             @OA\Property(property="length", type="number", format="float"),
     *             @OA\Property(property="width", type="number", format="float"),
     *             @OA\Property(property="height", type="number", format="float"),
     *             @OA\Property(property="fragile", type="boolean", example=true),
     *             @OA\Property(property="dangerous_goods", type="boolean", example=false),
     *             @OA\Property(property="pickup_address", type="string", example="123 Rue de la Paix"),
     *             @OA\Property(property="pickup_city", type="string", example="Paris"),
     *             @OA\Property(property="pickup_postal_code", type="string", example="75001"),
     *             @OA\Property(property="pickup_country", type="string", example="France"),
     *             @OA\Property(property="pickup_latitude", type="number", format="float", example=48.8566),
     *             @OA\Property(property="pickup_longitude", type="number", format="float", example=2.3522),
     *             @OA\Property(property="pickup_date_from", type="string", format="datetime"),
     *             @OA\Property(property="pickup_date_to", type="string", format="datetime"),
     *             @OA\Property(property="delivery_address", type="string"),
     *             @OA\Property(property="delivery_city", type="string"),
     *             @OA\Property(property="delivery_postal_code", type="string"),
     *             @OA\Property(property="delivery_country", type="string"),
     *             @OA\Property(property="delivery_latitude", type="number", format="float"),
     *             @OA\Property(property="delivery_longitude", type="number", format="float"),
     *             @OA\Property(property="delivery_date_limit", type="string", format="datetime"),
     *             @OA\Property(property="budget_min", type="number", format="float", example=20.00),
     *             @OA\Property(property="budget_max", type="number", format="float", example=50.00),
     *             @OA\Property(property="currency", type="string", example="EUR"),
     *             @OA\Property(property="priority", type="string", enum={"low","normal","high","urgent"}),
     *             @OA\Property(property="special_instructions", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shipment created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'weight' => 'required|numeric|min:0.1|max:1000',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'fragile' => 'boolean',
            'dangerous_goods' => 'boolean',
            'pickup_address' => 'required|string|max:255',
            'pickup_city' => 'required|string|max:100',
            'pickup_postal_code' => 'nullable|string|max:20',
            'pickup_country' => 'required|string|max:100',
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'pickup_date_from' => 'required|date|after:now',
            'pickup_date_to' => 'required|date|after:pickup_date_from',
            'delivery_address' => 'required|string|max:255',
            'delivery_city' => 'required|string|max:100',
            'delivery_postal_code' => 'nullable|string|max:20',
            'delivery_country' => 'required|string|max:100',
            'delivery_latitude' => 'required|numeric|between:-90,90',
            'delivery_longitude' => 'required|numeric|between:-180,180',
            'delivery_date_limit' => 'required|date|after:pickup_date_from',
            'budget_min' => 'required|numeric|min:0',
            'budget_max' => 'required|numeric|gte:budget_min',
            'currency' => 'string|max:3',
            'priority' => 'in:low,normal,high,urgent',
            'special_instructions' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if user can create shipments (must be sender or both)
        if (!in_array($user->user_type, ['sender', 'both'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only senders can create shipments'
            ], 403);
        }

        $shipment = Shipment::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'weight' => $request->weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'fragile' => $request->get('fragile', false),
            'dangerous_goods' => $request->get('dangerous_goods', false),
            'pickup_address' => $request->pickup_address,
            'pickup_city' => $request->pickup_city,
            'pickup_postal_code' => $request->pickup_postal_code,
            'pickup_country' => $request->pickup_country,
            'pickup_latitude' => $request->pickup_latitude,
            'pickup_longitude' => $request->pickup_longitude,
            'pickup_date_from' => $request->pickup_date_from,
            'pickup_date_to' => $request->pickup_date_to,
            'delivery_address' => $request->delivery_address,
            'delivery_city' => $request->delivery_city,
            'delivery_postal_code' => $request->delivery_postal_code,
            'delivery_country' => $request->delivery_country,
            'delivery_latitude' => $request->delivery_latitude,
            'delivery_longitude' => $request->delivery_longitude,
            'delivery_date_limit' => $request->delivery_date_limit,
            'budget_min' => $request->budget_min,
            'budget_max' => $request->budget_max,
            'currency' => $request->get('currency', 'EUR'),
            'priority' => $request->get('priority', 'normal'),
            'special_instructions' => $request->special_instructions,
            'status' => 'draft'
        ]);

        $shipment->load(['photos', 'primaryPhoto']);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'data' => [
                'shipment' => $shipment
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/shipments/{id}",
     *     operationId="getShipment",
     *     tags={"Shipments"},
     *     summary="Get shipment details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipment retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Shipment not found")
     * )
     */
    public function show($id)
    {
        $shipment = Shipment::with(['user', 'photos', 'primaryPhoto', 'matches.transporter'])
            ->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment retrieved successfully',
            'data' => [
                'shipment' => $shipment
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/shipments/{id}",
     *     operationId="updateShipment",
     *     tags={"Shipments"},
     *     summary="Update shipment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Shipment updated successfully"),
     *     @OA\Response(response=404, description="Shipment not found"),
     *     @OA\Response(response=403, description="Cannot update published shipment")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        // Cannot update published or matched shipments
        if (in_array($shipment->status, ['published', 'matched', 'in_transit', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update shipment in current status'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'string|max:2000',
            'weight' => 'numeric|min:0.1|max:1000',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'fragile' => 'boolean',
            'dangerous_goods' => 'boolean',
            'pickup_address' => 'string|max:255',
            'pickup_city' => 'string|max:100',
            'pickup_postal_code' => 'nullable|string|max:20',
            'pickup_country' => 'string|max:100',
            'pickup_latitude' => 'numeric|between:-90,90',
            'pickup_longitude' => 'numeric|between:-180,180',
            'pickup_date_from' => 'date|after:now',
            'pickup_date_to' => 'date|after:pickup_date_from',
            'delivery_address' => 'string|max:255',
            'delivery_city' => 'string|max:100',
            'delivery_postal_code' => 'nullable|string|max:20',
            'delivery_country' => 'string|max:100',
            'delivery_latitude' => 'numeric|between:-90,90',
            'delivery_longitude' => 'numeric|between:-180,180',
            'delivery_date_limit' => 'date|after:pickup_date_from',
            'budget_min' => 'numeric|min:0',
            'budget_max' => 'numeric|gte:budget_min',
            'currency' => 'string|max:3',
            'priority' => 'in:low,normal,high,urgent',
            'special_instructions' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $shipment->update($request->all());
        $shipment->load(['photos', 'primaryPhoto']);

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully',
            'data' => [
                'shipment' => $shipment
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/shipments/{id}",
     *     operationId="deleteShipment",
     *     tags={"Shipments"},
     *     summary="Delete shipment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Shipment deleted successfully"),
     *     @OA\Response(response=404, description="Shipment not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        // Cannot delete if already matched or in transit
        if (in_array($shipment->status, ['matched', 'in_transit', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shipment in current status'
            ], 403);
        }

        // Delete all photos
        foreach ($shipment->photos as $photo) {
            \Storage::disk('public')->delete($photo->file_path);
        }

        $shipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/shipments/{id}/publish",
     *     operationId="publishShipment",
     *     tags={"Shipments"},
     *     summary="Publish shipment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Shipment published successfully"),
     *     @OA\Response(response=400, description="Shipment cannot be published")
     * )
     */
    public function publish(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        if ($shipment->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft shipments can be published'
            ], 400);
        }

        $shipment->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment published successfully',
            'data' => [
                'shipment' => $shipment
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/shipments/{id}/cancel",
     *     operationId="cancelShipment",
     *     tags={"Shipments"},
     *     summary="Cancel shipment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Shipment cancelled successfully")
     * )
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $shipment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Shipment cancelled successfully',
            'data' => [
                'shipment' => $shipment
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/shipments/search",
     *     operationId="searchShipments",
     *     tags={"Shipments"},
     *     summary="Advanced search shipments",
     *     @OA\Parameter(name="q", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from_city", in="query", description="Pickup city", @OA\Schema(type="string")),
     *     @OA\Parameter(name="to_city", in="query", description="Delivery city", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function search(Request $request)
    {
        $query = Shipment::with(['user', 'photos', 'primaryPhoto'])
            ->published();

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->filled('from_city')) {
            $query->where('pickup_city', 'like', '%' . $request->from_city . '%');
        }

        if ($request->filled('to_city')) {
            $query->where('delivery_city', 'like', '%' . $request->to_city . '%');
        }

        $shipments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Search completed successfully',
            'data' => [
                'shipments' => $shipments->items(),
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'total_pages' => $shipments->lastPage(),
                    'total_items' => $shipments->total(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/shipments/nearby",
     *     operationId="getNearbyShipments",
     *     tags={"Shipments"},
     *     summary="Get nearby shipments",
     *     @OA\Parameter(name="latitude", in="query", required=true, @OA\Schema(type="number")),
     *     @OA\Parameter(name="longitude", in="query", required=true, @OA\Schema(type="number")),
     *     @OA\Parameter(name="radius", in="query", @OA\Schema(type="number", default=50)),
     *     @OA\Response(response=200, description="Nearby shipments")
     * )
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'numeric|min:1|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = $request->get('radius', 50);

        $shipments = Shipment::with(['user', 'photos', 'primaryPhoto'])
            ->published()
            ->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) * cos(radians(pickup_longitude) - radians(?)) + sin(radians(?)) * sin(radians(pickup_latitude)))) <= ?",
                [$lat, $lng, $lat, $radius]
            )
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Nearby shipments retrieved successfully',
            'data' => [
                'shipments' => $shipments->items(),
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'total_pages' => $shipments->lastPage(),
                    'total_items' => $shipments->total(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/shipments/my",
     *     operationId="getMyShipments",
     *     tags={"Shipments"},
     *     summary="Get user's shipments",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="User shipments retrieved")
     * )
     */
    public function myShipments(Request $request)
    {
        $user = $request->user();
        $shipments = $user->shipments()
            ->with(['photos', 'primaryPhoto', 'matches'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'User shipments retrieved successfully',
            'data' => [
                'shipments' => $shipments->items(),
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'total_pages' => $shipments->lastPage(),
                    'total_items' => $shipments->total(),
                ]
            ]
        ]);
    }
}
