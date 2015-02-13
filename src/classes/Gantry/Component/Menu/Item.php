<?php
namespace Gantry\Component\Menu;

use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;

/**
 * @property string $id
 * @property string $type
 * @property string $path
 * @property string $alias
 * @property string $title
 * @property string $link
 * @property string $parent_id
 * @property string $layout
 * @property int $browserNav
 * @property bool $menu_text
 * @property bool $visible
 * @property int $group
 * @property int $level
 */
class Item implements \ArrayAccess, \Iterator
{
    use ArrayAccessWithGetters, Export;

    protected $items;
    protected $menu;
    protected $groups = [];
    protected $children = [];
    protected $url;

    public function __construct($menu, $name, array $item = [])
    {
        $this->menu = $menu;

        $parent = dirname($name);
        $alias = basename($name);

        $this->items = $item + [
            'id' => preg_replace('|[^a-z0-9]|i', '-', $name) ?: 'root',
            'type' => 'link',
            'path' => $name,
            'alias' => $alias,
            'title' => ucfirst($alias),
            'link' => $name,
            'parent_id' => $parent != '.' ? $parent : '',
            'layout' => 'list',
            'browserNav' => 0,
            'menu_text' => true,
            'visible' => true,
            'group' => 0,
            'level' => 0,
        ];
    }

    /**
     * @return string
     */
    public function url($url = false)
    {
        if ($url !== false) {
            $this->url = $url;
        }
        return $this->url;
    }

    /**
     * @return Item
     */
    public function parent()
    {
        return $this->menu[$this->items['parent_id']];
    }

    public function groups()
    {
        return $this->groups ?: [$this->children];
    }

    public function children()
    {
        return $this->children;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }

    public function getGroup($i)
    {
        $groups = $this->groups();
        $i = (int) $i;

        return isset($groups[$i]) ? $groups[$i] : [];
    }

    public function update(array $data)
    {
        $this->items = array_replace($this->items, $data);

        return $this;
    }

    public function addChild(Item $child)
    {
        $child->level = $this->level + 1;
        $this->children[$child->alias] = $child;

        return $this;
    }

    public function removeChild(Item $child)
    {
        unset($this->children[$child->alias]);

        return $this;
    }

    public function sortChildren($ordering)
    {
        // Array with keys that point to the items.
        $children =& $this->children;

        if ($children) {
            if (is_array($ordering)) {
                // Remove extra items from ordering and reorder.
                $children = array_replace(array_intersect_key($ordering, $children), $children);
            } else {
                switch ((string) $ordering) {
                    case 'abc':
                        // Alphabetical ordering.
                        ksort($children);
                        break;
                    case 'cba':
                        // Reversed alphabetical ordering.
                        krsort($children);
                        break;
                }
            }
        }

        return $this;
    }


    public function reverse()
    {
        array_reverse($this->children, true);
        array_reverse($this->groups, true);

        return $this;
    }

    public function groupChildren(array $groups)
    {
        // Array with keys that point to the items.
        $children =& $this->children;

        if ($children) {
            $ordered = [];
            $this->groups[0] = [];
            foreach ($groups as $i => $ordering) {
                if (!$ordering || !is_array($ordering)) {
                    continue;
                }

                // Get the items for this group with proper ordering.
                $group = array_replace(
                    array_intersect_key($ordering, $children), array_intersect_key($children, $ordering)
                );

                // Assign each menu items to the group.
                $group = array_map(
                    function($value) use ($i) {
                        $value->group = $i;
                        return $value;
                    },
                    $group
                );

                // Update remaining children.
                $children = array_diff_key($children, $ordering);

                // Build child ordering.
                $ordered += $group;

                // Add items to the current group.
                $this->groups[$i] = $group;
            }

            if ($children) {
                // Add leftover children to the ordered list and to the first group.
                $ordered += $children;
                $this->groups[0] += $children;
            }

            // Reorder children by their groups.
            $children = $ordered;
        }

        return $this;
    }

    // Implements \Iterator

    /**
     * Returns the current child.
     *
     * @return mixed  Can return any type.
     */
    public function current()
    {
        return current($this->children);
    }

    /**
     * Returns the key of the current child.
     *
     * @return mixed  Returns scalar on success, or NULL on failure.
     */
    public function key()
    {
        return key($this->children);
    }

    /**
     * Moves the current position to the next child.
     *
     * @return void
     */
    public function next()
    {
        next($this->children);
    }

    /**
     * Rewinds back to the first child.
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->children);
    }

    /**
     * This method is called after Iterator::rewind() and Iterator::next() to check if the current position is valid.
     *
     * @return bool  Returns TRUE on success or FALSE on failure.
     */
    public function valid()
    {
        return key($this->children) !== null;
    }
}
