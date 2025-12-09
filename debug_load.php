<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Loading Parsedown...\n";
require 'Parsedown.php';
echo "Parsedown loaded.\n";

echo "Loading DBTagManager...\n";
require_once __DIR__ . '/lib/DBTagManager.php';
echo "DBTagManager loaded.\n";

echo "Loading ArticleMetaManager...\n";
require_once __DIR__ . '/lib/ArticleMetaManager.php';
echo "ArticleMetaManager loaded.\n";

echo "Testing DBTagManager...\n";
try {
    $tm = new DBTagManager();
    echo "DBTagManager instantiated.\n";
} catch (Throwable $e) {
    echo "Error instantiating DBTagManager: " . $e->getMessage() . "\n";
}

echo "Testing ArticleMetaManager...\n";
try {
    $amm = new ArticleMetaManager(__DIR__ . '/data/article_meta.json');
    echo "ArticleMetaManager instantiated.\n";
} catch (Throwable $e) {
    echo "Error instantiating ArticleMetaManager: " . $e->getMessage() . "\n";
}

echo "Done.\n";
