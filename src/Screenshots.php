<?php
namespace Flm\Media;
use Exception;
use FileUtil;
use Flm\ShellCmd;
use Utility;

class Screenshots
{

    protected $config = [];
    protected $videoFile;
    protected $output;

    public array $videoInfo;

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

    /**
     * @throws Exception
     */
    public function getSheetCmd() : ShellCmd
    {
        $vinfo = self::videoInfo($this->videoFile);

        $this->videoInfo = $vinfo;

        $params =  (object)$this->config;
        $params->imgfile = $this->output;
        $params->file = $this->videoFile;
        $params->binary = Utility::getExternal('ffmpeg');
        $params->fps = $vinfo['fps'];
        $params->frame_step = floor($vinfo['total_frames'] / ($params->rows * $params->columns));

        // should !not be the case
        if($params->frame_step > $params->fps) {
         //   $params->frame_step = $params->fps;
        }

        $filters = 'drawtext="timecode=\'00\:00\:00\:00\' :box=1 :boxcolor=black :boxborderw=5 :fontcolor=white :rate='.$params->fps.' :fontsize=ceil(6.1/100*h) :shadowcolor=white :x=0 :y=0",' .
            'scale="' . $params->width . ':-1",' .
            'select="not(mod(n\,' . $params->frame_step . '))",'.
            'tile="' . $params->columns . 'x' . $params->rows . ':padding=3:margin=2"';

        // return "{$params->binary} -i {$video_file} -an -vf {$filters} -vsync 0 -frames:v 1 -qscale:v 3 {$screenfile}";

        return ShellCmd::bin($params->binary,
            [   '-i ' => $params->file,
                '-an',
                '-vf '. $filters => true,
                '-vsync '=> 0,
                '-frames:v '=> 1,
                '-qscale:v ' => 3,
                $params->imgfile
            ]);
    }

    /**
     * @param $video_file
     * @return array
     * @throws Exception
     */
    public static function videoInfo($video_file)
    {
        $args = ['-v', 0, /*'-show_format', */'-show_streams', '-print_format', 'json', '-i', $video_file];
        $ffprobe = ShellCmd::bin(Utility::getExternal("ffprobe"), $args);
        $result = $ffprobe->runRemote();

        if ($result[0] != 0) {
            FileUtil::toLog(__METHOD__ . ' cmd ' . $ffprobe->cmd() . ' result: ' . json_encode($result));
            throw new Exception("Current ffmpeg/ffprobe not supported. Please compile a newer version.", 4);
        }

        $vinfo = json_decode(stripslashes(implode("\n", $result[1])), true);

        $video_stream = null;

        foreach ($vinfo['streams'] as $sk => $stream) {

            if ($stream['codec_type'] === 'video' ||
                array_search('video', $stream, true) !== false) {
                $video_stream = $stream;
                break;
            }
        }

        if (is_null($video_stream)) {
            FileUtil::toLog(__METHOD__ . ' cmd ' . $ffprobe->cmd() . ' result: ' . var_export($result, true) . ' video stream: ' . var_export($vinfo, true));
            throw new Exception("Invalid video file: " . $video_file, 4);
        }

        $fps = explode('/', isset($video_stream['avg_frame_rate'])
                ? $video_stream['avg_frame_rate']
                : ($video_stream["r_frame_rate"] ?? 0)
        );

        $video['duration'] = floor($video_stream['duration'] ?? 0);
        //$video['duration'] = floor(isset($vinfo['format']['duration']) ? $vinfo['format']['duration'] : (isset($video_stream['duration']) ? $video_stream['duration'] : 0));
        $video['fps'] = floor(array_shift($fps));
        $video['total_frames'] = intval($video_stream['nb_frames']);


/*        if ($video['total_frames'] < 1) {

            FileUtil::toLog(__METHOD__ . " WARNING: Total frames not detected from video stream: " . json_encode($video));

            $args = ['-v', 0, '-show_streams', '-print_format', 'json', '-count_frames', '-i', $video_file];

            $result = $ffprobe->setArgs($args)->runRemote();
            if ($result[0] != 0) {
                throw new Exception("Current ffmpeg/ffprobe not supported. Please compile a newer version.", 4);
            }

            $vinfo = json_decode(stripslashes(implode("\n", $result[1])), true);

            if (!isset($vinfo['streams'][$video['stream_id']])) {
                throw new Exception("Invalid video file: " . $video_file, 4);
            }

            $video['total_frames'] = $vinfo['streams'][$video['stream_id']]['nb_read_frames'];

        }*/

        $video['info'] = $vinfo;

        if (!$video['fps'] || !$video['total_frames']) {
            throw new Exception("Invalid video file: " . $video_file . ' -> '. var_export($video, true), 4);
        }
        return $video;
    }
}