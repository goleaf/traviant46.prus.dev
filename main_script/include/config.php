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
$gameConfigFile = TRAVIAN_ROOT . '/config/game.php';
if (!is_file($gameConfigFile)) {
    die('Game configuration file missing.');
}
$gameConfig = require $gameConfigFile;
if (!is_array($gameConfig)) {
    die('Invalid game configuration data.');
}
foreach ($gameConfig as $key => $value) {
    $config->$key = $value;
}
if (!isset($config->settings) || !is_object($config->settings)) {
    $config->settings = (object)[];
}
$config->settings->engine_filename = $connection['engine_filename'];
$config->settings->session_timeout = $globalConfig['staticParameters']['session_timeout'];
$config->settings->online_timeout = isset($config->settings->online_timeout) ? $config->settings->online_timeout : 600;
$config->settings->worldId = $connection['worldId'];
$config->settings->serverName = $connection['serverName'];
$config->settings->worldUniqueId = isset($config->settings->worldUniqueId) ? $config->settings->worldUniqueId : 0;
$config->settings->indexUrl = $globalConfig['staticParameters']['indexUrl'];
$config->settings->global_css_class = $globalConfig['staticParameters']['global_css_class'];
$config->settings->gameWorldUrl = $connection['gameWorldUrl'];
$config->settings->default_language = $globalConfig['staticParameters']['default_language'];
$config->settings->selectedLang = $globalConfig['staticParameters']['default_language'];
$config->settings->secure_hash_code = $connection['secure_hash_code'];
if (isset($config->settings->availableLanguages) && is_object($config->settings->availableLanguages)) {
    foreach ($config->settings->availableLanguages as $lang => $language) {
        if (!is_object($language)) {
            continue;
        }
        $language->title = 'Travian ' . $connection['worldId'];
        $language->ForumUrl = $globalConfig['staticParameters']['forumUrl'];
        $language->AnswersUrl = $globalConfig['staticParameters']['answersUrl'];
    }
}
if (!isset($config->game) || !is_object($config->game)) {
    $config->game = (object)[];
}
$config->game->speed = $connection['speed'];
$config->game->round_length = $connection['round_length'];
$config->game->round_length_real = $connection['round_length'];
$config->game->round_length_orig = $connection['round_length'];
if (!property_exists($config->game, 'movement_speed_increase')) {
    $config->game->movement_speed_increase = 1;
}
if (!property_exists($config->game, 'extra_training_time_multiplier')) {
    $config->game->extra_training_time_multiplier = 1;
}
if (!property_exists($config->game, 'start_time')) {
    $config->game->start_time = 0;
}
if (!property_exists($config->game, 'storage_multiplier')) {
    $config->game->storage_multiplier = 1;
}
if (!property_exists($config->game, 'cranny_multiplier')) {
    $config->game->cranny_multiplier = 1;
}
if (!property_exists($config->game, 'trap_multiplier')) {
    $config->game->trap_multiplier = 1;
}
if (!property_exists($config->game, 'cage_multiplier')) {
    $config->game->cage_multiplier = 1;
}
if (!property_exists($config->game, 'protection_time')) {
    $config->game->protection_time = 3 * 3600;
}
return $config;
