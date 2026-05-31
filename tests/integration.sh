#!/bin/bash

# Simple integration test script
# Make sure the docker containers are running before executing.

HOST="http://localhost:8080"
echo "Starting Integration Tests against $HOST..."

# Test 1: Redirect unauthenticated user from protected page (/map)
echo -n "Test 1: Unauthenticated access to /map -> "
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" -I $HOST/map)

if [ "$HTTP_STATUS" -eq 302 ]; then
    echo "PASSED (Redirected to login)"
else
    echo "FAILED (Expected 302, got $HTTP_STATUS)"
    exit 1
fi

# Test 2: Ensure login page renders 200 OK
echo -n "Test 2: Access /login page -> "
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" -I $HOST/login)

if [ "$HTTP_STATUS" -eq 200 ]; then
    echo "PASSED (200 OK)"
else
    echo "FAILED (Expected 200, got $HTTP_STATUS)"
    exit 1
fi

# Test 3: Ensure 404 page renders for unknown routes
echo -n "Test 3: Access unknown route -> "
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" -I $HOST/unknown-page-123)

if [ "$HTTP_STATUS" -eq 404 ]; then
    echo "PASSED (404 Not Found)"
else
    echo "FAILED (Expected 404, got $HTTP_STATUS)"
    exit 1
fi

echo "All integration tests completed successfully!"
exit 0
