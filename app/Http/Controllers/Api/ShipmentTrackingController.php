<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\DeliveryProof;
use App\Models\ShipmentStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShipmentTrackingController extends Controller
{
    /**
     * Mark shipment as picked up by transporter.
     */
    public function pickup(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Security: Only the assigned transporter can mark as picked up
        $match = $shipment->matches()->where('transporter_id', $user->id)->where('status', 'accepted')->first();
        if (!$match) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. You are not the assigned transporter for this shipment.'], 403);
        }

        if ($shipment->status !== 'matched') {
            return response()->json(['success' => false, 'message' => 'Shipment status must be matched to confirm pickup.'], 400);
        }

        DB::transaction(function () use ($shipment) {
            $shipment->update(['status' => 'in_transit']);
            
            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => 'in_transit',
                'notes' => 'Shipment picked up by transporter.'
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment marked as in transit.',
            'data' => $shipment->load('statusHistory')
        ]);
    }

    /**
     * Update current GPS location of the shipment.
     */
    public function updateLocation(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Security: Only the assigned transporter can update location
        $match = $shipment->matches()->where('transporter_id', $user->id)->where('status', 'accepted')->first();
        if (!$match) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($shipment->status !== 'in_transit') {
            return response()->json(['success' => false, 'message' => 'Can only update location for shipments in transit.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $tracking = ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed
        ]);

        // Broadcast event for real-time tracking
        broadcast(new \App\Events\LocationUpdated($tracking))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => $tracking
        ]);
    }

    /**
     * Get tracking history and current location.
     */
    public function getTracking(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Security: Only sender or assigned transporter can view tracking
        $isParticipant = ($shipment->user_id == $user->id) || 
                         ($shipment->matches()->where('transporter_id', $user->id)->where('status', 'accepted')->exists());

        if (!$isParticipant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $tracking = $shipment->tracking()->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $tracking
        ]);
    }

    /**
     * Upload a delivery proof (photo or signature).
     */
    public function uploadProof(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:pickup_photo,delivery_photo,sender_signature',
            'file' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'metadata' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Logic check: only sender can sign, only transporter can take photos
        if ($request->type === 'sender_signature' && $shipment->user_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Only the sender can provide a signature.'], 403);
        }

        if (in_array($request->type, ['pickup_photo', 'delivery_photo']) && !$shipment->matches()->where('transporter_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Only the transporter can upload photos.'], 403);
        }

        $path = $request->file('file')->store('proofs/' . $request->type, 'public');

        $proof = DeliveryProof::create([
            'shipment_id' => $shipment->id,
            'type' => $request->type,
            'file_path' => $path,
            'metadata' => json_decode($request->metadata, true)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proof uploaded successfully.',
            'data' => $proof
        ]);
    }

    /**
     * Mark as delivered by transporter.
     */
    public function deliver(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Security: Only the assigned transporter
        $match = $shipment->matches()->where('transporter_id', $user->id)->where('status', 'accepted')->first();
        if (!$match) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);

        if ($shipment->status !== 'in_transit') {
            return response()->json(['success' => false, 'message' => 'Shipment must be in transit to be marked as delivered.'], 400);
        }

        DB::transaction(function () use ($shipment) {
            $shipment->update(['status' => 'delivered']);
            
            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => 'delivered',
                'notes' => 'Transporteur marked as delivered. Awaiting sender confirmation.'
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment marked as delivered.',
            'data' => $shipment->load('statusHistory')
        ]);
    }

    /**
     * Final confirmation by sender.
     */
    public function confirmReceipt(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Security: Only the sender
        if ($shipment->user_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Only the sender can confirm receipt.'], 403);
        }

        if ($shipment->status !== 'delivered') {
            return response()->json(['success' => false, 'message' => 'Shipment must be marked as delivered by transporter first.'], 400);
        }

        DB::transaction(function () use ($shipment) {
            $shipment->update(['status' => 'completed']);
            
            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => 'completed',
                'notes' => 'Sender confirmed receipt. Transaction closed.'
            ]);

            // Update match status too
            $shipment->matches()->where('status', 'accepted')->update(['status' => 'completed', 'completed_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Receipt confirmed. Delivery complete.',
            'data' => $shipment->load('statusHistory')
        ]);
    }
}
