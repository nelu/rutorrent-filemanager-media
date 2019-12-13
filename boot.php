<?php

$flmPluginDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../filemanager';


require_once ($flmPluginDir . '/init.php');
require_once($flmPluginDir . '/src/BaseController.php');
require_once($flmPluginDir . '/src/FileManager.php');
require_once ($flmPluginDir . '/src/RemoteShell.php');
require_once ($flmPluginDir . '/src/Filesystem.php');
require_once ($flmPluginDir . '/src/TaskController.php');
require_once ($flmPluginDir . '/../_task/task.php');

$pluginDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
require_once ( $pluginDir . 'src/Tasks.php');
require_once ( $pluginDir . 'src/FileManagerMedia.php');
