<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile/documents",
     *     operationId="getDocuments",
     *     tags={"Documents"},
     *     summary="Get user documents",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Documents retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="documents", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $documents = $user->documents()->get();

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully',
            'data' => [
                'documents' => $documents
            ]
        ]);
    }

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
     *     ),
     *     @OA\Response(response=409, description="Document type already exists"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:id_card,passport,driving_license,other',
            'document_number' => 'string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'expiration_date' => 'date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if user already has this type of document
        $existingDocument = $user->documents()
            ->where('document_type', $request->document_type)
            ->first();

        if ($existingDocument) {
            return response()->json([
                'success' => false,
                'message' => 'Document of this type already exists. Please update the existing one.'
            ], 409);
        }

        // Store the document file
        $file = $request->file('document_file');
        $fileName = $user->id . '_' . $request->document_type . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('documents', $fileName, 'public');

        // Create document record
        $document = UserDocument::create([
            'user_id' => $user->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_file_path' => $filePath,
            'expiration_date' => $request->expiration_date,
            'verification_status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => [
                'document' => $document,
                'file_url' => Storage::url($filePath)
            ]
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $document = $user->documents()->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document retrieved successfully',
            'data' => [
                'document' => $document,
                'file_url' => Storage::url($document->document_file_path)
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $document = $user->documents()->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'document_number' => 'string|max:255',
            'document_file' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'expiration_date' => 'date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // If document was already verified, don't allow updates
        if ($document->verification_status === 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a verified document'
            ], 403);
        }

        $updateData = [];

        // Update document number if provided
        if ($request->has('document_number')) {
            $updateData['document_number'] = $request->document_number;
        }

        // Update expiration date if provided
        if ($request->has('expiration_date')) {
            $updateData['expiration_date'] = $request->expiration_date;
        }

        // Update file if provided
        if ($request->hasFile('document_file')) {
            // Delete old file
            if ($document->document_file_path) {
                Storage::disk('public')->delete($document->document_file_path);
            }

            // Store new file
            $file = $request->file('document_file');
            $fileName = $user->id . '_' . $document->document_type . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents', $fileName, 'public');
            $updateData['document_file_path'] = $filePath;

            // Reset verification status when file is changed
            $updateData['verification_status'] = 'pending';
            $updateData['verified_at'] = null;
            $updateData['verified_by'] = null;
        }

        $document->update($updateData);

        // Recalculate verification score if verification status changed
        $user->calculateVerificationScore();

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully',
            'data' => [
                'document' => $document->fresh(),
                'file_url' => Storage::url($document->fresh()->document_file_path)
            ]
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $document = $user->documents()->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        // Delete file from storage
        if ($document->document_file_path) {
            Storage::disk('public')->delete($document->document_file_path);
        }

        // Delete document record
        $document->delete();

        // Recalculate verification score after deleting document
        $user->calculateVerificationScore();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }
}
