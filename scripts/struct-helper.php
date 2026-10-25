#!/usr/bin/env php
<?php

declare(strict_types=1);

// Load this package, never the PHP files being edited.
foreach ([__DIR__ . '/../vendor/autoload.php', dirname(__DIR__, 3) . '/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        break;
    }
}
$marker = new \tommyknocker\struct\tools\FieldMarker();

$options = array_slice($argv, 1);
if (in_array('--help', $options, true) || in_array('-h', $options, true)) {
    echo "Usage: struct-helper.php [--dry-run] [--verbose] [--no-backup] <file-or-directory>\n";
    echo "Adds Field markers to resolved Struct subclasses without executing target files.\n";
    exit(0);
}
$dryRun = false;
$verbose = false;
$backup = true;
$path = null;
foreach ($options as $option) {
    switch ($option) {
        case '--dry-run':
        case '-d':
            $dryRun = true;

            break;
        case '--verbose':
        case '-v':
            $verbose = true;

            break;
        case '--no-backup':
        case '-n':
            $backup = false;

            break;
        default:
            if (str_starts_with($option, '-') || $path !== null) {
                fwrite(STDERR, "Unknown option or extra path: $option\n");
                exit(1);
            }
            $path = $option;
    }
}
if ($path === null || !file_exists($path)) {
    fwrite(STDERR, "Provide an existing PHP file or directory. Use --help for usage.\n");
    exit(1);
}
$files = [];
if (is_dir($path)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
} else {
    $files[] = $path;
}
$modified = 0;
$failed = 0;
foreach ($files as $file) {
    try {
        $source = file_get_contents($file);
        if ($source === false) {
            throw new RuntimeException('Cannot read file');
        }
        $result = $marker->addMarkers($source);
        if ($result === $source) {
            continue;
        }
        if (!$dryRun) {
            if ($backup && file_exists($file . '.bak')) {
                throw new RuntimeException('Backup already exists; move it before processing again');
            }
            if ($backup && !copy($file, $file . '.bak')) {
                throw new RuntimeException('Cannot create backup');
            }
            if (file_put_contents($file, $result, LOCK_EX) === false) {
                throw new RuntimeException('Cannot write file');
            }
        }
        $modified++;
        if ($verbose || $dryRun) {
            echo ($dryRun ? 'Would modify: ' : 'Modified: ') . $file . "\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "$file: {$e->getMessage()}\n");
        $failed++;
    }
}
echo 'Processed ' . count($files) . " files, " . ($dryRun ? 'would modify' : 'modified') . " $modified, failed $failed.\n";
exit($failed === 0 ? 0 : 1);
