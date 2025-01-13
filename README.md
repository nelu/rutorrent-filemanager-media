# filemanager-media
A plugin which extends filemanager functionality by adding media view capabilities (audio/video/image) and screenshot generation for videos both in File Manager and Files tab.

#### Media features for filemanager plugin which include:
- audio/video player for common media formats: mp3|mp4|avi|divx|mkv (browser dependent)
- image viewer with zoom support
- create video [screenshots functionality](https://github.com/nelu/rutorrent-filemanager-media/wiki): tile mosaic


#### Settings available in the screenshot dialog:
 - Screens rows: number of thumbnail rows in the output screensheet 
 - Screens columns: number of thumbnail columns in the output screensheet 
 - Thumbnail width: the width of each cell in the screensheet tile
   
See [Wiki](https://github.com/nelu/rutorrent-filemanager-media/wiki) for screenshots

#### Plugin configuration
All configuration options reside in `conf.php` and some of them support ENV config variables
  - `$allowedFormats` holds the allowed media formats file extensions (audio/video/image) in regex format
  - `streampath` is useful when you need a different url path for your media files, ex: when you use a replacement video player in your browser and which does not support web auth 
```php
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

```

##### TODO:
- add single file screenshots for File Manager entries

