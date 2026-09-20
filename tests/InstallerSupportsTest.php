<?php

namespace SolidBunch\ComposerInstallers\Tests;

use Composer\Factory;
use Composer\IO\NullIO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidBunch\ComposerInstallers\Installer;

class InstallerSupportsTest extends TestCase
{
    private string $home;

    protected function setUp(): void
    {
        $this->home = sys_get_temp_dir() . '/solidbunch-installers-home-' . bin2hex(random_bytes(6));
        mkdir($this->home, 0777, true);
        putenv('COMPOSER_HOME=' . $this->home);
    }

    protected function tearDown(): void
    {
        putenv('COMPOSER_HOME');
        $this->removeDirectory($this->home);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>|null, 1: string, 2: bool}>
     */
    public static function claimProvider(): iterable
    {
        yield 'no installer-paths' => [null, 'wordpress-core', false];
        yield 'empty installer-paths' => [[], 'wordpress-core', false];
        yield 'only a type rule for another type' => [
            ['web/x/' => ['type:kit-module']],
            'wordpress-core',
            false,
        ];
        yield 'type rule for the same type' => [
            ['web/x/' => ['type:wordpress-core']],
            'wordpress-core',
            true,
        ];
        yield 'type rule does not leak to the other supported type' => [
            ['web/x/' => ['type:wordpress-core']],
            'kit-module',
            false,
        ];
        yield 'name rule applies conservatively to a supported type' => [
            ['web/y/' => ['solidbunch/wordpress-core-no-content']],
            'wordpress-core',
            true,
        ];
        yield 'name rule applies conservatively to the other supported type' => [
            ['web/y/' => ['solidbunch/wordpress-core-no-content']],
            'kit-module',
            true,
        ];
        yield 'wildcard type rule is not matched' => [
            ['web/c/' => ['type:*']],
            'wordpress-core',
            false,
        ];
        yield 'vendor rule counts as a name rule' => [
            ['web/d/' => ['vendor:solidbunch']],
            'wordpress-core',
            true,
        ];
        yield 'unsupported type is never claimed' => [
            ['web/x/' => ['type:library', 'acme/library']],
            'library',
            false,
        ];
        yield 'a later rule can match after a non-matching one' => [
            ['web/a/' => ['type:kit-module'], 'web/b/' => ['type:wordpress-core']],
            'wordpress-core',
            true,
        ];
    }

    /**
     * @param array<string, mixed>|null $installerPaths
     */
    #[DataProvider('claimProvider')]
    public function testSupportsClaimsATypeOnlyWhenARuleCanApply(?array $installerPaths, string $type, bool $expected): void
    {
        $config = ['repositories' => [['packagist.org' => false]]];
        if ($installerPaths !== null) {
            $config['extra'] = ['installer-paths' => $installerPaths];
        }

        $composer = Factory::create(new NullIO(), $config, true);
        $installer = new Installer(new NullIO(), $composer);

        $this->assertSame($expected, $installer->supports($type));
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
