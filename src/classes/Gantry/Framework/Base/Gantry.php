<?php
namespace Gantry\Framework\Base;

use RocketTheme\Toolbox\DI\Container;
use Gantry\Component\Filesystem\StreamsServiceProvider;

class Gantry extends Container
{
    /**
     * @var static
     */
    protected static $instance;
    protected $wrapper;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = static::load();
        }

        return self::$instance;
    }

    public function route($route)
    {
        $routes = $this->offsetGet('routes');
        if (!isset($routes[$route])) {
            throw new \InvalidArgumentException(sprintf('Invalid route: %s', $route));
        }

        return $this->offsetGet('base_url') . $routes[$route];
    }

    public function wrapper($value = null)
    {
        if ($value !== null ) {
            $this->wrapper = $value;
        }

        return $this->wrapper;
    }

    protected static function load()
    {
        $instance = new static();

        $instance->register(new StreamsServiceProvider);

        return $instance;
    }
}
