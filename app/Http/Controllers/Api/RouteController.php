<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/routes",
     *     operationId="getRoutes",
     *     tags={"Routes"},
     *     summary="Get routes with filters",
     *     description="Retrieve routes with optional filters and pagination",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"draft", "published", "in_progress", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="departure_city",
     *         in="query",
     *         description="Filter by departure city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arrival_city",
     *         in="query",
     *         description="Filter by arrival city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="departure_date",
     *         in="query",
     *         description="Filter by departure date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="vehicle_type",
     *         in="query",
     *         description="Filter by vehicle type",
     *         @OA\Schema(type="string", enum={"car", "van", "truck", "motorcycle", "airplane", "boat", "other"})
     *     ),
     *     @OA\Parameter(
     *         name="min_capacity",
     *         in="query",
     *         description="Filter by minimum available capacity",
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
     *         description="Routes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="routes", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Route::with(['user', 'waypoints'])
            ->where('status', 'published')
            ->where('available_capacity_kg', '>', 0)
            ->orderBy('departure_date_from', 'asc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('departure_city')) {
            $query->where('departure_city', 'LIKE', '%' . $request->departure_city . '%');
        }

        if ($request->filled('arrival_city')) {
            $query->where('arrival_city', 'LIKE', '%' . $request->arrival_city . '%');
        }

        if ($request->filled('departure_date')) {
            $query->whereDate('departure_date_from', '>=', $request->departure_date);
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->filled('min_capacity')) {
            $query->where('available_capacity_kg', '>=', $request->min_capacity);
        }

        $perPage = $request->get('per_page', 15);
        $routes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Routes retrieved successfully',
            'data' => [
                'routes' => $routes->items(),
                'pagination' => [
                    'current_page' => $routes->currentPage(),
                    'total_pages' => $routes->lastPage(),
                    'total_items' => $routes->total(),
                    'per_page' => $routes->perPage(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/routes",
     *     operationId="createRoute",
     *     tags={"Routes"},
     *     summary="Create new route",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "departure_address", "departure_city", "departure_country", "departure_date_from", "departure_date_to", "arrival_address", "arrival_city", "arrival_country", "arrival_date_from", "arrival_date_to", "total_capacity_kg", "vehicle_type"},
     *             @OA\Property(property="title", type="string", example="Paris-Lyon - Transport Régulier"),
     *             @OA\Property(property="description", type="string", example="Trajet régulier avec camionnette"),
     *             @OA\Property(property="departure_address", type="string", example="Gare du Nord, Paris"),
     *             @OA\Property(property="departure_city", type="string", example="Paris"),
     *             @OA\Property(property="departure_country", type="string", example="France"),
     *             @OA\Property(property="departure_latitude", type="number", format="float", example=48.8808),
     *             @OA\Property(property="departure_longitude", type="number", format="float", example=2.3548),
     *             @OA\Property(property="departure_date_from", type="string", format="datetime"),
     *             @OA\Property(property="departure_date_to", type="string", format="datetime"),
     *             @OA\Property(property="arrival_address", type="string"),
     *             @OA\Property(property="arrival_city", type="string"),
     *             @OA\Property(property="arrival_country", type="string"),
     *             @OA\Property(property="arrival_latitude", type="number", format="float"),
     *             @OA\Property(property="arrival_longitude", type="number", format="float"),
     *             @OA\Property(property="arrival_date_from", type="string", format="datetime"),
     *             @OA\Property(property="arrival_date_to", type="string", format="datetime"),
     *             @OA\Property(property="total_capacity_kg", type="number", format="float", example=50),
     *             @OA\Property(property="vehicle_type", type="string", enum={"car", "van", "truck", "motorcycle", "airplane", "boat", "other"}),
     *             @OA\Property(property="vehicle_description", type="string"),
     *             @OA\Property(property="price_per_kg", type="number", format="float"),
     *             @OA\Property(property="min_shipment_price", type="number", format="float"),
     *             @OA\Property(property="recurring", type="boolean"),
     *             @OA\Property(property="special_conditions", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Route created successfully"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user is transporter or both
        if (!in_array($user->user_type, ['transporter', 'both'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only transporters can create routes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departure_address' => 'required|string|max:255',
            'departure_city' => 'required|string|max:100',
            'departure_country' => 'required|string|max:100',
            'departure_latitude' => 'nullable|numeric|between:-90,90',
            'departure_longitude' => 'nullable|numeric|between:-180,180',
            'departure_date_from' => 'required|date|after:now',
            'departure_date_to' => 'required|date|after:departure_date_from',
            'arrival_address' => 'required|string|max:255',
            'arrival_city' => 'required|string|max:100',
            'arrival_country' => 'required|string|max:100',
            'arrival_latitude' => 'nullable|numeric|between:-90,90',
            'arrival_longitude' => 'nullable|numeric|between:-180,180',
            'arrival_date_from' => 'required|date|after:departure_date_from',
            'arrival_date_to' => 'required|date|after:arrival_date_from',
            'total_capacity_kg' => 'required|numeric|min:1',
            'vehicle_type' => 'required|in:car,van,truck,motorcycle,airplane,boat,other',
            'vehicle_description' => 'nullable|string|max:255',
            'price_per_kg' => 'nullable|numeric|min:0',
            'min_shipment_price' => 'nullable|numeric|min:0',
            'recurring' => 'boolean',
            'recurring_pattern' => 'nullable|json',
            'special_conditions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $route = Route::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'departure_address' => $request->departure_address,
            'departure_city' => $request->departure_city,
            'departure_country' => $request->departure_country,
            'departure_latitude' => $request->departure_latitude,
            'departure_longitude' => $request->departure_longitude,
            'departure_date_from' => $request->departure_date_from,
            'departure_date_to' => $request->departure_date_to,
            'arrival_address' => $request->arrival_address,
            'arrival_city' => $request->arrival_city,
            'arrival_country' => $request->arrival_country,
            'arrival_latitude' => $request->arrival_latitude,
            'arrival_longitude' => $request->arrival_longitude,
            'arrival_date_from' => $request->arrival_date_from,
            'arrival_date_to' => $request->arrival_date_to,
            'total_capacity_kg' => $request->total_capacity_kg,
            'available_capacity_kg' => $request->total_capacity_kg,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_description' => $request->vehicle_description,
            'price_per_kg' => $request->price_per_kg,
            'min_shipment_price' => $request->min_shipment_price,
            'recurring' => $request->get('recurring', false),
            'recurring_pattern' => $request->recurring_pattern,
            'special_conditions' => $request->special_conditions,
            'status' => 'draft',
        ]);

        $route->load(['user', 'waypoints']);

        return response()->json([
            'success' => true,
            'message' => 'Route created successfully',
            'data' => [
                'route' => $route
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/{id}",
     *     operationId="getRoute",
     *     tags={"Routes"},
     *     summary="Get route details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route retrieved successfully"),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function show($id)
    {
        $route = Route::with(['user', 'waypoints' => function($query) {
            $query->orderBy('stop_order');
        }])->find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Route retrieved successfully',
            'data' => [
                'route' => $route
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/routes/{id}",
     *     operationId="updateRoute",
     *     tags={"Routes"},
     *     summary="Update route",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route updated successfully"),
     *     @OA\Response(response=404, description="Route not found"),
     *     @OA\Response(response=403, description="Cannot update published route")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $route = $user->routes()->find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        if ($route->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update route in current status'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'departure_address' => 'string|max:255',
            'departure_city' => 'string|max:100',
            'departure_country' => 'string|max:100',
            'departure_latitude' => 'nullable|numeric|between:-90,90',
            'departure_longitude' => 'nullable|numeric|between:-180,180',
            'departure_date_from' => 'date|after:now',
            'departure_date_to' => 'date|after:departure_date_from',
            'arrival_address' => 'string|max:255',
            'arrival_city' => 'string|max:100',
            'arrival_country' => 'string|max:100',
            'arrival_latitude' => 'nullable|numeric|between:-90,90',
            'arrival_longitude' => 'nullable|numeric|between:-180,180',
            'arrival_date_from' => 'date|after:departure_date_from',
            'arrival_date_to' => 'date|after:arrival_date_from',
            'total_capacity_kg' => 'numeric|min:1',
            'vehicle_type' => 'in:car,van,truck,motorcycle,airplane,boat,other',
            'vehicle_description' => 'nullable|string|max:255',
            'price_per_kg' => 'nullable|numeric|min:0',
            'min_shipment_price' => 'nullable|numeric|min:0',
            'special_conditions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update only provided fields
        $updateData = $request->only([
            'title', 'description', 'departure_address', 'departure_city',
            'departure_country', 'departure_latitude', 'departure_longitude',
            'departure_date_from', 'departure_date_to', 'arrival_address',
            'arrival_city', 'arrival_country', 'arrival_latitude',
            'arrival_longitude', 'arrival_date_from', 'arrival_date_to',
            'total_capacity_kg', 'vehicle_type', 'vehicle_description',
            'price_per_kg', 'min_shipment_price', 'special_conditions'
        ]);

        // Update available capacity if total capacity changed
        if (isset($updateData['total_capacity_kg'])) {
            $capacityDifference = $updateData['total_capacity_kg'] - $route->total_capacity_kg;
            $updateData['available_capacity_kg'] = $route->available_capacity_kg + $capacityDifference;
        }

        $route->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Route updated successfully',
            'data' => [
                'route' => $route->fresh(['user', 'waypoints'])
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/routes/{id}",
     *     operationId="deleteRoute",
     *     tags={"Routes"},
     *     summary="Delete route",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route deleted successfully"),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $route = $user->routes()->find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        // Delete waypoints first
        $route->waypoints()->delete();

        // Delete the route
        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/routes/{id}/publish",
     *     operationId="publishRoute",
     *     tags={"Routes"},
     *     summary="Publish route",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route published successfully"),
     *     @OA\Response(response=400, description="Route cannot be published")
     * )
     */
    public function publish(Request $request, $id)
    {
        $user = $request->user();
        $route = $user->routes()->find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        if ($route->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Route cannot be published in current status'
            ], 400);
        }

        $route->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Route published successfully',
            'data' => [
                'route' => $route->fresh(['user', 'waypoints'])
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/routes/{id}/complete",
     *     operationId="completeRoute",
     *     tags={"Routes"},
     *     summary="Mark route as completed",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route completed successfully")
     * )
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();
        $route = $user->routes()->find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        $route->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Route completed successfully',
            'data' => [
                'route' => $route->fresh(['user', 'waypoints'])
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/search",
     *     operationId="searchRoutes",
     *     tags={"Routes"},
     *     summary="Advanced search routes",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from_city",
     *         in="query",
     *         description="Departure city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="to_city",
     *         in="query",
     *         description="Arrival city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function search(Request $request)
    {
        $query = Route::with(['user', 'waypoints'])
            ->where('status', 'published')
            ->where('available_capacity_kg', '>', 0);

        if ($request->filled('q')) {
            $searchTerm = '%' . $request->q . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm);
            });
        }

        if ($request->filled('from_city')) {
            $query->where('departure_city', 'LIKE', '%' . $request->from_city . '%');
        }

        if ($request->filled('to_city')) {
            $query->where('arrival_city', 'LIKE', '%' . $request->to_city . '%');
        }

        $perPage = $request->get('per_page', 15);
        $routes = $query->orderBy('departure_date_from', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Search completed successfully',
            'data' => [
                'routes' => $routes->items(),
                'pagination' => [
                    'current_page' => $routes->currentPage(),
                    'total_pages' => $routes->lastPage(),
                    'total_items' => $routes->total(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/nearby",
     *     operationId="getNearbyRoutes",
     *     tags={"Routes"},
     *     summary="Get nearby routes",
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="radius",
     *         in="query",
     *         @OA\Schema(type="number", default=50)
     *     ),
     *     @OA\Response(response=200, description="Nearby routes")
     * )
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 50);

        $routes = Route::with(['user', 'waypoints'])
            ->where('status', 'published')
            ->where('available_capacity_kg', '>', 0)
            ->whereNotNull('departure_latitude')
            ->whereNotNull('departure_longitude')
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?))
                * cos(radians(departure_latitude))
                * cos(radians(departure_longitude) - radians(?))
                + sin(radians(?))
                * sin(radians(departure_latitude)))) AS distance_km
            ", [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $radius)
            ->orderBy('distance_km')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Nearby routes retrieved successfully',
            'data' => [
                'routes' => $routes->items(),
                'pagination' => [
                    'current_page' => $routes->currentPage(),
                    'total_pages' => $routes->lastPage(),
                    'total_items' => $routes->total(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/my",
     *     operationId="getMyRoutes",
     *     tags={"Routes"},
     *     summary="Get user's routes",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="User routes retrieved")
     * )
     */
    public function myRoutes(Request $request)
    {
        $user = $request->user();

        $query = $user->routes()->with(['waypoints' => function($query) {
            $query->orderBy('stop_order');
        }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $routes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'User routes retrieved successfully',
            'data' => [
                'routes' => $routes->items(),
                'pagination' => [
                    'current_page' => $routes->currentPage(),
                    'total_pages' => $routes->lastPage(),
                    'total_items' => $routes->total(),
                ]
            ]
        ]);
    }

    /**
     * Start a route
     */
    public function start(Request $request, $id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found',
            ], 404);
        }

        // Check if user is the owner
        if ($route->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action',
            ], 403);
        }

        // Check if route can be started
        if ($route->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Route must be published to start',
            ], 400);
        }

        $route->status = 'in_progress';
        $route->started_at = now();
        $route->save();

        $route->load(['user', 'waypoints']);

        return response()->json([
            'success' => true,
            'message' => 'Route started successfully',
            'data' => $route
        ]);
    }

    /**
     * Cancel a route
     */
    public function cancel(Request $request, $id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found',
            ], 404);
        }

        // Check if user is the owner
        if ($route->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action',
            ], 403);
        }

        // Check if route can be cancelled
        if (!in_array($route->status, ['published', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Route cannot be cancelled in current status',
            ], 400);
        }

        $route->status = 'cancelled';
        $route->cancelled_at = now();

        if ($request->filled('reason')) {
            $route->cancellation_reason = $request->reason;
        }

        $route->save();

        $route->load(['user', 'waypoints']);

        return response()->json([
            'success' => true,
            'message' => 'Route cancelled successfully',
            'data' => $route
        ]);
    }

    /**
     * Get routes statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_routes' => Route::where('user_id', $user->id)->count(),
            'active_routes' => Route::where('user_id', $user->id)->where('status', 'published')->count(),
            'completed_routes' => Route::where('user_id', $user->id)->where('status', 'completed')->count(),
            'cancelled_routes' => Route::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            'draft_routes' => Route::where('user_id', $user->id)->where('status', 'draft')->count(),
            'in_progress_routes' => Route::where('user_id', $user->id)->where('status', 'in_progress')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Route statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}