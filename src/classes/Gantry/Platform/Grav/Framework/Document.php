<?php
namespace Gantry\Framework;

use Grav\Common\Grav;

class Document
{
    public static function addHeaderTag(array $element)
    {
        // TODO: use new class
        return false;
    }

    public static function rootUri()
    {
        $config = Grav::instance()['config'];
        return $config->get('base_url_relative');
    }
}
