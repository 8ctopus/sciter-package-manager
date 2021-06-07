<?php declare(strict_types=1);

namespace Oct8pus\SPM;

class Helper
{
    /**
     * Delete directory recursively
     * @param  string $dir
     * @return bool
     * @note https://www.php.net/manual/en/function.rmdir.php#110489
     */
    static function delTree(string $dir) : bool
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file)
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");

        return rmdir($dir);
    }
}
