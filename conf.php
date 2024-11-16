<?php

// path on domain where a symlink to view.php can be found
// change only if you use web AUTH
// example: http://mydomain.com/stream/view.php
// $streampath = '/plugins/filemanager-media/view.php';
$streampath = './plugins/filemanager-media/view.php';


// regex file extensions
$allowedFormats = [
    'video' => 'avi|divx|mpeg|mp4|mkv|webm',
    'audio' => 'mp3|wav|ogg|aac',
    'image' => 'png|jpe?g|gif|ico|bmp|svg|webp'
];

$allowedViewFormats = implode("|", $allowedFormats);
