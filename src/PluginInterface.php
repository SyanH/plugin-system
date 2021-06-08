<?php

namespace Syan\PluginSystem;

interface PluginInterface
{
    public function startCallback($worker);
    public function enabledCallback();
    public function disabledCallback();
}