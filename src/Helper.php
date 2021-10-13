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
     * Get author and project
     * @param  string $url
     * @return [type]
     */
    static function authorProject(string $url) /* php 8 only : array|bool */
    {
        // get author and project from url
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === false)
            return false;

        // extract user and project from url
        $matches;

        if (preg_match("~/(.*)/(.*)/?~", $path, $matches) !== 1)
            return false;

        return [$matches[1], $matches[2]];
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

    /**
     * Determine if a command exists on the current environment
     *
     * @param string $command The command to check
     * @return bool True if the command has been found ; otherwise, false.
     * @author https://stackoverflow.com/a/18540185/10126479
     */
    static function commandExists($command)
    {
        $whereIsCommand = PHP_OS === 'WINNT' ? 'where' : 'which';

        $process = proc_open("${whereIsCommand} ${command}", [
                0 => array("pipe", "r"), //STDIN
                1 => array("pipe", "w"), //STDOUT
                2 => array("pipe", "w"), //STDERR
            ],
            $pipes
        );

        if ($process === false)
            return false;

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return $stdout !== '';
    }
}
