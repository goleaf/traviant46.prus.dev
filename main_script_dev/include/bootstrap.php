<?php
use Core\Caching\Caching;
use Core\Caching\GlobalCaching;
use Core\Config;
use Core\Database\DB;
use Core\Database\GlobalDB;
use Core\Helper\WebService;
if (!extension_loaded("redis")) {
    die("Redis extension not available.");
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
define("GLOBAL_CACHING_KEY", get_current_user());
define("ROOT_PATH", dirname(__DIR__) . DIRECTORY_SEPARATOR);
define("PUBLIC_INTERNAL_PATH", dirname(__DIR__) . DIRECTORY_SEPARATOR . 'copyable/public/');
define("INCLUDE_PATH", __DIR__ . DIRECTORY_SEPARATOR);
define("RESOURCES_PATH", INCLUDE_PATH . "resources" . DIRECTORY_SEPARATOR);
define("LOCALE_PATH", RESOURCES_PATH . "Translation" . DIRECTORY_SEPARATOR);
define("TEMPLATES_PATH", RESOURCES_PATH . "Templates" . DIRECTORY_SEPARATOR);
define("SVG_TEMPLATES", RESOURCES_PATH . "Svg" . DIRECTORY_SEPARATOR);
define("SVG_COMMON_TEMPLATES", RESOURCES_PATH . "Svg" . DIRECTORY_SEPARATOR. "svg.php");
define("SVG_FIELD_TEMPLATES", RESOURCES_PATH . "Svg" . DIRECTORY_SEPARATOR. "resourceFieldSvg.php");
define("SVG_BUILDING_TEMPLATES", RESOURCES_PATH . "Svg" . DIRECTORY_SEPARATOR. "buildFieldSvg.php");
require_once INCLUDE_PATH . "Core" . DIRECTORY_SEPARATOR . 'Autoloader.php';
require_once INCLUDE_PATH . "functions.general.php";
global $config;
$cache = Caching::getInstance();
$config = Config::getInstance();
if (!property_exists($config, 'db')) {
    die("Installation is not completed.");
}
$db = DB::getInstance();

function initialize_world_config(DB $db)
{
    $columns = $db->query('SHOW COLUMNS FROM config');
    if ($columns === false) {
        logError('Unable to fetch config table structure.');
        return false;
    }
    $fields = [];
    while ($column = $columns->fetch_assoc()) {
        if ($column['Field'] === 'id' && strpos($column['Extra'] ?? '', 'auto_increment') !== false) {
            continue;
        }
        if ($column['Default'] !== null) {
            $fields[$column['Field']] = $column['Default'];
            continue;
        }
        if (preg_match('/int|decimal|double|float|real|bit/i', $column['Type'])) {
            $fields[$column['Field']] = 0;
            continue;
        }
        $fields[$column['Field']] = '';
    }
    if (!$fields) {
        logError('Config table has no writable columns.');
        return false;
    }
    $fieldNames = '`' . implode('`,`', array_keys($fields)) . '`';
    $values = [];
    foreach ($fields as $value) {
        $values[] = "'" . $db->real_escape_string((string)$value) . "'";
    }
    $query = sprintf('INSERT INTO config (%s) VALUES (%s)', $fieldNames, implode(',', $values));
    if ($db->query($query) === false) {
        logError('Failed to auto-create config row.');
        return false;
    }
    return true;
}

function fetch_world_config(DB $db)
{
    $result = $db->query('SELECT * FROM config');
    if ($result === false) {
        $error = ($db->mysqli instanceof \mysqli) ? $db->mysqli->error : '';
        logError('Failed to fetch config row: ' . $error);
        return false;
    }
    if (!$result->num_rows) {
        if (!initialize_world_config($db)) {
            logError('No config row found.');
            return false;
        }
        $result = $db->query('SELECT * FROM config');
        if ($result === false || !$result->num_rows) {
            $error = ($db->mysqli instanceof \mysqli) ? $db->mysqli->error : '';
            logError('Failed to fetch config row after initialization: ' . $error);
            return false;
        }
    }
    return $result->fetch_assoc();
}

{
    $result = null;
    if (php_sapi_name() == 'cli') {
        $row = fetch_world_config($db);
        if ($row === false) {
            exit("We are having issues, please try again in a moment. E1");
        }
        $config->dynamic = (object)$row;
    } else {
        if (($_cache = $cache->get("WorldConfig"))) {
            $config->dynamic = $_cache;
        } else {
            $row = fetch_world_config($db);
            if ($row === false) {
                exit("We are having issues, please try again in a moment. E1");
            }
            $config->dynamic = (object)$row;
            $cache->set('WorldConfig', $config->dynamic, 300);
        }
    }
    if (!property_exists($config->dynamic, 'startTime')) {
        logError("No column found in config row.");
        exit("We are having issues, please try again in a moment. E2");
    }
    $config->game->start_time = $config->dynamic->startTime;
    $config->settings->worldUniqueId = $config->dynamic->worldUniqueId;
    define("MAP_SIZE", $config->dynamic->map_size);
}
if (property_exists($config->dynamic, 'isRestore') && $config->dynamic->isRestore && !defined("IS_UPDATE")) {
    exit("We are having issues, please try again in a moment. E3");
}
require(INCLUDE_PATH . "config/config.after.php");
if (!$config->dynamic->installed) {
    $config->dynamic->maintenance = true;
}
function check_ip_access()
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
            exit("You are not allowed to access here.");
        }
    }
}

if (php_sapi_name() != 'cli') {
    Caching::getInstance();
    check_ip_access();
}
