<?php

namespace Icinga\Module\Graphite;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use Icinga\Web\Hook\GrapherHook;
use Icinga\Web\Url;

class Grapher extends GrapherHook
{
    protected $hasPreviews = true;
    protected $hasTinyPreviews = true;
    protected $graphiteConfig;
    protected $baseUrl = 'http://graphite.com/render/?';
    protected $serviceMacro = 'icinga2.$host.name$.services.$service.name$.$service.check_command$.perfdata.$metric$.value';
    protected $hostMacro = 'icinga2.$host.name$.host.$host.check_command$.perfdata.$metric$.value';
    protected $imageUrlMacro = '&target=$target$&source=0&width=300&height=120&hideAxes=true&lineWidth=2&hideLegend=true&colorList=049BAF';
    protected $largeImageUrlMacro = '&target=$target$&source=0&width=800&height=700&colorList=049BAF&lineMode=connected';
    protected $legacyMode = false;
    protected $graphiteKeys = array();
    protected $graphiteLabels = array();
    protected $areaMode = "all";
    protected $iframeWidth = "800px";
    protected $iframeHeight = "700px";


    protected function init()
    {
        $cfg = Config::module('graphite')->getSection('graphite');
        $this->baseUrl = rtrim($cfg->get('base_url', $this->baseUrl), '/');
        $this->legacyMode = filter_var($cfg->get('legacy_mode', $this->legacyMode), FILTER_VALIDATE_BOOLEAN);
        $this->serviceMacro = $cfg->get('service_name_template', $this->serviceMacro);
        $this->hostMacro = $cfg->get('host_name_template', $this->hostMacro);
        $this->imageUrlMacro = $cfg->get('graphite_args_template', $this->imageUrlMacro);
        $this->largeImageUrlMacro = $cfg->get('graphite_large_args_template', $this->largeImageUrlMacro);
    }

    public function has(MonitoredObject $object)
    {
        if ($object instanceof Host) {
            $service = '_HOST_';
        } elseif ($object instanceof Service) {
            $service = $object->service_description;
        } else {
            return false;
        }

        return true;
    }

    public function parseGrapherConfig($graphite_vars = array())
    {

	if (!empty($graphite_vars)) {

		if (!empty($graphite_vars->iframe_w)) {
	    		$this->iframeWidth = $graphite_vars->iframe_w;
		}
		if (!empty($graphite_vars->iframe_h)) {
	    		$this->iframeHeight = $graphite_vars->iframe_h;
		}
		if (!empty($graphite_vars->area_mode)) {
	    		$this->areaMode = $graphite_vars->area_mode;
		}

	}

    }

    public function getKeysAndLabels($graphite_vars = array(), $vars) 
    {

        if (array_key_exists("graphite_keys", $vars)) {
            $this->graphiteKeys = $vars["graphite_keys"];
            $this->graphiteLabels = $vars["graphite_keys"];
            if (array_key_exists("graphite_labels", $vars)) {
		if (count($vars["graphite_keys"]) == count($vars["graphite_labels"])) {
                    $this->graphiteLabels = $vars["graphite_labels"];
                }
            }
        }

    }

    public function getPerfdataKeys($object) 
    {
    	foreach (PerfdataSet::fromString($object->perfdata)->asArray() as $pd) {
            $this->graphiteKeys[] = $pd->getLabel();
            $this->graphiteLabels[] = $pd->getLabel();
        }
    }

    public function getPreviewHtml(MonitoredObject $object)
    {
        $perfdata_property = $object->getType() . "_process_perfdata";
        if ( ! $object->$perfdata_property ) return '';

        $object->fetchCustomvars();

        if (array_key_exists("graphite", $object->customvars)) {
		$this->parseGrapherConfig($object->customvars["graphite"]);
		$this->getKeysAndLabels($object->customvars["graphite"], $object->customvars);
	} else {
		$this->getKeysAndLabels(array(), $object->customvars);
	}

	if (empty($this->graphiteKeys)) {
		$this->getPerfDataKeys($object);
	}

        if ($object instanceof Host) {
            $host = $object;
            $service = null;
        } elseif ($object instanceof Service) {
            $service = $object;
            $host = null;
        } else {
            return '';
        }

        $html = "<table class=\"avp newsection\">\n"
               ."<tbody>\n";

        for ($key = 0; $key < count($this->graphiteKeys); $key++) {
            $html .= "<tr><th>\n"
                  . $this->graphiteLabels[$key]
                  . '</th><td>'
                  . $this->getPreviewImage($host, $service, $this->graphiteKeys[$key])
                  . "</td>\n"
                  . "<tr>\n";
        }

        $html .= "</tbody></table>\n";
        return $html;
    }

    // Currently unused,
    public function getSmallPreviewImage($host, $service = null)
    {
        return null;
    }

    private function getPreviewImage($host, $service, $metric)
    {

        if ($host != null){
            $target = Macro::resolveMacros($this->hostMacro, $host, $this->legacyMode, true);
        } elseif  ($service != null ){
            $target = Macro::resolveMacros($this->serviceMacro, $service, $this->legacyMode, true);
        } else {
           $target = '';
        }

        $target = Macro::resolveMacros($target, array("metric"=>$metric), $this->legacyMode, true, true);

        $imgUrl = $this->baseUrl . Macro::resolveMacros($this->imageUrlMacro, array("target" => $target, "areaMode" => $this->areaMode), $this->legacyMode);

        $largeImgUrl = $this->baseUrl . Macro::resolveMacros($this->largeImageUrlMacro, array("target" => $target, "areaMode" => $this->areaMode), $this->legacyMode);

        $url = Url::fromPath('graphite', array(
            'graphite_url' => urlencode($largeImgUrl),
	    'graphite_iframe_w' => urlencode($this->iframeWidth),
	    'graphite_iframe_h' => urlencode($this->iframeHeight)
        ));

        $html = '<a href="%s" title="%s"><img src="%s" alt="%s" width="300" height="120" /></a>';

        return sprintf(
            $html,
            $url,
            $metric,
            $imgUrl,
            $metric
       );
    }
}
