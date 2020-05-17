<?php
header("Content-type:text/html;charset=utf-8");
require_once "function_common.php";
ignore_user_abort(true);// 函数设置与客户机断开是否会终止脚本的执行。
ini_set('max_execution_time', 0);//数值 0 表示没有执行时间的限制，你的程序需要跑多久便跑多久
ini_set('display_errors', 'on');//关闭所有错误信息，为ON时为显示所有错误信息。
//接收数据库信息
$data = $_POST["data"];
$jiqiren = $_POST["jiqiren"];
if(!$data){
    echo json_encode(["code"=>"-1", "msg"=>"请输入数据库连接信息"]);
    exit;
}
if(!$jiqiren){
    echo json_encode(["code"=>"-1", "msg"=>"请选择机器人"]);
    exit;
}
$data = json_decode($data, true);
$opts_values = array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8');
// 连接脸猪数据库
$dsn_lianzhu = "mysql:host={$data['baseurl']};dbname={$data['basename']};port={$data['port']}";
try {
    $LianZhuDb = new PDO($dsn_lianzhu, $data['account'], $data['pwd'], $opts_values);
}catch (PDOException $e){
    error_log("数据库连接失败：" . "\n" . $e->getMessage() . "\n", 3, './lianzhu_text.txt');
}
if (!$LianZhuDb){
    echo json_encode(["code"=>"-1", "msg"=>"数据库连接失败"]);
    exit;
}
$ncnkdb = getDatabase($jiqiren);
if (!$ncnkdb){
    echo json_encode(["code"=>"-1", "msg"=>"念初数据信息获取失败"]);
    exit;
}

// 连接念初数据库
$dsn_ncnk = "mysql:host={$ncnkdb['database_host']};dbname={$ncnkdb['database_name']};port={$ncnkdb['database_info']}";
try {
    $NianChuDb = new PDO($dsn_ncnk, $ncnkdb['database_username'], $ncnkdb['database_password'], $opts_values);
}catch (PDOException $e){
    error_log("念初数据库连接失败：" . "\n" . $e->getMessage() . "\n", 3, './lianzhu_text.txt');
}
if (!$NianChuDb){
    echo json_encode(["code"=>"-1", "msg"=>"念初数据库连接失败"]);
    exit;
}
fastcgi_finish_request();
//开始事务
$NianChuDb->beginTransaction();
// 清空会员表
$sql = <<<EOF
truncate member;
EOF;
$result = $NianChuDb->exec($sql);
// 查询会员表
$sql = <<<EOF
select * from fl_member_info;
EOF;
$ret = $LianZhuDb->query($sql);
while ($row = $ret->fetch(PDO::FETCH_ASSOC)) {
    $uid = $row['id'];
    $wxid = $row['username'];
//    $first_replace = str_replace('a', '*', $wxid);
//    $second_replace = str_replace('b', '|', $first_replace);
//    $wxid = '"' . str_replace('i', '-', $second_replace) . '"';
    $nickname = '"' . str_replace('"', "", $row['usernick']) . '"';
    $username = 0;
    $usertype = 1;
    $integral = 0;
    $qdcount = 0;
    $qdlastdt = "''";
    $mmpid = "''";
    $pidname = "''";
    $trjid = $row['inviter_id'];
    $groupid = "''";
    $groupwxid = "''";
    $groupname = "''";
    $balance = '"' . $row['cur_point'] . '"';
    $first_replace = str_replace('a', '*', $balance);
    $second_replace = str_replace('b', '|', $first_replace);
    $balance = str_replace('i', '-', $second_replace);
    $alipayrealname = '"' . $row['alipay_name'] . '"';
    $alipaynum = '"' . $row['alipay_num'] . '"';
    $regtime = '"' . $row['crt_time'] . '"';
    $order_sixbit = "''";
    $jdpid = "''";
    $pddpid = "''";
    $pdd_order_lastbit = "''";
    $order_num = '"' . $row['finish_order'] . '"';
    $smkey = 1;
    $jinjie1 = "''";
    $jinjie2 = "''";
    $jinjie3 = "''";
    $del = 0;
    $order_date = "''";
    $evaluate_num = 0;
    $avatar = "''";
    $sql = <<<EOF
        INSERT INTO member (uid,wxid,nickname,username,usertype,integral,qdcount,
        qdlastdt,mmpid,pidname,trjid,groupid,groupwxid,groupname,balance,alipayrealname,
        alipaynum,regtime,order_sixbit,jdpid,pddpid,pdd_order_lastbit,order_num,smkey,
        jinjie1,jinjie2,jinjie3,del,order_date,evaluate_num,
        avatar)
        VALUES ({$uid},"{$wxid}",{$nickname},{$username}, {$usertype},
        {$integral},{$qdcount},{$qdlastdt},{$mmpid},{$pidname},{$trjid},
        {$groupid},{$groupwxid},{$groupname},{$balance},{$alipayrealname},{$alipaynum},
        {$regtime},{$order_sixbit},{$jdpid},{$pddpid},{$pdd_order_lastbit},{$order_num},
        {$smkey},{$jinjie1},{$jinjie2},{$jinjie3},{$del},{$order_date},{$evaluate_num},
        {$avatar});
EOF;
    $ret_result = $NianChuDb->exec($sql);
    if (!$ret_result) {
        error_log("Member_Error_Info" . "\n" . print_r($sql, 1) . "\n", 3, './lianzhu_text.txt');
        // 回滚数据
        $NianChuDb->rollBack();
        echo json_encode(["code"=>"-1", "msg"=>"会员表写入失败"]);
        exit;
    }
}

