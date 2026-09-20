<?php

namespace SolidBunch\ComposerInstallers\Tests;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Package;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidBunch\ComposerInstallers\Installer;

/**
 * Covers rule precedence, placeholders and shape safety of Installer::getInstallPath().
 */
class InstallerPathTest extends TestCase
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
     * @return iterable<string, array{0: mixed, 1: string, 2: string, 3: string}>
     */
    public static function matchedProvider(): iterable
    {
        yield 'name rule wins over an earlier type rule' => [
            ['web/type' => ['type:kit-module'], 'web/name' => ['acme/mod']],
            'acme/mod',
            'kit-module',
            'web/name',
        ];
        yield 'name rule wins over a later type rule' => [
            ['web/name' => ['acme/mod'], 'web/type' => ['type:kit-module']],
            'acme/mod',
            'kit-module',
            'web/name',
        ];
        yield 'first of two matching name rules wins' => [
            ['web/first' => ['acme/mod'], 'web/second' => ['acme/mod']],
            'acme/mod',
            'kit-module',
            'web/first',
        ];
        yield 'first of two matching type rules wins' => [
            ['web/first' => ['type:kit-module'], 'web/second' => ['type:kit-module']],
            'acme/mod',
            'kit-module',
            'web/first',
        ];
        yield 'name and vendor placeholders in a type rule' => [
            ['kit-modules/{$vendor}/{$name}' => ['type:kit-module']],
            'acme/mod',
            'kit-module',
            'kit-modules/acme/mod',
        ];
        yield 'name placeholder in a name rule' => [
            ['kit-modules/{$name}' => ['acme/mod']],
            'acme/mod',
            'kit-module',
            'kit-modules/mod',
        ];
        yield 'bare string criteria is one criterion' => [
            ['web/x' => 'acme/mod'],
            'acme/mod',
            'kit-module',
            'web/x',
        ];
        yield 'non-string criteria are ignored, the string still matches' => [
            ['web/x' => [42, 'acme/mod']],
            'acme/mod',
            'kit-module',
            'web/x',
        ];
    }

    #[DataProvider('matchedProvider')]
    public function testMatchedRuleDeterminesThePath(mixed $installerPaths, string $name, string $type, string $expected): void
    {
        $installer = $this->createInstaller($installerPaths);

        $this->assertSame($expected, $installer->getInstallPath($this->createPackage($name, $type)));
    }

    /**
     * @return iterable<string, array{0: mixed, 1: string, 2: string}>
     */
    public static function unmatchedProvider(): iterable
    {
        yield 'no rule matches' => [
            ['web/other' => ['acme/other'], 'web/t' => ['type:wordpress-core']],
            'acme/mod',
            'kit-module',
        ];
        yield 'wildcard type rule is not matched' => [
            ['web/c' => ['type:*']],
            'acme/mod',
            'kit-module',
        ];
        yield 'installer-paths is a string' => [
            'web/x',
            'acme/mod',
            'kit-module',
        ];
        yield 'criteria list without any string' => [
            ['web/x' => [42, null]],
            'acme/mod',
            'kit-module',
        ];
    }

    #[DataProvider('unmatchedProvider')]
    public function testUnmatchedPackageFallsBackToTheVendorPath(mixed $installerPaths, string $name, string $type): void
    {
        $installer = $this->createInstaller($installerPaths);

        $path = $installer->getInstallPath($this->createPackage($name, $type));

        $this->assertTrue(str_ends_with($path, 'vendor/' . $name), $path);
    }

    /**
     * @return iterable<string, array{0: mixed, 1: string, 2: string, 3: string}>
     */
    public static function trailingSlashProvider(): iterable
    {
        yield 'trailing slash of a type rule is stripped' => [
            ['web/wp-core/' => ['type:wordpress-core']],
            'solidbunch/wordpress-core-no-content',
            'wordpress-core',
            'web/wp-core',
        ];
        yield 'trailing slash is stripped after placeholder substitution' => [
            ['kit-modules/{$name}/' => ['type:kit-module']],
            'acme/mod',
            'kit-module',
            'kit-modules/mod',
        ];
        yield 'trailing slash of a name rule is stripped' => [
            ['web/y/' => ['acme/mod']],
            'acme/mod',
            'kit-module',
            'web/y',
        ];
        yield 'a slash-only rule is skipped in favour of a later type rule' => [
            ['/' => ['type:wordpress-core'], 'web/wp-core/' => ['type:wordpress-core']],
            'solidbunch/wordpress-core-no-content',
            'wordpress-core',
            'web/wp-core',
        ];
        yield 'a slash-only name rule does not stop the type pass' => [
            ['/' => ['acme/mod'], 'web/t/' => ['type:kit-module']],
            'acme/mod',
            'kit-module',
            'web/t',
        ];
    }

    #[DataProvider('trailingSlashProvider')]
    public function testTrailingSlashIsStrippedFromTheReturnedPath(mixed $installerPaths, string $name, string $type, string $expected): void
    {
        $installer = $this->createInstaller($installerPaths);

        $this->assertSame($expected, $installer->getInstallPath($this->createPackage($name, $type)));
    }

    public function testSlashOnlyRuleIsNeverReturnedAndFallsBackToTheVendorPath(): void
    {
        $installer = $this->createInstaller(['/' => ['type:wordpress-core']]);

        $path = $installer->getInstallPath($this->createPackage('solidbunch/wordpress-core-no-content', 'wordpress-core'));

        $this->assertNotSame('/', $path);
        $this->assertNotSame('', $path);
        $this->assertTrue(str_ends_with($path, 'vendor/solidbunch/wordpress-core-no-content'), $path);
    }

    public function testNameRuleDoesNotMakeTheInstallerClaimAnUnsupportedType(): void
    {
        $installer = $this->createInstaller(['web/y' => ['acme/library']]);

        $this->assertFalse($installer->supports('library'));
    }

    private function createInstaller(mixed $installerPaths): Installer
    {
        $config = [
            'repositories' => [['packagist.org' => false]],
            'extra' => ['installer-paths' => $installerPaths],
        ];

        $composer = Factory::create(new NullIO(), $config, true);

        return new Installer(new NullIO(), $composer);
    }

    private function createPackage(string $name, string $type): Package
    {
        $package = new Package($name, '1.0.0.0', '1.0.0');
        $package->setType($type);

        return $package;
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
