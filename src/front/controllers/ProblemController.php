<?php


namespace app\controllers;


use app\models\Contest;
use app\models\ContestProblem;
use app\models\ContestUser;
use app\models\LanguageType;
use app\models\Problem;
use app\models\Status;
use app\common\Util;
use app\models\User;
use Faker\Provider\Uuid;
use yii\db\Exception;
use yii\db\Query;
use yii\web\NotFoundHttpException;

class ProblemController extends BaseController {
    public function actionList($id = 1) {

        $pageSize = \Yii::$app->params['queryPerPage'];

        $whereArray = ['is_hide' => 0];
        $andWhereArray = [];
        $search = \Yii::$app->request->get('search');
        if ($search) {
            $andWhereArray = ['or',
                ['like', 'title', $search.'%', false],
                ['like', 'title', '%'.$search, false],
                ['like', 'source', $search.'%', false],
                ['like', 'source', '%'.$search, false]];
        }

        $totalPage = Problem::totalPage($pageSize, $whereArray, $andWhereArray);

        $problems = Problem::findByWhere($pageSize, $id, $whereArray, $andWhereArray);

        // TODO use redis to optimization
        $acArray = [];
        if (Util::isLogin()) {
            foreach ($problems as $problem) {
                $acStatus = Status::find()
                    ->select('id')
                    ->where(['problem_id' => $problem->id, 'user_id' => Util::getUser(), 'result' => 'Accepted'])
                    ->one();
                if ($acStatus) {
                    $acArray[] = $problem->id;
                }
            }
        }

        $pageArray = Util::getPaginationArray($id, 6, $totalPage);

        $this->smarty->assign('webTitle', 'Problem List');
        $this->smarty->assign('search', $search);
        $this->smarty->assign('pageArray', $pageArray);
        $this->smarty->assign('totalPage', $totalPage);
        $this->smarty->assign('pageNow', $id);
        $this->smarty->assign('acArray', $acArray);
        $this->smarty->assign('problems', $problems);
        return $this->smarty->display('problem/problem.html');
    }

    public function actionView($id) {
        $problem = Problem::findById($id);
        if (!$problem) {
            throw new NotFoundHttpException("不存在这个题目");
        } else if ($problem->is_hide && !Util::isRoot()) {
            $this->smarty->assign('msg', "你无权查看 $id 这个题目！");
            return $this->smarty->display('common/error.html');
        }
        $this->smarty->assign('vmMultiplier', \Yii::$app->params['vmMultiplier']);
        $this->smarty->assign('problem', $problem);
        $this->smarty->assign('languageTypeList', LanguageType::find()->all());

        $this->smarty->assign('webTitle', "Problem $id");
        return $this->smarty->display('problem/problemView.html');
    }

    public function actionSubmit() {

        if (!isset(\Yii::$app->session['user_id']))
            return json_encode(["code" => 2, "data" => "请先登录"]);

        if (\Yii::$app->request->isPost) {
            try {
                $contestId = \Yii::$app->request->post('contestId');

                if ($contestId != 0) {
                    $contest = Contest::find()->select('start_time, end_time')->where(['id' => $contestId])->one();
                    if ($contest) {
                        if (time() > strtotime($contest->end_time))
                            return json_encode(["code" => 1, "data" => "时间已过，无法提交"]);
                        if (time() < strtotime($contest->start_time))
                            return json_encode(["code" => 1, "data" => "尚未开始，无法提交"]);
                    }
                    // TODO Warn: Not Safe
                    if (!ContestUser::haveUser($contestId, Util::getUser()))
                        ContestUser::addUser($contestId, Util::getUser());
                }

                $problemId = \Yii::$app->request->post('problemId');
                $isHide = false;
                $isHide = Problem::find()->select('is_hide')->where(['id' => $problemId])->one()->is_hide;
                if ($isHide && $contestId == 0 && !Util::isRoot())
                    return json_encode(["code" => 1, "data" => "隐藏题目无法提交"]);

                if (\Yii::$app->params['contestWhiteList'] && !in_array($contestId, \Yii::$app->params['contestWhiteList']))
                    return json_encode(["code" => 1, "data" => "限制比赛提交中"]);

                $status = new Status();
                $status->user_id = \Yii::$app->session['user_id'];
                $status->problem_id = $problemId;
                $status->language_id = \Yii::$app->request->post('languageId');
                $status->source = \Yii::$app->request->post('sourceCode');
                $status->is_shared = intval(\Yii::$app->request->post('isShared'));
                $status->contest_id = $contestId;
                $status->result = "Send to Judge";
                $status->submit_time = date("Y-m-d H:i:s");
                $status->save();

                if ($contestId == 0) {
                    User::addTotalSubmit($status->user_id);
                    Problem::addTotalSubmit($status->problem_id);
                } else {
                    ContestProblem::addTotalSubmit($contestId, $status->problem_id);
                }

                Util::sendRunIdToJudge($status->id);

                return json_encode(["code" => 0, "data" => ["result" => "Send to Judge", "id" => $status->id]]);
            } catch (\Exception $e) {
                return json_encode(["code" => 1, "data" => "出现错误啦"]);
            }

        } else {
            return json_encode(["code" => 1, "data" => "请求方式错误"]);
        }
    }

