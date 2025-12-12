#!/bin/bash

# Change directory to the script's location
cd "$(dirname "$0")"

echo "Starting SSG Build..."
php build_local.php

echo ""
echo "Build finished."
read -n 1 -s -r -p "Press any key to close..."
