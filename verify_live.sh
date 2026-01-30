#!/bin/bash

# Base URL
URL="https://navajowhite-marten-733773.hostingersite.com/api"

# Login (Device Based)
echo "Logging in with Device..."
RESPONSE=$(curl -s -X POST $URL/device-login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{
        "device_id": "device_123456789", 
        "mac_address": "AA:BB:CC:DD:EE:FF", 
        "model": "Pixel 9 Pro", 
        "location": "37.7749,-122.4194"
    }')

TOKEN=$(echo $RESPONSE | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "Login failed: $RESPONSE"
    exit 1
fi

echo "Token received: $TOKEN"

# Create dummy file
echo "dummy image content" > test_image.txt

# Upload Media
echo "Uploading Media..."
curl -s -X POST $URL/upload-media \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" \
    -F "file=@test_image.txt"

echo -e "\n"

# Backup Data
echo "Backing up Data..."
curl -s -X POST $URL/backup-data \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"type": "contacts", "data": "{\"contact1\": \"123456789\"}"}'

echo -e "\n"

# Cleanup
rm test_image.txt
echo "Verification Complete."
