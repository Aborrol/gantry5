<?php
namespace Gantry\Component\Data;

use Gantry\Component\Getters\Getters;
use Gantry\Component\Filesystem\FileInterface;
use Gantry\Component\Filesystem\File;

/**
 * Recursive data object
 *
 * @author RocketTheme
 * @license MIT
 */
class Data extends Getters implements DataInterface
{
    /**
     * @var Blueprints
     */
    protected $blueprints;

    /**
     * @var File\General
     */
    protected $storage;

    /**
     * @param array $items
     * @param Blueprint $blueprints
     */
    public function __construct(array $items = array(), Blueprint $blueprints = null)
    {
        $this->items = $items;

        $this->blueprints = $blueprints;
    }

    /**
     * Get value by using dot notation for nested arrays/objects.
     *
     * @example $value = $data->value('this.is.my.nested.variable');
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function value($name, $default = null, $separator = '.')
    {
        return $this->get($name, $default, $separator);
    }

    /**
     * Get value by using dot notation for nested arrays/objects.
     *
     * @example $value = $data->get('this.is.my.nested.variable');
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function get($name, $default = null, $separator = '.')
    {
        $path = explode($separator, $name);
        $current = $this->items;
        foreach ($path as $field) {
            if (is_object($current) && isset($current->{$field})) {
                $current = $current->{$field};
            } elseif (is_array($current) && isset($current[$field])) {
                $current = $current[$field];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Sey value by using dot notation for nested arrays/objects.
     *
     * @example $value = $data->set('this.is.my.nested.variable', true);
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      New value.
     * @param string  $separator  Separator, defaults to '.'
     */
    public function set($name, $value, $separator = '.')
    {
        $path = explode($separator, $name);
        $current = &$this->items;
        foreach ($path as $field) {
            if (is_object($current)) {
                // Handle objects.
                if (!isset($current->{$field})) {
                    $current->{$field} = array();
                }
                $current = &$current->{$field};
            } else {
                // Handle arrays and scalars.
                if (!is_array($current)) {
                    $current = array($field => array());
                } elseif (!isset($current[$field])) {
                    $current[$field] = array();
                }
                $current = &$current[$field];
            }
        }

        $current = $value;
    }

    /**
     * Set default value by using dot notation for nested arrays/objects.
     *
     * @example $data->def('this.is.my.nested.variable', 'default');
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function def($name, $default = null, $separator = '.')
    {
        $this->set($name, $this->get($name, $default, $separator), $separator);
    }

    /**
     * Merge two sets of data together.
     *
     * @param array $data
     */
    public function merge(array $data)
    {
        if ($this->blueprints) {
            $this->items = $this->blueprints->mergeData($this->items, $data);
        } else {
            $this->items = array_merge($this->items, $data);
        }
    }

    /**
     * Return blueprints.
     *
     * @return Blueprint
     */
    public function blueprints()
    {
        return $this->blueprints;
    }

    /**
     * Validate by blueprints.
     *
     * @throws \Exception
     */
    public function validate()
    {
        if ($this->blueprints) {
            $this->blueprints->validate($this->items);
        }
    }

    /**
     * Filter all items by using blueprints.
     */
    public function filter()
    {
        if ($this->blueprints) {
            $this->items = $this->blueprints->filter($this->items);
        }
    }

    /**
     * Get extra items which haven't been defined in blueprints.
     *
     * @return array
     */
    public function extra()
    {
        return $this->blueprints ? $this->blueprints->extra($this->items) : array();
    }

    /**
     * Save data if storage has been defined.
     */
    public function save()
    {
        $file = $this->file();
        if ($file) {
            $file->save($this->items);
        }
    }

    /**
     * Returns whether the data already exists in the storage.
     *
     * NOTE: This method does not check if the data is current.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->file()->exists();
    }

    /**
     * Return unmodified data as raw string.
     *
     * NOTE: This function only returns data which has been saved to the storage.
     *
     * @return string
     */
    public function raw()
    {
        return $this->file()->raw();
    }

    /**
     * Set or get the data storage.
     *
     * @param FileInterface $storage Optionally enter a new storage.
     * @return FileInterface
     */
    public function file(FileInterface $storage = null)
    {
        if ($storage) {
            $this->storage = $storage;
        }
        return $this->storage;
    }
}
