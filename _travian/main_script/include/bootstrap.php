<?php
use Core\Caching\Caching;
use Core\Caching\GlobalCaching;
use Core\Config;
use Core\Database\GlobalDB;
use Core\Helper\WebService;

if (!function_exists('geoip_country_code_by_name')) {
    die('Geoip extension not available.');
}

if (!extension_loaded('redis')) {
    die('Redis extension not available.');
}

$start_time = microtime(true);

if (php_sapi_name() != 'cli') {
    set_time_limit(120);
    ob_start();
    if (!session_start()) {
        logError("Could not start session.");
        die("Couldn't start session.");
    }
}

$paths = \App\ValueObjects\Travian\LegacyPaths::resolve();

require_once $paths->includePath() . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';
require_once $paths->includePath() . 'functions.general.php';

global $config;
$config = Config::getInstance();

if (!isset($config->db)) {
    die('Installation is not completed.');
}

if (isset($config->dynamic->isRestore) && $config->dynamic->isRestore && !defined('IS_UPDATE')) {
    exit('We are having issues, please try again in a moment. E3');
}

require $paths->includePath() . 'config/config.after.php';

if (!$config->dynamic->installed) {
    $config->dynamic->maintenance = true;
}

function check_ip_access(): void
{
    $ip = WebService::ipAddress();

    if (!empty($ip)) {
        $ip = ip2long($ip);
        $cache = GlobalCaching::getInstance();

        if (!($banned = $cache->get("IPCheck:$ip"))) {
            $banned = GlobalDB::getInstance()->query("SELECT * FROM banIP WHERE ip='$ip' AND (blockTill=0 OR blockTill > " . time() . ")");
            $cache->set("IPCheck:$ip", $banned->num_rows ? $banned->fetch_assoc() : [], 1440);
        }

        if (is_array($banned) && sizeof($banned) > 1) {
            exit('You are not allowed to access here.');
        }
    }
}

if (php_sapi_name() != 'cli') {
    Caching::getInstance();
    check_ip_access();
}
