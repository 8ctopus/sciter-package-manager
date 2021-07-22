# Sciter package manager (SPM)

SPM is an experimental sciter.js package manager.

## how to install

    curl -LO https://github.com/8ctopus/sciter-package-manager/releases/download/0.1.4/spm.phar

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

```sh
php spm.phar install
```

## debug code

```sh
composer install

php src/Entrypoint.php install
```

## build phar

```sh
php src/Compiler.php
```
