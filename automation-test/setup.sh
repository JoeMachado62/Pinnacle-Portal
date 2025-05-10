#!/bin/bash

# DealerTrack Puppeteer Automation Test Setup Script
# This script installs dependencies and prepares the environment for running the tests

echo "Setting up DealerTrack Puppeteer Automation Test..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Node.js is not installed. Please install Node.js v14 or higher."
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node -v | cut -d 'v' -f 2 | cut -d '.' -f 1)
if [ "$NODE_VERSION" -lt 14 ]; then
    echo "Node.js version $NODE_VERSION is too old. Please install Node.js v14 or higher."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "npm is not installed. Please install npm v6 or higher."
    exit 1
fi

# Install dependencies
echo "Installing dependencies..."
npm install

# Create screenshots directory if it doesn't exist
if [ ! -d "screenshots" ]; then
    echo "Creating screenshots directory..."
    mkdir screenshots
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "WARNING: .env file not found. Using default .env file."
    echo "Please update the .env file with your DealerTrack credentials before running the tests."
fi

echo "Setup complete!"
echo ""
echo "To run the basic test:"
echo "  node test.js"
echo ""
echo "To run the advanced test:"
echo "  node advanced-test.js"
echo ""
echo "Make sure to update the .env file with your DealerTrack credentials before running the tests."
