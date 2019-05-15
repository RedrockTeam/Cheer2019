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
            cookie("openid", $openid);
            $timestamp = time() + 3600;
            cookie("_e", $timestamp);

            $token = sha1($openid . "redrock@lalala" . $timestamp);

            cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));
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
                $modelData["college_name"] = "无";
            } else {
                $modelData["stunum"] = $data->usernumber;
                $modelData["name"] = $data->realname;
                $modelData["college_name"] = $data->collage;
                switch ($data->collage) {
                    case "现代邮政学院":
                        $modelData["college"] = 9;
                        break;
                    case "重庆国际半导体学院":
                        $modelData["college"] = 5;
                        break;
                    case "网络空间安全与信息法学院":
                        $modelData["college"] = 13;
                        break;
                    case "计算机学院":
                        $modelData["college"] = 2;
                        break;
                    case "光电学院":
                        $modelData["college"] = 5;
                        break;
                    case "生物学院":
                        $modelData["college"] = 7;
                        break;
                    case "安法学院":
                        $modelData["college"] = 13;
                        break;
                    default:
                        for ($i = 0; $i < count($this->collegeMapper); $i++) {
                            if ($data->collage[0] == $this->collegeMapper[$i][0])
                                $modelData["college"] = $i;
                        }
                        break;
                }
            }

            $addStatus = $userModel->data($modelData)->add();

            if (!$addStatus)
                returnJson(500);


            cookie("openid", $openid);
            $timestamp = time() + 3600;
            cookie("_e", $timestamp);

            $token = sha1($openid . "redrock@lalala" . $timestamp);

            cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));

            header("Location:" . FRONT_ENTRANCE . "?r=" . rand());
        }
    }

    public function _before_userStatus()
    {
        if (!IS_POST)
            returnJson(405);
        $openid = cookie("openid");
        if (empty($openid))
            returnJson(403, "invalid openid");

        $expire = cookie("_e");
        if (cookie("_t") != sha1($openid . "redrock@lalala" . $expire))
            returnJson(428, "invalid token or expire");
    }

    public function userStatus()
    {
        $openid = cookie("openid");

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

            $collegeData[$i]["created_at"]=date("Y-m-d H:i:s"); //日期 Y-m-d H:i:s 格式 使用date函数进行格式化
        
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

    //投票前置操作
    public function _before_vote()
    {
        if (!IS_POST)
            returnJson(405);
        $openid = cookie("openid");
        if (empty($openid))
            returnJson(403, "invalid openid");

        $expire = cookie("_e");
        //_t token参数 用于本地存储JWT http only expire in 3600
        if (cookie("_t") != sha1($openid . "redrock@lalala" . $expire))
            returnJson(428, "invalid token or expire");
    }

    //投票操作
    public function vote()
    {
        $openid = cookie("openid");
        //openid验证
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
            $Model = new \Think\Model();
            $Model->execute("UPDATE `colleges` 
SET colleges.native_num = ( SELECT COUNT( id ) FROM vote_log WHERE colleges.id = vote_log.voteto AND vote_log.user_college = colleges.id ),
colleges.foreign_num = ( SELECT COUNT( id ) FROM vote_log WHERE colleges.id = vote_log.voteto AND vote_log.user_college != colleges.id )");
            returnJson(200);
        } catch (Exception $exception) {
            returnJson(500);
        }
    }
}