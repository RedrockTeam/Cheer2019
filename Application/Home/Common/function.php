<?php
const GET_OPENID_URL = "https://wx.idsbllp.cn/MagicLoop/index.php?s=/addon/Api/Api/oauth&redirect=";

const FRONT_ENTRANCE = "";

function returnJson($status, $info = "", $data = array())
{
    switch ($status) {
        case 500:
            $report = array('status' => 500, 'info' => '服务器错误');
            break;
        case 404:
            $report = array('status' => 404, 'info' => 'error parameter');
            break;
        case 403:
            $report = array('status' => 403, 'info' => 'Don\'t permit');
            break;
        case 801:
            $report = array('status' => 801, 'info' => 'invalid parameter');
            break;
        case 200:
            $report = array('status' => 200, 'info' => 'success');
            break;
        case 415:
            $report = array("status" => 415, "info" => "invalid request way");
            break;
        case 'datatable':
            $report = array('draw' => intval($data['draw']), 'recordsFiltered' => intval($data['recordsFiltered']), 'recordsTotal' => intval($data['recordsTotal']), 'data' => $data['data']);
            unset($data);
            break;
        default:
            $report = array('status' => $status);
    }

    if (!empty($info)) {
        $report['info'] = $info;
    }
    if (!empty($data)) {
        $report['data'] = $data;
    }
    header("Content-Type:application/json");
    header('Access-Control-Allow-Origin: *');
    echo json_encode($report);
    exit;
}

const IS_LEGAL_API = "https://wx.idsbllp.cn/MagicLoop/index.php?s=/addon/Api/Api/isOpenidLegal";

function getStuInfoByOpenid($openid)
{
    if (empty($openid))
        returnJson(403, "illegal openid!");

    $ch = curl_init();
    $options = array(
        CURLOPT_URL => "https://wx.idsbllp.cn/MagicLoop/index.php?s=/addon/UserCenter/UserCenter/getStuInfoByOpenId&openId=" . $openid,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
    );
    curl_setopt_array($ch, $options);
    $isLegal = curl_exec($ch);
    $isLegal = json_decode($isLegal);

    if ($isLegal->status != 200)
        returnJson(403, "illegal openid!");

    $ch = curl_init();
    $options = array(
        CURLOPT_URL => "https://wx.idsbllp.cn/MagicLoop/index.php?s=/addon/UserCenter/UserCenter/getStuInfoByOpenId&openId=" . $openid,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $result = json_decode($result);

    if ($result->status == 400)
        return null;


    $post_data = array(
        "stuNum" => $result->data->usernumber,
        "idNum" => $result->data->idnum
    );

    $options = array(
        CURLOPT_URL => "https://wx.idsbllp.cn/api/verify",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS, $post_data,
        CURLOPT_HEADER => 0,
    );

    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $result = json_decode($result);

    if ($result->status == 200)
        return $result->data;
    else
        return null;
}