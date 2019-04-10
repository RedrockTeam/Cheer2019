<?php

namespace Home\Controller;

use Firebase\JWT\JWT;
use Think\Controller;

class IndexController extends Controller
{
    const REDIRECT_URI = "https://wx.idsbllp.cn/game/Cheer2019/index.php/Home/Index/info";

    const TOKEN_KEY = "cheer2019";

    const ISS = "redrock.team";

    public function index()
    {
        return "hello";
    }

    public function entrance()
    {
        $url = GET_OPENID_URL . urlencode(self::REDIRECT_URI);
        header("Location:" . $url);
    }

    public function info()
    {
        $data = I("get.");

        $openid = $data['openid'];
        if (empty($openid))
            returnJson(403, "openid is not found");

        $data = getStuInfoByOpenid($openid);
        var_dump($data);

        $payload = array(
            "openid" => $openid,
            "iss" => self::ISS,
            "iat" => time(),
            "nbf" => time() + 3600
        );

        $token = JWT::encode($payload, self::TOKEN_KEY);

        cookie("openid", $openid);
        cookie("_t", $token, array('expire' => 3600, 'httponly' => TRUE));

        echo $openid;

        header("Location:" . FRONT_ENTRANCE);
    }

    public function userStatus()
    {

    }
}