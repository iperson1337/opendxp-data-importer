<?php

use OpenDxp\Tests\Support\Util\Autoloader;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
    $pimcoreTestDir = __DIR__ . '/../vendor/pimcore/pimcore/tests';
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    include __DIR__ . '/../../../../vendor/autoload.php';
    $pimcoreTestDir = __DIR__ . '/../../../../vendor/pimcore/pimcore/tests';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '' && file_exists(getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php')) {
    include getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php';
    $pimcoreTestDir = getenv('PIMCORE_PROJECT_ROOT') . '/vendor/pimcore/pimcore/tests';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '') {
    throw new \Exception('Invalid Pimcore project root "' . getenv('PIMCORE_PROJECT_ROOT') . '"');
} else {
    throw new \Exception('Unknown configuration! Pimcore project root not found, please set env variable PIMCORE_PROJECT_ROOT.');
}

$pimcoreTestsSupportDir = ($pimcoreTestDir . '/Support');
//Pimcore 10 BC layer
if (!is_dir($pimcoreTestsSupportDir)) {
    $pimcoreTestsSupportDir = ($pimcoreTestDir . '/_support');
}

include ($pimcoreTestsSupportDir . '/Util/Autoloader.php');

\OpenDxp\Bootstrap::setProjectRoot();
\OpenDxp\Bootstrap::bootstrap();

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);
Autoloader::addNamespace('OpenDxp\Tests', $pimcoreTestsSupportDir); //Pimcore 10 BC layer
Autoloader::addNamespace('OpenDxp\Tests\Support', $pimcoreTestsSupportDir);
Autoloader::addNamespace('OpenDxp\Model\DataObject', PIMCORE_CLASS_DIRECTORY . '/DataObject');
Autoloader::addNamespace('OpenDxp\Bundle\DataImporterBundle\Tests', __DIR__);
Autoloader::addNamespace('OpenDxp\Bundle\DataImporterBundle\Tests', __DIR__ . '/_support');

echo __DIR__ . '/_support';

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

if (!defined('PIMCORE_TEST')) {
    define('PIMCORE_TEST', true);
}
