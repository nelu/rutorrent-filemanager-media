<?php

use Flm\Helper;
use Flm\Media\FileManagerMedia;

$pluginDir = dirname(__FILE__);
require_once($pluginDir . '/boot.php');
$config = include($pluginDir . '/conf.php');

(new FileManagerMedia(array_merge_recursive(Helper::getConfig(), $config)))
    ->handleRequest();