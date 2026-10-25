#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
package_check_dir="$(mktemp -d "${TMPDIR:-/tmp}/struct-package.XXXXXXXX")"
trap 'rm -rf "$package_check_dir"' EXIT
mkdir -p "$package_check_dir/dist" "$package_check_dir/consumer"
cd "$project_root"

# Build the current working tree, not a previous tag or a symlink to source.
composer archive --format=tar --dir="$package_check_dir/dist" --file=struct --no-interaction
php -- "$project_root/composer.json" "$package_check_dir" <<'PHP'
<?php
$source = $argv[1];
$directory = $argv[2];
$archive = $directory . '/dist/struct.tar';
$distribution = new PharData($archive);
foreach (['docs/README.md', 'docs/validation.md'] as $required) {
    if (!isset($distribution[$required])) {
        throw new RuntimeException('Archive is missing its documentation');
    }
}
$allowed = ['src', 'docs', 'examples', 'scripts', 'README.md', 'LICENSE', 'CHANGELOG.md', 'composer.json', 'extension.neon'];
foreach (new RecursiveIteratorIterator(new PharData($archive)) as $file) {
    $relative = substr($file->getPathname(), strlen('phar://' . $archive . '/'));
    if (!$file->isDir() && !in_array(explode('/', $relative)[0], $allowed, true)) {
        throw new RuntimeException('Archive contains a non-distribution path');
    }
}
$package = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
foreach (['require-dev', 'autoload-dev', 'scripts', 'archive'] as $key) {
    unset($package[$key]);
}
$package['version'] = '2.0.0';
$package['dist'] = ['type' => 'tar', 'url' => 'file://' . $archive];
$consumer = [
    'name' => 'struct/package-check',
    'version' => '1.0.0',
    'repositories' => [['type' => 'package', 'package' => $package], ['packagist.org' => false]],
    'require' => ['tommyknocker/struct' => '2.0.0'],
];
file_put_contents($directory . '/consumer/composer.json', json_encode($consumer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
PHP

# Disable network: every runtime dependency must be present in the local artifact.
COMPOSER_DISABLE_NETWORK=1 COMPOSER_CACHE_DIR="$package_check_dir/cache" composer install \
    --working-dir="$package_check_dir/consumer" --no-dev --no-plugins --no-scripts --no-interaction --no-progress

installed_package="$package_check_dir/consumer/vendor/tommyknocker/struct"
for example in "$installed_package"/examples/[0-9][0-9]_*.php; do
    php "$example" > /dev/null
done

cat > "$package_check_dir/consumer/Model.php" <<'PHP'
<?php
use tommyknocker\struct\Struct;
final class PackageProbe extends Struct {
    public readonly int $id;
}
PHP
php "$package_check_dir/consumer/vendor/bin/struct-helper.php" --no-backup "$package_check_dir/consumer/Model.php"
php -- "$package_check_dir/consumer" <<'PHP'
<?php
$directory = $argv[1];
require $directory . '/vendor/autoload.php';
require $directory . '/Model.php';
if ((new PackageProbe(['id' => 42]))->toArray() !== ['id' => 42]) {
    throw new RuntimeException('Installed CLI did not produce a usable DTO');
}
PHP

echo 'Package check passed: isolated Composer install, all examples, and installed CLI.'
