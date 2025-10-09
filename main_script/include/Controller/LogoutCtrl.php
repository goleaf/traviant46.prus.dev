<?php

namespace Controller;

use Auth\Session\SessionManager;
use Core\Session;
use resources\View\OutOfGameView;
use resources\View\PHPBatchView;

class LogoutCtrl extends OutOfGameCtrl
{
    private $sessionManager;

    public function __construct()
    {
        if (!Session::getInstance()->isValid()) {
            $this->innerRedirect("LoginCtrl");
        }
        $this->sessionManager = new SessionManager();
        $this->view = new OutOfGameView();
        $this->view->vars['titleInHeader'] = T("Logout", "Logout");
        $this->view->vars['bodyCssClass'] = 'perspectiveBuildings';
        $this->view->vars['contentCssClass'] = 'logout';
        $this->view->vars['content'] = PHPBatchView::render('system/logout',
            [
                'time'     => time(),
                'username' => Session::getInstance()->getName(),
                'lowRes'   => isset($_COOKIE['lowRes']) && $_COOKIE['lowRes'] == 1 ? 1 : 0,
            ]);
        if (!$this->sessionManager->logout()) {
            $this->redirect('dorf1.php');
        }
    }
}