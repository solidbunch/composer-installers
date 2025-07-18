<?php

namespace SolidBunch\ComposerInstallers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin that registers the custom installer.
 */
class Plugin implements PluginInterface
{
    /**
     * Called when the plugin is activated.
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $installer = new Installer($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    /**
     * Called when the plugin is deactivated (Composer 2+).
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Optional: no action needed
    }

    /**
     * Called when the plugin is uninstalled (Composer 2+).
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Optional: no action needed
    }
}
