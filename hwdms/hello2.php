<?php
require_once('api/conn.php');
date_default_timezone_set('Asia/Shanghai');
// poi map
$poi_map = array(
//    "SYIB" => range(100, 115),
//    "SYFW" => range(130, 165),
//    "SYWSA" => range(280, 282),
//MRA1 POI
    "MRA1IB" => range(500, 523),
    "MRA1FW" => range(530, 559),
    "MRA1WSA" => range(560, 564),
    "MRA1EV-IB" => range(841, 842),
    "MRA1EV-FW" => range(865, 867),
//MRA2 POI
    "MRA2FW" => array_merge(range(230, 249), range(253,254)),
    "MRA2WSA" => range(565, 568),
//MFA POI
    "MFAIB" => range(600, 619),
    "MFAFW" => array_merge(range(640, 657), range(700,705)),
    "MFAWSA" => range(636, 638)
);
//工厂常驻收件人
//$email_receiver_plant_always = array(
//    "MRA1" => ["tianl03@bbac.com.cn"],
//    "MRA2" => ["dengteng@bbac.com.cn","zhangys@bbac.com.cn","hemzh@bbac.com.cn","yangsh03@bbac.com.cn","wanghanrui@bbac.com.cn","lidd@bbac.com.cn","zhouy06@bbac.com.cn"],
//   "MFA" => [],
//    "SY" => []
//);
// email地址1 MRA1
$email_receiver1 = array(
    "二区" => ["niech@bbac.com.cn","zhaojg@bbac.com.cn","jiaoj@bbac.com.cn"],
    "快修" => ["niech@bbac.com.cn","xinrzh@bbac.com.cn","liup@bbac.com.cn"],
    "MOE" => ["tianl03@bbac.com.cn","tanf@bbac.com.cn","zhangp08@bbac.com.cn","chaihx@bbac.com.cn","linzhk@bbac.com.cn","guanwj@bbac.com.cn","yuzhsh@bbac.com.cn","xub01@bbac.com.cn","liyq@bbac.com.cn","dongbao@bbac.com.cn","hanjy@bbac.com.cn"],
    null => []
);
// email地址2 MRA2
$email_receiver2 = array(
    "二区" => ["hanyq@bbac.com.cn","fengsh01@bbac.com.cn","jiash01@bbac.com.cn","gongxch@bbac.com.cn"],
    "快修" => ["zhouzhch01@bbac.com.cn","fengsh01@bbac.com.cn","kongxl@bbac.com.cn","gongxch@bbac.com.cn"],
    "MOE" => ["dengteng@bbac.com.cn","zhangys@bbac.com.cn","hemzh@bbac.com.cn","yangsh03@bbac.com.cn","wanghanrui@bbac.com.cn","lidd@bbac.com.cn","zhouy06@bbac.com.cn"],
    null => []
);
// email地址3 MFA
$email_receiver3 = array(
    "二区" => ["cheng@bbac.com.cn","gaoyong2@bbac.com.cn"],
    "快修" => ["liys@bbac.com.cn","chenzh@bbac.com.cn"],
    "MOE" => ["liuchang@bbac.com.cn","shaosh@bbac.com.cn","zhangling@bbac.com.cn","tangych@bbac.com.cn","shengming.du@bbac.com.cn","lvxg@bbac.com.cn","chenl03@bbac.com.cn","songdf@bbac.com.cn","panchh@bbac.com.cn","liuy12@bbac.com.cn","zhangych01@bbac.com.cn"],
    null => []
);
//工厂和Email地址映射
$factory_email = array(
    "MRA1"=> $email_receiver1,
    "MRA2"=> $email_receiver2,
    "MFA"=> $email_receiver3
);
# alter table car_retention add enable bit comment '起禁用状态' ;

