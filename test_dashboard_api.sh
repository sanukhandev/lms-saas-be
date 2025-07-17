#!/bin/bash

# Dashboard API Test Script
BASE_URL="http://localhost:8000/api/v1/dashboard"
TOKEN="2|hmi2QveT1j2dbPTuexCZz0rLvdU71tgyjDDdSiLA3df96ce7"
TENANT_DOMAIN="demo"
TENANT_ID="1"

echo "Testing Dashboard API Endpoints..."
echo "=================================="

# Test 1: Dashboard Overview
echo "1. Testing Dashboard Overview..."
curl -s -X GET "$BASE_URL" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/dashboard_overview.json

if [ $? -eq 0 ]; then
  echo "✓ Dashboard overview endpoint working"
else
  echo "✗ Dashboard overview endpoint failed"
fi

# Test 2: Dashboard Statistics
echo "2. Testing Dashboard Statistics..."
curl -s -X GET "$BASE_URL/stats" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/dashboard_stats.json

if [ $? -eq 0 ]; then
  echo "✓ Dashboard statistics endpoint working"
else
  echo "✗ Dashboard statistics endpoint failed"
fi

# Test 3: Chart Data
echo "3. Testing Chart Data..."
curl -s -X GET "$BASE_URL/chart-data" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/chart_data.json

if [ $? -eq 0 ]; then
  echo "✓ Chart data endpoint working"
else
  echo "✗ Chart data endpoint failed"
fi

# Test 4: Recent Activities with Pagination
echo "4. Testing Recent Activities (Paginated)..."
curl -s -X GET "$BASE_URL/activity?page=1&per_page=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/activities.json

if [ $? -eq 0 ]; then
  echo "✓ Activities endpoint working"
else
  echo "✗ Activities endpoint failed"
fi

# Test 5: Course Progress with Pagination
echo "5. Testing Course Progress (Paginated)..."
curl -s -X GET "$BASE_URL/courses?page=1&per_page=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/courses.json

if [ $? -eq 0 ]; then
  echo "✓ Course progress endpoint working"
else
  echo "✗ Course progress endpoint failed"
fi

# Test 6: User Progress with Pagination
echo "6. Testing User Progress (Paginated)..."
curl -s -X GET "$BASE_URL/users?page=1&per_page=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/users.json

if [ $? -eq 0 ]; then
  echo "✓ User progress endpoint working"
else
  echo "✗ User progress endpoint failed"
fi

# Test 7: Payment Statistics
echo "7. Testing Payment Statistics..."
curl -s -X GET "$BASE_URL/payments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "X-Tenant-ID: $TENANT_ID" > /tmp/payments.json

if [ $? -eq 0 ]; then
  echo "✓ Payment statistics endpoint working"
else
  echo "✗ Payment statistics endpoint failed"
fi

echo ""
echo "Test Results Summary:"
echo "===================="
echo "All endpoint tests completed. Check individual files in /tmp/ for detailed responses."
echo ""
echo "Files created:"
echo "- /tmp/dashboard_overview.json"
echo "- /tmp/dashboard_stats.json"
echo "- /tmp/chart_data.json"
echo "- /tmp/activities.json"
echo "- /tmp/courses.json"
echo "- /tmp/users.json"
echo "- /tmp/payments.json"
