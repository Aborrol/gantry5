<?php
define('PRIME_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));
define('PRIME_URI', dirname($_SERVER['SCRIPT_NAME']));

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once PRIME_ROOT . '/includes/gantry.php';

// Get current theme and path.
$path = explode('/', Gantry\Component\Filesystem\Folder::getRelativePath($_SERVER['REQUEST_URI'], PRIME_URI), 2);
$theme = array_shift($path);
$path = array_shift($path);
$extension = strrchr(basename($path), '.');
$path = substr(trim($path, '/') ?: 'home', 0, -strlen($extension) ?: 9999);

define('THEME', $theme);
define('PAGE_PATH', $path);
define('PAGE_EXTENSION', trim($extension, '.') ?: 'html');

// Bootstrap selected theme.
$include = PRIME_ROOT . "/themes/{$theme}/includes/gantry.php";
if (is_file($include)) {
    include $include;
}

// Enter to administration if we are in /ROOT/theme/admin. Also display installed themes if no theme has been selected.
if (!isset($gantry['theme']) || strpos($path, 'admin') === 0) {
    require_once PRIME_ROOT . '/admin/admin.php';
    exit();
}

// Boot the service.
/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme']->setLayout('gantry-layouts://test.yaml');

try {
    // Render the page.
    echo $theme->render('@pages/' . PAGE_PATH . '.' . PAGE_EXTENSION . '.twig');
} catch (Twig_Error_Loader $e) {
    // Or display error if template file couldn't be found.
    echo $theme->render('@pages/_error.html.twig', ['error' => $e]);
}