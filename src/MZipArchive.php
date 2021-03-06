<?php declare(strict_types=1);

/**
 * @author https://www.php.net/manual/en/ziparchive.extractto.php#116353
 */

namespace Oct8pus\SPM;

use ZipArchive;

class MZipArchive extends ZipArchive
{
    public function extractSubdirTo($destination, $subdir)
    {
        $errors = [];

        // prepare dirs
        $destination = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $destination);
        $subdir      = str_replace(["/", "\\"], "/", $subdir);

        if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, "UTF-8") * -1) !== DIRECTORY_SEPARATOR)
            $destination .= DIRECTORY_SEPARATOR;

        if (substr($subdir, -1) !== "/")
            $subdir .= "/";

        // extract files
        for ($i = 0; $i < $this->numFiles; $i++) {
            $filename = $this->getNameIndex($i);

            if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) === $subdir) {
                $relativePath = substr($filename, mb_strlen($subdir, "UTF-8"));
                $relativePath = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $relativePath);

                if (mb_strlen($relativePath, "UTF-8") > 0)
                {
                    if (substr($filename, -1) === "/") { // Directory
                        // new dir
                        if (!is_dir($destination . $relativePath))
                            if (!@mkdir($destination . $relativePath, 0755, true))
                                $errors[$i] = $filename;
                    }
                    else {
                        if (dirname($relativePath) !== ".") {
                            if (!is_dir($destination . dirname($relativePath))) {
                                // New dir (for file)
                                @mkdir($destination . dirname($relativePath), 0755, true);
                            }
                        }

                        // new file
                        if (@file_put_contents($destination . $relativePath, $this->getFromIndex($i)) === false)
                            $errors[$i] = $filename;
                    }
                }
            }
        }

        return $errors;
    }
}
