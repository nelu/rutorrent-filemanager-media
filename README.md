# filemanager-media
Media features for filemanager plugin which include:
- audio/video player for common media formats: mp3|mp4|avi|divx|mkv (browser dependent)
- image viewer with zoom support
- create video screenshots functionality: single mosaic


Plugin configuration `conf.php`:

```php 
$streampath = '/plugins/filemanager-media/view.php';
$allowedFormats = [
    'video' => 'avi|divx|mpeg|mp4|mkv',
    'audio' => 'mp3|wav|ogg',
    'image' => 'png|jpe?g'
];
```
  - `$allowedFormats` holds the allowed media formats file extensions (audio/video/image) in regex format
  - `$streampath` is useful when you need a different url path for your media files (ex: when you use a replacement video player in your browser and web auth) 
  
Settings available in the screenshot dialog:
 - Screens rows: number of thumbnail rows in the output screensheet 
 - Screens columns: number of thumbnail columns in the output screensheet 
 - Thumbnail width: the width of each cell in the screensheet tile



