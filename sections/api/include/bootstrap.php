<?php
use Core\WebService;
define("TEMPLATES_PATH", __DIR__ . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR);
if (!defined('TRAVIAN_ROOT')) {
    define('TRAVIAN_ROOT', dirname(__DIR__, 3));
}
define("FILTERING_PATH", TRAVIAN_ROOT . '/filtering/');
require "vendor/autoload.php";
spl_autoload_register(function ($name) {
    $location = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';
    if (is_file($location)) {
        require($location);
    } else {
        throw new Exception("Couldn't load $name.");
    }
});
require "config.php";
require "functions.php";
global $twig;
if(!is_writable(TEMPLATES_PATH . "Cache")){
    die("Cache dir not writable.");
}
$loader = new Twig_Loader_Filesystem(TEMPLATES_PATH);
$twig = new Twig_Environment($loader, array(
    'cache' => TEMPLATES_PATH . "Cache"
));
$function = new Twig_SimpleFunction('T', function ($t) {
    return T($t);
});
$twig->addFunction($function);
$twig->addGlobal('WEBSITE_INDEX_URL', WebService::getIndexUrl());
$twig->addGlobal('GPACK_URL', WebService::getProtocol() . '://gpack.' . WebService::getRealDomain());
