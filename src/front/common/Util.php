<?php
/**
 * Created by PhpStorm.
 * User: torapture
 * Date: 17-12-1
 * Time: 下午7:28
 */

namespace app\common;


use app\models\User;

class Util {
    static public function getDirs($target) {
        $ret = [];
        $total = scandir($target);
        foreach ($total as $dir) {
            if ($dir != "." && $dir != "..") {
                $temp = $target.'/'.$dir;
                if (is_dir($temp))
                    array_push($ret, $dir);
            }
        }
        return $ret;
    }
    static public function getPaginationArray($now, $need, $total) {
        $now = (int)$now;
        $need = (int)$need;
        $total = (int)$total;

        if ($total == 0) return [];
        if ($now < 1) $now = 1;
        if ($now > $total) $now = $total;

        if ($need >= $total)
            return range(1, $total);

        $L = (int)(($need + 1) / 2);
        $from = max(1, $now - $L + 1);
        $end = $from + $need - 1;
        if ($end > $total) {
            $end = $total;
            $from = $end - $need + 1;
        }
        return range($from, $from + $need - 1);
    }

    static public function sendToJudgeBySocket($runid, $host, $port, $connectString) {
        $msg = $connectString . " " . strval($runid);
        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $host, $port);
            socket_write($socket, $msg, strlen($msg));
            socket_close($socket);
            return true;
        } catch (\Exception $e) {
            if (isset($socket) && $socket)
                socket_close($socket);
            return false;
        }
    }

    static public function sendRunIdToJudge($runid) {
        $judger = \Yii::$app->params['judgerList'][array_rand(\Yii::$app->params['judgerList'])];
        $host = $judger['host'];
        $port = $judger['port'];
        $connectString = $judger['connectString'];
        Util::sendToJudgeBySocket($runid, $host, $port, $connectString);
    }

    static public function sendRunIdToRejudge($runid) {
        if (\Yii::$app->params['rejudgerList'])
            $rejudgerList = \Yii::$app->params['rejudgerList'];
        else
            $rejudgerList = \Yii::$app->params['judgerList'];
        $judger = $rejudgerList[array_rand($rejudgerList)];
        $host = $judger['host'];
        $port = $judger['port'];
        $connectString = $judger['connectString'];
        Util::sendToJudgeBySocket($runid, $host, $port, $connectString);
    }

    static public function isLogin() {
        return isset(\Yii::$app->session['user_id']);
    }

    static public function getUser() {
        return \Yii::$app->session['user_id'];
    }

    static public function getUserName() {
        return \Yii::$app->session['username'];
    }

    static public function isRoot() {
        $userId = self::getUser();
        if ($userId)
            return User::isRoot($userId);
        else
            return false;
    }

    static public function getDuration($startTime, $endTime) {
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $duration = $endTime - $startTime;
        $ret = '';
        $day = floor($duration / 86400);
        $hour = floor($duration % 86400 / 3600);
        $min = floor($duration % 3600 / 60);
        if ($day > 0)
            $ret = sprintf("%d天%02d:%02d:%02d", $day, $hour, $min, 0);
        else
            $ret = $ret = sprintf("%02d:%02d:%02d", $hour, $min, 0);
        return $ret;
    }

    static public function ignoreJs($str) {
        $preg = "/<script[\s\S]*?<\/script>/i";
        return preg_replace($preg,"", $str);
    }
}