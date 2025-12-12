<?php
// build_local.php
// Wrapper to sync tags from remote and then run the build.

echo "=== Step 1: Syncing Tags from Remote ===\n";
require_once __DIR__ . '/sync_tags_from_remote.php';

echo "\n=== Step 2: Running Build Process ===\n";
require_once __DIR__ . '/build.php';

echo "\n=== Local Build Complete ===\n";
