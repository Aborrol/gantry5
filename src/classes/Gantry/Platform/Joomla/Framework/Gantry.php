<?php
namespace Gantry\Framework;

use Gantry\Base\Config;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['site'] = function ($c) {
            return new Site;
        };

        return $container;
    }
}
