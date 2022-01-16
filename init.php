<?php

require_once ($pluginDir . '/conf.php');

$theSettings->registerPlugin("filemanager-media");
$jResult.= 'plugin.config = '.json_encode([
        'public_endpoint'=> $streampath,
        'allowedViewFormats' => $allowedViewFormats
        ]) . ';';


