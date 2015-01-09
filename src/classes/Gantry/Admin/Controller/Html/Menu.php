<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu as MenuObject;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'index',
            '/*'            => 'item',
            '/*/**'         => 'item',
        ],
        'POST' => [
            '/'             => 'store',
            '/*'            => 'item',
            '/*/**'         => 'item',
        ],
        'PUT' => [
            '/*' => 'replace'
        ],
        'PATCH' => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];

    public function index()
    {
        return $this->item('main-menu');
    }

    public function item($id)
    {
        $path = array_filter(func_get_args());

        try {
            $resource = $this->loadResource($id);
            array_shift($path);
        } catch (\Exception $e) {
            // Continue for now...
            $id = 'main-menu';
            $resource = $this->loadResource($id);
        }

        $this->params['id'] = 'menu';
        $this->params['prefix'] = 'particles.menu.';
        $this->params['route'] = 'settings';
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['menu'] = $resource;

        /** @var MenuObject $menu */
        $menu = $this->container['menu'];

        /** @var Config $config */
        $config = $this->container['config'];

        $this->params['particle'] = $config->get('particles.instances.menu.' . $id);

        $config->joinDefaults('particles.menu.items', $menu->instance($config->get('particles.menu'))->getMenuItems());

        return $this->container['admin.theme']->render('@gantry-admin/menu.html.twig', $this->params);
    }

    /**
     * Load resource.
     *
     * @param string $id
     * @return Config
     */
    protected function loadResource($id)
    {
        /** @var MenuObject $menus */
        $menus = $this->container['menu'];

        /** @var Config $config */
        $config = $this->container['config'];
        $params = $config->get('particles.instances.menu.' . $id);

        if (!$params) {
            throw new \RuntimeException('Resource not found', 404);
        }

        return $menus->instance($params);
    }

    /**
     * Load blueprints.
     *
     * @return object
     */
    protected function loadBlueprints()
    {
        return $this->container['particles']->get('menu');
    }
}
