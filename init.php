<?php
$config = include('conf.php');
$theSettings->registerPlugin("filemanager-media");
$jResult .= 'plugin.config = ' . json_encode($config) . ';';