// 清空订单表
$sql = <<<EOF
truncate orders;
EOF;
$result = $NianChuDb->exec($sql);

//查询淘宝订单
$sql = <<<EOF
select * from fl_order_alimama;
EOF;
$ret = $LianZhuDb->query($sql);
while ($row = $ret->fetch(PDO::FETCH_ASSOC)) {
    $orderno = '"' . $row['trade_parent_id'] . '"';
    $batch = '"' . $row['trade_id'] . '"';
    $item_id = '"' . $row['num_iid'] . '"';
    $item_title = '"' . str_replace('"', "", $row['item_title']) . '"';
    $item_number = $row['item_num'] ? $row['item_num'] : 0;
    $pay_price = '"' . $row['alipay_total_price'] . '"';
    $price = '"' . $row['price'] . '"';
    $income_rate = $row['income_rate'] * 100;
    $commission = '"' . $row['pub_share_pre_fee'] . '"';
    $site_id = $row['site_id'] ? $row['site_id'] : 0;
    $adzone_id = $row['adzone_id'] ? $row['adzone_id'] : 0;
    $pid = '"' . $adzone_id . "_" . $site_id . '"';
    $site_name = '"' . $row['site_name'] . '"';
    $create_time = '"' . $row['create_time'] . '"';
    $earning_time = '"' . $row['earning_time'] . '"';
    $order_type = '"' . $row['order_type'] . '"';
    $user_id = '"' . $row['db_robotname'] . '"';
    $db_userid = $row['db_userid'];
    $modifytime = '"' . date('Y-m-d H:i:s', time()) . '"';
    switch ($row['tk_status']) {
        case 3://结算
            $tk_status = 1;
            $flstatus = 1;//返利状态
            break;
        case 12:
            $tk_status = 0;
            break;
        case 13:
            $tk_status = 2;
            break;
        case 14:
            $tk_status = 4;
            break;
        default:
            $flstatus = 0;
            break;
    }
    //取微信id
    $wxid_sql = <<<EOF
    select username from fl_member_info where id={$user_id};
EOF;
    $wx_ret = $LianZhuDb->query($wxid_sql);
    $wx_row = $wx_ret->fetch(PDO::FETCH_ASSOC);
    if (isset($wx_row['username'])) {
        $wx_id = '"' . $wx_row['username'] . '"';
    } else {
        $wx_id = "''";
    }
    $sql = <<<EOF
      INSERT INTO orders (orderno,batch,iid,title,amount,price,realprice,
      tkrate,tkcommission,mediaid,adid,mmpid,pidname,createdt,accountdt,ordertype,
      orderstatus,userid,wxid,flstatus,luckdrawTAG,exceltype,modifytime)
      VALUES ({$orderno},{$batch}, {$item_id},{$item_title}, {$item_number},
      {$price},{$pay_price},{$income_rate},{$commission},{$site_id},{$adzone_id},
      {$pid},{$site_name},{$create_time},{$earning_time},{$order_type},{$tk_status},
      {$db_userid},{$wx_id},{$flstatus},0,0,{$modifytime});
EOF;
    $ret_result = $NianChuDb->exec($sql);
    if (!$ret_result) {
        error_log("TaoBao_Error_Info" . "\n" . print_r($sql, 1) . "\n", 3, './lianzhu_text.txt');
        $NianChuDb->rollBack();//回滚数据
        echo json_encode(["code"=>"-1", "msg"=>"淘宝订单写入失败"]);
        exit;
    }
}

