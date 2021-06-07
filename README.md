# SPM

Sciter package manager is a command line tool to install sciter packages.

## how to install

    curl -L -o spm.phar https://github.com/8ctopus/webp8/releases/download/v0.0.1/spm.phar

    # check hash against the one published under releases
    sha256sum spm.phar

## how to use

* add `sciter.json` to your project

```json
{
    "require": {
        "https://github.com/8ctopus/sciter-fontawesome": "1.0.0"
    }
}
```

* install packages

    php spm.phar install

## build phar

    php src/Compiler.php
