<?php

namespace SolidBunch\ComposerInstallers\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs a real `composer update` in a throw-away project. Every package comes from a path repository
 * and Composer's network access is disabled, so the tests need no network.
 */
class InstallerPlacementTest extends TestCase
{
    private const CORE_PACKAGE_DIR = 'solidbunch/wordpress-core-no-content';

    /** Every location a placeholder core could end up in across the cases below. */
    private const CANDIDATE_DIRECTORIES = [
        'web/wp',
        'web/wp-core',
        'web/x',
        'web/y',
        'web/c',
        'vendor/solidbunch/wordpress-core-no-content',
    ];

    private string $project;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir() . '/solidbunch-installers-project-' . bin2hex(random_bytes(6));
        mkdir($this->project . '/packages', 0777, true);
        mkdir($this->project . '/home', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->project);
    }

    /**
     * @return iterable<string, array{0: bool, 1: array<string, mixed>, 2: string}>
     */
    public static function placementProvider(): iterable
    {
        yield 'other installer, install dir only, no rule' => [
            true,
            ['wordpress-install-dir' => 'web/wp'],
            'web/wp',
        ];
        yield 'other installer, both settings, type rule' => [
            true,
            ['wordpress-install-dir' => 'web/wp', 'installer-paths' => ['web/wp-core/' => ['type:wordpress-core']]],
            'web/wp-core',
        ];
        yield 'this plugin only, no rule' => [
            false,
            [],
            'vendor/solidbunch/wordpress-core-no-content',
        ];
        yield 'this plugin only, type rule' => [
            false,
            ['installer-paths' => ['web/x/' => ['type:wordpress-core']]],
            'web/x',
        ];
        yield 'this plugin only, exact name rule' => [
            false,
            ['installer-paths' => ['web/y/' => [self::CORE_PACKAGE_DIR]]],
            'web/y',
        ];
        yield 'other installer, install dir and wildcard rule that is not matched' => [
            true,
            ['wordpress-install-dir' => 'web/wp', 'installer-paths' => ['web/c/' => ['type:*']]],
            'web/wp',
        ];
    }

    /**
     * @param array<string, mixed> $extra
     */
    #[DataProvider('placementProvider')]
    public function testCorePackageLandsWhereTheWinningInstallerPutsIt(bool $withOtherInstaller, array $extra, string $expected): void
    {
        $this->writeProject($withOtherInstaller, $extra);

        [$exitCode, $output] = $this->runComposerUpdate();

        $this->assertSame(0, $exitCode, $output);

        $found = array_values(array_filter(
            self::CANDIDATE_DIRECTORIES,
            fn (string $directory): bool => is_file($this->project . '/' . $directory . '/index.php')
        ));

        $this->assertSame([$expected], $found, $output);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function writeProject(bool $withOtherInstaller, array $extra): void
    {
        $root = dirname(__DIR__);

        $pluginComposer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $pluginComposer['version'] = '1.1.0';
        mkdir($this->project . '/packages/plugin', 0777, true);
        file_put_contents(
            $this->project . '/packages/plugin/composer.json',
            json_encode($pluginComposer, JSON_THROW_ON_ERROR)
        );
        $this->copyDirectory($root . '/src', $this->project . '/packages/plugin/src');

        $this->copyDirectory(__DIR__ . '/fixtures/wordpress-core-placeholder', $this->project . '/packages/core');

        $repositories = [
            ['packagist.org' => false],
            ['type' => 'path', 'url' => 'packages/plugin', 'options' => ['symlink' => false]],
            ['type' => 'path', 'url' => 'packages/core', 'options' => ['symlink' => false]],
        ];
        $require = [
            'solidbunch/composer-installers' => '*',
            self::CORE_PACKAGE_DIR => '*',
        ];
        $allowPlugins = ['solidbunch/composer-installers' => true];

        if ($withOtherInstaller) {
            $this->copyDirectory(__DIR__ . '/fixtures/other-core-installer', $this->project . '/packages/other');
            $repositories[] = ['type' => 'path', 'url' => 'packages/other', 'options' => ['symlink' => false]];
            $require['acme/wordpress-core-installer'] = '*';
            $allowPlugins['acme/wordpress-core-installer'] = true;
        }

        $composerJson = [
            'name' => 'test/project',
            'repositories' => $repositories,
            'require' => $require,
            'config' => ['allow-plugins' => $allowPlugins, 'lock' => false],
        ];
        if ($extra !== []) {
            $composerJson['extra'] = $extra;
        }

        file_put_contents(
            $this->project . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runComposerUpdate(): array
    {
        $command = [
            PHP_BINARY,
            dirname(__DIR__) . '/vendor/composer/composer/bin/composer',
            'update',
            '--no-interaction',
            '--no-progress',
            '--no-audit',
            '--working-dir=' . $this->project,
        ];
        $environment = [
            'COMPOSER_HOME' => $this->project . '/home',
            'COMPOSER_CACHE_DIR' => $this->project . '/home/cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_ALLOW_SUPERUSER' => '1',
            'PATH' => (string) getenv('PATH'),
        ];

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
        $this->assertIsResource($process);

        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output];
    }

    private function copyDirectory(string $source, string $destination): void
    {
        mkdir($destination, 0777, true);

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $target = $destination . '/' . substr($item->getPathname(), strlen($source) + 1);
            if ($item->isDir()) {
                mkdir($target, 0777, true);
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
