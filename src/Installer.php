<?php

namespace SolidBunch\ComposerInstallers;

use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * Custom installer for the package types listed in TYPES ("kit-module" and "wordpress-core") only.
 *
 * A type is claimed only when "extra.installer-paths" of the root project has a rule that can match it
 * (see supports()). Package-name rules therefore apply only to packages of those two types.
 */
class Installer extends LibraryInstaller
{
    protected array $paths = [];

    protected const TYPES = [
        'kit-module',
        'wordpress-core'
    ];


    /**
     * Constructor that loads installer-paths from root composer.json.
     */
    public function __construct(IOInterface $io, Composer $composer)
    {
        // Constructor compatible with Composer 2.2+
        parent::__construct($io, $composer);

        $extra = $composer->getPackage()->getExtra();
        if (is_array($extra['installer-paths'] ?? null)) {
            $this->paths = $extra['installer-paths'];
        }
    }


    /**
     * Returns the custom install path for the given package.
     *
     * Rules naming the exact package name take precedence over "type:" rules; within each group the first rule
     * in the order of "extra.installer-paths" wins. Without a matching rule the default vendor path is returned.
     * A custom path never ends with a slash.
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $name = $package->getPrettyName(); // vendor/name

        $path = $this->findPath($name) ?? $this->findPath('type:' . $package->getType());
        if ($path === null) {
            return parent::getInstallPath($package);
        }

        // InstallerInterface::getInstallPath() requires a path that does not end with a slash
        return rtrim($this->replaceVars((string) $path, $name), '/');
    }


    /**
     * Claims a package type only when "extra.installer-paths" of the root project has a rule that can apply to it.
     *
     * A "type:<type>" criterion applies when it names this exact type. Any other criterion is a package name;
     * a name cannot be evaluated here because only the type is passed in, so it is treated as applicable.
     * Without a matching rule another installer (or Composer's default) handles the package.
     */
    public function supports(string $packageType): bool
    {
        if (!in_array($packageType, self::TYPES, true)) {
            return false;
        }

        foreach ($this->paths as $criteriaList) {
            foreach ((array) $criteriaList as $criteria) {
                if (!is_string($criteria)) {
                    continue;
                }

                if (!str_starts_with($criteria, 'type:') || substr($criteria, 5) === $packageType) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Returns the path template of the first rule (in declaration order) that lists the given criteria.
     */
    private function findPath(string $criteria): int|string|null
    {
        foreach ($this->paths as $path => $criteriaList) {
            if (in_array($criteria, (array) $criteriaList, true)) {
                return $path;
            }
        }

        return null;
    }


    /**
     * Replaces placeholders like {$vendor} and {$name} in the target path.
     */
    private function replaceVars(string $path, string $prettyName): string
    {
        [$vendor, $name] = explode('/', $prettyName, 2);
        return str_replace(['{$vendor}', '{$name}'], [$vendor, $name], $path);
    }
}
