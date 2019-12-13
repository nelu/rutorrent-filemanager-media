<?php
namespace Flm\Media;

use Exception;
use Flm\Filesystem as Fs;
use Flm\Helper;
use Flm\RemoteShell as Remote;
use LFS;
use rXMLRPCCommand;

class FileManagerMedia extends \Flm\BaseController {

    public function __construct($config)
    {
        if(isset($_SERVER["PATH_INFO"]) && !empty($_SERVER["PATH_INFO"]))
        {
            $_POST['action'] = 'viewMedia';
            $_POST['target'] = $_SERVER["PATH_INFO"];
        }

        parent::__construct($config);
    }

    public function viewMedia($params) {


        if (!isset($_POST['target'])) {
            self::jsonError(16);
        }

        $file = $_POST['target'];

        $ext = Helper::getExt($file);

        if (!preg_match('/^(avi|divx|mpeg|mp[34]|mkv|png|jpeg)$/i', $ext))
        {
            self::jsonError('404 Invalid format'. $ext);
        }


        $sf = $this->flm()->getWorkDir($file);

        if(!is_file($sf)){
            self::jsonError(18);
        }

        $this->streamFile($sf);

        die();
    }


    protected function streamFile($filename, $contentType = null,  $mustExit = false )
    {
        global $canUseXSendFile;
        $stat = @LFS::stat($filename);
        if($stat && @LFS::is_file($filename) && @LFS::is_readable($filename))
        {
            $etag = sprintf('"%x-%x-%x"', $stat['ino'], $stat['size'], $stat['mtime'] * 1000000);
            if( 	(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) ||
                (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $stat['mtime']))
                header('HTTP/1.0 304 Not Modified');
            else
            {
                header('Content-Type: '.(is_null($contentType) ? mime_content_type ($filename) : $contentType));

                if($mustExit &&
                    $canUseXSendFile &&
                    function_exists('apache_get_modules') &&
                    in_array('mod_xsendfile', apache_get_modules()))
                {
                    header("X-Sendfile: ".$filename);
                }
                else
                {
                    header('Cache-Control: ');
                    header('Expires: ');
                    header('Pragma: ');
                    header('Etag: '.$etag);
                    header('Last-Modified: ' . date('r', $stat['mtime']));
                    set_time_limit(0);
                    ignore_user_abort(!$mustExit);
                    header('Accept-Ranges: bytes');


                    if(ob_get_level())
                        while(@ob_end_clean());

                    $begin = 0;
                    $end = $stat['size'];
                    if(isset($_SERVER['HTTP_RANGE']))
                    {
                        if(preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches))
                        {
                            $begin=intval($matches[1]);
                            if(!empty($matches[2]))
                                $end=intval($matches[2]);
                        }
                    }
                    $streamSize = $end -1;
                    $size = $end - $begin;
                    if((PHP_INT_SIZE<=4) && ($size >= 2147483647))
                        passthru('cat '.escapeshellarg($filename));
                    else
                    {
                        if(!ini_get("zlib.output_compression"))
                            header('Content-Length:' . $size);

                        if($size != $stat['size'])
                        {

                            $f = @fopen($filename,'rb');
                            if($f===false)
                                header ("HTTP/1.0 505 Internal Server Error");
                            else
                            {
                                header('HTTP/1.0 206 Partial Content');
                                header("Content-Range: bytes ".$begin."-".$streamSize."/".$stat['size']);

                                $cur = $begin;
                                fseek($f,$begin,0);
                                while( !feof($f) && ($cur<$end) && !connection_aborted() && (connection_status()==0) )
                                {
                                    print(fread($f,min(1024*16,$end-$cur)));
                                    $cur+=1024*16;
                                }
                                fclose($f);
                            }
                        }
                        else
                        {

                            header('HTTP/1.0 200 OK');
                            readfile($filename);
                        }
                    }
                }
            }
            if($mustExit)
                exit(0);
            else
                return(true);
        }
        return(false);
    }

    public function video_info($video_file) {

        Remote::get()->addCommand( new rXMLRPCCommand('execute_capture',
            array(getExternal("ffprobe"), '-v', 0, '-show_format', '-show_streams', '-print_format', 'json' ,'-i', $video_file)));
        //Remote::get()->success();


        if(!Remote::get()->success()) {$this->sdie('Current ffmpeg/ffprobe not supported. Please compile a newer version.'); }

        $vinfo = json_decode(stripslashes(Remote::get()->val[0]), true);

        $video_stream = false;
        $video['stream_id'] = 0;

        foreach($vinfo['streams'] as $sk => $stream) {

            if(array_search('video', $stream, true) !== false) {
                $video['stream_id'] = $sk;
                $video_stream = $stream;
            }
        }

        if($video_stream === false) {$this->sdie('Invalid video!');}

        $video['duration'] = floor(isset($vinfo['format']['duration']) ? $vinfo['format']['duration'] : (isset($video_stream['duration']) ? $video_stream['duration'] : 0));
        $video['frame_rate'] = floor(isset($video_stream['r_frame_rate']) ? eval("return (".$video_stream['r_frame_rate'].");") : 0);
        $video['total_frames'] = $video['duration']*$video['frame_rate'];

        if($video['total_frames'] < 1) {

            Remote::get()->addCommand( new rXMLRPCCommand('execute_capture',
                array(getExternal("ffprobe"), '-v', 0, '-show_streams', '-print_format', 'json', '-count_frames', '-i', $video_file)));

            $vinfo = json_decode(stripslashes(Remote::get()->val[0]), true);
            $video['total_frames'] = $vinfo['streams'][$video['stream_id']]['nb_read_frames'];

        }

        return $video;

    }

    public function videoScreenshots($file, $output) {

        $fs = Fs::get();

        $video_file = $this->getUserDir($file);
        $screens_file = $this->getUserDir($output);

        if (!$fs->isFile($video_file) ) {
            throw new Exception("Error Processing Request", 6);
        }else  if($fs->isFile($screens_file)) {
            throw new Exception("dest is file", 16);
        }

        $defaults = array('scrows' => '12', 'sccols' => 4, 'scwidth' => 300 );

        $uisettings = json_decode(file_get_contents(getSettingsPath().'/uisettings.json'), true);
        $settings = array();

        foreach($defaults as $k => $value) {
            $settings[$k] = (isset($uisettings['webui.fManager.'.$k]) && ($uisettings['webui.fManager.'.$k] > 1)) ? $uisettings['webui.fManager.'.$k] : $value;
        }

        $vinfo = $this->video_info($video_file);

        $frame_step = floor($vinfo['total_frames'] / ($settings['scrows'] * $settings['sccols']));

        $settings['frame_step'] = $frame_step;

        $temp = Helper::getTempDir();


        $args = array('action' => 'makeScreensheet',
            'params' => array(
                'imgfile' => $screens_file,
                'file' => $video_file,
                'options' => $settings,
                'binary'=> getExternal('ffmpeg')
            ),
            'temp' => $temp );

        $task = $temp['dir'].'task';

        file_put_contents($task, json_encode($args));

        $task_opts = array  ( 'requester'=>'filemanager',
            'name'=>'screensheet',
        );

        $rtask = new \rTask( $task_opts );
        $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
        $ret = $rtask->start($commands, 0);

        //   var_dump($ret);

        return $temp;
    }

    public function fileScreenSheet($params) {



        if (!isset($params->to)) {
            self::jsonError(2);
        }

        if (!isset($params->target)) {
            self::jsonError(2);
        }

        try {



            $temp = $this->flm()->videoScreenshots($params->target, $params->to);

        } catch (\Exception $err) {
            var_dump($err);
            self::jsonError($err->getCode());
            return false;
        }

        return ['error' => 0, 'tmpdir' => $temp['tok']];


        $e->screenshots($e->postlist['target'], $e->postlist['to']);
    }

}