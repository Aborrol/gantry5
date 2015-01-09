<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Menu\Item;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;

class Menu implements \ArrayAccess, \Iterator
{
    use ArrayAccessWithGetters, Iterator, Export;

    protected $app;

    protected $default;
    protected $base;
    protected $active;
    protected $params;

    /**
     * @var array
     */
    protected $items;

    protected $defaults = [
        'menu' => null,
        'base' => 0,
        'startLevel' => 1,
        'endLevel' => 0,
        'showAllChildren' => false,
        'highlightAlias' => true,
        'highlightParentAlias' => true,
        'window_open' => null
    ];

    public function __construct()
    {
        $this->default = 'home';
        $this->active  = PAGE_PATH;
    }

    public function instance(array $params = [])
    {
        $params += $this->defaults;

        $instance = clone $this;
        $instance->params = $params;

        $instance->items = $instance->getList($params);

        return $instance;
    }

    public function root()
    {
        return $this->offsetGet('');
    }

    /**
     * @return object
     */
    public function getBase()
    {
        return $this->offsetGet($this->base);
    }

    /**
     * @return object
     */
    public function getDefault()
    {
        return $this->offsetGet($this->default);
    }

    /**
     * @return object
     */
    public function getActive()
    {
        return $this->offsetGet($this->active);
    }

    public function isActive($item)
    {
        if ($item->path && strpos($this->base, $item->path) === 0) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->path == $this->getActive()->path;
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
        $items = (array) isset($this->params['items']) ? $this->params['items'] : null;

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

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $config
     *
     * @return array
     */
    protected function getList(array $config)
    {
        $items = (array) (isset($config['items']) ? $config['items'] : null);
        $params = (array) (isset($config['config']) ? $config['config'] : null);

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
        $menuItems = array_unique(array_merge(Folder::all($folder, $options), array_keys($items)));
        sort($menuItems);

        $all = ['' => new Item('')];
        foreach ($menuItems as $name) {
            $parent = dirname($name);
            $level = substr_count($name, '/') + 1;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || (!$showAll && $level > 1 && strpos($parent, $path) !== 0)
                || ($start > 1 && strpos(dirname($parent), $path) !== 0)
                || ($name[0] == '_' || strpos($name, '_'))) {
                continue;
            }

            $item = new Item($name, isset($items[$name]) && is_array($items[$name]) ? $items[$name] : []);

            // Deal with home page.
            if ($item->link == 'home') {
                $item->link = '';
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
                    $item->link = null;
                    break;

                case 'url':
                    break;

                case 'alias':
                default:
                    $item->link = '/' . trim(PRIME_URI . '/' . THEME . '/' . $item->link, '/');
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
            if (isset($all[$item->parent])) {
                $all[$item->parent]->addChild($item);
            } else {
                $all['']->addChild($item);
            }
            $all[$item->path] = $item;
        }

        $ordering = (array) (isset($config['ordering']) ? $config['ordering'] : null);
        $this->sortAll($all, $ordering);

        return $all;
    }

    protected function sortAll(array &$items, array &$ordering, $path = '')
    {
        if (empty($items[$path]->children)) {
            return;
        }

        /** @var Item $item */
        $item = $items[$path];
        if ($this->isAssoc($ordering)) {
            $item->sortChildren($ordering);
        } else {
            $item->groupChildren($ordering);
        }

        foreach ($ordering as $key => &$value) {
            if (is_array($value)) {
                $newPath = $path ? $path . '/' . $key : $key;
                $this->sortAll($items, $value, $newPath);
            }
        }
    }


    protected function isAssoc(array $array)
    {
        return (array_values($array) !== $array);
    }


}
