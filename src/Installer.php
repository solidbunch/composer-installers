<?php

namespace SolidBunch\ComposerInstallers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installers\Installer as BaseInstaller;

class Installer extends BaseInstaller
{
    public function __construct(IOInterface $io, Composer $composer)
    {
        parent::__construct($io, $composer);

        $this->locations = array_merge($this->locations, [
            'kit-module'     => 'kit-modules/{$name}/',
            'wordpress-core' => 'cweb/wp-core/',
        ]);
    }
}
