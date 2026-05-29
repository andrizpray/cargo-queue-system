<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite: drop FK constraints, make nullable, recreate
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('CREATE TABLE vehicles_backup AS SELECT * FROM vehicles');
        DB::statement('DROP TABLE vehicles');
        DB::statement('CREATE TABLE vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plate_number VARCHAR(255) UNIQUE NOT NULL,
            vehicle_type_id INTEGER NULL,
            location_id INTEGER NULL,
            driver_name VARCHAR(255) NULL,
            driver_phone VARCHAR(255) NULL,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id) ON DELETE RESTRICT,
            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
        )');
        DB::statement('INSERT INTO vehicles (id, plate_number, vehicle_type_id, location_id, driver_name, driver_phone, is_active, created_at, updated_at) SELECT id, plate_number, vehicle_type_id, location_id, driver_name, driver_phone, is_active, created_at, updated_at FROM vehicles_backup');
        DB::statement('DROP TABLE vehicles_backup');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('CREATE TABLE vehicles_backup AS SELECT * FROM vehicles');
        DB::statement('DROP TABLE vehicles');
        DB::statement('CREATE TABLE vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plate_number VARCHAR(255) UNIQUE NOT NULL,
            vehicle_type_id INTEGER NOT NULL,
            location_id INTEGER NOT NULL,
            driver_name VARCHAR(255) NULL,
            driver_phone VARCHAR(255) NULL,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id) ON DELETE RESTRICT,
            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
        )');
        DB::statement('INSERT INTO vehicles (id, plate_number, vehicle_type_id, location_id, driver_name, driver_phone, is_active, created_at, updated_at) SELECT id, plate_number, vehicle_type_id, location_id, driver_name, driver_phone, is_active, created_at, updated_at FROM vehicles_backup');
        DB::statement('DROP TABLE vehicles_backup');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
