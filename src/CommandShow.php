<?php declare(strict_types=1);

namespace Oct8pus\SPM;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Oct8pus\SPM\Curl;
use Oct8pus\SPM\MZipArchive;
use Oct8pus\SPM\Helper;

class CommandShow extends Command
{
    private static $sciter_file = 'sciter.json';
    private $output, $io;

    /**
     * Configure command options
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('show')
            ->setDescription('Show packages');
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

        $this->output = $output;

        // get path to sciter.json
        $file = realpath(self::$sciter_file);

        // check for sciter.json
        if (gettype($file) !== "string" || !file_exists($file)) {
            $this->io->error("File sciter.json not found - FAILED");
            return 1;
        }

        // parse sciter.json
        $array = $this->dependencies($file);

        if (!$array)
            return 1;

        // any requires?
        if (!array_key_exists('require', $array) || count($array['require']) === 0) {
            $this->io->warning("No packages required");
            return 0;
        }

        // show requires
        if (!$this->show($array['require']))
            return 1;

        //$this->io->success('All packages installed');
        return 0;
    }

    /**
     * Get dependencies
     * @param  string $file
     * @return array on success, false otherwise
     */
    protected function dependencies(string $file) : array|bool
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

    protected function show(array $requires) : void
    {
        // create table
        $table = new Table($this->output);

        $table->setHeaders(['repo', 'current version', 'latest']);

        foreach ($requires as $url => $version) {
            // get author and project from url
            $path = parse_url($url, PHP_URL_PATH);

            if ($path === false) {
                $this->io->error("Parse url - FAILED");
                return;
            }

            // extract user and project from url
            $matches;

            if (preg_match("~/(.*)/(.*)/?~", $path, $matches) !== 1) {
                $this->io->error("Extract user and project - FAILED");
                return;
            }

            $author  = $matches[1];
            $project = $matches[2];

            // create tags url
            $tags = "https://api.github.com/repos/{$author}/{$project}/tags";

            // download tags json
            $json   = "";
            $info   = [];
            $result = Curl::download2($tags, $json, $info, true);

            if ($result !== true) {
                $this->io->error("Download tags - FAILED");
                return;
            }

            // convert json to php array
            $array = json_decode($json, true);

            // get latest version
            $version2 = $array[0]["name"];

            // add table row
            $table->addRow([$url, $version, $version2]);
        }

        // show table
        $table->render();
    }
}
