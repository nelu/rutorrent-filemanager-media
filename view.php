<?php

if (isset($_SERVER["PATH_INFO"]) && !empty($_SERVER["PATH_INFO"])) {
    $_POST['action'] = 'viewMedia';
    $_POST['target'] = $_SERVER["PATH_INFO"];
}

require_once(dirname(__FILE__) . '/action.php');

