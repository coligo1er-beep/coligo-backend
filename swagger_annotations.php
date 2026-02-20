<?php
// Annotations Swagger supplémentaires pour tous les endpoints

/* AuthController - Logout */
/**
 * @OA\Post(
 *     path="/api/auth/logout",
 *     operationId="logoutUser",
 *     tags={"Authentication"},
 *     summary="Logout user",
 *     description="Revoke the current authentication token",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logged out successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Logged out successfully")
 *         )
 *     )
 * )
 */

/* AuthController - Refresh */
/**
 * @OA\Post(
 *     path="/api/auth/refresh",
 *     operationId="refreshToken",
 *     tags={"Authentication"},
 *     summary="Refresh authentication token",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Token refreshed successfully"
 *     )
 * )
 */

/* AuthController - Forgot Password */
/**
 * @OA\Post(
 *     path="/api/auth/forgot-password",
 *     operationId="forgotPassword",
 *     tags={"Authentication"},
 *     summary="Request password reset",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="test@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password reset OTP sent"
 *     )
 * )
 */

/* OtpController - Send */
/**
 * @OA\Post(
 *     path="/api/otp/send",
 *     operationId="sendOtp",
 *     tags={"OTP"},
 *     summary="Send OTP code",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="type", type="string", enum={"phone","email"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP sent successfully"
 *     )
 * )
 */

/* OtpController - Verify */
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
 *     @OA\Response(
 *         response=200,
 *         description="OTP verified successfully"
 *     )
 * )
 */

/* ProfileController - Show */
/**
 * @OA\Get(
 *     path="/api/profile",
 *     operationId="showProfile",
 *     tags={"Profile"},
 *     summary="Get user profile",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profile retrieved successfully"
 *     )
 * )
 */

/* ProfileController - Update */
/**
 * @OA\Put(
 *     path="/api/profile",
 *     operationId="updateProfile",
 *     tags={"Profile"},
 *     summary="Update user profile",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="date_of_birth", type="string", format="date"),
 *             @OA\Property(property="gender", type="string", enum={"male","female","other"}),
 *             @OA\Property(property="address_street", type="string"),
 *             @OA\Property(property="address_city", type="string"),
 *             @OA\Property(property="latitude", type="number", format="float"),
 *             @OA\Property(property="longitude", type="number", format="float")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profile updated successfully"
 *     )
 * )
 */

/* Documents - Index */
/**
 * @OA\Get(
 *     path="/api/profile/documents",
 *     operationId="getDocuments",
 *     tags={"Documents"},
 *     summary="Get user documents",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Documents retrieved successfully"
 *     )
 * )
 */

/* Documents - Store */
/**
 * @OA\Post(
 *     path="/api/profile/documents",
 *     operationId="uploadDocument",
 *     tags={"Documents"},
 *     summary="Upload identity document",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"document_type", "document_file"},
 *                 @OA\Property(property="document_type", type="string", enum={"id_card","passport","driving_license","other"}),
 *                 @OA\Property(property="document_number", type="string"),
 *                 @OA\Property(property="document_file", type="string", format="binary"),
 *                 @OA\Property(property="expiration_date", type="string", format="date")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Document uploaded successfully"
 *     )
 * )
 */
?>