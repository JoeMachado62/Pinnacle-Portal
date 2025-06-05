#!/bin/bash

echo "Running all DealerTrack Puppeteer tests..."
echo
echo "Make sure Chrome is running with remote debugging enabled"
echo "and you are logged into DealerTrack before continuing."
echo
echo "Press Enter to start the tests..."
read

echo
echo "Running basic test script (test-puppeteer.js)..."
echo
node test-puppeteer.js

echo
echo "Basic test completed."
echo
echo "Press Enter to continue to the comprehensive test..."
read

echo
echo "Running comprehensive test script (test-dealertrack-comprehensive.js)..."
echo
node test-dealertrack-comprehensive.js

echo
echo "Comprehensive test completed."
echo
echo "Press Enter to continue to the declined applications test..."
read

echo
echo "Running declined applications test script (test-declined-applications.js)..."
echo
node test-declined-applications.js

echo
echo "Declined applications test completed."
echo
echo "Press Enter to continue to the credit application test..."
read

echo
echo "Running credit application test script (test-credit-application.js)..."
echo
node test-credit-application.js

echo
echo "All tests completed."
echo
echo "Check the screenshots directory for test results."
echo
read -p "Press Enter to exit..."
