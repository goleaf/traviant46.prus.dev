<?php

namespace Controller;

use App\Livewire\Map\MapView;
use Core\Session;
use Core\Village;
use Game\Formulas;
use Model\Quest;
use resources\View\GameView;
use resources\View\PHPBatchView;

class KarteCtrl extends GameCtrl
{
    public function __construct()
    {
        parent::__construct();
        Quest::getInstance()->setQuestBitwise("world", 5, 1);
        $this->view = new GameView();
        $this->view->vars['bodyCssClass'] = 'map perspectiveBuildings';
        $this->view->vars['contentCssClass'] = 'map';
        $this->view->vars['containerCssClass'] = 'contentPage';
        $this->view->vars['answerId'] = 106;
        $this->view->vars['showHeaderBar'] = TRUE;
        $this->view->vars['showCloseButton'] = TRUE;
        if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $xy = Formulas::kid2xy($_GET['d']);
            $this->redirect("position_details.php?x={$xy['x']}&y={$xy['y']}");
        }
        if (isset($_GET['x']) && isset($_GET['y'])) {
            $kid = Formulas::xy2kid($_GET['x'], $_GET['y']);
        } else {
            $kid = Village::getInstance()->getKid();
        }
        $this->view->vars['titleInHeader'] = T("map", "map");
        $zoomLevel = isset($_GET['zoom']) && $_GET['zoom'] >= 1 && $_GET['zoom'] <= 3 ? (int)$_GET['zoom'] : 1;
        $fullView = FALSE;
        if (isset($_GET['fullscreen']) && $_GET['fullscreen'] == 1 && Session::getInstance()->hasPlus()) {
            $fullView = TRUE;
        }
        $view = new PHPBatchView("map/normal");
        $mapView = new MapView();
        $view->vars = $mapView->build($kid, $zoomLevel, $fullView);
        $this->view->vars['content'] = $view->output();
    }

}
