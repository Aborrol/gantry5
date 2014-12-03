<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Layouts extends JsonController
{
    public function index(array $params)
    {
        $options = [
            'compare' => 'Filename',
            'pattern' => '|\.json|',
            'filters' => ['key' => '|\.json|'],
            'key' => 'SubPathname',
            'value' => 'Pathname'
        ];

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $files = Folder::all($locator->findResource('gantry-theme://layouts/presets'), $options);

        $response = ['layouts'];
        foreach($files as $name => $structure) {
            $content = JsonFile::instance($structure)->content();
            $response['layouts'][$name] = $content;
        }

        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/layouts/picker.html.twig', ['presets' => $response]);

        return new JsonResponse($response);
    }
}
