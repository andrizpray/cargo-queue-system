#!/bin/bash

# WhatsApp Notification System Test Script
# This script tests all the WhatsApp notification endpoints

BASE_URL="http://127.0.0.1:8000/api"
TOKEN=""

echo "=========================================="
echo "WhatsApp Notification System Test Script"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print success
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Function to print error
error() {
    echo -e "${RED}✗${NC} $1"
}

# Test 1: Register a driver user
echo "=== Test 1: Register Driver User ==="
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
    -H "Content-Type: application/json" \
    -d '{"name":"Test Driver","email":"driver@test.com","password":"password123","role":"driver"}')
echo "Response: $RESPONSE"
TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
if [ -n "$TOKEN" ]; then
    success "Driver registered with token: ${TOKEN:0:20}..."
else
    error "Failed to register driver"
fi
echo ""

# Test 2: Register an admin user
echo "=== Test 2: Register Admin User ==="
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
    -H "Content-Type: application/json" \
    -d '{"name":"Test Admin","email":"admin@test.com","password":"password123","role":"admin"}')
echo "Response: $RESPONSE"
ADMIN_TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
if [ -n "$ADMIN_TOKEN" ]; then
    success "Admin registered with token: ${ADMIN_TOKEN:0:20}..."
else
    error "Failed to register admin"
fi
echo ""

# Test 3: Update driver WhatsApp phone
echo "=== Test 3: Update Driver WhatsApp Phone ==="
DRIVER_ID=$(curl -s "$BASE_URL/auth/me" -H "Authorization: Bearer $TOKEN" | grep -o '"id":[0-9]*' | cut -d':' -f2)
RESPONSE=$(curl -s -X PUT "$BASE_URL/users/$DRIVER_ID/phone" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"whatsapp_phone":"+6281234567890","whatsapp_notifications_enabled":true}')
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "+6281234567890"; then
    success "WhatsApp phone updated successfully"
else
    error "Failed to update WhatsApp phone"
fi
echo ""

# Test 4: Update admin WhatsApp phone
echo "=== Test 4: Update Admin WhatsApp Phone ==="
ADMIN_ID=$(curl -s "$BASE_URL/auth/me" -H "Authorization: Bearer $ADMIN_TOKEN" | grep -o '"id":[0-9]*' | cut -d':' -f2)
RESPONSE=$(curl -s -X PUT "$BASE_URL/users/$ADMIN_ID/phone" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"whatsapp_phone":"+6289876543210","whatsapp_notifications_enabled":true}')
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "+6289876543210"; then
    success "Admin WhatsApp phone updated successfully"
else
    error "Failed to update admin WhatsApp phone"
fi
echo ""

# Test 5: Get user profile
echo "=== Test 5: Get User Profile ==="
RESPONSE=$(curl -s -X GET "$BASE_URL/users/$DRIVER_ID" \
    -H "Authorization: Bearer $TOKEN")
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "whatsapp_phone"; then
    success "User profile retrieved with WhatsApp settings"
else
    error "Failed to get user profile"
fi
echo ""

# Test 6: Get notification settings for a location
echo "=== Test 6: Get Notification Settings for Location ==="
RESPONSE=$(curl -s -X GET "$BASE_URL/locations/1/notification-settings" \
    -H "Authorization: Bearer $ADMIN_TOKEN")
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "notify_admin_on_queue_created"; then
    success "Notification settings retrieved"
else
    error "Failed to get notification settings"
fi
echo ""

# Test 7: Update notification settings
echo "=== Test 7: Update Notification Settings ==="
RESPONSE=$(curl -s -X PUT "$BASE_URL/locations/1/notification-settings" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"notify_admin_on_queue_created":true,"send_reminders":true,"reminder_minutes_before":5}')
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "reminder_minutes_before"; then
    success "Notification settings updated"
else
    error "Failed to update notification settings"
fi
echo ""

# Test 8: Create a queue (this should trigger WhatsApp notification to admin)
echo "=== Test 8: Create a Queue ==="
RESPONSE=$(curl -s -X POST "$BASE_URL/queues" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"vehicle_type_id":1,"location_id":1,"plate_number":"B 1234 ABC","driver_name":"Test Driver","notes":"Test queue"}')
echo "Response: $RESPONSE"
QUEUE_ID=$(echo $RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
if [ -n "$QUEUE_ID" ]; then
    success "Queue created with ID: $QUEUE_ID"
else
    error "Failed to create queue"
fi
echo ""

# Test 9: Update queue status (this should trigger WhatsApp notification to driver)
echo "=== Test 9: Update Queue Status to Loading ==="
if [ -n "$QUEUE_ID" ]; then
    RESPONSE=$(curl -s -X PUT "$BASE_URL/queues/$QUEUE_ID/status" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"status":"loading","notes":"Please proceed to loading area"}')
    echo "Response: $RESPONSE"
    if echo "$RESPONSE" | grep -q "loading"; then
        success "Queue status updated to loading"
    else
        error "Failed to update queue status"
    fi
fi
echo ""

# Test 10: Get notification history
echo "=== Test 10: Get Notification History ==="
RESPONSE=$(curl -s -X GET "$BASE_URL/notifications" \
    -H "Authorization: Bearer $ADMIN_TOKEN")
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "notification_type"; then
    success "Notification history retrieved"
else
    error "Failed to get notification history"
fi
echo ""

# Test 11: Get notification stats
echo "=== Test 11: Get Notification Statistics ==="
RESPONSE=$(curl -s -X GET "$BASE_URL/notifications/stats" \
    -H "Authorization: Bearer $ADMIN_TOKEN")
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q "total"; then
    success "Notification statistics retrieved"
else
    error "Failed to get notification statistics"
fi
echo ""

# Test 12: Update queue status to done
echo "=== Test 12: Update Queue Status to Done ==="
if [ -n "$QUEUE_ID" ]; then
    RESPONSE=$(curl -s -X PUT "$BASE_URL/queues/$QUEUE_ID/status" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"status":"done","notes":"Queue completed"}')
    echo "Response: $RESPONSE"
    if echo "$RESPONSE" | grep -q "done"; then
        success "Queue status updated to done"
    else
        error "Failed to update queue status"
    fi
fi
echo ""

echo "=========================================="
echo "Test Summary Complete"
echo "=========================================="
echo ""
echo "Note: WhatsApp notifications are DISABLED by default (WHATSAPP_ENABLED=false)."
echo "To enable real WhatsApp notifications:"
echo "1. Set WHATSAPP_ENABLED=true in .env"
echo "2. Configure your Twilio credentials in .env"
echo "3. Restart the server"
echo ""
echo "To send queue reminders manually, run:"
echo "  php artisan queues:send-reminders"
