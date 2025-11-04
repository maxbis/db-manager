#!/bin/bash

# Database Manager Test Runner Script
# Simple shell script to run the Selenium test

echo "🚀 Database Manager Test Runner"
echo "================================"

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed or not in PATH"
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "database_manager_test.py" ]; then
    echo "❌ Please run this script from the test directory"
    exit 1
fi

# Install dependencies if requirements.txt exists
if [ -f "requirements.txt" ]; then
    echo "📦 Installing dependencies..."
    pip3 install -r requirements.txt
    if [ $? -ne 0 ]; then
        echo "❌ Failed to install dependencies"
        exit 1
    fi
fi

# Run the test
echo "🚀 Starting test..."
python3 run_test.py

echo "✅ Test runner completed"
