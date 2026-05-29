# Cargo Queue System

**Laravel 13 + SQLite + Flutter** - Sistem antrian kendaraan untuk muat/bongkar di pabrik/warehouse.

## 📋 Overview

Cargo Queue System adalah aplikasi manajemen antrian kendaraan yang dirancang untuk warehouse/pabrik dengan fitur:
- Real-time queue tracking via WebSocket (Laravel Reverb)
- Barcode scanning (Code128) untuk identifikasi kendaraan
- Multi-location support
- Role-based access control (driver, security, admin)
- Queue history audit trail untuk setiap perubahan status

## 🏗️ Architecture

```
Backend: Laravel 13 + SQLite (PostgreSQL for prod)
         └─ Laravel Reverb (WebSocket) port 8080
  ↓
API REST (JSON) + Bearer Token Auth (Sanctum)
  ↓
Mobile App: Flutter (Android primary, iOS compatible)
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- SQLite (default) or PostgreSQL for production
- Node.js 18+ (for Laravel Mix/assets)

### Installation

```bash
# Clone repository
git clone git@github.com:andrizpray/cargo-queue-system.git
cd cargo-queue-system

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations (uses SQLite by default)
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Start dev server
php artisan serve --host=0.0.0.0 --port=8000

# For WebSocket (real-time) - run in separate terminal
php artisan reverb:start --port=8080
```

Server akan berjalan di `http://43.134.37.14:8000`
WebSocket tersedia di `ws://43.134.37.14:8080`

### Production Deployment (PostgreSQL)

```bash
# Update .env for PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=your_postgres_host
DB_PORT=5432
DB_DATABASE=cargo_queue
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed default users
php artisan db:seed
```

## 🔐 Default Test Users

Setelah seeding, login dengan:

| Role    | Email              | Password    |
|---------|--------------------|-------------|
| driver  | driver@test.com    | password123 |
| security| security@test.com  | password123 |
| admin   | admin@test.com     | password123 |

## 📊 Database Schema

### Tables

| Table           | Purpose                                      |
|-----------------|----------------------------------------------|
| `users`         | User accounts dengan role (driver/security/admin) |
| `locations`     | Warehouse/pabrik locations                   |
| `vehicle_types` | Roll, Sheet, Roll+Sheet, Waste, Sesetan      |
| `vehicles`      | Kendaraan dengan barcode Code128             |
| `queues`        | Antrian kendaraan (waiting→loading→done/cancelled) |
| `queue_history` | Audit trail untuk setiap perubahan status    |

### Schema Details

**vehicles table:**
- `vehicle_type_id` - nullable (admin edits later)
- `location_id` - nullable
- `plate_number` - nullable (can be empty)
- `barcode_code128` - unique identifier

**queues table:**
- `weight_kg` (tonase) - nullable (admin fills later)
- `cargo_description` - nullable
- `driver_name` - optional
- `driver_phone` - optional
- `notes` - optional

### Relationships

```
Location
  ├─ hasMany Vehicles
  └─ hasMany Queues

VehicleType
  └─ hasMany Vehicles

Vehicle
  ├─ belongsTo VehicleType (nullable)
  ├─ belongsTo Location (nullable)
  ├─ hasMany Queues
  └─ hasMany QueueHistory

Queue
  ├─ belongsTo Vehicle
  ├─ belongsTo Location
  └─ hasMany QueueHistory

QueueHistory
  ├─ belongsTo Queue
  ├─ belongsTo Vehicle
  └─ belongsTo User (changed_by)
```

## 🔌 API Endpoints

### Authentication

**Login**
```bash
POST /api/login
Content-Type: application/json

{
  "email": "driver@test.com",
  "password": "password123"
}

Response: 200 OK
{
  "data": {
    "user": { "id": 1, "name": "Driver", "email": "driver@test.com", "role": "driver" },
    "token": "1|abc123..."
  }
}
```

