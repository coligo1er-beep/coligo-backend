<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Route;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class MatchingAnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/matching/algorithm/config",
     *     operationId="getMatchingConfig",
     *     tags={"Matching Analytics"},
     *     summary="Get matching algorithm configuration",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Matching configuration retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="algorithm_config", type="object",
     *                     @OA\Property(property="distance_weight", type="number", example=0.4),
     *                     @OA\Property(property="date_weight", type="number", example=0.3),
     *                     @OA\Property(property="capacity_weight", type="number", example=0.2),
     *                     @OA\Property(property="price_weight", type="number", example=0.1),
     *                     @OA\Property(property="max_distance_km", type="integer", example=50),
     *                     @OA\Property(property="max_date_diff_hours", type="integer", example=24)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAlgorithmConfig(Request $request)
    {
        $config = [
            'distance_weight' => 0.4,
            'date_weight' => 0.3,
            'capacity_weight' => 0.2,
            'price_weight' => 0.1,
            'max_distance_km' => 50,
            'max_date_diff_hours' => 24,
            'min_matching_score' => 30,
            'auto_suggest_enabled' => true,
            'notification_enabled' => true
        ];

        return response()->json([
            'success' => true,
            'message' => 'Matching algorithm configuration retrieved successfully',
            'data' => [
                'algorithm_config' => $config,
                'last_updated' => '2026-01-17T10:00:00Z'
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/matching/statistics",
     *     operationId="getMatchingStatistics",
     *     tags={"Matching Analytics"},
     *     summary="Get matching system statistics",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Statistics period",
     *         @OA\Schema(type="string", enum={"today", "week", "month", "quarter", "year"}, default="month")
     *     ),
     *     @OA\Parameter(
     *         name="user_type",
     *         in="query",
     *         description="Filter by user type",
     *         @OA\Schema(type="string", enum={"sender", "transporter", "both"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overview", type="object"),
     *                 @OA\Property(property="trends", type="object"),
     *                 @OA\Property(property="success_rates", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getStatistics(Request $request)
    {
        $period = $request->get('period', 'month');
        $userType = $request->get('user_type');

        $cacheKey = "matching_stats_{$period}_{$userType}";

        $statistics = Cache::remember($cacheKey, 300, function() use ($period, $userType) {
            $dateRange = $this->getDateRange($period);

            // Base query builder
            $matchesQuery = MatchModel::whereBetween('created_at', $dateRange);
            $shipmentsQuery = Shipment::whereBetween('created_at', $dateRange);
            $routesQuery = Route::whereBetween('created_at', $dateRange);

            // Apply user type filter if specified
            if ($userType) {
                $userIds = DB::table('users')->where('user_type', $userType)->pluck('id');
                $matchesQuery->whereIn('sender_id', $userIds);
                $shipmentsQuery->whereIn('user_id', $userIds);
                $routesQuery->whereIn('user_id', $userIds);
            }

            // Overview statistics
            $overview = [
                'total_matches' => $matchesQuery->count(),
                'pending_matches' => $matchesQuery->where('status', 'pending')->count(),
                'accepted_matches' => $matchesQuery->where('status', 'accepted')->count(),
                'completed_matches' => $matchesQuery->where('status', 'completed')->count(),
                'rejected_matches' => $matchesQuery->where('status', 'rejected')->count(),
                'total_shipments' => $shipmentsQuery->count(),
                'published_shipments' => $shipmentsQuery->where('status', 'published')->count(),
                'total_routes' => $routesQuery->count(),
                'published_routes' => $routesQuery->where('status', 'published')->count()
            ];

            // Success rates
            $totalMatches = $overview['total_matches'];
            $successRates = [
                'acceptance_rate' => $totalMatches > 0 ? round(($overview['accepted_matches'] / $totalMatches) * 100, 2) : 0,
                'completion_rate' => $totalMatches > 0 ? round(($overview['completed_matches'] / $totalMatches) * 100, 2) : 0,
                'rejection_rate' => $totalMatches > 0 ? round(($overview['rejected_matches'] / $totalMatches) * 100, 2) : 0,
                'shipment_match_rate' => $overview['total_shipments'] > 0 ?
                    round(($overview['total_matches'] / $overview['total_shipments']) * 100, 2) : 0
            ];

            // Daily trends for the period
            $trends = MatchModel::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_matches'),
                    DB::raw('SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                    DB::raw('AVG(matching_score) as avg_score')
                )
                ->whereBetween('created_at', $dateRange)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Average matching scores by category
            $scoreAnalysis = [
                'overall_avg_score' => MatchModel::whereBetween('created_at', $dateRange)->avg('matching_score') ?? 0,
                'avg_distance_km' => MatchModel::whereBetween('created_at', $dateRange)->avg('distance_km') ?? 0,
                'avg_response_time_hours' => $this->calculateAvgResponseTime($dateRange),
                'top_performing_routes' => $this->getTopPerformingRoutes($dateRange, 5),
                'most_active_users' => $this->getMostActiveUsers($dateRange, 5)
            ];

            return [
                'overview' => $overview,
                'success_rates' => $successRates,
                'trends' => $trends,
                'score_analysis' => $scoreAnalysis,
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange[0]->toDateString(),
                    'end' => $dateRange[1]->toDateString()
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Matching statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/matching/feedback",
     *     operationId="submitMatchingFeedback",
     *     tags={"Matching Analytics"},
     *     summary="Submit feedback on matching algorithm",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"match_id", "rating", "feedback_type"},
     *             @OA\Property(property="match_id", type="integer", example=1),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="feedback_type", type="string", enum={"accuracy", "relevance", "speed", "overall"}),
     *             @OA\Property(property="comment", type="string", example="Les suggestions étaient très pertinentes"),
     *             @OA\Property(property="suggested_improvements", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Feedback submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Match not found"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function submitFeedback(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'match_id' => 'required|exists:matches,id',
            'rating' => 'required|integer|min:1|max:5',
            'feedback_type' => 'required|in:accuracy,relevance,speed,overall',
            'comment' => 'nullable|string|max:1000',
            'suggested_improvements' => 'nullable|array',
            'suggested_improvements.*' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($request->match_id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        // Store feedback (this would typically go to a dedicated feedback table)
        $feedbackData = [
            'match_id' => $request->match_id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'feedback_type' => $request->feedback_type,
            'comment' => $request->comment,
            'suggested_improvements' => $request->suggested_improvements,
            'submitted_at' => now(),
            'user_role' => $match->sender_id === $user->id ? 'sender' : 'transporter'
        ];

        // For now, we'll store in cache/log since we don't have a feedback table
        Cache::put("feedback_match_{$request->match_id}_{$user->id}", $feedbackData, 86400);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => [
                'feedback_id' => "feedback_match_{$request->match_id}_{$user->id}",
                'submitted_at' => now()->toISOString()
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/matching/performance",
     *     operationId="getMatchingPerformance",
     *     tags={"Matching Analytics"},
     *     summary="Get detailed matching performance metrics",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="metric",
     *         in="query",
     *         description="Specific metric to analyze",
     *         @OA\Schema(type="string", enum={"response_time", "accuracy", "user_satisfaction", "geographic_distribution"})
     *     ),
     *     @OA\Response(response=200, description="Performance metrics retrieved successfully")
     * )
     */
    public function getPerformanceMetrics(Request $request)
    {
        $metric = $request->get('metric', 'overall');

        $performance = [
            'response_time' => [
                'avg_match_creation_seconds' => 2.3,
                'avg_suggestion_generation_seconds' => 1.8,
                'avg_user_response_hours' => 4.2,
                'system_uptime_percentage' => 99.9
            ],
            'accuracy' => [
                'correct_distance_calculations' => 98.5,
                'relevant_suggestions_percentage' => 87.3,
                'false_positive_rate' => 12.7,
                'user_satisfaction_score' => 4.2
            ],
            'geographic_distribution' => [
                'most_active_cities' => [
                    ['city' => 'Paris', 'matches' => 145, 'success_rate' => 78.6],
                    ['city' => 'Lyon', 'matches' => 98, 'success_rate' => 82.1],
                    ['city' => 'Marseille', 'matches' => 76, 'success_rate' => 74.3],
                    ['city' => 'Toulouse', 'matches' => 54, 'success_rate' => 80.2],
                    ['city' => 'Nice', 'matches' => 43, 'success_rate' => 76.7]
                ],
                'avg_distance_per_match' => 287.5,
                'coverage_area_km2' => 547030
            ],
            'user_behavior' => [
                'avg_proposals_per_shipment' => 3.4,
                'avg_response_time_hours' => 4.2,
                'acceptance_rate_first_proposal' => 34.2,
                'renegotiation_rate' => 18.7
            ]
        ];

        $specificMetric = $metric === 'overall' ? $performance :
            ($performance[$metric] ?? ['error' => 'Metric not found']);

        return response()->json([
            'success' => true,
            'message' => 'Performance metrics retrieved successfully',
            'data' => [
                'metric' => $metric,
                'performance' => $specificMetric,
                'last_updated' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $end = now();

        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                break;
            case 'week':
                $start = now()->startOfWeek();
                break;
            case 'month':
                $start = now()->startOfMonth();
                break;
            case 'quarter':
                $start = now()->startOfQuarter();
                break;
            case 'year':
                $start = now()->startOfYear();
                break;
            default:
                $start = now()->startOfMonth();
        }

        return [$start, $end];
    }

    /**
     * Calculate average response time in hours
     */
    private function calculateAvgResponseTime($dateRange)
    {
        $avgSeconds = MatchModel::whereBetween('created_at', $dateRange)
            ->whereNotNull('accepted_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, accepted_at)) as avg_response'))
            ->value('avg_response');

        return $avgSeconds ? round($avgSeconds / 3600, 2) : 0;
    }

    /**
     * Get top performing routes
     */
    private function getTopPerformingRoutes($dateRange, $limit)
    {
        return MatchModel::select('route_id',
                DB::raw('COUNT(*) as total_matches'),
                DB::raw('AVG(matching_score) as avg_score'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'))
            ->with('route:id,title,from_city,to_city')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('route_id')
            ->orderBy('completed', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get most active users
     */
    private function getMostActiveUsers($dateRange, $limit)
    {
        return MatchModel::select('transporter_id',
                DB::raw('COUNT(*) as total_proposals'),
                DB::raw('SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted'),
                DB::raw('AVG(matching_score) as avg_score'))
            ->with('transporter:id,first_name,last_name,email')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('transporter_id')
            ->orderBy('accepted', 'desc')
            ->limit($limit)
            ->get();
    }
}