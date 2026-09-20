<?php

namespace SolidBunch\ComposerInstallers;

use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * Custom installer that supports any package types defined in "extra.installer-paths" of the root project.
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
        if (isset($extra['installer-paths'])) {
            $this->paths = $extra['installer-paths'];
        }
    }


    /**
     * Returns the custom install path for the given package.
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $type = $package->getType();
        $name = $package->getPrettyName(); // vendor/name

        foreach ($this->paths as $path => $criteriaList) {
            foreach ($criteriaList as $criteria) {
                if (str_starts_with($criteria, 'type:')) {
                    if ($type === substr($criteria, 5)) {
                        return $this->replaceVars($path, $name);
                    }
                } elseif ($criteria === $name) {
                    return $this->replaceVars($path, $name);
                }
            }
        }

        return parent::getInstallPath($package);
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
     * Replaces placeholders like {$vendor} and {$name} in the target path.
     */
    private function replaceVars(string $path, string $prettyName): string
    {
        [$vendor, $name] = explode('/', $prettyName, 2);
        return str_replace(['{$vendor}', '{$name}'], [$vendor, $name], $path);
    }
}
