<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;

final class AttributeHelperCliTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/struct-cli-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $path) {
            unlink($path);
        }
        rmdir($this->directory);
    }

    /** @param list<string> $args
     * @return array{int, string}
     */
    private function runCli(array $args): array
    {
        $process = proc_open([PHP_BINARY, __DIR__ . '/../scripts/struct-helper.php', ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output];
    }

    public function testDryRunDoesNotExecuteOrChangeSource(): void
    {
        $file = $this->directory . '/Models.php';
        $marker = $this->directory . '/executed';
        $source = '<?php namespace CliFixture; use tommyknocker\\struct\\Struct; file_put_contents(' . var_export($marker, true) . ', "executed"); final class Input extends Struct { public readonly string $name; }';
        file_put_contents($file, $source);
        [$status, $output] = $this->runCli(['--dry-run', $file]);
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('Would modify:', $output);
        $this->assertSame($source, file_get_contents($file));
        $this->assertFileDoesNotExist($marker);
        $this->assertFileDoesNotExist($file . '.bak');
    }

    public function testNamespacedClassesAreEditedOnceWithNoBackupWhenRequested(): void
    {
        $file = $this->directory . '/Models.php';
        $source = <<<'SOURCE'
            <?php
            namespace CliFixture;
            use tommyknocker\struct\Struct;
            final class First extends Struct {
                public readonly string|int $id;
                #[\tommyknocker\struct\Field]
                public readonly string $name;
            }
            final class Second extends \tommyknocker\struct\Struct {
                public readonly string $name;
                public string $internal = 'unchanged';
            }
            SOURCE;
        file_put_contents($file, $source);
        [$status, $output] = $this->runCli(['--no-backup', $file]);
        $this->assertSame(0, $status, $output);
        $modified = file_get_contents($file);
        $this->assertIsString($modified);
        $this->assertSame(3, substr_count($modified, '#[\\tommyknocker\\struct\\Field]'));
        $this->assertFileDoesNotExist($file . '.bak');
        [$status, $output] = $this->runCli([$file]);
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('modified 0', $output);
        $this->assertSame($modified, file_get_contents($file));
    }

    public function testBackupContainsOriginalSource(): void
    {
        $file = $this->directory . '/Model.php';
        $source = '<?php use tommyknocker\\struct\\Struct; class Model extends Struct { public readonly int $id; }';
        file_put_contents($file, $source);
        [$status, $output] = $this->runCli([$file]);
        $this->assertSame(0, $status, $output);
        $this->assertSame($source, file_get_contents($file . '.bak'));
    }

    public function testInvalidPhpProducesFailureWithoutWriting(): void
    {
        $file = $this->directory . '/Bad.php';
        $source = '<?php class Bad extends Struct { public readonly ; }';
        file_put_contents($file, $source);
        [$status, $output] = $this->runCli([$file]);
        $this->assertSame(1, $status, $output);
        $this->assertSame($source, file_get_contents($file));
        $this->assertFileDoesNotExist($file . '.bak');
    }
}
