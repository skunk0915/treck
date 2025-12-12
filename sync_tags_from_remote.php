<?php
// sync_tags_from_remote.php

$remoteUrl = 'https://sensei-omoi.flow-t.net/data/tags.json';
$localPath = __DIR__ . '/data/tags.json';

echo "Syncing tags from $remoteUrl ...\n";

$json = file_get_contents($remoteUrl);

if ($json === false) {
    echo "ERROR: Failed to fetch tags from remote.\n";
    exit(1);
}

// Basic validation
$data = json_decode($json, true);
if ($data === null) {
    echo "ERROR: Invalid JSON received.\n";
    exit(1);
}

// Check for the problematic key
$checkKey = 'montbell_fleece_complete_guide.md';
if (isset($data[$checkKey])) {
    echo "Validation: '$checkKey' IS present in remote data.\n";
    echo "Tags: " . json_encode($data[$checkKey], JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "WARNING: '$checkKey' is STILL MISSING in remote data.\n";
}

$bytes = file_put_contents($localPath, $json);
echo "Saved $bytes bytes to $localPath\n";
echo "Sync Complete.\n";
