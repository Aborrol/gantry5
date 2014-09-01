<?php
defined('_JEXEC') or die;

use Gantry\Framework\Gantry;

try
{
    // Attempt to locate Gantry Framework if it hasn't already been loaded.
    if (!class_exists('Gantry'))
    {
        $paths = array(
            __DIR__ . '/../src/bootstrap.php',          // Look if Gantry has been included to the template.
            JPATH_THEMES . '/gantry/src/bootstrap.php'  // Finally look from the default gantry template.
        );

        foreach ($paths as $path)
        {
            if ($path && is_file($path))
            {
                $bootstrap = $path;
            }
        }

        if (!$bootstrap)
        {
            throw new LogicException('Gantry Framework not found!');
        }

        require_once $bootstrap;
    }

    // Get Gantry instance and return it.
    return Gantry::instance();
}
catch (Exception $e)
{
    // Oops, something went wrong!

    if (class_exists( '\Tracy\Debugger' ) && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode ) {
        // We have Tracy enabled; will display and/or log error with it.
        throw $e;
    }

    // In frontend we want to prevent template from loading.
    JError::raiseError(500, 'Failed to load template: ' . $e->getMessage());
}
