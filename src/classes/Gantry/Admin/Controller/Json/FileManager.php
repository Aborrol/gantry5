<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;


class FileManager extends JsonController
{
    protected $httpVerbs = [
        'GET'  => [
            '/*' => 'index'
        ],
        'POST' => [
            '/'            => 'index',
            '/select'      => 'select',
            '/select/*'    => 'select',
            '/subfolder'   => 'subfolder',
            '/subfolder/*' => 'subfolder'
        ],
        'PUT'  => [
            '/*' => 'upload'
        ]
    ];

    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $base = $locator->base;
        $bookmarks = [];

        if (isset($_POST)) {
            $drives = isset($_POST['root']) ? ($_POST['root'] != 'false' ? $_POST['root'] : [DS]) : [DS];
        }

        if (!is_array($drives)) {
            $drives = [$drives];
        }

        foreach ($drives as $drive) {
            // cleanup of the path so it's chrooted.
            $drive = str_replace('..', '', $drive);
            $stream = explode('://', $drive);
            $scheme = $stream[0];

            $isStream = $locator->schemeExists($scheme);
            $path = rtrim($base, DS) . DS . ltrim($scheme, DS);

            // It's a stream but the scheme doesn't exist. we skip it.
            if (!$isStream && (count($stream) == 2 || !file_exists($path))) {
                continue;
            }
            if ($isStream && !count($resources = $locator->findResources($drive, false))) {
                continue;
            }

            $key = $isStream ? $drive : preg_replace('#' . DS . '{2,}+#', DS, $drive);

            if (!array_key_exists($key, $bookmarks)) {
                $bookmarks[$key] = $isStream ? $resources : [rtrim(Folder::getRelativePath($path), DS) . DS];
            }
        }

        if (!count($bookmarks)) {
            throw new \RuntimeException((count($drives) > 1 ? 'directories' : 'directory') . ' "' . implode('", "',
                    $drives) . '" not found', 404);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $files = new \ArrayObject();
        $folders = [];
        $active = [];

        $index = 0;
        // iterating the folder and collecting subfolders and files
        foreach ($bookmarks as $key => $bookmark) {
            $folders[$key] = [];

            if (!$index) {
                $active[] = $key;
            }

            foreach($bookmark as $folder) {
                $folders[$key][$folder] = new \ArrayObject();
                if (!$index) {
                    $active[] = $folder;
                }

                foreach (new \DirectoryIterator($base . DS . ltrim($folder, DS)) as $info) {
                    // no dot files nor files beginning with dot
                    if ($info->isDot() || substr($info->getFilename(), 0, 1) == '.') { continue; }
                    $file = new \stdClass();

                    foreach(['getFilename', 'getExtension', 'getPerms', 'getMTime', 'getBasename', 'getPath', 'getPathname', 'getSize', 'getType', 'isReadable', 'isWritable', 'isDir', 'isFile'] as $method){
                        $keyMethod = strtolower(preg_replace("/^(is|get)/", '', $method));
                        $file->{$keyMethod} = $info->{$method}();
                        if ($method == 'getPathname') {
                            $file->{$keyMethod} = Folder::getRelativePath($file->{$keyMethod});
                        }
                    }

                    if ($file->dir) {
                        $folders[$key][$folder]->append($file);
                    } else {
                        if (!$index) {
                            $file->mime = finfo_file($finfo, $file->pathname);
                            $files->append($file);
                        }
                    }
                }

                $index++;
            }
        }

//        die;

        /*foreach (new \DirectoryIterator($path) as $info) {
            // no dot files nor files beginning with dot
            if ($info->isDot() || substr($info->getFilename(), 0, 1) == '.') { continue; }
            $file = new \stdClass();

            foreach(['getFilename', 'getExtension', 'getPerms', 'getMTime', 'getBasename', 'getPath', 'getPathname', 'getSize', 'getType', 'isReadable', 'isWritable', 'isDir', 'isFile'] as $method){
                $key = strtolower(preg_replace("/^(is|get)/", '', $method));
                $file->{$key} = $info->{$method}();
                if ($method == 'getPathname') {
                    $file->{$key} =  Folder::getRelativePath($file->{$key});
                }
            }

            if ($file->dir) { $folders->append($file); }
            else { $files->append($file); }
        }*/
        xdebug_break();
        $response = [];
        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/ajax/filemanager.html.twig', ['active' => $active, 'base' => $base, 'bookmarks' => $bookmarks, 'folders' => $folders, 'files' => $files]);

        return new JsonResponse($response);
    }

    public function select()
    {
        $response = [];
        $response['html'] = 'select';

        return new JsonResponse($response);

    }

    public function subfolder()
    {
        $response = [];
        $response['html'] = 'subfolder';

        return new JsonResponse($response);
    }
}