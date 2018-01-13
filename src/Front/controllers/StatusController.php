<?php

namespace app\controllers;

use app\models\LanguageType;
use app\models\Status;
use app\models\User;
use app\common\Util;
use yii\db\Query;
use yii\web\NotFoundHttpException;

class StatusController extends BaseController {
    public function actionList($id = 1) {

        $pageSize = \Yii::$app->params['queryPerPage'];


        $langList = LanguageType::getLangList();

        $whereArray = [];
        $whereArray["contest_id"] = 0;
        if ($pid = \Yii::$app->request->get('pid'))
            $whereArray['problem_id'] = $pid;
        if ($name = \Yii::$app->request->get('name'))
            $whereArray['user_id'] = User::findByUsername($name)->id;
        if ($lang = \Yii::$app->request->get('lang'))
            $whereArray['language_id'] = $lang;
        if ($result = \Yii::$app->request->get('result'))
            $whereArray['result'] = $result;

        $totalPage = Status::totalPage($whereArray, $pageSize);


        $statuses = Status::getStatuses($id, $pageSize, $whereArray);

        $pageArray = Util::getPaginationArray($id, 8, $totalPage);

        $this->smarty->assign('pid', $pid);
        $this->smarty->assign('name', $name);
        $this->smarty->assign('lang', $lang);
        $this->smarty->assign('result', $result);

        $this->smarty->assign('langList', $langList);
        $this->smarty->assign('webTitle', 'Status');
        $this->smarty->assign('pageArray', $pageArray);
        $this->smarty->assign('totalPage', $totalPage);
        $this->smarty->assign('pageNow', $id);
        $this->smarty->assign('statuses', $statuses);

        return $this->smarty->display('status/status.html');
    }


    public function actionSource($id) {
        $status = Status::findById($id);

        if (!$status)
            throw new NotFoundHttpException("Fucking $id!");

        $isRoot = false;
        if (\Yii::$app->session['user_id']) {
            $isRoot = User::find()
                ->select('is_root')
                ->where(['id' => \Yii::$app->session['user_id']])
                ->one()
                ->is_root;
        }

        if (!$status->is_shared && $status->user_id != \Yii::$app->session['user_id'] && !$isRoot) {
            $msg = '你休想查看！';
            $this->smarty->assign('msg', $msg);
            return $this->smarty->display('common/error.html');
        }

        $source = $status->source;
        $source = str_replace('<', '&lt;', $source);
        $source = str_replace('>', '&gt;', $source);

        $username = User::find()
            ->select('username')
            ->where(['id' => $status->user_id])
            ->one()
            ->username;

        $isPython = $status->language_id == 4 || $status->language_id == 5;


        $pre = ($isPython ? "" : "/**\n") .
                ($isPython ? "#" : '*') . ' this code is made by ' . $username . "\n" .
                ($isPython ? "#" : '*') . ' Problem: '. $status->problem_id . "\n".
                ($isPython ? "#" : '*') . ' Result: '. $status->result . "\n".
                ($isPython ? "#" : '*') . ' Submission Date: '. $status->submit_time . "\n".
                ($isPython ? "#" : '*') . ' Time: '. $status->time_used . "ms\n".
                ($isPython ? "#" : '*') . ' Memory: '. $status->memory_used . "KB\n".
                ($isPython ? "" : '*/') . "\n";

        $source = $pre . $source;

        $lang = '';
        if ($status->language_id == 1 || $status->language_id == 2)
            $lang = 'cpp';
        else if ($status->language_id == 3)
            $lang = 'java';
        else
            $lang = 'python';
        $this->smarty->assign('source', $source);
        $this->smarty->assign('lang', $lang);

        $this->smarty->assign('webTitle', 'Source');
        return $this->smarty->display('status/source.html');
    }

    public function actionCeinfo($id) {
        $status = Status::getCeInfo($id);
        if (!$status)
            throw new NotFoundHttpException("Fucking $id!");

        $ceinfo = $status->ce_info;

        $ceinfo = str_replace('<', '&lt;', $ceinfo);
        $ceinfo = str_replace('>', '&gt;', $ceinfo);

        $this->smarty->assign('ceinfo', $ceinfo);

        $this->smarty->assign('webTitle', 'CE Info');
        return $this->smarty->display('status/ceinfo.html');
    }

    public function actionResult($id) {
        $status = Status::find()->select('result, time_used, memory_used')
                                ->where("id=:id", [":id" => $id])
                                ->one();
        if ($status) {
            $finished = in_array($status->result, ["Restricted Function", "Wrong Answer", "Presentation Error", "Accepted",
                "Output Limit Exceeded", "Runtime Error", "Memory Limit Exceeded", "Time Limit Exceeded", "Compile Error", "Judge Error"]);
            return json_encode([
                "code" => 0,
                "data" => [
                    "result" => $status->result,
                    "timeUsed" => $status->time_used,
                    "memoryUsed" => $status->memory_used,
                    "finished" => $finished,
                ],
            ]);
        } else {
            return json_encode(["code" => 1, "data" => "fucking not exists $id"]);
        }
    }
}