<?php
require dirname(__DIR__) . "/include/env.php";
if(IS_DEV){
    require TRAVIAN_ROOT . "/main_script_dev/include/mainInclude.php";
} else {
    require TRAVIAN_ROOT . "/main_script/include/mainInclude.php";
}
