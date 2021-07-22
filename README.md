# Sciter package manager (SPM)

SPM is an experimental command line tool to install sciter packages.

## how to install

    curl -LO https://github.com/8ctopus/sciter-package-manager/releases/download/0.0.1/spm.phar

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

```
    php spm.phar install
```

## build phar

    php src/Compiler.php

## run spm in debug

```sh
composer install

php src/Entrypoint.php install
```