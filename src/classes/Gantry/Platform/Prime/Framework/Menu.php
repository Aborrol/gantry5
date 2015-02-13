<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends AbstractMenu
{
    public function __construct()
    {
        $this->default = 'home';
        $this->active  = PAGE_PATH;
    }

    public function getMenus()
    {
        static $list;

        if ($list === null) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $finder = new ConfigFileFinder;

            $list = $finder->getFiles($locator->findResources('gantry-config://menu', false));

            // Always have main menu.
            $list += ['mainmenu' => 1];

            $list = array_keys($list);
            sort($list);
        }

        return $list;
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     *
     * @return  string
     */
    protected function calcBase($path)
    {
        if (!$path || !is_file(PRIME_ROOT . "/pages/{$path}.html.twig")) {
            // Use active menu item or fall back to default menu item.
            $path = $this->active ?: $this->default;
        }

        // Return base menu item.
        return $path;
    }

    public function getMenuItems()
    {
        $config = $this->config();
        $items = isset($config['items']) ? $config['items'] : [];

        $folder = PRIME_ROOT . '/pages';
        if (!is_dir($folder)) {
            return $items;
        }

        $options = [
            'pattern' => '|\.html\.twig|',
            'filters' => ['key' => '|\.html\.twig|', 'value' => function () { return []; }],
            'key' => 'SubPathname'
        ];

        $items += Folder::all($folder, $options);
        ksort($items);

        return $items;
    }

    public function config()
    {
        if (!$this->config) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $menu = $this->params['menu'];

            $this->config = new Config(CompiledYamlFile::instance($locator("gantry-config://menu/{$menu}.yaml"))->content());
        }

        return $this->config;
    }

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $params
     *
     * @return array
     */
    protected function getList(array $params)
    {
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        $path    = $this->base;
        $start   = $params['startLevel'];
        $end     = $params['endLevel'];
        $showAll = $params['showAllChildren'];

        $options = [
            'levels' => $end - $start,
            'pattern' => '|\.html\.twig|',
            'filters' => ['value' => '|\.html\.twig|']
        ];

        $folder = PRIME_ROOT . '/pages';
        if (!is_dir($folder)) {
            return [];
        }
        $config = $this->config();
        $items = isset($config['items']) ? $config['items'] : [];
        $menuItems = array_unique(array_merge(Folder::all($folder, $options), array_keys($items)));
        sort($menuItems);

        /** @var array|Item[] $all */
        $all = ['' => new Item($this, '', ['layout' => 'horizontal'])];
        foreach ($menuItems as $name) {
            $parent = dirname($name);
            $level = substr_count($name, '/') + 1;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || (!$showAll && $level > 1 && strpos($parent, $path) !== 0)
                || ($start > 1 && strpos(dirname($parent), $path) !== 0)
                || (!$name || $name[0] == '_' || strpos($name, '_'))
            ) {
                continue;
            }

            $item = new Item($this, $name, isset($items[$name]) && is_array($items[$name]) ? $items[$name] : []);

            // Deal with home page.
            if ($item->link == 'home') {
                $item->url('');
            }

            // Placeholder page.
            if ($item->type == 'link' && !is_file(PRIME_ROOT . "/pages/{$item->path}.html.twig")) {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'hidden':
                case 'separator':
                case 'heading':
                    // Separator and heading have no link.
                    $item->url(null);
                    break;

                case 'url':
                    break;

                case 'alias':
                default:
                    $item->url('/' . trim(PRIME_URI . '/' . THEME . '/' . $item->link, '/'));
            }

            switch ($item->browserNav)
            {
                default:
                case 0:
                    // Target window: Parent.
                    $item->anchor_attributes = '';
                    break;
                case 1:
                    // Target window: New with navigation.
                    $item->anchor_attributes = ' target="_blank"';
                    break;
                case 2:
                    // Target window: New without navigation.
                    $item->anchor_attributes = ' onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes' . ($params['window_open'] ? ',' . $params['window_open'] : '') . '\');return false;"';
                    break;
            }

            // Build nested tree structure.
            if (isset($all[$item->parent_id])) {
                $all[$item->parent_id]->addChild($item);
            } else {
                $all['']->addChild($item);
            }
            $all[$item->path] = $item;
        }

        $ordering = $config['ordering'] ? $config['ordering'] : [];
        $this->sortAll($all, $ordering);

        return $all;
    }
}