**Register** *(password confirmation removed)*
```bash
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

Response: 201 Created
```

### Queue Management

**Create Queue** *(plate_number OR vehicle_id, tonase optional)*
```bash
POST /api/queues
Authorization: Bearer {token}
Content-Type: application/json

{
  "location_id": 1,
  "plate_number": "B 1234 ABC",
  "vehicle_id": null,
  "weight_kg": null,
  "cargo_description": null,
  "driver_name": "John Doe",
  "driver_phone": "08123456789",
  "notes": "Optional notes"
}

Response: 201 Created
{
  "data": {
    "id": 1,
    "queue_number": 1,
    "status": "waiting",
    "plate_number": "B 1234 ABC",
    "weight_kg": null,
    "cargo_description": null,
    "driver_name": "John Doe",
    "driver_phone": "08123456789",
    "vehicle": null,
    "location": { ... }
  }
}
```

**Get All Queues for Location**
```bash
GET /api/queues/location/{location_id}?per_page=15

Response: 200 OK
{
  "data": [ ... ],
  "current_page": 1,
  "total": 20,
  "last_page": 2
}
```

**Get Queue by ID**
```bash
GET /api/queues/{id}

Response: 200 OK
{ "data": { ... } }
```

**Update Queue Status**
```bash
PUT /api/queues/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "loading",
  "notes": "Started loading"
}

Response: 200 OK
{
  "data": {
    "id": 1,
    "status": "loading",
    "started_at": "2026-05-29T10:00:00Z",
    ...
  }
}
```

### Vehicle Management

**Scan Barcode**
```bash
POST /api/vehicles/scan
Authorization: Bearer {token}
Content-Type: application/json

{
  "barcode_code128": "VH00000001"
}

Response: 200 OK
{
  "data": {
    "id": 1,
    "plate_number": "B 1234 ABC",
    "barcode_code128": "VH00000001",
    "vehicle_type": { ... },
    "location": { ... }
  }
}
```

**Get Vehicle by Barcode**
```bash
GET /api/vehicles/{barcode_code128}

Response: 200 OK
{ "data": { ... } }
```

**Get Vehicles by Location**
```bash
GET /api/vehicles/location/{location_id}

Response: 200 OK
{ "data": [ ... ] }
```

### Location & Vehicle Types

```bash
GET /api/locations
GET /api/vehicle-types
```

## 📱 Queue Status Flow

```
waiting → loading → done
       ↘ cancelled
```

**Status Timestamps:**
- `created_at` - Queue dibuat
- `arrived_at` - Kendaraan tiba (set when status changes to anything except waiting)
- `started_at` - Mulai loading (status → loading)
- `completed_at` - Selesai/dibatalkan (status → done/cancelled)

## 🔐 Authentication & Authorization

**Roles:**
- **driver** - Create queue, view own queues, update profile
- **security** - Scan barcode, check-in vehicles, update queue status
- **admin** - Full access: manage queues, vehicles, locations, view all data

**Token-based Auth (Laravel Sanctum):**
```bash
# Include token in all authenticated requests
Authorization: Bearer {token}
```

## 📈 Reports

⚠️ **Coming Soon** - Reports module not yet implemented

Planned features:
- Wait time per kendaraan
- Throughput per staff
- Peak hours analysis
- Vehicle type breakdown

## 🐛 Known Issues & Fixes

### Fixed Issues (v1.1)

✅ **Bug #1** - Missing `barcode_code128` in Vehicle $fillable
- Fixed: Added to $fillable array

✅ **Bug #2** - No QueueHistory records on status update
- Fixed: Implemented QueueHistory logging in updateStatus()

✅ **Bug #3** - Vehicle creation allows data inconsistency
- Fixed: Added validation for existing vehicles

✅ **Bug #4** - Missing input validation in VehicleController::show()
- Fixed: Added Validator for barcode parameter

✅ **Bug #5** - Missing location_id validation in QueueController::index()
- Fixed: Added Location::findOrFail()

