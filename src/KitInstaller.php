<?php

namespace SolidBunch\Composer;

use Composer\Installers\BaseInstaller;

class KitInstaller extends BaseInstaller
{
    protected $locations = [
        'kit-module' => null,
        'wordpress-core' => null,
    ];
}