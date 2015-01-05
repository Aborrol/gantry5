<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use RocketTheme\Toolbox\File\JsonFile;

class Pages extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'         => 'index',
            '/create'   => 'create',
            '/create/*' => 'create',
            '/*'        => 'display',
            '/*/edit'   => 'edit'
        ],
        'POST' => [
            '/'  => 'store'
        ],
        'PUT' => [
            '/*' => 'replace'
        ],
        'PATCH' => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];

    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $this->params);
    }

    public function create($id = null)
    {
        $locator = $this->container['locator'];

        // TODO: remove hardcoded layout.
        $this->params['layout'] = JsonFile::instance($locator('gantry-theme://layouts/presets/'.$id.'.json'))->content();

        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $this->params);
    }

    public function edit($id)
    {
        $locator = $this->container['locator'];

        // TODO: remove hardcoded layout.
        $this->params['layout'] = JsonFile::instance($locator('gantry-theme://layouts/test.json'))->content();

        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $this->params);
    }
}
