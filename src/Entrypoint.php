<?php declare(strict_types=1);

// program entry point
if (file_exists(__DIR__ .'/../vendor/autoload.php'))
    require(__DIR__ .'/../vendor/autoload.php');
else
    require(__DIR__ .'/vendor/autoload.php');

$app = new Symfony\Component\Console\Application('spm', '0.1.6');
$app->add(new Oct8pus\SPM\CommandInstall());
$app->add(new Oct8pus\SPM\CommandShow());

$app->run();
