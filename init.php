<?php

require_once ($pluginDir . '/conf.php');


$theSettings->registerPlugin("filemanager-media");

echo 'theWebUI.settings["webui.flm-media.config"] = '.json_encode(['public_endpoint'=> $streampath]) . ';';

