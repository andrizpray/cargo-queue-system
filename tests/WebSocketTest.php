<?php

namespace Tests;

use App\Events\QueueCreated;
use App\Events\QueueStatusChanged;
use App\Models\Location;
use App\Models\Queue;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\TestCase;

class WebSocketTest extends TestCase
{
    /**
     * Test that QueueCreated event is broadcastable
     */
    public function test_queue_created_event_is_broadcastable(): void
    {
        $vehicleType = VehicleType::factory()->create();
        $location = Location::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'vehicle_type_id' => $vehicleType->id,
            'location_id' => $location->id,
        ]);

        $queue = Queue::factory()->create([
            'vehicle_id' => $vehicle->id,
            'location_id' => $location->id,
        ]);

        $event = new QueueCreated($queue);

        $this->assertTrue(method_exists($event, 'broadcastOn'));
        $this->assertTrue(method_exists($event, 'broadcastAs'));
        $this->assertTrue(method_exists($event, 'broadcastWith'));

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertEquals('queue.created', $event->broadcastAs());
    }

    /**
     * Test that QueueStatusChanged event is broadcastable
     */
    public function test_queue_status_changed_event_is_broadcastable(): void
    {
        $vehicleType = VehicleType::factory()->create();
        $location = Location::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'vehicle_type_id' => $vehicleType->id,
            'location_id' => $location->id,
        ]);

        $queue = Queue::factory()->create([
            'vehicle_id' => $vehicle->id,
            'location_id' => $location->id,
            'status' => 'waiting',
        ]);

        $event = new QueueStatusChanged($queue, 'waiting', 'loading', 'Starting cargo loading');

        $this->assertTrue(method_exists($event, 'broadcastOn'));
        $this->assertTrue(method_exists($event, 'broadcastAs'));
        $this->assertTrue(method_exists($event, 'broadcastWith'));

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertEquals('queue.status-changed', $event->broadcastAs());

        $data = $event->broadcastWith();
        $this->assertEquals('waiting', $data['previous_status']);
        $this->assertEquals('loading', $data['new_status']);
    }

    /**
     * Test broadcast data structure
     */
    public function test_broadcast_data_structure(): void
    {
        $vehicleType = VehicleType::factory()->create();
        $location = Location::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'vehicle_type_id' => $vehicleType->id,
            'location_id' => $location->id,
        ]);

        $queue = Queue::factory()->create([
            'vehicle_id' => $vehicle->id,
            'location_id' => $location->id,
        ]);

        $event = new QueueCreated($queue);
        $data = $event->broadcastWith();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('queue_number', $data);
        $this->assertArrayHasKey('location_id', $data);
        $this->assertArrayHasKey('vehicle_id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('arrived_at', $data);
    }
}
