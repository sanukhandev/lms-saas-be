#!/bin/bash

# Test script for dashboard endpoints

echo "Testing Dashboard Endpoints..."
echo "================================"

BASE_URL="http://localhost:8000/api/v1"

# Test basic dashboard endpoint
echo "1. Testing main dashboard endpoint..."
curl -s -X GET "$BASE_URL/dashboard" \
  -H "Authorization: Bearer test_token" \
  -H "Content-Type: application/json" \
  | head -20

echo -e "\n\n2. Testing new overview endpoint..."
curl -s -X GET "$BASE_URL/dashboard/overview" \
  -H "Authorization: Bearer test_token" \
  -H "Content-Type: application/json" \
  | head -20

echo -e "\n\n3. Testing stats endpoint..."
curl -s -X GET "$BASE_URL/dashboard/stats" \
  -H "Authorization: Bearer test_token" \
  -H "Content-Type: application/json" \
  | head -20

echo -e "\n\n4. Testing chart data endpoint..."
curl -s -X GET "$BASE_URL/dashboard/chart-data" \
  -H "Authorization: Bearer test_token" \
  -H "Content-Type: application/json" \
  | head -20

echo -e "\n\n5. Testing activity endpoint..."
curl -s -X GET "$BASE_URL/dashboard/activity" \
  -H "Authorization: Bearer test_token" \
  -H "Content-Type: application/json" \
  | head -20

echo -e "\n\nDone!"
