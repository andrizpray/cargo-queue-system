# WebSocket Real-Time Updates - Implementation Guide

## Overview
This document describes the WebSocket real-time updates implementation for the cargo queue system using Laravel Reverb.

## Architecture

### Components
1. **Laravel Reverb** - WebSocket server for real-time communication
2. **Broadcasting Events** - Three main events for queue updates
3. **Broadcasting Channels** - Two channel types for message routing
4. **QueueController** - Updated to dispatch events on queue changes

## Broadcasting Events

### 1. QueueCreated
**File:** `app/Events/QueueCreated.php`

Dispatched when a new queue entry is created.

**Channels:**
- `queues.all` - Global queue updates
- `queues.{location_id}` - Location-specific updates

**Broadcast Data:**
```json
{
  "id": 1,
  "queue_number": 1,
  "location_id": 1,
  "vehicle_id": 1,
  "status": "waiting",
  "arrived_at": "2026-05-28T12:00:00Z",
  "vehicle": { ... },
  "location": { ... }
}
```

### 2. QueueStatusChanged
**File:** `app/Events/QueueStatusChanged.php`

Dispatched when a queue status is updated (waiting → loading → done/cancelled).

**Channels:**
- `queues.all` - Global queue updates
- `queues.{location_id}` - Location-specific updates

**Broadcast Data:**
```json
{
  "id": 1,
  "queue_number": 1,
  "location_id": 1,
  "vehicle_id": 1,
  "previous_status": "waiting",
  "new_status": "loading",
  "status": "loading",
  "started_at": "2026-05-28T12:05:00Z",
  "completed_at": null,
  "notes": "Starting cargo loading",
  "vehicle": { ... },
  "location": { ... }
}
```

### 3. QueueDeleted
**File:** `app/Events/QueueDeleted.php`

Dispatched when a queue entry is deleted.

**Channels:**
- `queues.all` - Global queue updates
- `queues.{location_id}` - Location-specific updates

**Broadcast Data:**
```json
{
  "id": 1,
  "queue_number": 1,
  "location_id": 1
}
```

## Broadcasting Channels

### Channel Configuration
**File:** `routes/channels.php`

```php
// Global queue updates - accessible to all authenticated users
Broadcast::channel('queues.all', function ($user) {
    return true;
});

// Location-specific updates - accessible to all authenticated users
Broadcast::channel('queues.{location_id}', function ($user, $location_id) {
    return true;
});
```

## Configuration

### Environment Variables
**File:** `.env`

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=cargo-queue
REVERB_APP_KEY=cargo-queue-key
REVERB_APP_SECRET=cargo-queue-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Reverb Server Configuration
**File:** `config/reverb.php`

- Server Host: `0.0.0.0` (listen on all interfaces)
- Server Port: `8080` (configurable via REVERB_SERVER_PORT)
- Max Request Size: `10,000` bytes
- Ping Interval: `60` seconds
- Activity Timeout: `30` seconds

## Running the WebSocket Server

### Start Reverb Server
```bash
php artisan reverb:start
```

### With Debug Output
```bash
php artisan reverb:start --debug
```

### With Custom Host/Port
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## Testing WebSocket Connections

### Using wscat (Node.js)
```bash
# Install wscat globally
npm install -g wscat

# Connect to WebSocket server
wscat -c ws://localhost:8080/app/cargo-queue-key

# Subscribe to channel
{"event":"subscribe","data":{"channel":"queues.all"}}

# Subscribe to location-specific channel
{"event":"subscribe","data":{"channel":"queues.1"}}
```

### Using curl (HTTP Upgrade)
```bash
curl -i -N -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Key: SGVsbG8sIHdvcmxkIQ==" \
  -H "Sec-WebSocket-Version: 13" \
  http://localhost:8080/app/cargo-queue-key
```

### Using PHP WebSocket Client
```php
$client = new WebSocket('ws://localhost:8080/app/cargo-queue-key');
$client->connect();

// Subscribe to channel
$client->send(json_encode([
    'event' => 'subscribe',
    'data' => ['channel' => 'queues.all']
]));

// Listen for messages
while ($message = $client->receive()) {
    echo $message . PHP_EOL;
}
```

## API Integration

### Creating a Queue (Triggers QueueCreated Event)
```bash
POST /api/queues
Content-Type: application/json

{
  "vehicle_type_id": 1,
  "location_id": 1,
  "plate_number": "ABC-1234",
  "driver_name": "John Doe",
  "driver_phone": "555-1234",
  "notes": "Fragile cargo"
}
```

**WebSocket Event Broadcast:**
```json
{
  "event": "queue.created",
  "channel": "queues.all",
  "data": { ... }
}
```

### Updating Queue Status (Triggers QueueStatusChanged Event)
```bash
PATCH /api/queues/1/status
Content-Type: application/json

{
  "status": "loading",
  "notes": "Starting cargo loading"
}
```

**WebSocket Event Broadcast:**
```json
{
  "event": "queue.status-changed",
  "channel": "queues.all",
  "data": { ... }
}
```

## Client-Side Implementation

### JavaScript/Vue.js Example
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'cargo-queue-key',
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Listen to global queue updates
Echo.channel('queues.all')
    .listen('queue.created', (event) => {
        console.log('New queue created:', event);
        // Update UI
    })
    .listen('queue.status-changed', (event) => {
        console.log('Queue status changed:', event);
        // Update UI
    })
    .listen('queue.deleted', (event) => {
        console.log('Queue deleted:', event);
        // Update UI
    });

// Listen to location-specific updates
Echo.channel('queues.1')
    .listen('queue.created', (event) => {
        console.log('New queue at location 1:', event);
    });
```

## Troubleshooting

### WebSocket Connection Issues
1. Ensure Reverb server is running: `php artisan reverb:start`
2. Check firewall allows port 8080
3. Verify REVERB_HOST and REVERB_PORT in .env
4. Check browser console for connection errors

### Events Not Broadcasting
1. Verify `BROADCAST_CONNECTION=reverb` in .env
2. Check that events implement `ShouldBroadcast`
3. Verify channel authorization in `routes/channels.php`
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Performance Optimization
1. Enable Redis scaling for multiple server instances
2. Adjust `max_connections` based on expected load
3. Monitor memory usage with `php artisan reverb:start --debug`
4. Use connection pooling for database queries in events

## Security Considerations

1. **Channel Authorization** - Implement proper authorization logic in `routes/channels.php`
2. **Rate Limiting** - Enable rate limiting in `config/reverb.php`
3. **Message Validation** - Validate all broadcast data before sending
4. **TLS/SSL** - Use HTTPS/WSS in production
5. **CORS** - Configure allowed origins in `config/reverb.php`

## Files Modified/Created

### Created Files
- `app/Events/QueueCreated.php`
- `app/Events/QueueStatusChanged.php`
- `app/Events/QueueDeleted.php`
- `tests/WebSocketTest.php`

### Modified Files
- `app/Http/Controllers/QueueController.php` - Added event dispatching
- `routes/channels.php` - Added queue broadcasting channels
- `.env` - Added Reverb configuration
- `composer.json` - Added laravel/reverb dependency

### Configuration Files
- `config/broadcasting.php` - Broadcasting configuration
- `config/reverb.php` - Reverb server configuration

## Next Steps

1. Install frontend dependencies: `npm install laravel-echo pusher-js`
2. Configure Echo in your frontend application
3. Subscribe to channels in your Vue/React components
4. Test with WebSocket client (wscat or browser DevTools)
5. Deploy Reverb server to production environment
