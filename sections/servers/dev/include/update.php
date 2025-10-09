<?php
require __DIR__ . "/env.php";
if(IS_DEV){
    require TRAVIAN_ROOT . "/main_script_dev/include/update.php";
} else {
    require TRAVIAN_ROOT . "/main_script/include/update.php";
}
