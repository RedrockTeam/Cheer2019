<?php

namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
    const REDIRECT_URI = "https://wx.idsbllp.cn/game/Cheer2019/index.php/Home/Index/info";

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

        cookie("openid", $openid);
        echo $openid;
//        $this->redirect(FRONT_ENTRANCE);
    }
}