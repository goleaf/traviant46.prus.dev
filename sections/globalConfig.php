<?php
global $globalConfig;
if (!defined('TRAVIAN_ROOT')) {
    define('TRAVIAN_ROOT', dirname(__DIR__));
}
$globalConfig = [];
$globalConfig['staticParameters'] = [];
$globalConfig['staticParameters']['default_language'] = 'us';
$globalConfig['staticParameters']['default_timezone'] = 'Australia/Sydney';
$globalConfig['staticParameters']['default_direction'] = 'LTR';
$globalConfig['staticParameters']['default_dateFormat'] = 'y.m.d';
$globalConfig['staticParameters']['default_timeFormat'] = 'H:i';
$baseDomain = 'traviant46.prus.dev';
$globalConfig['staticParameters']['indexUrl'] = 'https://' . $baseDomain . '/';
$globalConfig['staticParameters']['forumUrl'] = 'https://forum.' . $baseDomain . '/';
$globalConfig['staticParameters']['answersUrl'] = 'https://answers.travian.com/index.php';
$globalConfig['staticParameters']['helpUrl'] = 'https://help.' . $baseDomain . '/';
$globalConfig['staticParameters']['adminEmail'] = '';
$globalConfig['staticParameters']['session_timeout'] = 6 * 3600;
$globalConfig['staticParameters']['default_payment_location'] = 2;
$globalConfig['staticParameters']['global_css_class'] = 'travian';
$globalConfig['staticParameters']['gpacks'] = require (TRAVIAN_ROOT . '/sections/gpack/gpack.php');
$globalConfig['staticParameters']['recaptcha_public_key'] = '';
$globalConfig['staticParameters']['recaptcha_private_key'] = '';
$globalConfig['cachingServers'] = [
    'memcached' => [
        ['127.0.0.1', 11211],
    ],
];
$globalConfig['dataSources'] = [];
// static global database
$globalConfig['dataSources']['globalDB']['hostname'] = 'localhost';
$globalConfig['dataSources']['globalDB']['username'] = 'sql_traviant46';
$globalConfig['dataSources']['globalDB']['password'] = 'sql_traviant46';
$globalConfig['dataSources']['globalDB']['database'] = 'sql_traviant46';
$globalConfig['dataSources']['globalDB']['charset'] = 'utf8mb4';
