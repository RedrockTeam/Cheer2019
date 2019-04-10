<?php

namespace Home\Controller;

//use Firebase\JWT\JWT;
use Think\Controller;
use Think\Db;
use Think\Exception;

class IndexController extends Controller
{
    private $collegeMapper = array(
        0 => "测试学院",
        1 => "通信与信息工程学院",
        2 => "计算机科学与技术学院/人工智能学院",
        3 => "自动化学院",
        4 => "先进制造工程学院",
        5 => "光电工程学院/重庆国际半导体学院",
        6 => "软件工程学院",
        7 => "生物信息学院",
        8 => "理学院",
        9 => "经济管理学院/现代邮政学院",
        10 => "传媒艺术学院",
        11 => "外国语学院",
        12 => "国际学院",
        13 => "网络空间安全与信息法学院"
    );
    const REDIRECT_URI = "https://wx.idsbllp.cn/game/Cheer2019/index.php/Home/Index/info";

    const TOKEN_KEY = "cheer2019";

    const ISS = "redrock.team";

    public function index()
    {
        echo "hello";
    }

    public function entrance()
    {
        $url = GET_OPENID_URL . urlencode(self::REDIRECT_URI);
        header("Location:" . $url);
    }

    public function info()
    {
        $data = I("get.");
        $nickname = $data["nickname"];
        $headimg_url = $data["headimgurl"];
        $openid = $data['openid'];

        if (empty($openid))
            $this->error("你好像还没有关注公众号或者打开方式错误哦！");

        $userModel = M("users");
        $isExist = $userModel->where(array("openid" => $openid))->count();

        if ($isExist == 1) {
//            $payload = array(
//                "openid" => $openid,
//                "iss" => self::ISS,
//                "iat" => time(),
//                "nbf" => time() + 3600
//            );
//            $token = JWT::encode($payload, self::TOKEN_KEY);

            cookie("openid", $openid);
//            cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));

            header("Location:" . FRONT_ENTRANCE . "?r=" . rand());
        } else {
            $data = getStuInfoByOpenid($openid);


            $modelData = array(
                "openid" => $openid,
                "nickname" => $nickname,
                "headimg_url" => $headimg_url
            );

            if (is_null($data)) {
                $modelData["stunum"] = "";
                $modelData["name"] = "";
                $modelData["college"] = "";
            } else {
                $modelData["stunum"] = $data->usernumber;
                $modelData["name"] = $data->realname;
                if ($data->collage == "现代邮政学院")
                    $modelData["college"] = 9;
                else if ($data->collage == "重庆国际半导体学院")
                    $modelData["college"] = 5;
                else if ($data->collage == "网络空间安全与信息法学院")
                    $modelData["college"] = 13;
                else {
                    for ($i = 0; $i < count($this->collegeMapper); $i++) {
                        if ($data->collage[0] == $this->collegeMapper[$i][0])
                            $modelData["college"] = $i;
                    }
                }
            }

            $addStatus = $userModel->data($modelData)->add();

            if (!$addStatus)
                returnJson(500);

            $payload = array(
                "openid" => $openid,
                "iss" => self::ISS,
                "iat" => time(),
                "nbf" => time() + 3600
            );

//            $token = JWT::encode($payload, self::TOKEN_KEY);
            cookie("openid", $openid);
//            cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));

            header("Location:" . FRONT_ENTRANCE . "?r=" . rand());
        }
    }

    public function _before_userStatus()
    {
        if (!IS_POST)
            returnJson(405);
    }

    public function userStatus()
    {
        $openid = cookie("openid");
        if (empty($openid))
            returnJson(403, "invalid openid");

        $userModel = M("users");

        $user = $userModel->where(array("openid" => $openid))->find();

        $logModel = M("vote_log");
        $voteRecords = $logModel
            ->where(array(
                "userid" => $user["id"],
                "time" => array("BETWEEN", array(date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")))
            ))->select();

        $collegeModel = M("colleges");
        $collegeData = $collegeModel->select();

        for ($i = 0; $i < count($collegeData); $i++) {
            $collegeData[$i]["isPraise"] = 0;
            for ($j = 0; $j < count($voteRecords); $j++) {
                if ($voteRecords[$j]["voteto"] == $collegeData[$i]["id"])
                    $collegeData[$i]["isPraise"] = 1;
            }
            $collegeData[$i]['id'] = (int)$collegeData[$i]['id'];
            $collegeData[$i]['native_num'] = (int)$collegeData[$i]['native_num'];
            $collegeData[$i]['foreign_num'] = (int)$collegeData[$i]['foreign_num'];
            $collegeData[$i]['in_num'] = (int)$collegeData[$i]['in_num'];
        }

        $data = array(
            "college_status" => $collegeData,
            "surplus_times" => 5 - count($voteRecords),
            "info" => array(
                "college" => (int)$user["college"]
            )
        );
        returnJson(200, "success", $data);
    }

    public function vote()
    {
        $openid = cookie("openid");
        if (empty($openid))
            returnJson(403, "invalid openid");

        $voteTo = (int)I("post.vote_to");
        if (!is_numeric($voteTo) || ($voteTo > 13 && $voteTo < 1))
            returnJson(400, "invalid parameter");

        $logModel = M("vote_log");
        $userModel = M("users");

        $user = $userModel->where(array("openid" => $openid))->find();

        if (empty($user))
            returnJson(403, "invalid openid");

        $voteRecords = $logModel
            ->where(array(
                "userid" => $user["id"],
                "time" => array("BETWEEN", array(date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")))
            ))->select();

        if (count($voteRecords) >= 5)
            returnJson(427, "no enough times to vote");

        for ($i = 0; $i < 5; $i++) {
            if ((int)$voteRecords[$i]["voteto"] == $voteTo)
                returnJson(426, "you have voted to this team");
        }

        try {
            $isInsert = $logModel->data(array(
                "userid" => $user["id"],
                "voteto" => $voteTo,
                "user_college" => $user["college"],
                "time" => date("Y-m-d H:i:s")
            ))->add();

            if ($isInsert)
                returnJson(200);
            else
                returnJson(500);
        } catch (Exception $exception) {
            returnJson(500);
        }
    }

    public function cacheUpdate()
    {
        if (I("get.auth") != "lalala")
            $this->error();

        try {
            M()->query("UPDATE `colleges` 
SET colleges.native_num = ( SELECT COUNT( id ) FROM vote_log WHERE colleges.id = vote_log.voteto AND vote_log.user_college = colleges.id ),
colleges.foreign_num = ( SELECT COUNT( id ) FROM vote_log WHERE colleges.id = vote_log.voteto AND vote_log.user_college != colleges.id )");
            returnJson(200);
        } catch (Exception $exception) {
            returnJson(500);
        }
    }
}