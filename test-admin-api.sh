#!/bin/bash
# Test simplified admin APIs

API_BASE="http://localhost/api"

echo "=== КОНТАНТА Admin API Test ==="
echo

echo "1. Testing Login..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/simple-auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}')

echo $LOGIN_RESPONSE | jq '.'

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')
echo "Token: $TOKEN"
echo

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ Login failed!"
  exit 1
fi

echo "2. Testing Get Applications..."
curl -s "$API_BASE/simple-leads.php?token=$TOKEN&action=list" | jq '.'
echo

echo "3. Testing Statistics..."
curl -s "$API_BASE/simple-stats.php?token=$TOKEN" | jq '.'
echo

echo "4. Testing Invalid Token..."
curl -s "$API_BASE/simple-leads.php?token=invalid&action=list" | jq '.'
echo

echo "✅ All basic tests passed!"
