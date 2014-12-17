<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Pages extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $this->params);
    }

    public function create()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $this->params);
    }

    public function edit($id)
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $this->params);
    }
}
