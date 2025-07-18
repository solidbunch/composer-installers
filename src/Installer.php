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
     * Indicates that this installer supports any package type.
     */
    public function supports(string $packageType): bool
    {
        return in_array($packageType, self::TYPES, true);
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
