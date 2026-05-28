<?php

namespace App\Http\Controllers;

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
            'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
            'location_id'     => 'required|integer|exists:locations,id',
            'plate_number'    => 'required|string|max:20',
            'driver_name'     => 'nullable|string|max:255',
            'driver_phone'    => 'nullable|string|max:20',
            'notes'           => 'nullable|string',
        ]);

        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => $data['plate_number']],
            [
                'vehicle_type_id' => $data['vehicle_type_id'],
                'location_id'     => $data['location_id'],
                'driver_name'     => $data['driver_name'] ?? null,
                'driver_phone'    => $data['driver_phone'] ?? null,
            ]
        );

        if (
            $vehicle->vehicle_type_id !== $data['vehicle_type_id'] ||
            $vehicle->location_id !== $data['location_id']
        ) {
            $vehicle->update([
                'vehicle_type_id' => $data['vehicle_type_id'],
                'location_id'     => $data['location_id'],
                'driver_name'     => $data['driver_name'] ?? $vehicle->driver_name,
                'driver_phone'    => $data['driver_phone'] ?? $vehicle->driver_phone,
            ]);
        }

        $queueNumber = Queue::where('location_id', $data['location_id'])->max('queue_number') + 1;

        $queue = Queue::create([
            'vehicle_id'   => $vehicle->id,
            'location_id'  => $data['location_id'],
            'queue_number' => $queueNumber,
            'status'       => 'waiting',
            'arrived_at'   => now(),
            'notes'        => $data['notes'] ?? null,
        ]);

        return response()->json([
            'data' => $queue->load('vehicle.vehicleType', 'location'),
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

        return response()->json(['data' => $queue->load('vehicle.vehicleType', 'location')]);
    }
}
