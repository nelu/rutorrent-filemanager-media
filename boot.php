<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$flmPluginDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../filemanager';


require_once($flmPluginDir . '/src/BaseController.php');
require_once($flmPluginDir . '/src/FileManager.php');
require_once($flmPluginDir . '/src/RemoteShell.php');
require_once($flmPluginDir . '/src/Filesystem.php');
require_once($flmPluginDir . '/src/TaskController.php');
require_once($flmPluginDir . '/src/FsUtils.php');
require_once($flmPluginDir . '/../_task/task.php');
require_once($flmPluginDir . '/src/Helper.php');

$pluginDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
require_once($pluginDir . 'src/Screenshots.php');
require_once($pluginDir . 'src/FileManagerMedia.php');
require_once($pluginDir . "/../../php/util.php");

