<?php

use Icinga\Web\Controller\ModuleActionController;

class Graphite_ConfigController extends ModuleActionController
{
    public function indexAction()
    {
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('config');
        $hintHtml = $this->view->escape($this->translate(
          ' In case your graphite web url differs from %s'
          . ' or if your graphite prefix differs from "%s" please'
          . ' create a config file'
          . ' in %s following this example:'
         ));

        $this->view->escapedHint = sprintf(
            $hintHtml,
            '<b>http://graphite.com/render/?</b>',
            '<b>icinga</b>',
            '<b>' . $this->Config()->getConfigFile() . '</b>'
        );
    }
}
