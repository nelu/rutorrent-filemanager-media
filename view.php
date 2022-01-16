<?php

use Flm\Helper;
use Flm\Media\FileManagerMedia;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$pluginDir = dirname(__FILE__);
require_once($pluginDir . '/boot.php');
require_once($pluginDir . '/conf.php');

$conf = Helper::getConfig();
$conf['allowedViewFormats'] = $allowedViewFormats;

$c = new FileManagerMedia($conf);

$c->handleRequest();

