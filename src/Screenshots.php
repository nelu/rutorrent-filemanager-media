<?php


namespace Flm\Media;


use Exception;
use FileUtil;
use Flm\Filesystem as Fs;
use Flm\Helper;
use Flm\RemoteShell;
use rTask;
use rXMLRPCCommand;
use Utility;

class Screenshots
{

    protected $config;


    public function __construct($config = null)
    {
        $this->config = $config;

    }

    public function doVideoScreenSheet($video_file, $screens_file, $settings)
    {

        $fs = Fs::get();

        if (!$fs->isFile($video_file)) {
            throw new Exception("No such file", 6);
        } else if ($fs->isFile($screens_file)) {
            throw new Exception("File already exists", 16);
        }

        $defaults = ['rows' => '12', 'columns' => 4, 'width' => 300];

        foreach ($defaults as $k => $value) {
            if (!isset($settings[$k]) || $settings[$k] < 1) {
                $settings[$k] = $value;
            }
        }

        $vinfo = $this->videoInfo($video_file);

        $frame_step = floor($vinfo['total_frames'] / ($settings['rows'] * $settings['columns']));

        $settings['frame_step'] = $frame_step;

        $params = (object)[
            'imgfile' => $screens_file,
            'file' => $video_file,
            'options' => (object)$settings,
            'binary' => Utility::getExternal('ffmpeg')
        ];

        $task_opts = [
            'requester' => 'filemanager-media',
            'name' => 'screensheet',
            'arg' => $screens_file
        ];

        $rtask = new rTask($task_opts);

        $cmd = self::ffmpegScreensheetCmd($params);

        return $rtask->start([$cmd], 0);;
    }

    /**
     * @param $video_file
     * @return mixed
     * @throws Exception
     */
    public static function videoInfo($video_file)
    {

        $video_file = Helper::mb_escapeshellarg($video_file);

        $args = ['-v', 0, '-show_format', '-show_streams', '-print_format', 'json', '-i', $video_file];

        if (!($info = RemoteShell::get()->execOutput(Utility::getExternal("ffprobe"), $args))) {
            throw new Exception("Current ffmpeg/ffprobe not supported. Please compile a newer version.", 4);
        }

        $vinfo = json_decode(stripslashes(implode("\n", $info)), true);

        $video_stream = null;
        $video['stream_id'] = 0;

        foreach ($vinfo['streams'] as $sk => $stream) {

            if (array_search('video', $stream, true) !== false) {
                $video['stream_id'] = $sk;
                $video_stream = $stream;
            }
        }

        if (is_null($video_stream)) {
            throw new Exception("Invalid video file: " . $video_file, 4);
        }

        $video['duration'] = floor(isset($vinfo['format']['duration']) ? $vinfo['format']['duration'] : (isset($video_stream['duration']) ? $video_stream['duration'] : 0));
        $video['frame_rate'] = floor(isset($video_stream['r_frame_rate']) ? eval("return (" . $video_stream['r_frame_rate'] . ");") : 0);
        $video['total_frames'] = $video['duration'] * $video['frame_rate'];


        if ($video['total_frames'] < 1) {

            FileUtil::toLog(__METHOD__ . " WARNING: Total frames not detected from video stream: " . json_encode($video));

            $args = ['-v', 0, '-show_streams', '-print_format', 'json', '-count_frames', '-i', $video_file];

            if (!($info = RemoteShell::get()->execOutput(Utility::getExternal("ffprobe"), $args))) {
                throw new Exception("Current ffmpeg/ffprobe not supported. Please compile a newer version.", 4);
            }

            $vinfo = json_decode(stripslashes(implode("\n", $info)), true);

            if (!isset($vinfo['streams'][$video['stream_id']])) {
                throw new Exception("Invalid video file: " . $video_file, 4);
            }

            $video['total_frames'] = $vinfo['streams'][$video['stream_id']]['nb_read_frames'];

        }

        return $video;

    }

    public static function ffmpegScreensheetCmd($params)
    {

        $options = $params->options;
        $video_file = Helper::mb_escapeshellarg($params->file);
        $screenfile = Helper::mb_escapeshellarg($params->imgfile);

        $filters = 'drawtext="timecode=\'00\:00\:00\:00\' :rate=24 :fontcolor=white :fontsize=21 :shadowcolor=black :x=5 :y=5",' .
            'scale="min(' . $options->width . '\, iw*3/2):-1",' .
            'select="not(mod(n\,' . $options->frame_step . ')),tile=' . $options->columns . 'x' . $options->rows . '"';

        return "{$params->binary} -i {$video_file} -an -vf {$filters} -vsync 0 -frames:v 1 {$screenfile}";
    }

}