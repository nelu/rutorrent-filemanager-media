<?php

use Flm\Filesystem as Fs;
use Flm\Helper;
use Flm\RemoteShell as Remote;

$flmPluginDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../filemanager';

require_once ($flmPluginDir . '/init.php');
require_once($flmPluginDir . '/src/BaseController.php');
require_once($flmPluginDir . '/src/FileManager.php');
require_once ($flmPluginDir . '/src/RemoteShell.php');
require_once ($flmPluginDir . '/src/Filesystem.php');
require_once ($flmPluginDir . '/../_task/task.php');
require_once ( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'src/FileManagerMedia.php');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$c = new \Flm\Media\FileManagerMedia(Helper::getConfig());

$c->handleRequest();

