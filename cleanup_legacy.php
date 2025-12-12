<?php
// cleanup_legacy.php
// Removes legacy static files from root directory

$articleDir = __DIR__ . '/article';
$rootDir = __DIR__;

echo "Starting cleanup of legacy artifacts...\n";

// Safety check: specific directories to NEVER touch
$protected = [
    '.git',
    '.vscode',
    '.agent',
    '.venv',
    'article',
    'css',
    'js',
    'img',
    'lib',
    'views',
    'data',
    'static',
    'node_modules',
    'twitter',
    'instagram',
    'youtube',
    'summary',
    '素材'
];
$protectedMap = array_fill_keys($protected, true);

// 1. Identify Article Directories
$files = glob($articleDir . '/*.md');
$count = 0;

foreach ($files as $file) {
    $filename = basename($file, '.md');
    $targetDir = $rootDir . '/' . $filename;

    // Check if directory exists in root
    if (is_dir($targetDir)) {
        // Safety: Ensure it's not a protected dir (though filenames shouldn't match)
        if (isset($protectedMap[$filename])) {
            echo "SKIPPING protected directory matching article name: $filename\n";
            continue;
        }

        echo "Removing directory: $filename\n";
        recursiveRmDir($targetDir);
        $count++;
    }
}

// 2. Remove index.html
if (file_exists($rootDir . '/index.html')) {
    echo "Removing index.html\n";
    unlink($rootDir . '/index.html');
}

// 3. Remove legacy articles.json (if strictly legacy location, but we output to there in prev build structure)
// The new build outputs to static/js/articles.json.
// The old build output to js/articles.json? No, old build output to js/articles.json.
// Wait, if old build output to js/articles.json, that file is inside 'js' which is a source folder.
// Let's check if 'js' contains ONLY source files or if articles.json is considered generated.
// Usually articles.json is generated.
if (file_exists($rootDir . '/js/articles.json')) {
    echo "Removing legacy js/articles.json\n";
    unlink($rootDir . '/js/articles.json');
}

echo "Cleanup complete. Removed $count article directories.\n";

function recursiveRmDir($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? recursiveRmDir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}
