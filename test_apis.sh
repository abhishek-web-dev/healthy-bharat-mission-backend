#!/bin/bash
BASE_URL="http://localhost:8000/api"

echo "=== MODULE 6 API TESTS ==="

echo "1. Health Library (Articles)"
curl -s -X GET "$BASE_URL/articles" | jq '.data | length'

echo "2. Programs"
curl -s -X GET "$BASE_URL/programs" | jq '.data | length'

echo "3. Health Conditions"
curl -s -X GET "$BASE_URL/health-conditions" | jq '.data | length'

echo "4. Success Stories"
curl -s -X GET "$BASE_URL/success-stories" | jq '.data | length'

echo "5. Contact Form POST"
curl -s -X POST "$BASE_URL/contact" -H "Content-Type: application/json" -d '{"name":"Test User","email":"test@example.com","phone":"9876543210","subject":"programs","message":"This is a test message"}' | jq '.success'
