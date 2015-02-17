<?php
namespace Gantry\Admin\Theme;

use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeList
{
    /**
     * @return array
     */
    public static function getStyles()
    {
        // Load styles
        $db    = \JFactory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('s.id, s.template AS name, title, s.params')
            ->from('#__template_styles AS s')
            ->where('s.client_id = 0')
            ->where('e.enabled = 1')
            ->leftJoin('#__extensions AS e ON e.element=s.template AND e.type='
            . $db->quote('template') . ' AND e.client_id=s.client_id');

        $db->setQuery($query);
        $templates = (array) $db->loadObjectList();

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $list = array();

        foreach ($templates as $template)
        {
            if (file_exists(JPATH_SITE . '/templates/' . $template->name . '/gantry/theme.yaml'))
            {
                $details = new ThemeDetails($template->name);

                if (!$locator->schemeExists('gantry-theme-' . $template->name)) {
                    $locator->addPath('gantry-themes-' . $template->name, '', $details->getPaths());
                }

                $params = new \JRegistry($template->params);

                $details['id'] = $template->id;
                $details['name'] = $template->name;
                $details['title'] = $template->title;
                $details['thumbnail'] = 'template_thumbnail.png';
                $details['preview_url'] = \JUri::root(false) . 'index.php?templateStyle=' . $template->id;
                $details['admin_url'] = \JRoute::_('index.php?option=com_gantryadmin&view=overview&style=' . $template->id, false);
                $details['params'] = $params->toArray();

                $list[$template->id] = $details;
            }
        }

        // Add Thumbnails links.
        foreach ($list as $details) {
            $details['thumbnail'] = self::getImage($locator, $details, 'thumbnail');
        }

        return $list;
    }

    protected static function getImage(UniformResourceLocator $locator, $details, $image)
    {
        $image = $details["details.images.{$image}"];

        if (!strpos($image, '://')) {
            $name = $details['name'];
            $image = "gantry-themes-{$name}://{$image}";
        }

        try {
            $image = $locator->findResource($image, false);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }
}