✅ **Bug #6** - Password confirmation required on register
- Fixed: Removed password_confirmation requirement

✅ **Bug #7** - Tonase (weight_kg) required on queue creation
- Fixed: weight_kg and cargo_description now nullable

✅ **Bug #8** - plate_number required on queue creation
- Fixed: plate_number optional, vehicle_id can be used as fallback

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/QueueControllerTest.php

# Test API endpoints with curl
curl -X POST http://43.134.37.14:8000/api/queues \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"location_id":1,"plate_number":"B 1234 ABC"}'
```

## 📦 Sample Data

Database seeded dengan:
- **3 Users:** driver@test.com, security@test.com, admin@test.com
- **3 Locations:** Warehouse A, B, C
- **5 Vehicle Types:** Roll, Sheet, Roll+Sheet, Waste, Sesetan
- **10 Vehicles:** VH00000001 - VH00000010 (dengan barcode Code128)
- **20 Queues:** Mixed statuses (waiting, loading, done, cancelled)

## 🚢 Deployment

### Server Requirements
- PHP 8.2+ with extensions: mbstring, xml, json, PDO
- Composer 2+
- SQLite or PostgreSQL
- Node.js 18+ (for asset compilation)

### Production Setup

```bash
# Clone & setup
git clone git@github.com:andrizpray/cargo-queue-system.git
cd cargo-queue-system
composer install --optimize-autoloader --no-dev

# Environment
cp .env.production .env
php artisan key:encrypt
php artisan migrate --force
php artisan db:seed --force

# Start services
php artisan serve --host=0.0.0.0 --port=8000 &
php artisan reverb:start --port=8080 &
```

### Using Supervisor (recommended for production)

```ini
[program:cargo-queue]
command=php /path/to/cargo-queue-system/artisan serve --host=0.0.0.0 --port=8000
directory=/path/to/cargo-queue-system
autostart=true
autorestart=true

[program:cargo-queue-reverb]
command=php /path/to/cargo-queue-system/artisan reverb:start --port=8080
directory=/path/to/cargo-queue-system
autostart=true
autorestart=true
```

## 📝 Environment Variables

```env
APP_NAME="Cargo Queue System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://43.134.37.14:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# For Production (PostgreSQL)
# DB_CONNECTION=pgsql
# DB_HOST=localhost
# DB_PORT=5432
# DB_DATABASE=cargo_queue
# DB_USERNAME=postgres
# DB_PASSWORD=secret

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=app_id
REVERB_APP_KEY=app_key
REVERB_APP_SECRET=app_secret
REVERB_HOST=43.134.37.14
REVERB_PORT=8080
```

## 🔄 Next Steps

- [x] Laravel backend with SQLite
- [x] Authentication (Sanctum)
- [x] Queue CRUD with status flow
- [x] Barcode Code128 scanning
- [x] Queue history audit trail
- [x] Real-time updates (Reverb WebSocket)
- [ ] Vue 3 admin dashboard
- [ ] WhatsApp notifications
- [ ] Rate limiting & API security
- [ ] Comprehensive test suite
- [ ] Production deployment with SSL

## 📚 Documentation

- [API Documentation](docs/API.md) ⚠️ Coming soon
- [Flutter Integration Guide](docs/FLUTTER.md) ⚠️ Coming soon
- [Deployment Guide](docs/DEPLOYMENT.md) ⚠️ Coming soon

## 🤝 Contributing

1. Create feature branch: `git checkout -b feature/your-feature`
2. Commit changes: `git commit -am 'Add feature'`
3. Push to branch: `git push origin feature/your-feature`
4. Open Pull Request

## 📄 License

MIT License - see LICENSE file for details

## 👤 Author

**Andriz** - [GitHub](https://github.com/andrizpray)

## 📞 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Contact: andriz@example.com

---

**Last Updated:** 2026-05-29
**Version:** 1.1.0
