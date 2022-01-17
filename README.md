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
  - `$allowedFormats` holds the allowed media formats file extensions (audio/video/image)
  - `$streampath` is useful when you need a different the url path for the media viewer (ex: when you use a replacement video player in your browser and web auth) 
  
