#!/bin/bash

echo "Launching Chrome with remote debugging enabled..."
echo
echo "This will open Chrome with remote debugging on port 9222"
echo "Please log in to DealerTrack after Chrome launches"
echo
echo "Press Ctrl+C to close this window after you're done testing"
echo

# Determine OS and set Chrome path
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    CHROME="google-chrome"
else
    echo "Unsupported OS. Please launch Chrome manually with:"
    echo "chrome --remote-debugging-port=9222 --user-data-dir=~/ChromeDebugProfile"
    exit 1
fi

# Launch Chrome with remote debugging
"$CHROME" --remote-debugging-port=9222 --user-data-dir="$HOME/ChromeDebugProfile" &

echo
echo "Chrome launched with remote debugging enabled on port 9222"
echo
echo "Now you can run the test scripts:"
echo "node test-puppeteer.js"
echo "node test-dealertrack-comprehensive.js"
echo
echo "Remember to log in to DealerTrack before running the tests!"
echo

read -p "Press Enter to close this window..."
