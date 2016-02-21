<?php

use Icinga\Web\Controller\ActionController;

class Graphite_IndexController extends ActionController
{
    public function indexAction()
    {
        $this->view->url = $this->getParam('graphite_url');
        $this->view->iframe_w = $this->getParam('graphite_iframe_w');
        $this->view->iframe_h = $this->getParam('graphite_iframe_h');
    }
}
