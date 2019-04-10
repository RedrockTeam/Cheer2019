<?php

namespace Home\Controller;

use Firebase\JWT\JWT;
use Think\Controller;

class IndexController extends Controller
{
    private $collegeMapper = array(
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
            returnJson(403, "openid is not found");

        $userModel = M("users");
        $isExist = $userModel->where(array("openid" => $openid))->count();

        if ($isExist == 1) {
            cookie("openid", $openid);
            header("Location:" . FRONT_ENTRANCE);
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
                $modelData["college"] = $data->collage;
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

//        $token = JWT::encode($payload, self::TOKEN_KEY);
            cookie("openid", $openid);
//        cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));

            header("Location:" . FRONT_ENTRANCE);
        }
    }

    public function userStatus()
    {

    }
}