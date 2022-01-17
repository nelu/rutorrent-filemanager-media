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

    protected $config = [];
    protected $videoFile;
    protected $output;

    const DEFAULT_SHEET_CONFIG = ['rows' => '6', 'columns' => 4, 'width' => 480];


    public function __construct($videoSrc, $output, $config = [])
    {
        $this->videoFile = $videoSrc;
        $this->output = $output;
        $this->setConfig($config);

    }

    public function setConfig($cfg = [])
    {
        foreach (self::DEFAULT_SHEET_CONFIG as $k => $value) {
            if (!isset($cfg[$k]) || $cfg[$k] < 1) {
                $this->config[$k] = $value;
            } else{
                $this->config[$k] = $cfg[$k];
            }
        }
    }

    public function getSheetCmd()
    {
        $vinfo = self::videoInfo($this->videoFile);

        $frame_step = floor($vinfo['total_frames'] / ($this->config['rows'] * $this->config['columns']));

        $this->config['frame_step'] = $frame_step;

        $params = (object)[
            'imgfile' => $this->output,
            'file' => $this->videoFile,
            'options' => (object)$this->config,
            'binary' => Utility::getExternal('ffmpeg')
        ];

        $cmd = self::ffmpegScreensheetCmd($params);

        return [$cmd];

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

        $filters = 'drawtext="timecode=\'00\:00\:00\:00\' :box=1 :boxcolor=black :boxborderw=5 :fontcolor=white :rate=30 :fontsize=ceil(6.1/100*h) :shadowcolor=white :x=0 :y=0",' .
            'scale="' . $options->width . ':-1",' .
            'select="not(mod(n\,' . $options->frame_step . '))",'.
            'tile="' . $options->columns . 'x' . $options->rows . ':padding=3:margin=2"';

        return "{$params->binary} -i {$video_file} -an -vf {$filters} -vsync 0 -frames:v 1 -qscale:v 3 {$screenfile}";
    }

}