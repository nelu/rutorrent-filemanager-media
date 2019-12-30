<?php


namespace Flm\Media;


use Exception;
use Flm\FsUtils;
use Flm\Helper;
use Flm\TaskController;
use rTask;
use Throwable;

class Tasks extends TaskController
{
    public function  makeScreensheet() {
        $cmd = FsUtils::ffmpegScreensheetCmd(clone $this->info->params);

        try {
            $output =  $this->LogCmdExec($cmd);
        }
        catch (Exception $err) {
            var_dump($err);
        }


        $task_opts = [
            'requester'=>'filemanager-media',
            'name'=>'screenshots',
            'arg' =>  count($this->info->params->files) . ' files in ' .  $this->info->params->archive
        ];

        $ret = false;
        try {
            $cmds = [
                'cd ' . Helper::mb_escapeshellarg($this->info->params->options->workdir),
                '{', FsUtils::getArchiveCompressCmd($this->info->params),  '}',
            ];

            $rtask = new rTask( $task_opts );
            $ret = $rtask->start($cmds, rTask::FLG_DEFAULT & rTask::FLG_ECHO_CMD);
        }
        catch (Throwable $err) {
            $ret = $err;
        }
    }

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

    public static function getTaskCmd($taskFile) {
        return getExternal("php"). ' '. dirname(__FILE__) .'/..'. DIRECTORY_SEPARATOR. $taskFile;
    }
}