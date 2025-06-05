@echo off
echo Running all DealerTrack Puppeteer tests...
echo.
echo Make sure Chrome is running with remote debugging enabled
echo and you are logged into DealerTrack before continuing.
echo.
echo Press any key to start the tests...
pause > nul

echo.
echo Running basic test script (test-puppeteer.js)...
echo.
node test-puppeteer.js

echo.
echo Basic test completed.
echo.
echo Press any key to continue to the comprehensive test...
pause > nul

echo.
echo Running comprehensive test script (test-dealertrack-comprehensive.js)...
echo.
node test-dealertrack-comprehensive.js

echo.
echo Comprehensive test completed.
echo.
echo Press any key to continue to the declined applications test...
pause > nul

echo.
echo Running declined applications test script (test-declined-applications.js)...
echo.
node test-declined-applications.js

echo.
echo Declined applications test completed.
echo.
echo Press any key to continue to the credit application test...
pause > nul

echo.
echo Running credit application test script (test-credit-application.js)...
echo.
node test-credit-application.js

echo.
echo All tests completed.
echo.
echo Check the screenshots directory for test results.
echo.
pause
