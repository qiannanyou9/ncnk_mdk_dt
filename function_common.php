<?php
require __DIR__."/vendor/autoload.php";
use Curl\Curl;
/**
 * 获取数据库信息
 * @param $data
 * @return array|bool
 * @throws ErrorException
 */
function getDatabase($data){
    if(!$data){
        return false;
    }
    $database = [];
    $url = "https://cloudmanage.nianchu.net/api/5cd671178e40b";
    $jiqiren_arr = explode("&&", $data[0]);
    $data_arr = ["wx_id"=>$jiqiren_arr[0],"main_id"=>$jiqiren_arr[1]];
    $headers = [];
    $headers[] = ["key"=>"version","value"=>"v3.0"];
    $request = httpsRequest($url, $data_arr, $headers, "get");
    $database = [];
    $res = json_decode($request, true);
    if($res["code"] == "1" && $res["data"]["code"] == "1"){
        $database_info = $res["data"]["data"]["sub_database_info"];
        if($database_info){
            $database = [
                "database_host"=>decryptDatabase($database_info["database_host"])
                ,"database_info"=>$database_info["database_port"]
                ,"database_name"=>$database_info["database_name"]
                ,"database_username"=>$database_info["database_username"]
                ,"database_password"=>$database_info["database_password"]
            ];
        }
    }

    return $database;
}
function decryptDatabase($str){
    $database = $str;
    $database = str_replace("+", "l", $database);
    $database = str_replace("-", "p", $database);
    $database = str_replace("*", "o", $database);
    $database = str_replace("&", "i", $database);
    $database = str_replace("^", "u", $database);
    $base = base64_decode($database);

    return $base;
}

/**
 * 发送http请求
 * @param string $url
 * @param string $type
 * @param array $data
 * @param $header
 * @throws ErrorException
 */
function httpsRequest($url,$data=[],$headers=[],$type="get")
{
    $curl = new Curl();
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false); // 规避SSL验证
    //$curl->setOpt(CURLOPT_SSL_VERIFYHOST, false); // 跳过HOST验证
    // 判断是否设置header
    if ($headers){
        foreach ($headers as $k=>$v){
            $curl->setHeader($v["key"],$v["value"]); // 设置header
        }
    }
    // 判断请求的类型
    switch ($type){
        case "get":
            $curl->get($url,$data);
            break;
        case "post":
            $curl->post($url,$data);
            break;
    }
    // 检测是否请求成功
    if ($curl->error){
        return json_encode(["code"=>$curl->errorCode, "msg"=>$curl->errorMessage]);
    }
    $res = json_decode(json_encode($curl->response), true);
    return json_encode(["code"=>1, "msg"=>"请求成功", "data"=>$res]);
}