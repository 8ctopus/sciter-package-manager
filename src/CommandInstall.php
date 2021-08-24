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
    private $installed = [];
    private $io;

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
        $this->io = new SymfonyStyle($input, $output);

        // delete vendor dir
        $dir = Helper::getcwd() ."/vendor/";

        // delete vendor directory if not also used by composer
        if (is_dir($dir) && !file_exists($dir .'autoload.php'))
            Helper::delTree($dir);

        // get path to sciter.json
        $file = realpath(Helper::$sciter_file);

        // check for sciter.json
        if (gettype($file) !== "string" || !file_exists($file)) {
            $this->io->error("File sciter.json not found - FAILED");
            return 1;
        }

        // parse sciter.json
        $array = Helper::dependencies($file);

        if (!$array)
            return 1;

        // any requires?
        if (!array_key_exists('require', $array) || count($array['require']) === 0) {
            $this->io->warning("No packages required");
            return 0;
        }

        // install requires
        if (!$this->install($array['require']))
            return 1;

        $this->io->success('All packages installed');

        return 0;
    }

    /**
     * Install dependencies
     * @param  array $requires
     * @return bool
     */
    protected function install(array $requires) : bool
    {
        // loop through requires
        foreach ($requires as $require => $version) {
            //https://github.com/8ctopus/sciter-fontawesome/releases/tag/1.0.0.zip
            //https://github.com/8ctopus/sciter-fontawesome/archive/refs/tags/1.0.0.zip

            if (in_array("{$require}:{$version}", $this->installed, true)) {
                $this->io->writeln("Skip install {$require}:{$version}...");
                continue;
            }

            $this->io->writeln("Install {$require}:{$version}...");
            $this->installed[] = "{$require}:{$version}";

            // get temporary file name for archive
            $archive = tempnam(sys_get_temp_dir(), "spm") .'.zip';

            if ($archive === false) {
                $this->io->error("Get archive temporary name - FAILED");
                return false;
            }

            // get archive url
            $url = "{$require}/archive/refs/tags/{$version}.zip";

            // download archive
            $info = [];
            $result = Curl::downloadFile($url, $archive, $info, true);

            if ($result !== true) {
                $this->io->error("Download archive - FAILED");
                return false;
            }

            // open archive
            $zip = new MZipArchive();
            $result = $zip->open($archive, MZipArchive::RDONLY);

            if ($result !== true) {
                $this->io->error("Open zip - FAILED - {$result}");
                return false;
            }

            // get author and project from url
            $path = parse_url($url, PHP_URL_PATH);

            if ($path === false) {
                $this->io->error("Parse url - FAILED");
                return false;
            }

            $matches;

            if (preg_match("~/(.*?)/(.*?)/~", $path, $matches) !== 1) {
                $this->io->error("Extract user and project - FAILED");
                return false;
            }

            $author  = $matches[1];
            $project = $matches[2];

            // set package installation dir
            $dir = Helper::getcwd() ."/vendor/{$author}/{$project}/src/";

            // delete directory if it exists
            if (is_dir($dir))
                Helper::delTree($dir);

            // create dir if it doesn't exist
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                $this->io->error("Make package directory - FAILED - {$result}");
                return false;
            }

            // get archive first directory which we do not want to extract
            $filename = $zip->getNameIndex(0);
            $fileinfo = pathinfo($filename);

            // extract package subdir to vendor dir
            $zip->extractSubdirTo($dir, $fileinfo['basename'] .'/src');

            $zip->close();

            // update vendor path in source files
            $this->updateVendorPath($dir);

            // check if package has dependencies
            $file = $dir . Helper::$sciter_file;

            if (file_exists($file)) {
                // parse sciter.json
                $array = Helper::dependencies($file);

                if (!$array)
                    break;

                // any requires?
                if (!array_key_exists('require', $array) || count($array['require']) === 0) {
                    $this->io->warning("No packages required");
                    break;
                }

                if (!$this->install($array['require']))
                    $this->io->error("Install packages - FAILED");
            }
        }

        return true;
    }

    /**
     * Update vendor path in source files
     * @param  string $dir
     * @return void
     */
    protected function updateVendorPath(string $dir) : void
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            if (!in_array(pathinfo($file, PATHINFO_EXTENSION), ["js", "htm", "html"]))
                continue;

            $content = file_get_contents($dir . $file, false);

            $count   = 0;

            $content = str_replace("../vendor/", "../../../", $content, $count);

            if (!$count)
                continue;

            $this->io->writeln("Updated vendor path {$dir}{$file}");

            file_put_contents($dir . $file, $content);
        }
    }
}
