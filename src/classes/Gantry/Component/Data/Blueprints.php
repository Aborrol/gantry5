<?php
namespace Gantry\Component\Data;

use Symfony\Component\Yaml\Yaml;

/**
 * Blueprints class keeps track on blueprint instances.
 *
 * @author RocketTheme
 * @license MIT
 */
class Blueprints
{
	protected $extension = '.yaml';
    protected $search;
    protected $types;
    protected $instances = array();

    /**
     * @param  string  $search  Search path.
     */
    public function __construct($search)
    {
        $this->search = rtrim($search, '\\/') . '/';
    }

    /**
     * Get blueprint.
     *
     * @param  string  $type  Blueprint type.
     * @return Blueprint
     * @throws \RuntimeException
     */
    public function get($type)
    {
        if (!isset($this->instances[$type])) {
            if (is_file($this->search . $type . $this->extension)) {
                $blueprints = (array) Yaml::parse($this->search . $type . $this->extension);
            } else {
                $blueprints = array();
                // throw new \RuntimeException("Blueprints for '{$type}' cannot be found! {$this->search}{$type}");
            }

            $blueprint = new Blueprint($type, $blueprints, $this);

            if (isset($blueprints['@extends'])) {
                // Extend blueprint by other blueprints.
                $extends = (array) $blueprints['@extends'];
                foreach ($extends as $extendType) {
                    $blueprint->extend($this->get($extendType));
                }
            }

            $this->instances[$type] = $blueprint;
        }

        return $this->instances[$type];
    }

    /**
     * Get all available blueprint types.
     *
     * @return  array  List of type=>name
     */
    public function types()
    {
        if ($this->types === null) {
            $this->types = array();

            $iterator   = new \DirectoryIterator($this->search);
            /** @var \DirectoryIterator $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || '.' . $file->getExtension() != $this->extension) {
                    continue;
                }
                $name = $file->getBasename($this->extension);
                $this->types[$name] = ucfirst(strtr($name, '_', ' '));
            }
        }
        return $this->types;
    }
}
