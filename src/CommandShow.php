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
    private $output, $io;

    /**
     * Configure command options
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('show')
            ->setDescription('Show installed and latest packages version');
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

        // show requires
        if (!$this->show($array['require']))
            return 1;

        return 0;
    }

    /**
     * Show current and latest version
     * @param  array  $requires
     * @return bool true on success, false otherwise
     */
    protected function show(array $requires) : bool
    {
        // create table
        $table = new Table($this->output);

        $table->setHeaders(['repo', 'current version', 'latest']);

        foreach ($requires as $url => $version) {
            // get author and project from url
            $path = parse_url($url, PHP_URL_PATH);

            if ($path === false) {
                $this->io->error("Parse url - FAILED");
                return false;
            }

            // extract user and project from url
            $matches;

            if (preg_match("~/(.*)/(.*)/?~", $path, $matches) !== 1) {
                $this->io->error("Extract user and project - FAILED");
                return false;
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
                return false;
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

        return true;
    }
}
