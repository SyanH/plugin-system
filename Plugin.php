<?php

namespace Syan\PluginSystem;

use Syan\PluginSystem\Exceptions\AttributeNotExistsException;
use Syan\PluginSystem\Helpers\Str;
use stdClass;

/**
 * Class Plugin
 * @package Syan\PluginSystem
 */
abstract class Plugin
{

    /**
     * Your plugins unique name
     *
     * @var string $name
     */
    protected string $name;

    /**
     * Your plugins readable name
     *
     * @var string $title
     */
    protected string $title;

    /**
     * Your plugins description
     *
     * @var string $description
     */
    protected string $description = '';

    /**
     * Your plugins version
     *
     * @var string $version
     */
    protected string $version = 'v1.0';

    /**
     * Your plugins author
     *
     * @var string $author
     */
    protected string $author = '';

    /**
     * Your plugins dependency
     *
     * @var array $dependencys
     */
    protected array $dependencys = [];

    /**
     * Plugin constructor.
     * @param array $attributes
     * @throws AttributeNotExistsException
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill attributes from array in plugin
     *
     * @param array $attributes
     * @return Plugin
     * @throws AttributeNotExistsException
     */
    public function fill(array $attributes) : Plugin
    {
        foreach ($attributes as $key => $value) $this->setAttribute($key, $value);
        return $this;
    }

    /**
     * Set attribute in plugin
     *
     * @param string $key
     * @param $value
     * @return Plugin
     * @throws AttributeNotExistsException
     */
    public function setAttribute(string $key, $value) : Plugin
    {
        if (!isset($this->{$key})) throw new AttributeNotExistsException;
        $this->{$key} = $value;
        return $this;
    }

    /**
     * Get attribute from plugin
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return null;
    }

    /**
     * Check plugin method is exists
     *
     * @param string $name
     * @return bool
     */
    public function hasMethod(string $name): bool
    {
        foreach (get_class_methods($this) as $index => $method) {
            if ($method === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check plugin method is exists
     *
     * @param string $name
     * @return bool
     */
    public function hasFunction(string $name): bool
    {
        return $this->hasMethod($name);
    }

    /**
     * Check plugin is enabled
     *
     * @return bool
     */
    public function isEnabled() : bool
    {
        // get filename of plugin without extension and disable parameters
        $filename = pathinfo($this->filename)['dirname'].DIRECTORY_SEPARATOR.explode('.', pathinfo($this->filename)['filename'])[0];

        // check file is exists
        return (file_exists($filename.'.php'));
    }

    /**
     * Check plugin is disabled
     *
     * @return bool
     */
    public function isDisabled() : bool
    {
        // reverse isEnabled method
        return !$this->isEnabled();
    }

    public function enableCallback() {}

    public function disableCallback() {}

    /**
     * Enable plugin
     *
     * @return $this
     */
    public function enable()
    {
        $this->enableCallback();
        // get filename of plugin without extension and disable parameters
        $filename = pathinfo($this->filename)['dirname'].DIRECTORY_SEPARATOR.explode('.', pathinfo($this->filename)['filename'])[0];

        // remove disabled state if is disabled
        if ($this->isDisabled()) rename($filename.'.disabled.php', $filename.'.php');

        // set filename of plugin in memory
        $this->filename = $filename.'.php';

        // return this for chain functions
        return $this;
    }

    /**
     * Disable plugin
     *
     * @return $this
     */
    public function disable()
    {
        $this->disableCallback();
        // get filename of plugin without extension and disable parameters
        $filename = pathinfo($this->filename)['dirname'].DIRECTORY_SEPARATOR.pathinfo($this->filename)['filename'];

        // add disabled state if is enabled
        if ($this->isEnabled()) rename($filename.'.php', $filename.'.disabled.php');

        // set filename of plugin in memory
        $this->filename = $filename.'.disabled.php';

        // return this for chain functions
        return $this;
    }

    /**
     * Toggle enabled state of plugin
     *
     * @return $this
     */
    public function toggle()
    {
        return $this->isEnabled() ? $this->disable() : $this->enable();
    }

    /**
     * Execute method in plugin
     *
     * @param string $name
     * @param array $arguments
     * @return object
     */
    public function execute(string $name, array $arguments = []) : object
    {
        // each all methods in plugin
        foreach (get_class_methods($this) as $index => $method)
        {
            // check if method is requested
            if ($method === $name)
            {
                $starts_at = microtime(true);

                $result = new stdClass;
                $result->enabled = $this->isEnabled();
                $result->success = true;
                $result->class = $this;
                $result->function = $name;
                $result->arguments = $arguments;
                $result->return = call_user_func_array(array($this, $name), $arguments);

                $ends_at = microtime(true);
                $result->executed_seconds = ($ends_at - $starts_at);
                return $result;
            }
        }

        // requested method is not found
        return (object) [
            'enabled' => $this->isEnabled(),
            'success' => false,
            'class' => $this,
            'function' => $name,
            'arguments' => $arguments,
            'return' => null,
            'executed_seconds' => 0,
        ];
    }
}