<?php
namespace Gantry\Component\Stylesheet;

use Gantry\Component\Stylesheet\Scss\Compiler;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ScssCompiler extends CssCompiler
{
    /**
     * @var string
     */
    public $type = 'scss';

    /**
     * @var string
     */
    public $name = 'SCSS';

    /**
     * @var Compiler
     */
    protected $compiler;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->compiler = new Compiler();

    }

    /**
     * Get default lookup paths.
     *
     * @return array
     */
    public function getDefaultPaths()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $paths = array_merge(
            $locator->findResources('gantry-theme://scss'),
            $locator->findResources('gantry-engine://scss')
        );

        return $paths;
    }

    /**
     * Get default files to compile.
     *
     * @return array
     */
    public function getDefaultFiles()
    {
        $gantry = Gantry::instance();

        $files = [$gantry['theme.name'], 'custom'];

        return $files;
    }

    public function compile($in)
    {
        return $this->compiler->compile($in);
    }

    public function resetCache()
    {
    }

    /**
     * @param string $in    Filename without path or extension.
     * @param string $out   Full path to the file to be written.
     * @return bool         True if the output file was saved.
     */
    public function compileFile($in, $out = null)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        if (!$out) {
            $out = $locator->findResource("gantry-theme://css-compiled/{$in}{$this->scope}.css", true, true);
        }

        // Set the lookup paths.
        $this->compiler->setBasePath($out);
        $this->compiler->setImportPaths($this->paths ?: $this->getDefaultPaths());
        $this->compiler->setFormatter('scss_formatter_nested');

        // Run the compiler.
        $this->compiler->setVariables($this->getVariables());

        $scss = "@import '{$in}';\n";
        $css = $this->compiler->compile($scss);
        if ($css == $scss) {
            $css = "/* No {$in} CSS */\n";
        }

        $file = File::instance($out);

        // Attempt to lock the file for writing.
        $file->lock(false);

        //TODO: Better way to handle double writing files at same time.
        if ($file->locked() === false) {
            // File was already locked by another process.
            return false;
        }

        $file->save($css);
        $file->unlock();

        return true;
    }
}
