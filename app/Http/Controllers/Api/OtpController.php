<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OtpController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/otp/send",
     *     operationId="sendOtp",
     *     tags={"OTP"},
     *     summary="Send OTP code",
     *     description="Send One-Time Password to user's email or phone",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="phone", type="string", example="+33123456789"),
     *             @OA\Property(property="type", type="string", enum={"phone","email"}, example="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="expires_in", type="integer", example=600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=429, description="OTP already sent")
     * )
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email|string',
            'type' => 'required|in:phone,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user
        $user = User::where('email', $request->email)
                   ->orWhere('phone', $request->phone)
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check for existing valid OTP
        $existingOtp = OtpCode::where('user_id', $user->id)
            ->where('type', $request->type)
            ->valid()
            ->first();

        if ($existingOtp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP already sent. Please wait before requesting a new one.'
            ], 429);
        }

        // Generate new OTP
        $otpCode = rand(100000, 999999);

        OtpCode::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // TODO: Send OTP via SMS or Email based on type
        // For now, we'll just return success with the OTP code (remove in production)

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'type' => $request->type,
                'expires_in' => 600, // 10 minutes in seconds
                // Remove this in production - only for testing
                'otp_code' => $otpCode
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/otp/verify",
     *     operationId="verifyOtp",
     *     tags={"OTP"},
     *     summary="Verify OTP code",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","code"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="type", type="string", enum={"phone","email"}),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP verified successfully"),
     *     @OA\Response(response=400, description="Invalid or expired OTP")
     * )
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email|string',
            'type' => 'required|in:phone,email',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user
        $user = User::where('email', $request->email)
                   ->orWhere('phone', $request->phone)
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Find valid OTP
        $otpCode = OtpCode::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$otpCode) {
            // Increment attempts
            OtpCode::where('user_id', $user->id)
                ->where('type', $request->type)
                ->where('code', $request->code)
                ->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark OTP as used
        $otpCode->markAsUsed();

        // Update user verification status
        if ($request->type === 'email') {
            $user->update(['email_verified_at' => Carbon::now()]);
        } elseif ($request->type === 'phone') {
            $user->update(['phone_verified_at' => Carbon::now()]);
        }

        // Calculate and update verification score
        $score = $user->calculateVerificationScore();

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'verified' => true,
                'verification_type' => $request->type,
                'verification_score' => $score,
                'is_verified' => $user->fresh()->is_verified
            ]
        ]);
    }

    public function resend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email|string',
            'type' => 'required|in:phone,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user
        $user = User::where('email', $request->email)
                   ->orWhere('phone', $request->phone)
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Invalidate all existing OTPs for this user and type
        OtpCode::where('user_id', $user->id)
            ->where('type', $request->type)
            ->update(['used_at' => Carbon::now()]);

        // Generate new OTP
        $otpCode = rand(100000, 999999);

        OtpCode::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // TODO: Send OTP via SMS or Email

        return response()->json([
            'success' => true,
            'message' => 'New OTP sent successfully',
            'data' => [
                'type' => $request->type,
                'expires_in' => 600,
                // Remove this in production - only for testing
                'otp_code' => $otpCode
            ]
        ]);
    }
}
