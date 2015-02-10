<?php
namespace Gantry\Framework\Base;

use Grav\Common\Grav;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Document
{
    public static function addHeaderTag(array $element)
    {
        return false;
    }

    public static function rootUri()
    {
        return '';
    }

    /**
     * Return URL to the resource.
     *
     * @example {{ url('theme://images/logo.png')|default('http://www.placehold.it/150x100/f4f4f4') }}
     *
     * @param  string $input    Resource to be located.
     * @param  bool $domain     True to include domain name.
     * @return string|null      Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false)
    {
        if (!$url) {
            return null;
        }

        if ($url[0] == '/') {
            // Absolute path in our server, nothing to do.
            // TODO: add support to include domain..
            return $url;

        } elseif (strpos($url, '://') !== false) {
            // Resolve stream to a relative path.
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            try {
                // Attempt to find our resource.
                $url = $locator->findResource($url, false);
            } catch (\Exception $e) {
                // Scheme did not exist; assume that we had valid scheme (like http) so no modification is needed.
                return $url;
            }
        }

        // TODO: add support to include domain..
        return $url ? rtrim(static::rootUri(), '/') .'/'. $url : null;
    }
}
