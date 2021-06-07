<?php declare(strict_types=1);

namespace Oct8pus\SPM;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Oct8pus\SPM\Curl;
use Oct8pus\SPM\MZipArchive;
use Oct8pus\SPM\Helper;

class CommandInstall extends Command
{
    private static $sciter_file = 'sciter.json';
    /**
     * Configure command options
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('install')
            ->setDescription('Install packages');
    }

    /**
     * Execute command
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        // beautify input, output interface
        $io = new SymfonyStyle($input, $output);

        // get current working directory
        //REM $io->writeln(getcwd());

        $file = realpath(self::$sciter_file);

        //REM $io->writeln($file);

        // check for sciter.json
        if (!file_exists($file)) {
            $io->error("File sciter.json not found - FAILED");
            return 1;
        }

        // load file
        $json = file_get_contents($file);

        if ($json === false) {
            $io->error("Read sciter.json - FAILED");
            return 1;
        }

        // convert json to php array
        $array = json_decode($json, true);

        if ($array === null) {
            $io->error("Parse sciter.json - FAILED");
            return 1;
        }

        // any requires?
        if (!array_key_exists('require', $array) || count($array['require']) === 0) {
            $io->warning("No packages required");
            return 0;
        }

        // loop through requires
        foreach ($array['require'] as $require => $version) {
            //https://github.com/8ctopus/sciter-fontawesome/releases/tag/1.0.0.zip
            //https://github.com/8ctopus/sciter-fontawesome/archive/refs/tags/1.0.0.zip

            $io->writeln("Install {$require}:{$version}...");

            // get temporary file name for archive
            $archive = tempnam(sys_get_temp_dir(), "spm") .'.zip';

            if ($archive === false) {
                $io->error("Get archive temporary name - FAILED");
                return 1;
            }

            // get archive url
            $url = "{$require}/archive/refs/tags/{$version}.zip";

            // download archive
            $info = [];
            $result = Curl::downloadFile($url, $archive, $info, true);

            if ($result !== true) {
                $io->error("Download archive - FAILED");
                return 1;
            }

            // open archive
            $zip = new MZipArchive();
            $result = $zip->open($archive, MZipArchive::RDONLY);

            if ($result !== true) {
                $io->error("Open zip - FAILED - {$result}");
                return 1;
            }

            // get author and project from url
            $path = parse_url($url, PHP_URL_PATH);

            if ($path === false) {
                $io->error("Parse url - FAILED");
                return 1;
            }

            $matches;

            if (preg_match("/\/(.*?)\/(.*?)\//", $path, $matches) !== 1) {
                $io->error("Extract user and project - FAILED");
                return 1;
            }

            $author  = $matches[1];
            $project = $matches[2];

            // set package installation dir
            $dir = getcwd() ."/vendor/{$author}/{$project}";

            // delete directory if it exists
            if (is_dir($dir))
                Helper::delTree($dir);

            // create vendor dir if it doesn't exist
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                $io->error("Make package directory - FAILED - {$result}");
                return 1;
            }

            // get archive first directory which we do not want to extract
            $filename = $zip->getNameIndex(0);
            $fileinfo = pathinfo($filename);

            // extract package subdir to vendor dir
            $zip->extractSubdirTo($dir, $fileinfo['basename']);

            /*
            // extract package to vendor dir
            // skip first directory in archive
            for ($i = 1; $i < $zip->numFiles; $i++) {
                // get filename
                $filename = $zip->getNameIndex($i);

                $fileinfo = pathinfo($filename);
                if (!copy("zip://test.zip#{$filename}", "{$dir}/{$fileinfo['basename']}")) {
                    $io->error("Extract file from package - FAILED - {$fileinfo['basename']}");
                    return 1;
                }
            }
            */

            $zip->close();
        }

        $io->success('All packages installed');

        return 0;
    }
}
