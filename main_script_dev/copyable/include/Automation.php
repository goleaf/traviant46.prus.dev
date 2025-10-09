#!/usr/bin/php -q
<?php
require __DIR__ . "/env.php";
if(IS_DEV){
    require TRAVIAN_ROOT . "/main_script_dev/include/AutomationEngine.php";
} else {
    require TRAVIAN_ROOT . "/main_script/include/AutomationEngine.php";
}
