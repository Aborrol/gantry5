<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;

class Layouts extends JsonController
{
    public function index()
    {
        return new JsonResponse(['foo' => 1]);
    }
}
