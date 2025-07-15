#!/bin/bash

echo "=== LMS Authentication System Test ==="
echo ""

echo "1. Testing CORS preflight request..."
curl -X OPTIONS "http://localhost:8000/api/v1/auth/login" \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -s -o /dev/null -w "Status: %{http_code}\n"

echo ""
echo "2. Testing login with tenant admin..."
response=$(curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Origin: http://localhost:5173" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@acme-university.com", "password": "password123"}' \
  -s)

echo "Response: $response"
echo ""

echo "3. Testing login with super admin..."
response=$(curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Origin: http://localhost:5173" \
  -H "Content-Type: application/json" \
  -d '{"email": "superadmin@lms.com", "password": "password123"}' \
  -s)

echo "Response: $response"
echo ""

echo "4. Testing basic API endpoint..."
curl -X GET "http://localhost:8000/api/test" \
  -H "Origin: http://localhost:5173" \
  -s

echo ""
echo ""
echo "=== Test Complete ==="
echo ""
echo "Frontend should now be able to:"
echo "1. Make API requests without CORS errors"
echo "2. Login with tenant admin: admin@acme-university.com / password123"
echo "3. Login with super admin: superadmin@lms.com / password123"
echo "4. Access protected routes after authentication"
echo ""
echo "Start your frontend with: npm run dev"
echo "Navigate to: http://localhost:5173/sign-in"
