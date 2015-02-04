<?php
namespace Gantry\Admin\Particles;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Particles
{
    protected $container;
    protected $files;
    protected $particles;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function all()
    {
        if (!$this->particles)
        {
            $files = $this->locateParticles();

            $this->particles = [];
            foreach ($files as $key => $file) {
                $filename = key($file);
                $this->particles[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
            }
        }

        return $this->particles;
    }

    public function group()
    {
        $particles = $this->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            $list[$type][$name] = $particle;
        }

        return $list;
    }

    public function get($id)
    {
        if ($this->particles[$id]) {
            return $this->particles[$id];
        }

        $files = $this->locateParticles();

        if (empty($files[$id])) {
            throw new \RuntimeException("Settings for '{$id}' not found.", 404);
        }

        $filename = key($files[$id]);
        $particle = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        $particle['subtype'] = $id; // TODO: can this be done better or is it fine like that?

        return $particle;
    }

    protected function locateParticles()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-blueprints://particles');

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}
