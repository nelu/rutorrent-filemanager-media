# filemanager-media
A plugin which extends filemanager functionality by adding media view capabilities (audio/video/image) and screenshot generation for videos both in File Manager and Files tab.

#### Media features for filemanager plugin which include:
- audio/video player for common media formats: mp3|mp4|avi|divx|mkv (browser dependent)
- image viewer with zoom support
- create video [screenshots functionality](https://github.com/nelu/rutorrent-filemanager-media/assets/3987091/1ffee760-aac5-443c-86e9-68a01353d2b7): tile mosaic

#### Settings available in the screenshot dialog:
 - Screens rows: number of thumbnail rows in the output screensheet 
 - Screens columns: number of thumbnail columns in the output screensheet 
 - Thumbnail width: the width of each cell in the screensheet tile

![ffmpeg-screenshots](https://github.com/nelu/rutorrent-filemanager-media/assets/3987091/ae027bbf-3f23-48a4-9f90-d3b645de971d)

#### Plugin configuration
All configuration options reside in `conf.php`:
  - `$allowedFormats` holds the allowed media formats file extensions (audio/video/image) in regex format
  - `$streampath` is useful when you need a different url path for your media files, ex: when you use a replacement video player in your browser and which does not support web auth 
```php 
$streampath = '';
$allowedFormats = [
    'video' => 'avi|divx|mpeg|mp4|mkv',
    'audio' => 'mp3|wav|ogg',
    'image' => 'png|jpe?g'
];
```

##### TODO:
- add single file screenshots for File Manager entries

