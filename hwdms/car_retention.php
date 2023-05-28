<?php
require_once('api/conn.php');
date_default_timezone_set('Asia/Shanghai');


// email地址1 MRA1
$email_receiver1 = array(
//    "二区" => ["niech@bbac.com.cn", "zhaojg@bbac.com.cn", "jiaoj@bbac.com.cn"],
    "二区" => ["zyt15833737572@163.com"],
//    "快修" => ["niech@bbac.com.cn", "xinrzh@bbac.com.cn", "liup@bbac.com.cn"],
    "快修" => ["zyt15833737572@163.com"],
    "MOE" => ["zyt15833737572@163.com"],
//    "MOE" => ["tianl03@bbac.com.cn", "tanf@bbac.com.cn", "zhangp08@bbac.com.cn", "chaihx@bbac.com.cn", "linzhk@bbac.com.cn", "guanwj@bbac.com.cn", "yuzhsh@bbac.com.cn", "xub01@bbac.com.cn", "liyq@bbac.com.cn", "dongbao@bbac.com.cn", "hanjy@bbac.com.cn"],
    null => []
);
// email地址2 MRA2
$email_receiver2 = array(
//    "二区" => ["hanyq@bbac.com.cn", "fengsh01@bbac.com.cn", "jiash01@bbac.com.cn", "gongxch@bbac.com.cn"],
//    "快修" => ["zhouzhch01@bbac.com.cn", "fengsh01@bbac.com.cn", "kongxl@bbac.com.cn", "gongxch@bbac.com.cn"],
//    "MOE" => ["dengteng@bbac.com.cn", "zhangys@bbac.com.cn", "hemzh@bbac.com.cn", "yangsh03@bbac.com.cn", "wanghanrui@bbac.com.cn", "lidd@bbac.com.cn", "zhouy06@bbac.com.cn"],
    null => []
);
// email地址3 MFA
$email_receiver3 = array(
//    "二区" => ["cheng@bbac.com.cn", "gaoyong2@bbac.com.cn"],
//    "快修" => ["liys@bbac.com.cn", "chenzh@bbac.com.cn"],
//    "MOE" => ["liuchang@bbac.com.cn", "shaosh@bbac.com.cn", "zhangling@bbac.com.cn", "tangych@bbac.com.cn", "shengming.du@bbac.com.cn", "lvxg@bbac.com.cn", "chenl03@bbac.com.cn", "songdf@bbac.com.cn", "panchh@bbac.com.cn", "liuy12@bbac.com.cn", "zhangych01@bbac.com.cn"],
    null => []
);
//工厂和Email地址映射
$factory_email = array(
    "MRA1" => $email_receiver1,
    "MRA2" => $email_receiver2,
    "MFA" => $email_receiver3
);


$id = isset($_POST["id"]) ? $_POST["id"] : null;
$factory_name = isset($_POST["factory_name"]) ? $_POST["factory_name"] : null;
$car_type = isset($_POST["car_type"]) ? $_POST["car_type"] : null;
$ecu = isset($_POST["ecu"]) ? $_POST["ecu"] : null;
$dtc = isset($_POST["dtc"]) ? $_POST["dtc"] : null;
$detection_point = isset($_POST["detection_point"]) ? $_POST["detection_point"] : null;
$creator = isset($_POST["creator"]) ? $_POST["creator"] : null;
$region = isset($_POST["region"]) ? $_POST["region"] : null;
$remark = isset($_POST["remark"]) ? $_POST["remark"] : null;

if ($id) {
    // 根据id修改update car_retention表 region = ?, remark = ?, enable = ? WHERE id = ?
    $updateSql = "UPDATE car_retention SET ";
    if ($factory_name) {
        $updateSql .= "factory_name ='" . $factory_name . "'";
    }
    if ($car_type) {
        $updateSql .= ",car_type ='" . $car_type . "'";
    }
    if ($ecu) {
        $updateSql .= ",ecu ='" . $ecu . "'";
    }
    if ($dtc) {
        $updateSql .= ",dtc ='" . $dtc . "'";
    }
    if ($detection_point) {
        $updateSql .= ",detection_point ='" . $detection_point . "'";
    }
    if ($region) {
        $updateSql .= ",region ='" . $region . "'";
    }
    if ($remark) {
        $updateSql .= ",remark ='" . $remark . "'";
    }
    $updateSql .= " where id = " . $id;
    $update_result = mysqli_query($conn, $updateSql);
    if ($update_result) {
        echo json_encode(array("code" => 20000, "message" => "修改成功！"));
    } else {
        echo json_encode(array("code" => -1, "message" => "修改成功！"));
    }
} else {
    // 新增insert car_retention表
    $insert_sql = "insert into car_retention (factory_name, car_type, ecu, dtc, detection_point, creator, created, region, remark, flag, enable) values ('" . $factory_name . "','" . $car_type . "','" . $ecu . "','" . $dtc . "','" . $detection_point . "','" . $creator . "',now(),'" . $region . "','" . $remark . "', true, true);";
    $insert_result = mysqli_query($conn, $insert_sql);
    if ($insert_result) {
        // 新增后发送邮件
        // 标题
        $title = "新增电检即时提醒";
        $content = "<h3>新增电检即时提醒</h3>";
        $content .= "工厂：" . $factory_name . "<br/>";
        $content .= "车型：" . $car_type . "<br/>";
        $content .= "ECU：" . $ecu . "<br/>";
        $content .= "DTC：" . $dtc . "<br/>";
        $content .= "检测点：" . $detection_point . "<br/>";
        $content .= "备注：" . $remark . "<br/>";
        // 获取工厂的email 接收人数组
        $email_receiver = $factory_email[$factory_name];
        if ($email_receiver) {
            // 地区
            if ($region) {
                $region_arr = explode(",", $region);
                $receiver = [];
                for ($l = 0; $l < count($region_arr); $l++) {
                    $receiver = array_merge($receiver, $email_receiver[$region_arr[$l]]);
                }
                include("api/send_e-mail.php");
            }
        }

        echo json_encode(array("code" => 20000, "message" => "修改成功！"));
    } else {
        echo json_encode(array("code" => -1, "message" => "修改成功！"));
    }
}