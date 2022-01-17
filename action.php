<?php

use Flm\Helper;
use Flm\Media\FileManagerMedia;

$pluginDir = dirname(__FILE__);
require_once($pluginDir . '/boot.php');
require_once($pluginDir . '/conf.php');

$conf = Helper::getConfig();

$conf['allowedViewFormats'] = $allowedViewFormats;
$conf['allowedFormats'] = $allowedFormats;

$c = new FileManagerMedia($conf);

$c->handleRequest();