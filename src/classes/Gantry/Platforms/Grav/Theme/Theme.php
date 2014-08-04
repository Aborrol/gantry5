<?php
namespace Gantry\Theme;

use Gantry\Base\Gantry;
use Symfony\Component\Yaml\Yaml;
use Gantry\Filesystem\File;
use Grav\Common\Registry;

class Theme extends \Gantry\Base\Theme
{
    public function __construct( $path, $name = '' )
    {
        parent::__construct($path, $name);

        $baseUrlRelative = Registry::get('Config')->get('system.base_url_relative');
        $this->url = $baseUrlRelative .'/'. USER_PATH . basename(THEMES_DIR) .'/'. $this->name;
    }

    public function render($file, array $context = array()) {}
}
