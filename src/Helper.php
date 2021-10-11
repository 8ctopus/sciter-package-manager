<?php declare(strict_types=1);

namespace Oct8pus\SPM;

class Helper
{
    static $sciter_file = 'sciter.json';

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

    /**
     * Get dependencies
     * @param  string $file
     * @return array on success, false otherwise
     */
    static function dependencies(string $file) /* php 8 only : array|bool */
    {
        // load file
        $json = file_get_contents($file);

        if ($json === false) {
            $this->io->error("Read sciter.json - FAILED");
            return false;
        }

        // convert json to php array
        $array = json_decode($json, true);

        if ($array === null) {
            $this->io->error("Parse sciter.json - FAILED");
            return false;
        }

        return $array;
    }

    /**
     * Get current working directory in unix format /
     * @return string
     */
    static function getcwd() : string
    {
        $dir = getcwd();

        if (strtolower(substr(php_uname('s'), 0, 3)) === 'win')
            return str_replace("\\", "/", $dir);
        else
            return $dir;
    }
}
