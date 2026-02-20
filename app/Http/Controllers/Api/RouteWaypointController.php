<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteWaypoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteWaypointController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/routes/{id}/waypoints",
     *     operationId="getRouteWaypoints",
     *     tags={"Routes"},
     *     summary="Get route waypoints",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Waypoints retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="waypoints", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index($routeId)
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        $waypoints = $route->waypoints()->orderBy('stop_order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Waypoints retrieved successfully',
            'data' => [
                'waypoints' => $waypoints
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/routes/{id}/waypoints",
     *     operationId="createRouteWaypoint",
     *     tags={"Routes"},
     *     summary="Add waypoint to route",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address", "city", "country", "stop_order"},
     *             @OA\Property(property="address", type="string", example="Gare de Lyon, Lyon"),
     *             @OA\Property(property="city", type="string", example="Lyon"),
     *             @OA\Property(property="country", type="string", example="France"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="stop_order", type="integer", example=1),
     *             @OA\Property(property="estimated_arrival", type="string", format="datetime"),
     *             @OA\Property(property="is_flexible", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Waypoint created successfully"),
     *     @OA\Response(response=404, description="Route not found"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request, $routeId)
    {
        $user = $request->user();
        $route = $user->routes()->find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        if ($route->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify waypoints of published route'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'stop_order' => 'required|integer|min:1',
            'estimated_arrival' => 'nullable|date',
            'is_flexible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if stop_order already exists and adjust if necessary
        $existingWaypoint = $route->waypoints()->where('stop_order', $request->stop_order)->first();
        if ($existingWaypoint) {
            // Shift existing waypoints to make room
            $route->waypoints()
                ->where('stop_order', '>=', $request->stop_order)
                ->increment('stop_order');
        }

        $waypoint = RouteWaypoint::create([
            'route_id' => $route->id,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'stop_order' => $request->stop_order,
            'estimated_arrival' => $request->estimated_arrival,
            'is_flexible' => $request->get('is_flexible', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Waypoint created successfully',
            'data' => [
                'waypoint' => $waypoint
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/routes/{id}/waypoints/{waypointId}",
     *     operationId="updateRouteWaypoint",
     *     tags={"Routes"},
     *     summary="Update waypoint",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="waypointId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Waypoint updated successfully"),
     *     @OA\Response(response=404, description="Waypoint not found")
     * )
     */
    public function update(Request $request, $routeId, $waypointId)
    {
        $user = $request->user();
        $route = $user->routes()->find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        if ($route->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify waypoints of published route'
            ], 403);
        }

        $waypoint = $route->waypoints()->find($waypointId);

        if (!$waypoint) {
            return response()->json([
                'success' => false,
                'message' => 'Waypoint not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'string|max:255',
            'city' => 'string|max:100',
            'country' => 'string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'stop_order' => 'integer|min:1',
            'estimated_arrival' => 'nullable|date',
            'is_flexible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'address', 'city', 'country', 'latitude', 'longitude',
            'estimated_arrival', 'is_flexible'
        ]);

        // Handle stop_order changes
        if ($request->has('stop_order') && $request->stop_order !== $waypoint->stop_order) {
            $newOrder = $request->stop_order;
            $oldOrder = $waypoint->stop_order;

            if ($newOrder > $oldOrder) {
                // Moving down - shift waypoints up
                $route->waypoints()
                    ->where('stop_order', '>', $oldOrder)
                    ->where('stop_order', '<=', $newOrder)
                    ->decrement('stop_order');
            } else {
                // Moving up - shift waypoints down
                $route->waypoints()
                    ->where('stop_order', '<', $oldOrder)
                    ->where('stop_order', '>=', $newOrder)
                    ->increment('stop_order');
            }

            $updateData['stop_order'] = $newOrder;
        }

        $waypoint->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Waypoint updated successfully',
            'data' => [
                'waypoint' => $waypoint->fresh()
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/routes/{id}/waypoints/{waypointId}",
     *     operationId="deleteRouteWaypoint",
     *     tags={"Routes"},
     *     summary="Delete waypoint",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="waypointId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Waypoint deleted successfully"),
     *     @OA\Response(response=404, description="Waypoint not found")
     * )
     */
    public function destroy(Request $request, $routeId, $waypointId)
    {
        $user = $request->user();
        $route = $user->routes()->find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        if ($route->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify waypoints of published route'
            ], 403);
        }

        $waypoint = $route->waypoints()->find($waypointId);

        if (!$waypoint) {
            return response()->json([
                'success' => false,
                'message' => 'Waypoint not found'
            ], 404);
        }

        $deletedOrder = $waypoint->stop_order;

        // Delete the waypoint
        $waypoint->delete();

        // Adjust stop_order for remaining waypoints
        $route->waypoints()
            ->where('stop_order', '>', $deletedOrder)
            ->decrement('stop_order');

        return response()->json([
            'success' => true,
            'message' => 'Waypoint deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/{id}/requests",
     *     operationId="getRouteRequests",
     *     tags={"Routes"},
     *     summary="Get shipment requests for route",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Route requests retrieved")
     * )
     */
    public function getRequests(Request $request, $routeId)
    {
        $user = $request->user();
        $route = $user->routes()->find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        // Get potential shipments that could match this route
        // This is a simplified matching - in practice, you'd implement more sophisticated algorithms
        $potentialShipments = collect();

        // You can implement sophisticated matching logic here
        // For now, we'll return an empty collection as a placeholder

        return response()->json([
            'success' => true,
            'message' => 'Route requests retrieved successfully',
            'data' => [
                'requests' => $potentialShipments,
                'route' => $route->load('waypoints')
            ]
        ]);
    }
}