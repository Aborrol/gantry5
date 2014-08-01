<?php
namespace Gantry;

class Factory
{
    protected $folder;
    protected $config;

    /**
     * @param $folder
     * @throws \LogicException
     */
    public function __construct($folder)
    {
        if (!is_dir($folder)) {
            throw new \LogicException('Theme not found!');
        }

        $this->folder = $folder;
    }
}
