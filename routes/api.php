<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\ShipmentPhotoController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\RouteWaypointController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MatchMessageController;
use App\Http\Controllers\Api\MatchingAnalyticsController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\UserBlockController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// LOT 1: Authentication & Profile Management

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// OTP Routes
Route::prefix('otp')->group(function () {
    Route::post('send', [OtpController::class, 'send']);
    Route::post('verify', [OtpController::class, 'verify']);
    Route::post('resend', [OtpController::class, 'resend']);
});

// Protected Routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('photo', [ProfileController::class, 'uploadPhoto']);
        Route::delete('photo', [ProfileController::class, 'deletePhoto']);
        Route::get('verification', [ProfileController::class, 'verificationStatus']);
    });

    // Document Management
    Route::prefix('profile/documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('{id}', [DocumentController::class, 'show']);
        Route::put('{id}', [DocumentController::class, 'update']);
        Route::delete('{id}', [DocumentController::class, 'destroy']);
    });

    // LOT 2: Shipment Management
    Route::prefix('shipments')->group(function () {
        // Main shipment routes
        Route::get('/', [ShipmentController::class, 'index']);
        Route::post('/', [ShipmentController::class, 'store']);
        Route::get('search', [ShipmentController::class, 'search']);
        Route::get('nearby', [ShipmentController::class, 'nearby']);
        Route::get('my-shipments', [ShipmentController::class, 'myShipments']);
        Route::get('{id}', [ShipmentController::class, 'show']);
        Route::put('{id}', [ShipmentController::class, 'update']);
        Route::delete('{id}', [ShipmentController::class, 'destroy']);
        Route::patch('{id}/publish', [ShipmentController::class, 'publish']);
        Route::patch('{id}/cancel', [ShipmentController::class, 'cancel']);

        // Photo management routes
        Route::get('{id}/photos', [ShipmentPhotoController::class, 'index']);
        Route::post('{id}/photos', [ShipmentPhotoController::class, 'store']);
        Route::delete('{id}/photos/{photoId}', [ShipmentPhotoController::class, 'destroy']);
        Route::put('{id}/photos/{photoId}/primary', [ShipmentPhotoController::class, 'setPrimary']);
        Route::put('{id}/photos/reorder', [ShipmentPhotoController::class, 'reorder']);
    });

    // LOT 3: Route Management
    Route::prefix('routes')->group(function () {
        // Main route routes
        Route::get('/', [RouteController::class, 'index']);
        Route::post('/', [RouteController::class, 'store']);
        Route::get('search', [RouteController::class, 'search']);
        Route::get('nearby', [RouteController::class, 'nearby']);
        Route::get('my', [RouteController::class, 'myRoutes']);
        Route::get('stats', [RouteController::class, 'stats']);
        Route::get('{id}', [RouteController::class, 'show']);
        Route::put('{id}', [RouteController::class, 'update']);
        Route::delete('{id}', [RouteController::class, 'destroy']);
        Route::post('{id}/publish', [RouteController::class, 'publish']);
        Route::post('{id}/start', [RouteController::class, 'start']);
        Route::post('{id}/complete', [RouteController::class, 'complete']);
        Route::post('{id}/cancel', [RouteController::class, 'cancel']);

        // Waypoint management routes
        Route::get('{id}/waypoints', [RouteWaypointController::class, 'index']);
        Route::post('{id}/waypoints', [RouteWaypointController::class, 'store']);
        Route::put('{id}/waypoints/{waypointId}', [RouteWaypointController::class, 'update']);
        Route::delete('{id}/waypoints/{waypointId}', [RouteWaypointController::class, 'destroy']);
        Route::get('{id}/requests', [RouteWaypointController::class, 'getRequests']);
    });

    // LOT 4: Matching System & Propositions
    Route::prefix('matches')->group(function () {
        // Main matching routes
        Route::get('suggestions', [MatchController::class, 'suggestions']);
        Route::post('/', [MatchController::class, 'store']);
        Route::get('my', [MatchController::class, 'myMatches']);
        Route::get('{id}', [MatchController::class, 'show']);
        Route::put('{id}/accept', [MatchController::class, 'accept']);
        Route::put('{id}/reject', [MatchController::class, 'reject']);
        Route::put('{id}/complete', [MatchController::class, 'complete']);

        // Match communication routes
        Route::get('{id}/messages', [MatchMessageController::class, 'index']);
        Route::post('{id}/messages', [MatchMessageController::class, 'store']);
        Route::put('{id}/messages/{messageId}/read', [MatchMessageController::class, 'markAsRead']);
        Route::put('{id}/messages/mark-all-read', [MatchMessageController::class, 'markAllAsRead']);
        Route::get('{id}/messages/unread-count', [MatchMessageController::class, 'unreadCount']);
    });

    // Matching analytics routes
    Route::prefix('matching')->group(function () {
        Route::get('algorithm/config', [MatchingAnalyticsController::class, 'getAlgorithmConfig']);
        Route::get('statistics', [MatchingAnalyticsController::class, 'getStatistics']);
        Route::post('feedback', [MatchingAnalyticsController::class, 'submitFeedback']);
        Route::get('performance', [MatchingAnalyticsController::class, 'getPerformanceMetrics']);
    });

    // LOT 5: Messaging System
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('start', [ConversationController::class, 'start']);
        Route::get('{id}/messages', [ConversationController::class, 'messages']);
        Route::post('{id}/messages', [ConversationController::class, 'sendMessage']);
        Route::put('{id}/read', [ConversationController::class, 'markAsRead']);
    });

    // User Blocking
    Route::prefix('users')->group(function () {
        Route::get('blocks', [UserBlockController::class, 'index']);
        Route::post('block', [UserBlockController::class, 'block']);
        Route::delete('unblock/{id}', [UserBlockController::class, 'unblock']);
    });
});
