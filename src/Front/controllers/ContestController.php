<?php

namespace app\controllers;

use app\common\Util;
use app\models\Contest;
use app\models\ContestProblem;
use yii\db\Exception;
use yii\web\NotFoundHttpException;

class ContestController extends BaseController {

    public function actionIndex($id = 1) {
        $pageSize = \Yii::$app->params['queryPerPage'];

        $whereArray = [];
        $andWhereArray = [];
        $search = \Yii::$app->request->get('search');
        if ($search) {
            $andWhereArray = ['or', ['like', 'title', '%'.$search.'%', false], ['like', 'manager', '%'.$search.'%', false]];
        }

        $totalPage = Contest::totalPage($whereArray, $pageSize, $andWhereArray);
        $contests = Contest::getContests($id, $pageSize, $whereArray, $andWhereArray);

        $pageArray = Util::getPaginationArray($id, 8, $totalPage);

        foreach($contests as &$contest) {
            $contest['duration'] = Util::getDuration($contest['start_time'], $contest['end_time']);
            $length = strtotime($contest['end_time']) - strtotime($contest['start_time']);
            $now = time();
            if ($now < strtotime($contest['start_time']))
                $percent = 0;
            else if ($now > strtotime($contest['end_time']))
                $percent = 100;
            else
                $percent = floor(($now - strtotime($contest['start_time'])) / $length * 100);
            $contest['percent'] = $percent;
        }

        $this->smarty->assign('webTitle', "Contest");
        $this->smarty->assign('contests', $contests);
        $this->smarty->assign('search', $search);
        $this->smarty->assign('pageArray', $pageArray);
        $this->smarty->assign('totalPage', $totalPage);
        $this->smarty->assign('pageNow', $id);
        return $this->smarty->display('contest/contest.html');
    }

    public function actionView($id = 0) {
        $contest = Contest::findById($id);
        if (!$contest) {
            throw new NotFoundHttpException("$id 这个比赛不存在！");
        }
        if ($contest->is_private) {
            if (!Util::isLogin() && !isset(\Yii::$app->session["cid:$id"])) {
                $this->smarty->assign('msg', "你还没有访问比赛 $id 的权限");
                return $this->smarty->display('common/error.html');
            }
        }
        return "fuck";
    }

    public function actionAdd($id = 0) {

        if (!Util::isLogin()) {
            $this->smarty->assign('msg', "请先登录");
            return $this->smarty->display('common/error.html');
        }

        $contestId = null;
        $problemList = [];

        if ($id != 0) {
            $contest = Contest::findById($id);
            if (!$contest)
                throw new NotFoundHttpException("$id 这个比赛不存在！");
            if ($contest->owner_id != Util::getUser()) {
                $this->smarty->assign('msg', "比赛 $id 不是你创建的，你无法修改！");
                return $this->smarty->display('common/error.html');
            }
            $problemList = ContestProblem::getProblemList($id);
            $contestId = $id;

            $startSecond = strtotime($contest->start_time); // 比赛开始的时间戳

            $startDate = date("Y-m-d", $startSecond); // 比赛开始的日期
            $startHour = floor(($startSecond - strtotime($startDate)) / 3600);
            $startMin = floor(($startSecond - strtotime($startDate)) % 3600 / 60);

            $endSecond = strtotime($contest->end_time);
            $length = $endSecond - $startSecond;

            $lengthDate = floor($length / 86400);
            $lengthHour = floor($length % 86400 / 3600);
            $lengthMin = floor($length % 3600 / 60);

            $lockSecond = strtotime($contest->lock_board_time);
            $lockLength = $lockSecond - $startSecond;

            $lockLengthDate = floor($lockLength / 86400);
            $lockLengthHour = floor($lockLength % 86400 / 3600);
            $lockLengthMin = floor($lockLength % 3600 / 60);

            $this->smarty->assign('contest', $contest);
            $this->smarty->assign('startDate', $startDate);
            $this->smarty->assign('startHour', $startHour);
            $this->smarty->assign('startMin', $startMin);
            $this->smarty->assign('lengthDate', $lengthDate);
            $this->smarty->assign('lengthHour', $lengthHour);
            $this->smarty->assign('lengthMin', $lengthMin);
            $this->smarty->assign('lockLengthDate', $lockLengthDate);
            $this->smarty->assign('lockLengthHour', $lockLengthHour);
            $this->smarty->assign('lockLengthMin', $lockLengthMin);
        }
        $this->smarty->assign('webTitle', 'Add Contest');
        $this->smarty->assign('problemList', $problemList);
        $this->smarty->assign('contestId', $contestId);
        return $this->smarty->display('contest/add.html');
    }

    public function actionDoAdd() {
        if (!Util::isLogin())
            json_encode(['code' => 1, 'data' => '请先登录']);

        $title = \Yii::$app->request->post('title');
        $beginTime = (int)\Yii::$app->request->post('beginTime');
        $length = (int)\Yii::$app->request->post('length');
        $lockBoardTime = (int)\Yii::$app->request->post('lockBoardTime');
        $password = \Yii::$app->request->post('password');
        $penalty = \Yii::$app->request->post('penalty');
        $hideOthers = (int)\Yii::$app->request->post('hideOthers');
        $description = \Yii::$app->request->post('description');
        $announcement = \Yii::$app->request->post('announcement');
        $problemList = \Yii::$app->request->post('problemList');

        $contestId = \Yii::$app->request->post('contestId');

        try {
            if ($contestId) {
                $contest = Contest::findById($contestId);
                if (!$contest)
                    return json_encode(['code' => 1, 'data' => '比赛不存在']);
                if ($contest->owner_id != Util::getUser())
                    return json_encode(['code' => 1, 'data' => '无法修改，比赛非你建']);
            } else {
                $contest = new Contest();
            }
            $contest->title = $title;
            $contest->description = $description;
            $contest->announcement = $announcement;
            $contest->is_private = $password ? true : false;
            $contest->start_time = date("Y-m-d H:i:s", $beginTime);
            $contest->end_time = date("Y-m-d H:i:s", $beginTime + $length);
            $contest->penalty = $penalty;
            $contest->lock_board_time = date("Y-m-d H:i:s", $beginTime + $lockBoardTime);
            $contest->hide_others = $hideOthers;
            $contest->owner_id = Util::getUser();
            $contest->manager = Util::getUserName();
            $contest->password = $password;

            $contest->save();
            ContestProblem::cleanContest($contest->id);
            ContestProblem::addProblemList($contest->id, $problemList);

            if ($contestId)
                $data = "比赛 $contest->id 修改成功";
            else
                $data = "比赛 $contest->id 创建成功";
            return json_encode(['code' => 0, 'data' => $data]);
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'data' => $e->getMessage()]);
        }
    }
}