    public function actionDetail($id) {
        if (!\Yii::$app->request->isGet)
            return json_encode(["code" => 1, "data" => "请求方式错误"]);

        $problem = Problem::findById($id);
        if (!$problem)
            return json_encode(["code" => 1, "data" => "没有 $id 这个题目"]);


        return json_encode(["code" => 0, "data" => [
            "id" => $problem->id,
            "title" => $problem->title,
            "timeLimit" => $problem->time_limit,
            "memoryLimit" => $problem->memory_limit,
            "special" => $problem->is_special_judge,
            "hide" => $problem->is_hide,
            "description" => $problem->description,
            "input" => $problem->input,
            "output" => $problem->output,
            "sampleInput" => $problem->sample_in,
            "sampleOutput" => $problem->sample_out,
            "hint" => $problem->hint,
            "source" => $problem->source,
            "author" => $problem->author,
        ]]);
    }

    public function actionUpdate() {
        if (!\Yii::$app->request->isPost)
            return json_encode(["code" => 1, "data" => "请求方式错误"]);

        if (!Util::isRoot()) {
            $this->smarty->assign('msg', "管不了 管不了.jpg");
            return json_encode(["code" => 1, "data" => "非管理员无法编辑"]);
        }

        $pid = \Yii::$app->request->post('pid');
        $title = \Yii::$app->request->post('title');
        $timeLimit = \Yii::$app->request->post('timeLimit');
        $memoryLimit = \Yii::$app->request->post('memoryLimit');
        $special = \Yii::$app->request->post('special');
        $hide = \Yii::$app->request->post('hide');
        $desc = \Yii::$app->request->post('desc');
        $input = \Yii::$app->request->post('input');
        $output = \Yii::$app->request->post('output');
        $sampleIn = \Yii::$app->request->post('sampleIn');
        $sampleOut = \Yii::$app->request->post('sampleOut');
        $hint = \Yii::$app->request->post('hint');
        $source = \Yii::$app->request->post('source');
        $author = \Yii::$app->request->post('author');

        $problem = Problem::findById($pid);

        $create = true;
        if ($problem) {
            $create = false;
        } else {
            $problem = new Problem();
        }

        $problem->title = $title;
        $problem->time_limit = $timeLimit;
        $problem->memory_limit = $memoryLimit;
        $problem->is_special_judge = $special;
        $problem->is_hide = $hide;
        $problem->description = $desc;
        $problem->input = $input;
        $problem->output = $output;
        $problem->sample_in = $sampleIn;
        $problem->sample_out = $sampleOut;
        $problem->hint = $hint;
        $problem->source = $source;
        $problem->author = $author;

        try {
            $problem->save();
            $pid = $problem->id;

            $data = '';

            if ($create) {
                $data = "创建题目: $pid";
            } else {
                $data = "修改题目: $pid";
            }
            return json_encode(["code" => 0, "data" => $data, "id" => $pid]);
        } catch (Exception $e) {
            return json_encode(["code" => 1, "data" => $e->getMessage()]);
        }
    }

    public function actionStatistic($id = 0) {

        $problem = Problem::findById($id);
        if (!$problem)
            throw new NotFoundHttpException("Fucking problem $id!");

        $pageSize = \Yii::$app->params['queryPerPage'];

        $page = \Yii::$app->request->get('page', 1);

        $totalPage = Status::getLeaderPage($id, $pageSize);
        $leaderList = Status::getLeaderList($id, $pageSize, $page);

        $pageArray = Util::getPaginationArray($page, 8, $totalPage);


        $this->smarty->assign('webTitle', 'Problem Statistic');

        $this->smarty->assign('problemId', $id);
        $this->smarty->assign('problem', $problem);

        $this->smarty->assign('rank', ($page - 1) * $pageSize + 1);
        $this->smarty->assign('pageArray', $pageArray);
        $this->smarty->assign('totalPage', $totalPage);
        $this->smarty->assign('pageNow', $page);
        $this->smarty->assign('leaderList', $leaderList);

        return $this->smarty->display('problem/statistic.html');
    }

    public function actionGetInfo($id) {

        $problem = Problem::find()
            ->select('title, author, is_hide')
            ->where(['id' => $id])
            ->one();

        if ($problem) { // 只有管理员用户才能添加隐藏题目
            if (!$problem->is_hide || ($problem->is_hide && Util::isRoot())) {
                $code = 0;
                $data = ["id" => $id, "title" => $problem->title, "author" => $problem->author, "is_hide" => $problem->is_hide];
            } else {
                $code = 1;
                $data = "非root用户不能访问隐藏题目 $id ！";
            }
        } else {
            $code = 2;
            $data = "没有 $id 这个题目！";
        }

        return json_encode(["code" => $code, "data" => $data]);

    }
}
