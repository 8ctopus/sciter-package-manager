# Sciter package manager

Sciter package manager (SPM) is an experimental sciter.js package manager written in php.

## requirements

- php >= 7.3
- php `curl` module enabled
- php `ZipArchive` module or `unzip` command must be present on your system

## how to install

```sh
curl -LO https://github.com/8ctopus/sciter-package-manager/releases/download/0.2.2/spm.phar
```

## how to use

* add `sciter.json` to your project

```json
{
    "require": {
        "https://github.com/8ctopus/sciter-fontawesome": "1.0.1",
        "https://github.com/8ctopus/sciter-dialogs": "1.2.22"
    }
}
```

* install packages

```sh
php spm.phar install
```

* show latest packages

```sh
php spm.phar show
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

## todo

- solve same module different version
- add update command

## known issues
