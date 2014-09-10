<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Nucleus($c['theme.path'], $this->template);
};

// Boot the service.
$theme = $gantry['theme'];

// Render the page.
echo $theme->render('index.html.twig');
