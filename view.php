<?php
use Flm\Helper;
use Flm\Media\FileManagerMedia;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/boot.php');

$c = new FileManagerMedia(Helper::getConfig());

$c->handleRequest();

