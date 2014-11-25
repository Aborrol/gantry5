<?php
namespace Gantry\Component\Config;

use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $gantry['blueprints'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];

            $cache = $locator->findResource('gantry-cache://compiled/blueprints', true, true);
            $paths = $locator->findResources('gantry-blueprints://config');
            $files = (new ConfigFileFinder)->locateFiles($paths);

            $config = new CompiledBlueprints($cache, $files);

            return $config->load();
        };
        $gantry['config'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];

            $cache = $locator->findResource('gantry-cache://compiled/config', true, true);
            $paths = $locator->findResources('gantry-config://');
            $files = (new ConfigFileFinder)->locateFiles($paths);

            $config = new CompiledConfig($cache, $files, function() use ($c) {
                return $c['blueprints'];
            });

            return $config->load();
        };
    }
}
