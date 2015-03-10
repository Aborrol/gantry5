<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Stylesheet\ScssCompiler;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Styles extends HtmlController
{

    protected $httpVerbs = [
        'GET' => [
            '/'              => 'index',
            '/blocks'        => 'undefined',
            '/blocks/*'      => 'display',
            '/blocks/*/**'   => 'formfield'
        ],
        'POST' => [
            '/'          => 'save',
            '/blocks'    => 'forbidden',
            '/blocks/*'  => 'save',
            '/compile'   => 'compile'
        ],
        'PUT' => [
            '/'         => 'save',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'save'
        ],
        'PATCH' => [
            '/'         => 'save',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'save'
        ],
        'DELETE' => [
            '/'         => 'forbidden',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'reset'
        ]
    ];

    public function index()
    {
        $configuration = $this->params['configuration'];

        if($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults'];
            $this->params['overrideable'] = true;
        }

        $this->params['blocks'] = $this->container['styles']->group();
        $this->params['route']  = "configurations.{$this->params['configuration']}.styles";

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/styles.html.twig', $this->params);
    }

    public function display($id)
    {
        $configuration = $this->params['configuration'];
        $style = $this->container['styles']->get($id);
        $blueprints = new BlueprintsForm($style);
        $prefix = 'styles.' . $id;

        if($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $this->params['overrideable'] = true;
        }

        $this->params += [
            'block' => $blueprints,
            'data' =>  $this->container['config']->get($prefix),
            'id' => $id,
            'parent' => "configurations/{$this->params['configuration']}/styles",
            'route'  => "configurations.{$this->params['configuration']}.styles.{$prefix}",
            'skip' => ['enabled']
        ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        $style = $this->container['styles']->get($id);

        // Load blueprints.
        $blueprints = new BlueprintsForm($style);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $fields['is_current'] = true;

        // Get the prefix.
        $prefix = "styles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $prefix .= '.' . $value;
        }
        array_pop($path);

        $this->params = [
                'blueprints' => $fields,
                'data' =>  $this->container['config']->get($prefix),
                'parent' => $path
                    ? "configurations/{$this->params['configuration']}/styles/blocks/{$id}/" . implode('/', $path)
                    : "configurations/{$this->params['configuration']}/styles/blocks/{$id}",
                'route' => 'styles.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/field.html.twig', $this->params);
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }


    public function compile()
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        $this->compileSettings();

        return new JsonResponse(['html' => 'The CSS was successfully compiled', 'title' => 'CSS Compiled']);
    }

    public function save($id = null)
    {
        /** @var Request $request */
        $request = $this->container['request'];
        /** @var Config $config */
        $config = $this->container['config'];

        if ($id) {
            $data = (array) $config->get('styles');
            $data[$id] = $request->getArray();
        } else {
            $data = $request->getArray('styles');
        }

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $configuration = $this->params['configuration'];
        $save_dir = $locator->findResource("gantry-config://{$configuration}", true, true);
        $filename = "{$save_dir}/styles.yaml";

        $file = YamlFile::instance($filename);
        $file->save($data);

        $this->compileSettings();

        return $id ? $this->display($id) : $this->index();
    }

    protected function compileSettings()
    {
        $configuration = $this->params['configuration'];

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $out = $this->container['theme.name'] . ($configuration != 'default' ? '_'. $configuration : '');

        $path = $locator->findResource("gantry-theme://css-compiled/{$out}.css", true, true);

        $compiler = new ScssCompiler();
        $compiler->setVariables($this->container['config']->flatten('styles', '-'));
        $compiler->compileFile($this->container['theme.name'], $path);

    }
}
