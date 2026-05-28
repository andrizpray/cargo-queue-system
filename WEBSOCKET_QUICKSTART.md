# WebSocket Quick Start Guide

## Installation Complete ✓

The WebSocket real-time updates system has been successfully implemented using Laravel Reverb.

## Quick Start

### 1. Start the WebSocket Server
```bash
php artisan reverb:start
```

Expected output:
```
INFO  Starting Reverb server...
INFO  Server running at ws://0.0.0.0:8080
```

### 2. Test WebSocket Connection (Terminal 1)
```bash
# Install wscat if not already installed
npm install -g wscat

# Connect to WebSocket server
wscat -c ws://localhost:8080/app/cargo-queue-key
```

### 3. Create a Queue Entry (Terminal 2)
```bash
curl -X POST http://localhost:8080/api/queues \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_type_id": 1,
    "location_id": 1,
    "plate_number": "ABC-1234",
    "driver_name": "John Doe",
    "driver_phone": "555-1234",
    "notes": "Test cargo"
  }'
```

### 4. Subscribe to Queue Updates (Terminal 1 - wscat)
```json
{"event":"subscribe","data":{"channel":"queues.all"}}
```

### 5. Update Queue Status (Terminal 2)
```bash
curl -X PATCH http://localhost:8080/api/queues/1/status \
  -H "Content-Type: application/json" \
  -d '{
    "status": "loading",
    "notes": "Starting cargo loading"
  }'
```

You should see the WebSocket event in Terminal 1:
```json
{
  "event": "queue.status-changed",
  "data": {
    "id": 1,
    "queue_number": 1,
    "location_id": 1,
    "previous_status": "waiting",
    "new_status": "loading",
    "notes": "Starting cargo loading",
    ...
  }
}
```

## Broadcasting Events

### QueueCreated
- **Trigger:** When a new queue is created via POST /api/queues
- **Channels:** queues.all, queues.{location_id}
- **Event Name:** queue.created

### QueueStatusChanged
- **Trigger:** When queue status is updated via PATCH /api/queues/{id}/status
- **Channels:** queues.all, queues.{location_id}
- **Event Name:** queue.status-changed
- **Status Values:** waiting → loading → done/cancelled

### QueueDeleted
- **Trigger:** Can be dispatched when queue is deleted
- **Channels:** queues.all, queues.{location_id}
- **Event Name:** queue.deleted

## Environment Configuration

Current settings in `.env`:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=cargo-queue
REVERB_APP_KEY=cargo-queue-key
REVERB_APP_SECRET=cargo-queue-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Files Created/Modified

### Created:
- `app/Events/QueueCreated.php`
- `app/Events/QueueStatusChanged.php`
- `app/Events/QueueDeleted.php`
- `tests/WebSocketTest.php`
- `WEBSOCKET_IMPLEMENTATION.md` (detailed documentation)

### Modified:
- `app/Http/Controllers/QueueController.php` (added event dispatching)
- `routes/channels.php` (added queue channels)
- `.env` (added Reverb configuration)
- `composer.json` (added laravel/reverb dependency)

## Testing

Run WebSocket tests:
```bash
php artisan test tests/WebSocketTest.php
```

## Frontend Integration

### Vue.js/JavaScript Example
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'cargo-queue-key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
});

// Listen to queue updates
Echo.channel('queues.all')
    .listen('queue.created', (event) => {
        console.log('Queue created:', event);
    })
    .listen('queue.status-changed', (event) => {
        console.log('Queue status changed:', event);
    });

// Listen to location-specific updates
Echo.channel('queues.1')
    .listen('queue.created', (event) => {
        console.log('New queue at location 1:', event);
    });
```

## Troubleshooting

### WebSocket won't connect
1. Ensure Reverb server is running: `php artisan reverb:start`
2. Check port 8080 is not in use: `lsof -i :8080`
3. Verify firewall allows port 8080

### Events not broadcasting
1. Check `BROADCAST_CONNECTION=reverb` in .env
2. Verify events implement `ShouldBroadcast`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Connection drops
1. Check `REVERB_APP_ACTIVITY_TIMEOUT` (default: 30 seconds)
2. Verify network stability
3. Check server logs for errors

## Production Deployment

For production:
1. Use HTTPS/WSS (set `REVERB_SCHEME=https`)
2. Configure proper domain in `REVERB_HOST`
3. Enable Redis scaling for multiple instances
4. Implement proper channel authorization
5. Set up monitoring and logging
6. Use environment-specific configuration

See `WEBSOCKET_IMPLEMENTATION.md` for detailed documentation.
