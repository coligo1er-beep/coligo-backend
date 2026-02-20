<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Shipment;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/matches/suggestions",
     *     operationId="getMatchSuggestions",
     *     tags={"Matches"},
     *     summary="Get automatic match suggestions",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type of suggestions: for_shipments, for_routes",
     *         @OA\Schema(type="string", enum={"for_shipments", "for_routes"})
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of suggestions to return",
     *         @OA\Schema(type="integer", default=10, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="suggestions", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type', 'for_shipments');
        $limit = min($request->get('limit', 10), 50);

        $suggestions = collect();

        if ($type === 'for_shipments' && in_array($user->user_type, ['sender', 'both'])) {
            // Find route suggestions for user's published shipments
            $userShipments = $user->shipments()
                ->where('status', 'published')
                ->get();

            foreach ($userShipments as $shipment) {
                $routeSuggestions = $this->findMatchingRoutes($shipment);
                foreach ($routeSuggestions->take($limit) as $route) {
                    $suggestions->push([
                        'shipment' => $shipment,
                        'route' => $route,
                        'matching_score' => $this->calculateMatchingScore($shipment, $route),
                        'suggested_price' => $this->calculateSuggestedPrice($shipment, $route),
                        'distance_km' => $this->calculateDistance(
                            $shipment->pickup_latitude,
                            $shipment->pickup_longitude,
                            $route->departure_latitude ?? 0,
                            $route->departure_longitude ?? 0
                        )
                    ]);
                }
            }
        } elseif ($type === 'for_routes' && in_array($user->user_type, ['transporter', 'both'])) {
            // Find shipment suggestions for user's published routes
            $userRoutes = $user->routes()
                ->where('status', 'published')
                ->get();

            foreach ($userRoutes as $route) {
                $shipmentSuggestions = $this->findMatchingShipments($route);
                foreach ($shipmentSuggestions->take($limit) as $shipment) {
                    $suggestions->push([
                        'route' => $route,
                        'shipment' => $shipment,
                        'matching_score' => $this->calculateMatchingScore($shipment, $route),
                        'suggested_price' => $this->calculateSuggestedPrice($shipment, $route),
                        'distance_km' => $this->calculateDistance(
                            $route->departure_latitude ?? 0,
                            $route->departure_longitude ?? 0,
                            $shipment->pickup_latitude,
                            $shipment->pickup_longitude
                        )
                    ]);
                }
            }
        }

        // Sort by matching score and limit results
        $suggestions = $suggestions->sortByDesc('matching_score')->take($limit);

        return response()->json([
            'success' => true,
            'message' => 'Match suggestions retrieved successfully',
            'data' => [
                'suggestions' => $suggestions->values(),
                'type' => $type,
                'total_suggestions' => $suggestions->count()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/matches",
     *     operationId="createMatch",
     *     tags={"Matches"},
     *     summary="Create manual match proposal",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipment_id", "route_id", "proposed_price"},
     *             @OA\Property(property="shipment_id", type="integer", example=1),
     *             @OA\Property(property="route_id", type="integer", example=1),
     *             @OA\Property(property="proposed_price", type="number", format="float", example=75.50),
     *             @OA\Property(property="pickup_datetime", type="string", format="datetime"),
     *             @OA\Property(property="delivery_datetime", type="string", format="datetime"),
     *             @OA\Property(property="transporter_message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Match proposal created successfully"),
     *     @OA\Response(response=404, description="Shipment or route not found"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'required|exists:routes,id',
            'proposed_price' => 'required|numeric|min:0.01',
            'pickup_datetime' => 'nullable|date|after:now',
            'delivery_datetime' => 'nullable|date|after:pickup_datetime',
            'transporter_message' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify shipment exists and is published
        $shipment = Shipment::find($request->shipment_id);
        if (!$shipment || $shipment->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found or not available for matching'
            ], 404);
        }

        // Verify route exists and is published and belongs to current user
        $route = $user->routes()->find($request->route_id);
        if (!$route || $route->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Route not found or not available for matching'
            ], 404);
        }

        // Check if match already exists
        $existingMatch = MatchModel::where('shipment_id', $request->shipment_id)
            ->where('route_id', $request->route_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingMatch) {
            return response()->json([
                'success' => false,
                'message' => 'A match proposal already exists for this shipment and route'
            ], 400);
        }

        // Calculate matching score and distance
        $matchingScore = $this->calculateMatchingScore($shipment, $route);
        $distanceKm = $this->calculateDistance(
            $shipment->pickup_latitude,
            $shipment->pickup_longitude,
            $route->departure_latitude ?? 0,
            $route->departure_longitude ?? 0
        );

        $match = MatchModel::create([
            'shipment_id' => $request->shipment_id,
            'route_id' => $request->route_id,
            'transporter_id' => $user->id,
            'sender_id' => $shipment->user_id,
            'status' => 'pending',
            'proposed_price' => $request->proposed_price,
            'pickup_datetime' => $request->pickup_datetime,
            'delivery_datetime' => $request->delivery_datetime,
            'matching_score' => $matchingScore,
            'distance_km' => $distanceKm,
            'estimated_duration_hours' => $distanceKm / 80, // Approximation: 80 km/h average
            'transporter_message' => $request->transporter_message
        ]);

        $match->load(['shipment.user', 'route.user', 'transporter', 'sender']);

        return response()->json([
            'success' => true,
            'message' => 'Match proposal created successfully',
            'data' => [
                'match' => $match
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/{id}",
     *     operationId="showMatch",
     *     tags={"Matches"},
     *     summary="Get match details",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Match retrieved successfully"),
     *     @OA\Response(response=404, description="Match not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $match = MatchModel::with(['shipment.user', 'route.user', 'transporter', 'sender'])
            ->where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Match retrieved successfully',
            'data' => [
                'match' => $match
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}/accept",
     *     operationId="acceptMatch",
     *     tags={"Matches"},
     *     summary="Accept match proposal",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="final_price", type="number", format="float"),
     *             @OA\Property(property="sender_response", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Match accepted successfully")
     * )
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();

        $match = MatchModel::where('sender_id', $user->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or not available for acceptance'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'final_price' => 'nullable|numeric|min:0.01',
            'sender_response' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::transaction(function() use ($match, $request) {
            $match->update([
                'status' => 'accepted',
                'final_price' => $request->get('final_price', $match->proposed_price),
                'sender_response' => $request->sender_response,
                'accepted_at' => now()
            ]);

            // Update shipment status
            $match->shipment->update(['status' => 'matched']);

            // Reject other pending matches for this shipment
            MatchModel::where('shipment_id', $match->shipment_id)
                ->where('id', '!=', $match->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejected_at' => now()
                ]);
        });

        $match->load(['shipment.user', 'route.user', 'transporter', 'sender']);

        return response()->json([
            'success' => true,
            'message' => 'Match accepted successfully',
            'data' => [
                'match' => $match
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}/reject",
     *     operationId="rejectMatch",
     *     tags={"Matches"},
     *     summary="Reject match proposal",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="sender_response", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Match rejected successfully")
     * )
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();

        $match = MatchModel::where('sender_id', $user->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or not available for rejection'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sender_response' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $match->update([
            'status' => 'rejected',
            'sender_response' => $request->sender_response,
            'rejected_at' => now()
        ]);

        $match->load(['shipment.user', 'route.user', 'transporter', 'sender']);

        return response()->json([
            'success' => true,
            'message' => 'Match rejected successfully',
            'data' => [
                'match' => $match
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}/complete",
     *     operationId="completeMatch",
     *     tags={"Matches"},
     *     summary="Mark match as completed",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Match completed successfully")
     * )
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();

        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->where('status', 'accepted')
            ->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or not available for completion'
            ], 404);
        }

        DB::transaction(function() use ($match) {
            $match->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Update shipment status
            $match->shipment->update(['status' => 'delivered']);
        });

        $match->load(['shipment.user', 'route.user', 'transporter', 'sender']);

        return response()->json([
            'success' => true,
            'message' => 'Match completed successfully',
            'data' => [
                'match' => $match
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/my",
     *     operationId="getMyMatches",
     *     tags={"Matches"},
     *     summary="Get user's matches",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"pending", "accepted", "rejected", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role in match",
     *         @OA\Schema(type="string", enum={"sender", "transporter"})
     *     ),
     *     @OA\Response(response=200, description="User matches retrieved successfully")
     * )
     */
    public function myMatches(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status');
        $role = $request->get('role');
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 15), 100);

        $query = MatchModel::with(['shipment.user', 'route.user', 'transporter', 'sender'])
            ->where(function($q) use ($user, $role) {
                if ($role === 'sender') {
                    $q->where('sender_id', $user->id);
                } elseif ($role === 'transporter') {
                    $q->where('transporter_id', $user->id);
                } else {
                    $q->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
                }
            });

        if ($status) {
            $query->where('status', $status);
        }

        $matches = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'User matches retrieved successfully',
            'data' => [
                'matches' => $matches->items(),
                'pagination' => [
                    'current_page' => $matches->currentPage(),
                    'total_pages' => $matches->lastPage(),
                    'total_items' => $matches->total(),
                    'per_page' => $matches->perPage()
                ]
            ]
        ]);
    }

    /**
     * Calculate matching score between shipment and route
     */
    private function calculateMatchingScore($shipment, $route)
    {
        $score = 0;

        // Distance score (closer is better) - 40% weight
        $pickupDistance = $this->calculateDistance(
            $shipment->pickup_latitude,
            $shipment->pickup_longitude,
            $route->departure_latitude ?? 0,
            $route->departure_longitude ?? 0
        );
        $deliveryDistance = $this->calculateDistance(
            $shipment->delivery_latitude,
            $shipment->delivery_longitude,
            $route->arrival_latitude ?? 0,
            $route->arrival_longitude ?? 0
        );
        $avgDistance = ($pickupDistance + $deliveryDistance) / 2;
        $distanceScore = max(0, 100 - ($avgDistance / 10)); // Penalty increases with distance
        $score += $distanceScore * 0.4;

        // Date compatibility - 30% weight
        $shipmentPickupFrom = strtotime($shipment->pickup_date_from);
        $shipmentPickupTo = strtotime($shipment->pickup_date_to);
        $routeDeparture = strtotime($route->departure_date_from ?? '');

        if ($routeDeparture >= $shipmentPickupFrom && $routeDeparture <= $shipmentPickupTo) {
            $score += 30; // Perfect date match
        } elseif (abs($routeDeparture - $shipmentPickupFrom) <= 86400) { // Within 24h
            $score += 15; // Acceptable date match
        }

        // Capacity compatibility - 20% weight
        if (isset($route->available_capacity_kg) && $route->available_capacity_kg >= $shipment->weight) {
            $capacityUtilization = $shipment->weight / $route->available_capacity_kg;
            $score += 20 * $capacityUtilization; // Better utilization = higher score
        }

        // Price compatibility - 10% weight
        if (isset($route->price_per_kg) && $shipment->budget_max) {
            $estimatedPrice = $route->price_per_kg * $shipment->weight;
            if ($estimatedPrice <= $shipment->budget_max) {
                $score += 10;
            }
        }

        return round($score, 2);
    }

    /**
     * Calculate suggested price based on shipment budget and route pricing
     */
    private function calculateSuggestedPrice($shipment, $route)
    {
        $budgetAvg = ($shipment->budget_min + $shipment->budget_max) / 2;

        if (isset($route->price_per_kg)) {
            $routePrice = $route->price_per_kg * $shipment->weight;
            return min($budgetAvg, $routePrice);
        }

        return $budgetAvg;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * asin(sqrt($a));

        return round($earth_radius * $c, 2);
    }

    /**
     * Find matching routes for a shipment
     */
    private function findMatchingRoutes($shipment)
    {
        return Route::where('status', 'published')
            ->where('user_id', '!=', $shipment->user_id)
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(departure_latitude)) * cos(radians(departure_longitude) - radians(?)) + sin(radians(?)) * sin(radians(departure_latitude)))) <= 50', [
                $shipment->pickup_latitude,
                $shipment->pickup_longitude,
                $shipment->pickup_latitude
            ])
            ->get();
    }

    /**
     * Find matching shipments for a route
     */
    private function findMatchingShipments($route)
    {
        return Shipment::where('status', 'published')
            ->where('user_id', '!=', $route->user_id)
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) * cos(radians(pickup_longitude) - radians(?)) + sin(radians(?)) * sin(radians(pickup_latitude)))) <= 50', [
                $route->departure_latitude ?? 0,
                $route->departure_longitude ?? 0,
                $route->departure_latitude ?? 0
            ])
            ->get();
    }
}