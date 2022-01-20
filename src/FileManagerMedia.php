<?php

namespace Flm\Media;

use Exception;
use Flm\BaseController;
use Flm\Filesystem as Fs;
use Flm\Helper;
use LFS;
use rTask;
use Throwable;

class FileManagerMedia extends BaseController
{
    public $allowedViewFormats = 'avi|divx|mpeg|mp[34]|mkv|png|jpe?g';

    public function __construct($config)
    {
        parent::__construct($config);

        if (isset($config['allowedViewFormats'])) {
            $this->allowedViewFormats = $config['allowedViewFormats'];
        }
    }

    public function createFileScreenshots($params)
    {
        return ['error' => 0, 'tmpdir' => 999];
    }

    public function createFileScreenSheet($params)
    {

        if (!isset($params->to)) {
            self::jsonError(2);
        }

        if (!isset($params->target)) {
            self::jsonError(2);
        }

        $fs = Fs::get();

        $vfile = $this->flm()->currentDir($params->target);
        $sfile = $this->flm()->currentDir($params->to);

        if (!$fs->isFile($vfile)) {
            throw new Exception("No such file", 6);
        } else if ($fs->isFile($sfile)) {
            throw new Exception("File already exists", 16);
        }

        $screens = new Screenshots($vfile, $sfile, $params->settings);

        $task_opts = [
            'requester' => 'filemanager-media',
            'name' => 'screensheet',
            'arg' => $params->to
        ];

        $cmds = $screens->getSheetCmd();

        return (new rTask($task_opts))
            ->start($cmds, rTask::FLG_ECHO_CMD);

    }

    public function viewMedia($params)
    {


        if (!isset($_POST['target'])) {
            self::jsonError(16);
        }

        $file = $_POST['target'];

        $ext = Helper::getExt($file);

        if (!preg_match('/^('.$this->allowedViewFormats.')$/i', $ext)) {
            self::jsonError('404 Invalid format' . $ext);
        }


        $sf = $this->flm()->currentDir($file);

        if (!is_file($sf)) {
            self::jsonError(18);
        }

        $this->streamFile($sf);

        die();
    }

    protected function streamFile($filename, $contentType = null, $mustExit = false)
    {
        global $canUseXSendFile;
        $stat = @LFS::stat($filename);
        if ($stat && @LFS::is_file($filename) && @LFS::is_readable($filename)) {
            $etag = sprintf('"%x-%x-%x"', $stat['ino'], $stat['size'], $stat['mtime'] * 1000000);
            if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) ||
                (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $stat['mtime']))
                header('HTTP/1.0 304 Not Modified');
            else {
                header('Content-Type: ' . (is_null($contentType) ? mime_content_type($filename) : $contentType));

                if ($mustExit &&
                    $canUseXSendFile &&
                    function_exists('apache_get_modules') &&
                    in_array('mod_xsendfile', apache_get_modules())) {
                    header("X-Sendfile: " . $filename);
                } else {
                    header('Cache-Control: ');
                    header('Expires: ');
                    header('Pragma: ');
                    header('Etag: ' . $etag);
                    header('Last-Modified: ' . date('r', $stat['mtime']));
                    set_time_limit(0);
                    ignore_user_abort(!$mustExit);
                    header('Accept-Ranges: bytes');


                    if (ob_get_level())
                        while (@ob_end_clean()) ;

                    $begin = 0;
                    $end = $stat['size'];
                    if (isset($_SERVER['HTTP_RANGE'])) {
                        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                            $begin = intval($matches[1]);
                            if (!empty($matches[2]))
                                $end = intval($matches[2]);
                        }
                    }
                    $streamSize = $end - 1;
                    $size = $end - $begin;
                    if ((PHP_INT_SIZE <= 4) && ($size >= 2147483647))
                        passthru('cat ' . escapeshellarg($filename));
                    else {
                        if (!ini_get("zlib.output_compression"))
                            header('Content-Length:' . $size);

                        if ($size != $stat['size']) {

                            $f = @fopen($filename, 'rb');
                            if ($f === false)
                                header("HTTP/1.0 505 Internal Server Error");
                            else {
                                header('HTTP/1.0 206 Partial Content');
                                header("Content-Range: bytes " . $begin . "-" . $streamSize . "/" . $stat['size']);

                                $cur = $begin;
                                fseek($f, $begin, 0);
                                while (!feof($f) && ($cur < $end) && !connection_aborted() && (connection_status() == 0)) {
                                    print(fread($f, min(1024 * 16, $end - $cur)));
                                    $cur += 1024 * 16;
                                }
                                fclose($f);
                            }
                        } else {

                            header('HTTP/1.0 200 OK');
                            readfile($filename);
                        }
                    }
                }
            }
            if ($mustExit)
                exit(0);
            else
                return (true);
        }
        return (false);
    }

}