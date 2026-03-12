<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Submit a review for a completed match.
     */
    public function store(Request $request, $matchId)
    {
        $match = MatchModel::findOrFail($matchId);
        $user = $request->user();

        // Security checks
        if ($match->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'You can only review completed deliveries.'], 400);
        }

        if ($match->sender_id !== $user->id && $match->transporter_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|min:10|max:500',
            'criteria' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $reviewedId = ($match->sender_id === $user->id) ? $match->transporter_id : $match->sender_id;

        $review = Review::updateOrCreate(
            [
                'match_id' => $match->id,
                'reviewer_id' => $user->id
            ],
            [
                'reviewed_id' => $reviewedId,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'criteria' => $request->criteria,
                'is_published' => true
            ]
        );

        // Update the reviewed user's average rating
        User::find($reviewedId)->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review
        ]);
    }

    /**
     * Get reviews for a specific user.
     */
    public function userReviews($userId)
    {
        $user = User::findOrFail($userId);
        $reviews = $user->reviewsReceived()
            ->with('reviewer')
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Respond to a review.
     */
    public function respond(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = $request->user();

        if ($review->reviewed_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'response' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $review->update(['response' => $request->response]);

        return response()->json([
            'success' => true,
            'message' => 'Response added successfully.',
            'data' => $review
        ]);
    }

    /**
     * Update a review (within 30 days).
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = $request->user();

        if ($review->reviewer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($review->created_at->addDays(30)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Reviews can only be edited within 30 days.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|min:10|max:500',
            'criteria' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $review->update($request->only(['rating', 'comment', 'criteria']));

        // Recalculate average
        User::find($review->reviewed_id)->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => $review
        ]);
    }
}
