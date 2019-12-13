<?php


namespace Flm\Media;


use Flm\Helper;
use Flm\TaskController;

class Tasks extends TaskController
{
    public static function ffmpegScreensheetCmd($params) {

        $options = $params->options;
        $video_file = Helper::mb_escapeshellarg($params->file);
        $screenfile = Helper::mb_escapeshellarg($params->imgfile);

        var_dump(__METHOD__, $params);

        $filters = //'drawtext="timecode=\'00\:00\:00\:00\' :rate=24 :fontcolor=white :fontsize=21 :shadowcolor=black :x=5 :y=5",' .
            'scale="min('. $options->scwidth. '\, iw*3/2):-1",' .
            'select="not(mod(n\,' . $options->frame_step . ')),tile=' . $options->scrows. 'x'. $options->sccols .'"';

        return <<<CMD
{$params->binary} -i {$video_file} -an -vf {$filters} -vsync 0 -frames:v 1 {$screenfile} 2>&1 | sed -u 's/^/0:  /'
CMD;
    }
}