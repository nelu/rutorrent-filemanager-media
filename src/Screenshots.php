<?php


namespace Flm\Media;


use Exception;
use Flm\Filesystem as Fs;
use Flm\Helper;
use Flm\RemoteShell as Remote;
use rXMLRPCCommand;

class Screenshots
{

    protected $config;
    /**
     * @var TaskController
     */
    private $taskController;

    public function __construct($config = null)
    {
        $this->config = $config;
        $this->taskController = new Tasks();

    }

    public function doVideoScreenSheet($file, $output) {

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

        $task_opts = array  ( 'requester'=>'filemanager-media',
            'name'=>'screensheet',
        );

        $rtask = new \rTask( $task_opts );
        $commands = array( Tasks::getTaskCmd() ." ". escapeshellarg($task) );
        $ret = $rtask->start($commands, 0);

        //   var_dump($ret);

        $temp = [];

        $args = ['action' => 'makeScreensheet',
            'params' => array(
                'imgfile' => $screens_file,
                'file' => $video_file,
                'options' => $settings,
                'binary'=> getExternal('ffmpeg')
            )
        ];


        $this->taskController->info = json_decode(json_encode($args));
        $temp['tok'] = $this->taskController->run();



        return $temp;
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

}