// 日志
$log_file = fopen("\\\\172.16.18.84\\moe-drive$\\09_Public\\hwdms\\keep_car" . date('Y-m-d') . ".txt", "a");
fwrite($log_file, "\r\n" . date('Y-m-d H:i:s') . ",定时扫描留车开始。\r\n");
// 查询今天数据
$query_res = mysqli_query($conn, "select * from car_retention where enabled = true");
if ($query_res->num_rows == 0) {22
    echo json_encode(array("code" => 20000));
    return;
}
$res = mysqli_fetch_all($query_res, MYSQLI_ASSOC);
fwrite($log_file, "\r\ncar_retention data: " . json_encode($res));
foreach ($res as $data) {
    // 工厂
    $factory = $data['factory_name'];
    //获取对应工厂的Email地址
    $email_receiver = $factory_email[$factory];
    // 车型
    $model = $data['car_type'];
    $id = $data['id'];
    $ecu = $data['ecu'];
    $dtc = $data['dtc'];
    $region = $data['region'];
    $remark = $data['remark'];
    $creator = $data['creator'];
    // 监测点
    $detection_point = $data['detection_point'];
    $detection_point_arr = explode(",", $detection_point);
    fwrite($log_file, "\r\监测点: " . $detection_point . json_encode($detection_point_arr));

    foreach ($detection_point_arr as $point) {
        fwrite($log_file, "\r\循环体监测点: " . $point);
        // 获取poi
        $fac_str = $factory . $point;
        $poi = $poi_map[$fac_str];
        if (!$poi) {
            continue;
        }
        // model取后三位
        $model_suffix = number_format(substr($model, 1, Strlen($model)));
        $aux_query_sql = "select distinct prod, model, poi, errortext, timestamp from `is`.auxnioinfo where errortext is not null and model like '" . $model_suffix . "%' and poi between " . $poi[0] . " and " . $poi[count($poi) - 1] . " and timestamp between unix_timestamp(CONCAT(CURDATE(),' 00:00:00')) and unix_timestamp(now())";
        // 根据poi和model 查询auxnio info
        $aux_query = mysqli_query($conn, $aux_query_sql);
        fwrite($log_file, "\r\naux query sql: " . $aux_query_sql);
        if ($aux_query->num_rows == 0) {
            echo json_encode(array("code" => 20000));
            continue;
        }
        $aux_res = mysqli_fetch_all($aux_query, MYSQLI_ASSOC);
        fwrite($log_file, "\r\naux query data: " . json_encode($aux_res));
        // 如果存在的话拿到errortext，去查询errordictionary根据errortext分开的id和ecu及dtc
        for ($k = 0; $k < count($aux_res); $k++) {
            $aux_data = $aux_res[$k];
            $text = $aux_data['errortext'];
            $prod = $aux_data['prod'];
            $aux_poi = $aux_data['poi'];
            $timestamp = $aux_data['timestamp'];
            $date_time_string = date("Y-m-d H:i:s", $timestamp);
            if (empty($text)) {
                continue;
            }
            $err_dict_sql = "select id, testgroup, ecu, dtc, status, text from `is`.errordictionary where dtc = '" . $dtc . "' and ecu = '" . $ecu . "' and id in (" . $text . ")";
            // 根据这个查询error dictionary所有匹配id的值
            fwrite($log_file, "\r\ndict query data sql: " . $err_dict_sql);
            $dictionary_query = mysqli_query($conn, $err_dict_sql);
            if ($dictionary_query->num_rows != 0) {
                $dictionary_res = mysqli_fetch_all($dictionary_query, MYSQLI_ASSOC);
                fwrite($log_file, "\r\ndict data：" . json_encode($dictionary_res));
                for ($j = 0; $j < count($dictionary_res); $j++) {
                    $dict = $dictionary_res[$j];
                    $dict_ecu = $dict['ecu'];
                    $dict_dtc = $dict['dtc'];
                    // 新增text发送
                    $text = $dict['text'];
                    // 判断是否重复
                    $email_sql = "select * from `is`.car_retention_email where prod = " . $prod . " and model = " . $model_suffix . " and ecu = '" . $dict_ecu . "' and dtc = '" . $dict_dtc . "' limit 1";
                    fwrite($log_file, "\r\nemail exists sql:" . $email_sql);
                    $email_res = mysqli_query($conn, $email_sql);
                    if ($email_res->num_rows != 0) {
                        // 有记录略过 不发
                        continue;
                    }
                    // 查询当天相同的dtc和ecu相关的 表格数据
                    $email_same_sql = "select * from `is`.car_retention_email where created >= CURDATE() and dtc = '" . $dtc . "' and ecu = '" . $ecu . "' order by created desc";

                    $table_html = "";
                    $email_same_res = mysqli_query($conn, $email_same_sql);
                    if ($email_same_res->num_rows != 0) {
                        // 存在相同ecu、dtc
                        $table_html .= "<table border='1'>";
                        $table_html .= "<th>创建时间</th>";
                        $table_html .= "<th>生产号</th>";
                        $table_html .= "<th>监测点</th>";
                        while ($same_row = mysqli_fetch_assoc($email_same_res)) {
                            $table_html .= '<tr>';
                            $table_html .= '<td>' . $same_row['created'] . '</td>';
                            $table_html .= '<td>' . $same_row['prod'] . '</td>';
                            $table_html .= '<td>' . $point . '</td>';
                            $table_html .= '</tr>';
                        }
                        $table_html .= "</table>";
                    }
                    fwrite($log_file, "\r\ntable html:" . $table_html);

                    // 添加新纪录
                    $insertSql = "insert into `is`.car_retention_email (prod, model, ecu, dtc, created) values (" . $prod . "," . $model_suffix . ",'" . $dict_ecu . "','" . $dict_dtc . "', from_unixtime(".$timestamp."))";
//                    $insertSql = `INSERT INTO is.car_retention_email (prod, model, ecu, dtc, created) VALUES ($prod, $model_suffix, '$dict_ecu', '$dict_dtc', FROM_UNIXTIME($timestamp))`;
                    fwrite($log_file, "\r\ninsertSql:" . $insertSql);
                    mysqli_query($conn, $insertSql);
                    // 发送邮件
                    $receiver = [];
                    if ($region) {
                        $region_arr = explode(",", $region);
                        for ($l = 0; $l < count($region_arr); $l++) {
                            $receiver = array_merge($receiver, $email_receiver[$region_arr[$l]]);
                        }
                        //总是发给创建人Email
                        $creator_sql = "select email from users where userID=" . $creator;
                        $creator_res = mysqli_query($conn, $creator_sql);
                        $user_row = mysqli_fetch_array($creator_res, MYSQLI_ASSOC);
                        // 常驻email接收人
                        //$email_receiver_plant_always_arr = $email_receiver_plant_always[$factory];
                        //$creator_email_receiver_arr = $user_row['email'];
                        //$receiver = array_merge((array)$receiver, (array)$user_row['email'], (array)$email_receiver_plant_always[$factory]);
                        $receiver = array_merge((array)$receiver, (array)$user_row['email']);
                        //$receiver = array_merge($receiver, $creator_email_receiver_arr);
                        fwrite($log_file, "\r\nemail merge receiver: " . json_encode($receiver));
                    } else {
                        // 如果region不存在，发给创建者
                        $creator_sql = "select email from users where userID=" . $creator;
                        $creator_res = mysqli_query($conn, $creator_sql);
                        $user_row = mysqli_fetch_array($creator_res, MYSQLI_ASSOC);
                        $receiver[0] = $user_row['email'];
                    }
                    // 标题
                    $title = "MOE 电检即时提醒 ". $dict_ecu ." ". $dict_dtc;
                    // 内容
                    $content = "Prod.: " . $prod ."<br/> Time: " . $date_time_string ."<br/> Model: " . $model_suffix ."<br/> ECU: " . $dict_ecu . "<br/> DTC: " . $dict_dtc . "<br/> Text: " . $text . "<br/> Test Spot: " . $point . "<br/> POI: " . $aux_poi . "<br/> <br/> 备注: " . $remark;
                    $content .= "</br>";
                    $content .= "</br>";
                    $content .= " 当天历史记录 ";
                    $content .= $table_html;
                    fwrite($log_file, "\r\nemail title: " . $title);
                    fwrite($log_file, "\r\nemail receiver: " . json_encode($receiver));
                    fwrite($log_file, "\r\nemail message: " . $content);
                    include("api/send_e-mail.php");
                }

            }
        }
    }

}
echo json_encode(array("code" => 20000));