//拼多多订单
$sql = <<<EOF
select * from fl_order_pinduoduo;
EOF;
$ret = $LianZhuDb->query($sql);
while ($row = $ret->fetch(PDO::FETCH_ASSOC)) {
    $orderno = '"' . $row['order_sn'] . '"';
    $batch = '"' . $row['order_sn'] . '"';
    $item_id = '"' . $row['goods_id'] . '"';
    $item_title = '"' . $row['goods_name'] . '"';
    $item_number = '"' . $row['goods_quantity'] . '"';
    $price = '"' . $row['goods_price'] . '"';
    $pay_price = '"' . $row['order_amount'] . '"';
    $income_rate = '"' . $row['promotion_rate'] . '"';
    $commission = '"' . $row['promotion_amount'] . '"';
    $site_id = 0;
    $adzone_id = 0;
    $pid = '"' . $row['p_id'] . '"';
    $site_name = "''";
    $create_time = '"' . $row['order_pay_time'] . '"';
    $earning_time = '"' . $row['order_receive_time'] . '"';
    $order_type = '"' . $row['type'] . '"';
    $tk_status = '"' . $row['order_status'] . '"';
    $db_userid = '"' . $row['db_userid'] . '"';
    $user_id = '"' . $row['db_robotname'] . '"';
    switch ($row['order_status']) {
        case -1://未支付
            $tk_status = -5;
            break;
        case 0://已支付
            $tk_status = 6;
            break;
        case 1://已成团
            $tk_status = 7;
            break;
        case 2://确认收货
            $tk_status = 8;
            break;
        case 3://审核成功
            $tk_status = 9;
            break;
        case 4://审核失败
            $tk_status = 10;
            break;
        case 5://已经结算
            $tk_status = 11;
            break;
        case 8://非多多金宝商品
            $tk_status = 12;
            break;
    }
    if ($row['order_status_desc'] == '审核通过' || $row['order_status_desc'] == '已结算' || $row['order_status_desc'] == '确认收货') {
        $flstatus = 1;
    } else {
        $flstatus = 0;
    }
    $modifytime = '"' . date('Y-m-d H:i:s', time()) . '"';
    //取微信id
    $wxid_sql = <<<EOF
    select username from fl_member_info where robot_name={$user_id};
EOF;
    $wx_ret = $LianZhuDb->query($wxid_sql);
    $wx_row = $wx_ret->fetch(PDO::FETCH_ASSOC);
    if (isset($wx_row['username'])) {
        $wx_id = '"' . $wx_row['username'] . '"';
    } else {
        $wx_id = "''";
    }
    $sql = <<<EOF
      INSERT INTO orders (orderno,batch,iid,title,amount,price,realprice,
      tkrate,tkcommission,mediaid,adid,mmpid,pidname,createdt,accountdt,ordertype,
      orderstatus,userid,wxid,flstatus,luckdrawTAG,exceltype,modifytime)
      VALUES ({$orderno},{$batch}, {$item_id},{$item_title}, {$item_number},
      {$price},{$pay_price},{$income_rate},{$commission},{$site_id},{$adzone_id},
      {$pid},{$site_name},{$create_time},{$earning_time},{$order_type},{$tk_status},
      {$db_userid},{$wx_id},{$flstatus},0,5,{$modifytime});
EOF;
    $ret_result = $NianChuDb->exec($sql);
    if (!$ret_result) {
        error_log("PinDuoDuo_Error_Info" . "\n" . print_r($sql, 1) . "\n", 3, './lianzhu_text.txt');
        $NianChuDb->rollBack();//回滚数据
        echo json_encode(["code"=>"-1", "msg"=>"拼多多订单写入失败"]);
        exit;
    }
}
$NianChuDb->commit();
$LianZhuDb = null;
$NianChuDb = null;