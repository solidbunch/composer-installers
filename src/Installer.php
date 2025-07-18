<?php

namespace SolidBunch\ComposerInstallers;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class Installer extends LibraryInstaller
{
    protected $supportedTypes = [
        'kit-module',
        'wordpress-core'
    ];

    public function supports($packageType)
    {
        return in_array($packageType, $this->supportedTypes, true);
    }

    public function getInstallPath(PackageInterface $package)
    {
        return parent::getInstallPath($package);
    }
}
