<?php
namespace Gantry\Filesystem;

/**
 * Folder helper class.
 *
 * @author RocketTheme
 * @license MIT
 */
abstract class Folder
{
    /**
     * Recursively find the last modified time under given path.
     *
     * @param string $path
     * @return int
     */
    public static function lastModified($path)
    {
        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        $last_modified = 0;

        /** @var \RecursiveDirectoryIterator $file */
        foreach ($iterator as $file) {
            $dir_modified = $file->getMTime();
            if ($dir_modified > $last_modified) {
                $last_modified = $dir_modified;
            }
        }
        return $last_modified;
    }

    /**
     * Return recursive list of all files and directories under given path.
     *
     * @param string $path
     * @param array  $params
     * @return array
     * @throws \RuntimeException
     */
    public static function all($path, array $params = array())
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new \RuntimeException("Path to {$path} doesn't exist.");
        }

        $compare = $params['compare'] ? 'get' . $params['compare'] : null;
        $pattern = $params['pattern'] ? $params['pattern'] : null;
        $filters = $params['filters'] ? $params['filters'] : null;
        $key = $params['key'] ? 'get' . $params['key'] : null;
        $value = $params['value'] ? 'get' . $params['value'] : 'SubPathname';

        $directory = new \RecursiveDirectoryIterator($realPath,
            \RecursiveDirectoryIterator::SKIP_DOTS + \FilesystemIterator::UNIX_PATHS + \FilesystemIterator::CURRENT_AS_SELF);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        $results = array();

        /** @var \RecursiveDirectoryIterator $file */
        foreach ($iterator as $file) {
            if ($compare && $pattern && !preg_match($pattern, $file->{$compare}())) {
                continue;
            }
            $fileKey = $key ? $file->{$key}() : null;
            $filePath = $file->{$value}();
            if ($filters) {
                if (isset($filters['key'])) {
                    $fileKey = preg_replace($filters['key'], '', $fileKey);
                }
                if (isset($filters['value'])) {
                    $filePath = preg_replace($filters['value'], '', $filePath);
                }
            }

            $results[$fileKey] = $filePath;
        }
        return $results;
    }

    /**
     * Recursively copy directory in filesystem.
     *
     * @param  string $source
     * @param  string $target
     * @throws \RuntimeException
     */
    public static function copy($source, $target)
    {
        $source = rtrim($source, '\\/');
        $target = rtrim($target, '\\/');

        if (!is_dir($source)) {
            throw new \RuntimeException('Cannot copy non-existing folder.');
        }

        // Make sure that path to the target exists before copying.
        self::mkdir($target);

        $success = true;

        // Go through all sub-directories and copy everything.
        $files = self::all($source);
        foreach ($files as $file) {
            $src = $source .'/'. $file;
            $dst = $target .'/'. $file;

            if (is_dir($src)) {
                // Create current directory.
                $success &= @mkdir($dst);
            } else {
                // Or copy current file.
                $success &= @copy($src, $dst);
            }
        }

        if (!$success) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        // Make sure that the change will be detected when caching.
        @touch(dirname($target));
    }

    /**
     * Move directory in filesystem.
     *
     * @param  string $source
     * @param  string $target
     * @throws \RuntimeException
     */
    public static function move($source, $target)
    {
        if (!is_dir($source)) {
            throw new \RuntimeException('Cannot move non-existing folder.');
        }

        // Make sure that path to the target exists before moving.
        self::mkdir(dirname($target));

        // Just rename the directory.
        $success = @rename($source, $target);

        if (!$success) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        // Make sure that the change will be detected when caching.
        @touch(dirname($source));
        @touch(dirname($target));
    }

    /**
     * Recursively delete directory from filesystem.
     *
     * @param  string $target
     * @throws \RuntimeException
     */
    public static function delete($target)
    {
        if (!is_dir($target)) {
            throw new \RuntimeException('Cannot delete non-existing folder.');
        }

        $success = self::doDelete($target);

        if (!$success) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        // Make sure that the change will be detected when caching.
        @touch(dirname($target));
    }

    /**
     * @param  string $folder
     * @return bool
     * @internal
     */
    protected static function doDelete($folder)
    {
        // Special case for symbolic links.
        if (is_link($folder)) {
            return @unlink($folder);
        }

        // Go through all items in filesystem and recursively remove everything.
        $files = array_diff(scandir($folder), array('.', '..'));
        foreach ($files as $file) {
            $path = "{$folder}/{$file}";
            (is_dir($path)) ? self::doDelete($path) : @unlink($path);
        }

        return @rmdir($folder);
    }

    /**
     * @param  string  $folder
     * @throws \RuntimeException
     * @internal
     */
    protected static function mkdir($folder)
    {
        if (is_dir($folder)) {
            return;
        }

        $success = @mkdir($folder, 0777, true);

        if (!$success) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }
    }
}
