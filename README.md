# Cargo Queue System

**Laravel 13 + SQLite + Flutter** - Sistem antrian kendaraan untuk muat/bongkar di pabrik/warehouse.

## 📋 Overview

Cargo Queue System adalah aplikasi manajemen antrian kendaraan yang dirancang untuk warehouse/pabrik dengan fitur:
- Real-time queue tracking
- Barcode scanning (Code128)
- Multi-location support
- Role-based access (Security, Admin Ekspedisi, Admin Utama)
- Comprehensive reporting

## 🏗️ Architecture

```
Backend: Laravel 13 + PostgreSQL/SQLite
  ↓
API REST (JSON)
  ↓
Mobile App: Flutter (iOS + Android)
  ↓
Dashboard Web: Vue 3 (admin panel)
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- SQLite (or PostgreSQL)
- Node.js 18+

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

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Start dev server
php artisan serve
```

Server akan berjalan di `http://localhost:8000`

## 📊 Database Schema

### Tables

| Table | Purpose |
|-------|---------|
| `locations` | Warehouse/pabrik locations |
| `vehicle_types` | Roll, Sheet, Roll+Sheet, Waste, Sesetan |
| `vehicles` | Kendaraan dengan barcode Code128 |
| `queues` | Antrian kendaraan (waiting, loading, done, cancelled) |
| `queue_history` | Audit trail untuk setiap perubahan status |

### Relationships

```
Location
  ├─ hasMany Vehicles
  └─ hasMany Queues

VehicleType
  └─ hasMany Vehicles

Vehicle
  ├─ belongsTo VehicleType
  ├─ belongsTo Location
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

### Queue Management

**Create Queue**
```bash
POST /api/queues
Content-Type: application/json

{
  "location_id": 1,
  "vehicle_type_id": 1,
  "plate_number": "B 1234 ABC",
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
    "vehicle": { ... },
    "location": { ... }
  }
}
```

**Get Queue by ID**
```bash
GET /api/queues/{id}

Response: 200 OK
{
  "data": {
    "id": 1,
    "queue_number": 1,
    "status": "waiting",
    "vehicle": { ... },
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

**Update Queue Status**
```bash
PUT /api/queues/{id}/status
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
    "started_at": "2026-05-28T10:00:00Z",
    ...
  }
}
```

### Vehicle Management

**Scan Barcode**
```bash
POST /api/vehicles/scan
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
{
  "data": {
    "id": 1,
    "plate_number": "B 1234 ABC",
    "barcode_code128": "VH00000001",
    ...
  }
}
```

## 📱 Queue Status Flow

```
waiting → loading → done
       ↘ cancelled
```

**Status Timestamps:**
- `created_at` - Queue dibuat
- `arrived_at` - Kendaraan tiba
- `started_at` - Mulai loading (status → loading)
- `completed_at` - Selesai/dibatalkan (status → done/cancelled)

## 🔐 Authentication & Authorization

**Roles:**
- **Security** - Check-in kendaraan, scan barcode
- **Admin Ekspedisi** - Panggil antrian, update status
- **Admin Utama** - Dashboard overview, reports, manage staff

*(Authentication akan diimplementasikan di fase berikutnya)*

## 📈 Reports

Fitur reporting mencakup:
- Wait time per kendaraan
- Throughput per staff
- Peak hours analysis
- Vehicle type breakdown
- Status distribution (parkir, masuk, muat, selesai, cancel)

## 🐛 Known Issues & Fixes

### Fixed Issues (v1.0)

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

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/QueueControllerTest.php

# Test API endpoints with curl
curl -X POST http://localhost:8000/api/queues \
  -H "Content-Type: application/json" \
  -d '{"location_id":1,"vehicle_type_id":1,"plate_number":"B 1234 ABC"}'
```

## 📦 Sample Data

Database seeded dengan:
- **3 Locations:** Warehouse A, B, C
- **5 Vehicle Types:** Roll, Sheet, Roll+Sheet, Waste, Sesetan
- **10 Vehicles:** VH00000001 - VH00000010 (dengan barcode Code128)
- **20 Queues:** Mixed statuses (waiting, loading, done, cancelled)

## 🚢 Deployment

### Local Server (Pabrik)

```bash
# Setup on Ubuntu 24.04
sudo apt update && sudo apt install -y php8.2 php8.2-sqlite php8.2-curl composer

# Clone & setup
git clone git@github.com:andrizpray/cargo-queue-system.git
cd cargo-queue-system
composer install
php artisan migrate --seed

# Run with Supervisor or systemd
php artisan serve --host=0.0.0.0 --port=8000
```

### Cloud Server (Testing)

```bash
# Deploy to DigitalOcean/AWS/etc
# Use Laravel Forge or manual setup
```

## 📝 Environment Variables

```env
APP_NAME="Cargo Queue System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Or PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=cargo_queue
DB_USERNAME=postgres
DB_PASSWORD=secret
```

## 🔄 Next Steps

- [ ] Flutter mobile app (barcode scanner + queue tracking)
- [ ] Vue 3 admin dashboard
- [ ] Real-time updates (Redis + WebSocket)
- [ ] WhatsApp notifications
- [ ] Authentication & authorization
- [ ] Rate limiting & API security
- [ ] Comprehensive test suite
- [ ] Production deployment

## 📚 Documentation

- [API Documentation](docs/API.md) *(coming soon)*
- [Flutter Integration Guide](docs/FLUTTER.md) *(coming soon)*
- [Deployment Guide](docs/DEPLOYMENT.md) *(coming soon)*

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

**Last Updated:** 2026-05-28
**Version:** 1.0.0 (Spike Complete)
