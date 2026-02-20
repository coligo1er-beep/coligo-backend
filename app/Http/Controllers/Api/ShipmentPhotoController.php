<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ShipmentPhotoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/shipments/{id}/photos",
     *     operationId="uploadShipmentPhotos",
     *     tags={"Shipments"},
     *     summary="Upload photos for shipment (max 5)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photos"},
     *                 @OA\Property(
     *                     property="photos",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Array of image files (max 5, each max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="primary_photo_index",
     *                     type="integer",
     *                     description="Index of the primary photo (0-based)",
     *                     example=0
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="photos", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="uploaded_count", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Too many photos or invalid files"),
     *     @OA\Response(response=404, description="Shipment not found")
     * )
     */
    public function store(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photos' => 'required|array|max:5',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'primary_photo_index' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check current photo count
        $currentPhotoCount = $shipment->photos()->count();
        $newPhotoCount = count($request->file('photos'));
        $totalAfterUpload = $currentPhotoCount + $newPhotoCount;

        if ($totalAfterUpload > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 5 photos allowed. Current: ' . $currentPhotoCount . ', Trying to add: ' . $newPhotoCount
            ], 400);
        }

        $uploadedPhotos = [];
        $primaryPhotoIndex = $request->get('primary_photo_index', 0);

        foreach ($request->file('photos') as $index => $file) {
            // Generate unique filename
            $fileName = $shipment->id . '_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('shipments', $fileName, 'public');

            // Determine sort order (start from current max + 1)
            $maxSortOrder = $shipment->photos()->max('sort_order') ?? -1;
            $sortOrder = $maxSortOrder + 1 + $index;

            // Create photo record
            $photo = ShipmentPhoto::create([
                'shipment_id' => $shipment->id,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_primary' => false, // Set later
                'sort_order' => $sortOrder
            ]);

            $uploadedPhotos[] = $photo;
        }

        // Set primary photo
        if (!empty($uploadedPhotos) && isset($uploadedPhotos[$primaryPhotoIndex])) {
            // Remove primary status from all current photos
            $shipment->photos()->update(['is_primary' => false]);

            // Set the new primary photo
            $uploadedPhotos[$primaryPhotoIndex]->update(['is_primary' => true]);
        }

        // Reload photos with URLs
        $allPhotos = $shipment->photos()->orderBy('sort_order')->get()->map(function($photo) {
            return [
                'id' => $photo->id,
                'file_name' => $photo->file_name,
                'file_size' => $photo->file_size,
                'mime_type' => $photo->mime_type,
                'is_primary' => $photo->is_primary,
                'sort_order' => $photo->sort_order,
                'url' => Storage::url($photo->file_path),
                'created_at' => $photo->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Photos uploaded successfully',
            'data' => [
                'photos' => $allPhotos,
                'uploaded_count' => count($uploadedPhotos),
                'total_photos' => $allPhotos->count()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/shipments/{id}/photos",
     *     operationId="getShipmentPhotos",
     *     tags={"Shipments"},
     *     summary="Get all photos for shipment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="photos", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index($id)
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $photos = $shipment->photos()->orderBy('sort_order')->get()->map(function($photo) {
            return [
                'id' => $photo->id,
                'file_name' => $photo->file_name,
                'file_size' => $photo->file_size,
                'mime_type' => $photo->mime_type,
                'is_primary' => $photo->is_primary,
                'sort_order' => $photo->sort_order,
                'url' => Storage::url($photo->file_path),
                'created_at' => $photo->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Photos retrieved successfully',
            'data' => [
                'photos' => $photos
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/shipments/{id}/photos/{photoId}",
     *     operationId="deleteShipmentPhoto",
     *     tags={"Shipments"},
     *     summary="Delete a shipment photo",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="photoId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Photo deleted successfully"),
     *     @OA\Response(response=404, description="Photo not found")
     * )
     */
    public function destroy(Request $request, $id, $photoId)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $photo = $shipment->photos()->find($photoId);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Delete file from storage
        Storage::disk('public')->delete($photo->file_path);

        // If this was the primary photo, set another photo as primary
        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $firstPhoto = $shipment->photos()->orderBy('sort_order')->first();
            if ($firstPhoto) {
                $firstPhoto->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/shipments/{id}/photos/{photoId}/primary",
     *     operationId="setShipmentPhotoPrimary",
     *     tags={"Shipments"},
     *     summary="Set photo as primary",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="photoId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Primary photo set successfully")
     * )
     */
    public function setPrimary(Request $request, $id, $photoId)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $photo = $shipment->photos()->find($photoId);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Remove primary status from all photos
        $shipment->photos()->update(['is_primary' => false]);

        // Set this photo as primary
        $photo->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary photo set successfully',
            'data' => [
                'photo' => [
                    'id' => $photo->id,
                    'file_name' => $photo->file_name,
                    'is_primary' => true,
                    'url' => Storage::url($photo->file_path)
                ]
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/shipments/{id}/photos/reorder",
     *     operationId="reorderShipmentPhotos",
     *     tags={"Shipments"},
     *     summary="Reorder photos",
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
     *             required={"photo_orders"},
     *             @OA\Property(
     *                 property="photo_orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="photo_id", type="integer"),
     *                     @OA\Property(property="sort_order", type="integer")
     *                 ),
     *                 example={{"photo_id": 1, "sort_order": 0}, {"photo_id": 2, "sort_order": 1}}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Photos reordered successfully")
     * )
     */
    public function reorder(Request $request, $id)
    {
        $user = $request->user();
        $shipment = $user->shipments()->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo_orders' => 'required|array',
            'photo_orders.*.photo_id' => 'required|integer|exists:shipment_photos,id',
            'photo_orders.*.sort_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->photo_orders as $order) {
            $photo = $shipment->photos()->find($order['photo_id']);
            if ($photo) {
                $photo->update(['sort_order' => $order['sort_order']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Photos reordered successfully'
        ]);
    }
}
