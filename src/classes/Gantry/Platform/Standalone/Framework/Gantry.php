<?php
namespace Gantry\Framework;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['config'] = function ($c) {
            return Config::instance(STANDALONE_ROOT . '/cache/config.php', $c['theme.path']);
        };

        $container['site'] = function ($c) {
            return new Site;
        };

        return $container;
    }
}
