<?php

namespace Syan\PluginSystem;

use Syan\PluginSystem\Helpers\Str;
use Syan\PluginSystem\Exceptions\PluginNotFoundException;

/**
 * Class PluginSystem
 * @package Syan\PluginSystem
 */
class PluginSystem
{

    /**
     * Plugins directory
     *
     * @var string $directory
     */
    public string $directory;

    /**
     * Plugins
     *
     * @var array $plugins
     */
    public array $plugins = [];

    /**
     * Plugins constructor.
     * @param string|null $directory
     * @param array|null $plugins
     */
    public function __construct(?string $directory = null, ?array $plugins = null)
    {
        if ($directory != null) $this->directory = $directory;
        if ($plugins != null) $this->plugins = $plugins;
    }

    /**
     * Add plugin to memory
     *
     * @param Plugin $plugin
     * @return $this
     */
    public function add(Plugin $plugin)
    {
        array_push($this->plugins, $plugin);
        return $this;
    }

    /**
     * Remove plugin from memory
     *
     * @param Plugin $plugin
     * @return $this
     */
    public function remove(Plugin $plugin)
    {
        $this->plugins = array_filter($this->plugins, function (Plugin $_plugin) use ($plugin) {
            return $_plugin != $plugin;
        });
        return $this;
    }

    /**
     * @param $name
     * @return array
     */
    public function __get($name)
    {
        if ($name === 'enabledPlugins' || $name === 'disabledPlugins') {
            $enabledPlugins = [];
            $disabledPlugins = [];

            foreach ($this->plugins as $plugin) {
                if ($plugin->isEnabled()) {
                    array_push($enabledPlugins, $plugin);
                }
                else {
                    array_push($disabledPlugins, $plugin);
                }
            }

            if ($name === 'disabledPlugins') {
                return $disabledPlugins;
            }

            return $enabledPlugins;
        }
        return $this->$name;
    }

    /**
     * Enable a specific plugin
     *
     * @param Plugin $plugin
     * @return Plugin
     */
    public function enable(Plugin $plugin)
    {
        return $plugin->enable();
    }

    /**
     * Disable a specific plugin
     *
     * @param Plugin $plugin
     * @return Plugin
     * @throws PluginNotFoundException
     */
    public function disable(Plugin $plugin)
    {
        return $plugin->disable();
    }

    /**
     * Toggle a specific plugin
     *
     * @param Plugin $plugin
     * @return Plugin
     */
    public function toggle(Plugin $plugin)
    {
        return $plugin->toggle();
    }

    /**
     * Check plugin is enabled
     *
     * @param Plugin $plugin
     * @return bool
     */
    public function isEnabled(Plugin $plugin) : bool
    {
        return $plugin->isEnabled();
    }

    /**
     * Check plugin is disabled
     *
     * @param Plugin $plugin
     * @return bool
     */
    public function isDisabled(Plugin $plugin) : bool
    {
        return $plugin->isDisabled();
    }

    /**
     * Load all plugins in a directory
     *
     * @param string|null $directory
     * @param bool $nested
     * @return $this
     */
    public function autoload(?string $directory = null, bool $nested = true)
    {
        if ($directory != null) $this->directory = $directory;

        // load plugins
        $this->autoloadDirectory($this->directory, $nested, '');

        // return $this for chained functions
        return $this;
    }

    public function autoloadDirectory(string $directory, bool $nested = true, string $prefix = '')
    {
        foreach (scandir($directory) as $path) {
            if ($path == '.' || $path == '..') {
                continue;
            }

            $path = implode(DIRECTORY_SEPARATOR, [$directory, $path]);

            $pluginFileName = str_replace(['.php', '.disabled.php'], ['', ''], $path);
            if (is_file($path) && Str::endsWith($pluginFileName, 'Plugin')) {
                $this->autoloadPlugin($path);
            }
            else if (is_dir($path)) {
                $prefix = $prefix.Str::afterLast($path, DIRECTORY_SEPARATOR);
                $this->autoloadDirectory($path, $nested, $prefix);
            }
        }
    }

    private function autoloadPlugin(string $plugin)
    {
        // check file is a php file
        if (Str::endsWith($plugin, '.php'))
        {
            $filename = $plugin;

            // load plugin from filename
            $plugin = $this->load($filename);

            // set plugin variables
            $plugin->filename = $filename;
            $plugin->enabled = $plugin->isEnabled();

            // add plugin to memory
            $this->add($plugin);
        }
    }

    /**
     * Load a plugin file
     *
     * @param string $pluginPath
     * @param mixed ...$attributes
     * @return Plugin
     */
    public function load(string $pluginPath, ...$attributes) : Plugin
    {
        // include once plugin file with return
        $classname = include_once $pluginPath;

        // if class name is not string then sets as filename
        if (!(is_string($classname) && strlen($classname) > 0)) $classname = pathinfo($pluginPath)['filename'];

        // if extension is disabled extension then remove disabled parameter in filename
        if (Str::endsWith($pluginPath, '.disabled.php')) $classname = explode('.', $classname)[0];

        // return plugin as class
        return new $classname($attributes);
    }

    /**
     * Execute method of all plugins
     *
     * @param string $name
     * @param mixed ...$arguments
     * @return bool
     */
    public function execute(string $name, ...$arguments) : bool
    {
        $success = true;
        foreach ($this->plugins as $plugin)
            if ($this->isEnabled($plugin))
                $success = $plugin->execute($name, $arguments)->success == false ? false : $success;
        return $success;
    }
}