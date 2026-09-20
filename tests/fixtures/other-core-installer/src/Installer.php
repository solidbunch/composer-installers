<?php

namespace Acme\CoreInstaller;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

/**
 * Behaves like the third-party core installers: claims every wordpress-core package and installs it
 * into "extra.wordpress-install-dir" of the root project ("wordpress" by default).
 */
class Installer extends LibraryInstaller
{
    public function supports(string $packageType): bool
    {
        return $packageType === 'wordpress-core';
    }

    public function getInstallPath(PackageInterface $package): string
    {
        $extra = $this->composer->getPackage()->getExtra();

        return $extra['wordpress-install-dir'] ?? 'wordpress';
    }
}
