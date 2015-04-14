<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

/**
 * Joomla manifest file modifier.
 *
 * @package Gantry\Admin
 */
class Manifest
{
    protected $theme;
    protected $path;
    protected $xml;

    /**
     * @param string $theme
     * @throws \RuntimeException
     */
    public function __construct($theme)
    {
        $this->theme = $theme;
        $this->path = JPATH_SITE . "/templates/{$theme}/templateDetails.xml";

        if (!is_file($this->path)) {
            throw new \RuntimeException(sprintf('Template %s does not exist.', $theme));
        }
        $this->xml = simplexml_load_file($this->path);
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getXml()
    {
        return $this->xml;
    }

    public function setPositions(array $positions)
    {
        $target = $this->xml->positions[0];

        // Remove all the old positions.
        foreach ($target->children() as $child) {
            unset($child);
        }

        // Create the new positions.
        foreach ($positions as $position) {
            $target->addChild('position', $position);
        }
    }

    public function save()
    {
        if (!$this->xml->asXML($this->path)) {
            throw new \RuntimeException(sprintf('Saving manifest for %s template failed', $this->theme));
        }
    }
}
