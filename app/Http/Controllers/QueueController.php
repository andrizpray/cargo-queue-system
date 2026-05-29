<?php

namespace App\Http\Controllers;

use App\Events\QueueCreated;
use App\Events\QueueDeleted;
use App\Events\QueueStatusChanged;
use App\Models\Location;
use App\Models\Queue;
use App\Models\QueueHistory;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id'         => 'nullable|integer|exists:vehicles,id',
            'vehicle_type_id'    => 'nullable|integer|exists:vehicle_types,id',
            'location_id'       => 'nullable|integer|exists:locations,id',
            'plate_number'      => 'nullable|string|max:20',
            'driver_name'       => 'nullable|string|max:255',
            'driver_phone'      => 'nullable|string|max:20',
            'cargo_description' => 'nullable|string|max:255',
            'weight_kg'         => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        // Default location_id to 1 if not provided (for backward compatibility)
        $locationId = $data['location_id'] ?? 1;

        // Determine vehicle - priority: vehicle_id > plate_number > generate placeholder
        if (!empty($data['vehicle_id'])) {
            // Use existing vehicle by ID
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        } elseif (!empty($data['plate_number'])) {
            // Create or find vehicle by plate number
            $vehicle = Vehicle::firstOrCreate(
                ['plate_number' => $data['plate_number']],
                [
                    'vehicle_type_id' => $data['vehicle_type_id'] ?? null,
                    'location_id'     => $locationId,
                    'driver_name'     => $data['driver_name'] ?? null,
                    'driver_phone'    => $data['driver_phone'] ?? null,
                ]
            );
        } else {
            // No vehicle info - require at least plate_number or vehicle_id
            throw \Illuminate\Validation\ValidationException::withMessages([
                'plate_number' => ['Plate number or vehicle ID is required.'],
            ]);
        }

        if (
            isset($data['vehicle_type_id']) &&
            ($vehicle->vehicle_type_id !== $data['vehicle_type_id'] || $vehicle->location_id !== $locationId)
        ) {
            $vehicle->update([
                'vehicle_type_id' => $data['vehicle_type_id'] ?? $vehicle->vehicle_type_id,
                'location_id'     => $locationId,
                'driver_name'     => $data['driver_name'] ?? $vehicle->driver_name,
                'driver_phone'    => $data['driver_phone'] ?? $vehicle->driver_phone,
            ]);
        }

        $queueNumber = Queue::where('location_id', $locationId)->max('queue_number') + 1;

        $queue = Queue::create([
            'vehicle_id'         => $vehicle->id,
            'location_id'        => $locationId,
            'queue_number'       => $queueNumber,
            'status'             => 'waiting',
            'arrived_at'         => now(),
            'notes'              => $data['notes'] ?? null,
            'cargo_description'  => $data['cargo_description'] ?? null,
            'weight_kg'          => $data['weight_kg'] ?? null,
            'user_id'            => $request->user()?->id, // Track the driver who created the queue
            'created_by'         => $request->user()?->id,
        ]);

        $queue->load('vehicle.vehicleType', 'location');

        // Broadcast queue created event
        QueueCreated::dispatch($queue);

        return response()->json([
            'data' => $queue,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $queue = Queue::with('vehicle.vehicleType', 'vehicle.location', 'location')->find($id);

        if (!$queue) {
            return response()->json(['message' => 'Queue not found'], 404);
        }

        return response()->json(['data' => $queue]);
    }

    public function index(Request $request, int $location_id): JsonResponse
    {
        Location::findOrFail($location_id);

        $queues = Queue::with('vehicle.vehicleType')
            ->where('location_id', $location_id)
            ->orderBy('queue_number')
            ->paginate($request->integer('per_page', 15));

        return response()->json($queues);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['waiting', 'loading', 'done', 'cancelled'])],
            'notes'  => 'nullable|string',
        ]);

        $queue = Queue::find($id);

        if (!$queue) {
            return response()->json(['message' => 'Queue not found'], 404);
        }

        $previousStatus = $queue->status;

        $queue->status = $data['status'];

        if ($data['status'] === 'loading') {
            $queue->started_at = now();
        } elseif ($data['status'] === 'done' || $data['status'] === 'cancelled') {
            $queue->completed_at = now();
        }

        if (isset($data['notes'])) {
            $queue->notes = $data['notes'];
        }

        $queue->updated_by = $request->user()?->id;
        $queue->save();

        QueueHistory::create([
            'queue_id'        => $queue->id,
            'vehicle_id'      => $queue->vehicle_id,
            'previous_status' => $previousStatus,
            'new_status'      => $data['status'],
            'notes'           => $data['notes'] ?? null,
            'changed_by'      => $request->user()?->id,
            'changed_at'      => now(),
        ]);

        $queue->load('vehicle.vehicleType', 'location');

        // Broadcast queue status changed event
        QueueStatusChanged::dispatch(
            $queue,
            $previousStatus,
            $data['status'],
            $data['notes'] ?? null
        );

        return response()->json(['data' => $queue]);
    }
}
