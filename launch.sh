#!/bin/bash
set -e

# This script automates the launch of the Chess360 application.

# Function to kill the backend process
cleanup() {
    echo "Caught exit signal. Cleaning up..."
    if [ ! -z "$BACKEND_PID" ]; then
        echo "Killing backend server (PID: $BACKEND_PID)..."
        kill $BACKEND_PID
    fi
    exit
}

# Trap EXIT signal to run cleanup function
trap cleanup EXIT

# Stop Docker to free up port 8080 for the frontend development server.
echo "Stopping Docker to free up port 8080..."
sudo systemctl stop docker
echo "Docker stopped."

# Start XAMPP for the PHP backend and MySQL database.
echo "--------------------------------------------------------"
echo "Starting XAMPP..."
sudo /opt/lampp/lampp start
echo "XAMPP started."
echo "Continuing with launch..."
echo "--------------------------------------------------------"


# Get the directory of the script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Start the Python backend server in the background.
echo "Starting Python backend server..."
cd "$SCRIPT_DIR/backend"
# Execute uvicorn directly from the venv
"$SCRIPT_DIR/backend/venv/bin/python" -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload &
BACKEND_PID=$!
echo "Backend started with PID: $BACKEND_PID"
cd "$SCRIPT_DIR"

# Give the backend a moment to start up
sleep 3

# Start the Vue.js frontend development server.
echo "Starting frontend development server..."
cd "$SCRIPT_DIR/frontend"
npm run serve 