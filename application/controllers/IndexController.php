<?php

use Icinga\Web\Controller\ActionController;

class Graphite_IndexController extends ActionController
{
    public function indexAction()
    {
        $this->view->url = $this->getParam('graphite_url');
    }
}
