<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     operationId="showProfile",
     *     tags={"Profile"},
     *     summary="Get user profile",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('documents');

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/profile",
     *     operationId="updateProfile",
     *     tags={"Profile"},
     *     summary="Update user profile",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}),
     *             @OA\Property(property="address_street", type="string"),
     *             @OA\Property(property="address_city", type="string"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated successfully"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'date_of_birth' => 'date',
            'gender' => 'in:male,female,other',
            'user_type' => 'in:sender,transporter,both',
            'address_street' => 'string|max:255',
            'address_city' => 'string|max:255',
            'address_postal_code' => 'string|max:20',
            'address_country' => 'string|max:255',
            'latitude' => 'numeric|between:-90,90',
            'longitude' => 'numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'first_name', 'last_name', 'date_of_birth', 'gender',
            'user_type', 'address_street', 'address_city',
            'address_postal_code', 'address_country', 'latitude', 'longitude'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()
            ]
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Delete old photo if exists
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Store new photo
        $path = $request->file('photo')->store('profiles', 'public');

        $user->update(['profile_photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo uploaded successfully',
            'data' => [
                'photo_url' => Storage::url($path),
                'user' => $user->fresh()
            ]
        ]);
    }

    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if (!$user->profile_photo) {
            return response()->json([
                'success' => false,
                'message' => 'No profile photo to delete'
            ], 404);
        }

        // Delete photo file
        Storage::disk('public')->delete($user->profile_photo);

        // Update user record
        $user->update(['profile_photo' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo deleted successfully'
        ]);
    }

    public function verificationStatus(Request $request)
    {
        $user = $request->user();
        $user->load('documents');

        $verificationData = [
            'is_verified' => $user->is_verified,
            'verification_score' => $user->verification_score,
            'email_verified' => !is_null($user->email_verified_at),
            'phone_verified' => !is_null($user->phone_verified_at),
            'documents_count' => $user->documents->count(),
            'verified_documents_count' => $user->documents->where('verification_status', 'verified')->count(),
            'pending_documents_count' => $user->documents->where('verification_status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Verification status retrieved successfully',
            'data' => $verificationData
        ]);
    }
}
