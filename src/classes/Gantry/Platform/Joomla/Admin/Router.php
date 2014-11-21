<?php
namespace Gantry\Admin;

use Gantry\Component\Router\RouterInterface;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Router implements RouterInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch()
    {
        $this->container['theme.path'] = JPATH_SITE . '/templates/gantry';

        $this->container['admin.theme'] = function () {
            return new \Gantry\Admin\Theme\Theme(GANTRYADMIN_PATH);
        };

        // Boot the service.
        $this->container['admin.theme'];
        $this->container['base_url'] = \JUri::base(false) . 'index.php?option=com_gantryadmin';

        $app = \JFactory::getApplication();
        $input = $app->input;
        $view = $input->getCmd('view', 'themes');
        $layout = $input->getCmd('layout', 'index');
        $style = $input->getInt('style', 0);
        $params = [
            'style' => $style,
            'id' => $input->getInt('id')
        ];

        $this->container['routes'] = [
            'themes' => '',
            'overview' => '&view=overview&style=' . $style,
            'settings' => '&view=settings&style=' . $style,
            'pages' => '&view=pages&style=' . $style,
            'pages/edit' => '&view=pages&layout=edit&style=' . $style,
            'pages/create' => '&view=pages&layout=create&style=' . $style,
            'assignments' => '&view=assignments&style=' . $style,
            'updates' => '&view=updates&style=' . $style,
        ];

        // Render the page.
        try {
            $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($view);

            if (class_exists($class) && method_exists($class, $layout)) {
                $controller = new $class($this->container);
                $controller->{$layout}($params);
            } else {
                throw new \RuntimeException('Not Found', 404);
            }

        } catch (\Exception $e) {
            if (class_exists('\Tracy\Debugger') && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode ) {
                // We have Tracy enabled; will display and/or log error with it.
                throw $e;
            }

            \JError::raiseError(500, $e->getMessage());
        }
    }
}
