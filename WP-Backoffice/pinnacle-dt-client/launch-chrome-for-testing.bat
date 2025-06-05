@echo off
echo Launching Chrome with remote debugging enabled...
echo.
echo This will open Chrome with remote debugging on port 9222
echo Please log in to DealerTrack after Chrome launches
echo.
echo Press Ctrl+C to close this window after you're done testing
echo.

start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222 --user-data-dir="%USERPROFILE%\ChromeDebugProfile"

echo Chrome launched with remote debugging enabled on port 9222
echo.
echo Now you can run the test scripts:
echo node test-puppeteer.js
echo node test-dealertrack-comprehensive.js
echo.
echo Remember to log in to DealerTrack before running the tests!

pause
