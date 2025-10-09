<?php
namespace App\Http\Controllers;

use Controller\GameCtrl;
use Core\Session;

class HeroController extends GameCtrl
{
    public function render(){

        $t = isset($_GET['t']) && $_GET['t'] >= 1 && $_GET['t'] <= 4 ? (int)$_GET['t'] : Session::getInstance()->getFavoriteTab("hero"); //(favourite);
        $pages = [
            1 => HeroInventoryController::class,
            2 => HeroFaceController::class,
            3 => HeroAdventureController::class,
            4 => HeroAuctionController::class,
        ];
        if(!isset($pages[$t])){
            $t = 1;
        }
        $controller = new $pages[$t]();
        echo $controller->render();
    }
}