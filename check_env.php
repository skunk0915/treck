<?php
echo "PHP_BINARY: " . (PHP_BINARY ?: 'EMPTY') . "\n";
echo "User: " . exec('whoami') . "\n";
echo "CWD: " . getcwd() . "\n";

echo "Testing build.php execution via fallback logic:\n";
$phpBinary = PHP_BINARY;
if (empty($phpBinary)) {
    $phpBinary = 'php';
}
$cmd = $phpBinary . ' ' . __DIR__ . '/build.php 2>&1';

echo "Cmd: $cmd\n";
exec($cmd, $out2, $ret2);
echo "Build Return: $ret2\n";
echo "Build Output (first 5 lines):\n";
echo implode("\n", array_slice($out2, 0, 5)) . "\n";
