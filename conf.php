<?php
global $pathToExternals;
// set with fullpath to binary or leave empty
$pathToExternals['ffmpeg'] ?? (
    $pathToExternals['ffmpeg'] = ''
);

// regex file extensions
$allowedFormats = [
    'video' => 'avi|divx|mpeg|mp4|mkv|webm',
    'audio' => 'mp3|wav|ogg|aac',
    'image' => 'png|jpe?g|gif|ico|bmp|svg|webp'
];

return [
    // path on domain where a symlink to view.php can be found
    // change only if you use web AUTH
    // example: https://mydomain.com/rutorrent/plugins/filemanager-media/stream/view.php
    // 'streampath' = './plugins/filemanager-media/view.php';
    'streampath' => $_ENV['RU_FLM_MEDIA_ENDPOINT'] ?? '',

    'allowedFormats' => $allowedFormats,
    'allowedViewFormats' => implode("|", $allowedFormats)
];
