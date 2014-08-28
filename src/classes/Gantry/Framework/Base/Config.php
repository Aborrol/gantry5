<?php
namespace Gantry\Framework\Base;

use Gantry\Component\Data\Blueprints;
use Gantry\Component\Data\Data;
use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\File\PhpFile;
use RocketTheme\Toolbox\File\YamlFile;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */
class Config extends Data
{
    /**
     * @var string Configuration location in the disk.
     */
    public $filename;

    /**
     * @var string Path to YAML configuration.
     */
    public $path;

    /**
     * @var string MD5 from the files.
     */
    public $key;

    /**
     * @var array Configuration file list.
     */
    public $files = array();

    /**
     * @var bool Flag to tell if configuration needs to be saved.
     */
    public $updated = false;

    /**
     * Constructor.
     */
    public function __construct($filename, $path, array $data = null)
    {
        $this->filename = $filename;
        $this->path = (string) $path;

        if ($data) {
            $this->key = $data['key'];
            $this->files = $data['files'];
            $this->items = $data['items'];
        }

        $this->reload(false);
    }

    /**
     * Force reload of the configuration from the disk.
     *
     * @param bool $force
     * @return $this
     */
    public function reload($force = true)
    {
        // Build file map.
        $files = $this->build();
        $key = md5(serialize($files) . GANTRY5_VERSION);

        if ($force || $key != $this->key) {
            // First take non-blocking lock to the file.
            PhpFile::instance($this->filename)->lock(false);

            // Reset configuration.
            $this->items = array();
            $this->files = array();
            $this->init($files);
            $this->key = $key;
        }

        return $this;
    }

    /**
     * Save configuration into file.
     *
     * Note: Only saves the file if updated flag is set!
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function save()
    {
        // If configuration was updated, store it as cached version.
        try {
            $file = PhpFile::instance($this->filename);

            // Only save configuration file if it was successfully locked to prevent multiple saves.
            if ($file->locked() !== false) {
                $file->save($this->toArray());
                $file->unlock();
            }
            $this->updated = false;
        } catch (\Exception $e) {
            // TODO: do not require saving to succeed, but display some kind of error anyway.
            throw new \RuntimeException('Writing configuration to cache folder failed.', 500, $e);
        }

        return $this;
    }

    /**
     * Gets configuration instance.
     *
     * @param  string  $filename
     * @param  string  $path
     * @return static
     */
    public static function instance($filename, $path)
    {
        // Load cached version if available..
        if (file_exists($filename)) {
            $data = require_once $filename;

            if (is_array($data) && isset($data['@class']) && $data['@class'] == __CLASS__) {
                $instance = new static($filename, $path, $data);
            }
        }

        // Or initialize new configuration object..
        if (!isset($instance)) {
            $instance = new static($filename, $path);
        }

        // If configuration was updated, store it as cached version.
        if ($instance->updated) {
            $instance->save();
        }

        return $instance;
    }

    /**
     * Convert configuration into an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '@class' => get_class($this),
            'key' => $this->key,
            'files' => $this->files,
            'items' => $this->items
        ];
    }

    /**
     * Initialize object by loading all the configuration files.
     *
     * @param array $files
     */
    protected function init(array $files)
    {
        $this->updated = true;

        // Combine all configuration files into one larger lookup table (only keys matter).
        $allFiles = $files['theme'];

        // Then sort the files to have all parent nodes first.
        // This is to make sure that child nodes override parents content.
        uksort(
            $allFiles,
            function($a, $b) {
                $diff = substr_count($a, '/') - substr_count($b, '/');
                return $diff ? $diff : strcmp($a, $b);
            }
        );

        $blueprints = new Blueprints($this->path . '/blueprints/config');

        $items = array();
        foreach ($allFiles as $name => $dummy) {
            $lookup = array(
                'theme' => $this->path . '/config/' . $name . '.yaml',
            );
            $blueprint = $blueprints->get($name);

            $data = new Data(array(), $blueprint);
            foreach ($lookup as $path) {
                if (is_file($path)) {
                    $data->merge(YamlFile::instance($path)->content());
                }
            }

            // Find the current sub-tree location.
            $current = &$items;
            $parts = explode('/', $name);
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = array();
                }
                $current = &$current[$part];
            }

            // Handle both updated and deleted configuration files.
            $current = $data->toArray();
        }

        $this->items = $items;
        $this->files = $files;
    }

    /**
     * Build a list of configuration files with their timestamps. Used for loading settings and caching them.
     *
     * @return array
     * @internal
     */
    protected function build()
    {
        // Find all system and user configuration files.
        $options = [
            'compare' => 'Filename',
            'pattern' => '|\.yaml$|',
            'filters' => ['key' => '|\.yaml$|'],
            'key' => 'SubPathname',
            'value' => 'MTime'
        ];

        $user = Folder::all($this->path . '/config', $options);

        return array('theme' => $user);
    }
}
