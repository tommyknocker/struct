#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace tommyknocker\struct\scripts;

require_once __DIR__ . '/../vendor/autoload.php';

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use tommyknocker\struct\tools\AttributeHelper;
use tommyknocker\struct\tools\exception\AttributeHelperException;
use tommyknocker\struct\tools\exception\ClassProcessingException;
use tommyknocker\struct\tools\exception\FileProcessingException;

/**
 * Struct Attribute Helper Console Tool
 *
 * Automatically generates Field attributes for Struct classes.
 * Usage: php struct-helper.php [options] <path>
 */
final class StructAttributeHelper
{
    private AttributeHelper $helper;
    private bool $dryRun = false;
    private bool $verbose = false;
    private bool $backup = true;

    public function __construct()
    {
        $this->helper = new AttributeHelper();
    }

    /**
     * Main entry point
     */
    public function run(array $argv): int
    {
        try {
            $options = $this->parseOptions($argv);
            $path = $this->getPath($argv);

            if ($path === null) {
                $this->showUsage();

                return 1;
            }

            $this->dryRun = $options['dry-run'] ?? false;
            $this->verbose = $options['verbose'] ?? false;
            $this->backup = $options['backup'] ?? true;

            if ($this->verbose) {
                echo "Processing path: {$path}\n";
                echo "Dry run: " . ($this->dryRun ? 'yes' : 'no') . "\n";
                echo "Backup: " . ($this->backup ? 'yes' : 'no') . "\n\n";
            }

            $files = $this->getFilesToProcess($path);
            $processed = 0;
            $modified = 0;

            foreach ($files as $file) {
                try {
                    if ($this->processFile($file)) {
                        $modified++;
                    }
                    $processed++;
                } catch (FileProcessingException $e) {
                    echo "Error processing {$file}: {$e->getMessage()}\n";
                }
            }

            echo "\nProcessed {$processed} files, modified {$modified} files.\n";

            return 0;

        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";

            return 1;
        }
    }

    /**
     * Parse command line options
     */
    private function parseOptions(array $argv): array
    {
        $options = [];
        $argvCount = count($argv);

        for ($i = 1; $i < $argvCount; $i++) {
            $arg = $argv[$i];

            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);
                $options[$option] = true;
            } elseif (str_starts_with($arg, '-')) {
                $shortOption = substr($arg, 1);
                switch ($shortOption) {
                    case 'v':
                        $options['verbose'] = true;

                        break;
                    case 'd':
                        $options['dry-run'] = true;

                        break;
                    case 'n':
                        $options['backup'] = false;

                        break;
                }
            }
        }

        return $options;
    }

    /**
     * Get path argument
     * @param array $argv
     * @return string|null
     */
    private function getPath(array $argv): ?string
    {
        foreach ($argv as $arg) {
            if (!str_starts_with($arg, '-') && $arg !== $argv[0]) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * Get list of files to process
     * @param string $path
     * @return array
     * @throws FileProcessingException
     */
    private function getFilesToProcess(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (is_dir($path)) {
            $files = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }

            return $files;
        }

        throw new FileProcessingException($path, 'Path does not exist');
    }

    /**
     * Process a single file
     */
    private function processFile(string $filePath): bool
    {
        if ($this->verbose) {
            echo "Processing: {$filePath}\n";
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException($filePath, 'Cannot read file');
        }

        // Include the file to make classes available
        try {
            include_once $filePath;
        } catch (Exception $e) {
            if ($this->verbose) {
                echo "  Warning: Could not include file: {$e->getMessage()}\n";
            }
        }

        $classes = $this->extractClasses($content);
        if (empty($classes)) {
            if ($this->verbose) {
                echo "  No Struct classes found\n";
            }

            return false;
        }

        $modified = false;
        $newContent = $content;

        foreach ($classes as $className) {
            try {
                if ($this->processClassInFile($className, $newContent)) {
                    $modified = true;
                }
            } catch (ClassProcessingException $e) {
                echo "  Error processing class {$className}: {$e->getMessage()}\n";
            }
        }

        if ($modified && !$this->dryRun) {
            if ($this->backup) {
                $this->createBackup($filePath);
            }
            file_put_contents($filePath, $newContent);
        }

        return $modified;
    }

    /**
     * Extract class names from file content
     */
    private function extractClasses(string $content): array
    {
        $classes = [];

        // Find classes that extend Struct
        if (preg_match_all('/class\s+(\w+)\s+extends\s+Struct/', $content, $matches)) {
            $classes = array_merge($classes, $matches[1]);
        }

        // Find final classes that extend Struct
        if (preg_match_all('/final\s+class\s+(\w+)\s+extends\s+Struct/', $content, $matches)) {
            $classes = array_merge($classes, $matches[1]);
        }

        return array_unique($classes);
    }

    /**
     * Process a class within file content
     */
    private function processClassInFile(string $className, string &$content): bool
    {
        try {
            $properties = $this->helper->getPropertiesNeedingAttributes($className);

            if (empty($properties)) {
                if ($this->verbose) {
                    echo "  Class {$className}: All properties already have attributes\n";
                }

                return false;
            }

            if ($this->verbose) {
                echo "  Class {$className}: Found " . count($properties) . " properties needing attributes\n";
            }

            $modified = false;

            foreach ($properties as $property) {
                $attribute = $this->helper->generateFieldAttribute($property);
                $content = $this->insertAttribute($content, $className, $property->getName(), $attribute);
                $modified = true;

                if ($this->verbose) {
                    echo "    Added attribute for property: {$property->getName()}\n";
                }
            }

            return $modified;

        } catch (AttributeHelperException $e) {
            throw new ClassProcessingException($className, $e->getMessage(), 0, $e);
        }
    }

    /**
     * Insert attribute before property declaration
     */
    private function insertAttribute(string $content, string $className, string $propertyName, string $attribute): string
    {
        // Find the property declaration with more flexible pattern
        $pattern = '/(\s+)(public\s+readonly\s+[^$]+\s+\$' . preg_quote($propertyName, '/') . ';)/';

        if (preg_match($pattern, $content, $matches)) {
            $indentation = $matches[1];
            $propertyDeclaration = $matches[2];
            $replacement = $indentation . $attribute . "\n" . $indentation . $propertyDeclaration;

            return preg_replace($pattern, $replacement, $content);
        }

        throw new ClassProcessingException($className, "Could not find property {$propertyName}");
    }

    /**
     * Create backup of file
     */
    private function createBackup(string $filePath): void
    {
        $backupPath = $filePath . '.bak';
        copy($filePath, $backupPath);

        if ($this->verbose) {
            echo "  Created backup: {$backupPath}\n";
        }
    }

    /**
     * Show usage information
     */
    private function showUsage(): void
    {
        echo "Struct Attribute Helper\n";
        echo "======================\n\n";
        echo "Usage: php struct-helper.php [options] <path>\n\n";
        echo "Options:\n";
        echo "  -v, --verbose    Verbose output\n";
        echo "  -d, --dry-run    Show what would be changed without modifying files\n";
        echo "  -n, --no-backup Don't create backup files\n";
        echo "  -h, --help       Show this help\n\n";
        echo "Examples:\n";
        echo "  php struct-helper.php src/MyStruct.php\n";
        echo "  php struct-helper.php --verbose src/\n";
        echo "  php struct-helper.php --dry-run tests/\n";
    }
}

// Run the tool
$tool = new StructAttributeHelper();
exit($tool->run($argv));
