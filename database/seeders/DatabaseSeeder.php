<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Queue;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample users with different roles - using test emails
        User::create([
            'name' => 'Driver User',
            'email' => 'driver@test.com',
            'password' => bcrypt('password123'),
            'role' => 'driver',
        ]);

        User::create([
            'name' => 'Security User',
            'email' => 'security@test.com',
            'password' => bcrypt('password123'),
            'role' => 'security',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // 3 Locations
        $locations = [
            ['name' => 'Warehouse A', 'code' => 'WH-A', 'address' => 'Jl. Industri No. 1', 'city' => 'Jakarta', 'province' => 'DKI Jakarta'],
            ['name' => 'Warehouse B', 'code' => 'WH-B', 'address' => 'Jl. Industri No. 2', 'city' => 'Surabaya', 'province' => 'Jawa Timur'],
            ['name' => 'Warehouse C', 'code' => 'WH-C', 'address' => 'Jl. Industri No. 3', 'city' => 'Bandung', 'province' => 'Jawa Barat'],
        ];

        foreach ($locations as $data) {
            Location::create(array_merge($data, ['is_active' => true]));
        }

        // 5 Vehicle Types
        $vehicleTypes = [
            ['name' => 'Roll',        'code' => 'ROLL',       'capacity_kg' => 5000,  'description' => 'Kendaraan pengangkut roll'],
            ['name' => 'Sheet',       'code' => 'SHEET',      'capacity_kg' => 4000,  'description' => 'Kendaraan pengangkut sheet'],
            ['name' => 'Roll+Sheet',  'code' => 'ROLL-SHEET', 'capacity_kg' => 8000,  'description' => 'Kendaraan pengangkut roll dan sheet'],
            ['name' => 'Waste',       'code' => 'WASTE',      'capacity_kg' => 3000,  'description' => 'Kendaraan pengangkut limbah'],
            ['name' => 'Sesetan',     'code' => 'SESETAN',    'capacity_kg' => 2000,  'description' => 'Kendaraan sesetan'],
        ];

        foreach ($vehicleTypes as $data) {
            VehicleType::create(array_merge($data, ['is_active' => true]));
        }

        // 10 Vehicles with Code128 barcodes
        $vehicleData = [
            ['plate_number' => 'B 1234 ABC', 'driver_name' => 'Budi Santoso',    'driver_phone' => '081234567890'],
            ['plate_number' => 'B 5678 DEF', 'driver_name' => 'Agus Wijaya',     'driver_phone' => '081234567891'],
            ['plate_number' => 'D 1111 GHI', 'driver_name' => 'Siti Rahayu',     'driver_phone' => '081234567892'],
            ['plate_number' => 'D 2222 JKL', 'driver_name' => 'Hendra Kusuma',   'driver_phone' => '081234567893'],
            ['plate_number' => 'L 3333 MNO', 'driver_name' => 'Dewi Lestari',    'driver_phone' => '081234567894'],
            ['plate_number' => 'L 4444 PQR', 'driver_name' => 'Rudi Hartono',    'driver_phone' => '081234567895'],
            ['plate_number' => 'F 5555 STU', 'driver_name' => 'Andi Prasetyo',   'driver_phone' => '081234567896'],
            ['plate_number' => 'F 6666 VWX', 'driver_name' => 'Yuni Astuti',     'driver_phone' => '081234567897'],
            ['plate_number' => 'T 7777 YZA', 'driver_name' => 'Bambang Irawan',  'driver_phone' => '081234567898'],
            ['plate_number' => 'T 8888 BCD', 'driver_name' => 'Rina Marlina',    'driver_phone' => '081234567899'],
        ];

        $locationIds    = Location::pluck('id')->toArray();
        $vehicleTypeIds = VehicleType::pluck('id')->toArray();

        foreach ($vehicleData as $i => $data) {
            Vehicle::create([
                'plate_number'    => $data['plate_number'],
                'barcode_code128' => 'VH' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'vehicle_type_id' => $vehicleTypeIds[$i % count($vehicleTypeIds)],
                'location_id'     => $locationIds[$i % count($locationIds)],
                'driver_name'     => $data['driver_name'],
                'driver_phone'    => $data['driver_phone'],
                'is_active'       => true,
            ]);
        }

        // 20 Queues with varied statuses
        $statuses   = ['waiting', 'loading', 'done', 'cancelled'];
        $vehicleIds = Vehicle::pluck('id')->toArray();
        $now        = Carbon::now();

        for ($i = 1; $i <= 20; $i++) {
            $status    = $statuses[($i - 1) % count($statuses)];
            $vehicleId = $vehicleIds[($i - 1) % count($vehicleIds)];
            $vehicle   = Vehicle::find($vehicleId);

            $arrivedAt   = $now->copy()->subHours(20 - $i)->subMinutes(rand(0, 59));
            $startedAt   = in_array($status, ['loading', 'done']) ? $arrivedAt->copy()->addMinutes(rand(5, 30)) : null;
            $completedAt = $status === 'done' ? $startedAt->copy()->addMinutes(rand(30, 120)) : null;

            Queue::create([
                'vehicle_id'   => $vehicleId,
                'location_id'  => $vehicle->location_id,
                'queue_number' => $i,
                'status'       => $status,
                'arrived_at'   => $arrivedAt,
                'started_at'   => $startedAt,
                'completed_at' => $completedAt,
                'notes'        => "Sample queue #{$i} - {$status}",
            ]);
        }
    }
}
