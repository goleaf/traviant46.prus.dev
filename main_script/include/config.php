<?php
if (!is_file(GLOBAL_CONFIG_FILE)) {
    die("Wrong configuration!");
}
global $globalConfig;
require(GLOBAL_CONFIG_FILE);
global $connection;
require(CONNECTION_FILE);
//Warning: You must not edit this config file manually.
date_default_timezone_set($globalConfig['staticParameters']['default_timezone']);
$configDir = dirname(ROOT_PATH) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
if (!is_file($configDir . 'game.php')) {
    die('Game configuration file missing.');
}
$gameSettings = require $configDir . 'game.php';
$toObject = function ($value) use (&$toObject) {
    if (!is_array($value)) {
        return $value;
    }
    $isSequential = array_keys($value) === range(0, count($value) - 1);
    if ($isSequential) {
        return array_map($toObject, $value);
    }
    $object = new stdClass();
    foreach ($value as $key => $item) {
        $object->{$key} = $toObject($item);
    }
    return $object;
};
global $config;
$config = (object)[
    'db'      => (object)$connection['database'],
    'dynamic' => (object)[],
    'timers'  => (object)[
        'ArtifactsReleaseTime'       => '',
        'wwPlansReleaseTime'         => '',
        'WWConstructStartTime'       => '',
        'WWUpLvlInterval'            => '',
        'AutoFinishTime'             => '',
        'auto_reinstall'             => $connection['auto_reinstall'],
        'auto_reinstall_start_after' => $connection['auto_reinstall_start_after'],
    ],
];
$objectKeys = [
    'display',
    'allianceBonus',
    'quest',
    'auction',
    'settings',
    'game',
    'gold',
    'masterBuilder',
    'farms',
    'custom',
    'bonus',
    'heroConfig',
    'extraSettings',
    'Voting',
];
foreach ($objectKeys as $key) {
    if (array_key_exists($key, $gameSettings)) {
        $config->{$key} = $toObject($gameSettings[$key]);
        unset($gameSettings[$key]);
    }
}
$arrayKeys = ['timezones', 'countryCodes', 'fakeUsersCountryCodes'];
foreach ($arrayKeys as $key) {
    if (array_key_exists($key, $gameSettings)) {
        $config->{$key} = $gameSettings[$key];
        unset($gameSettings[$key]);
    }
}
if (array_key_exists('fakeUsersCount', $gameSettings)) {
    $config->fakeUsersCount = $gameSettings['fakeUsersCount'];
    unset($gameSettings['fakeUsersCount']);
}
foreach ($gameSettings as $key => $value) {
    $config->{$key} = $value;
}
return $config;
