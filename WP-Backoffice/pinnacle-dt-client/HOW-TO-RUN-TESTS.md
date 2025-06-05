# How to Run the DealerTrack Puppeteer Tests

This guide provides simple, direct instructions for running the tests.

## Step 1: Launch Chrome with Remote Debugging

Double-click the file:
```
launch-chrome-for-testing.bat
```

This will open a new Chrome window. In this window:
1. Navigate to DealerTrack
2. Log in with your credentials
3. Go to the F&I home page

## Step 2: Run the Tests

Double-click the file:
```
run-all-tests.bat
```

Or to run a specific test, open Command Prompt and run:
```
node test-puppeteer.js
```

## Common Errors and Solutions

### Error: "The term '.\launch-run-all-tests.bat' is not recognized..."

The correct filename is `run-all-tests.bat` (not launch-run-all-tests.bat).

Try these commands instead:
```
.\run-all-tests.bat
```

Or simply double-click the `run-all-tests.bat` file in File Explorer.

### Error: "Cannot connect to browser"

Make sure:
1. Chrome is running with remote debugging (launched via `launch-chrome-for-testing.bat`)
2. You're logged into DealerTrack
3. You're on the F&I home page

## Screenshots

All tests save screenshots to the `screenshots` directory. Check these to see what happened during the test.
