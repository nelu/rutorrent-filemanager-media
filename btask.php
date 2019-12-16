<?php
require_once (dirname(__FILE__) . '/boot.php');

$task = new Flm\Media\Tasks($argv[1]);

$task->handle();
