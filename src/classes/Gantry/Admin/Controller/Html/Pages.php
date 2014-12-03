<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Pages extends HtmlController
{
    public function index(array $params)
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $params);
    }

    public function create(array $params)
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $params);
    }

    public function edit(array $params)
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $params);
    }
}
