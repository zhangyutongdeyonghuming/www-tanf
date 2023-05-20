<?php
require_once('api/conn.php');
date_default_timezone_set('Asia/Shanghai');
header("Content-Type: text/html;charset=utf-8");
// 修改car_retention的enable状态
//$log_file = fopen("\\\\172.16.18.84\\moe-drive$\\09_Public\\hwdms\\update_enable" . date('Y-m-d') . ".txt", "a");

$enable = $_POST['enable'];
$id = $_POST['id'];
$ecu = $_POST['ecu'];
$update_sql = "UPDATE car_retention SET enable = " . $enable . " WHERE id = " . $id . ";";
$update_result = mysqli_query($conn, $update_sql);

// email地址1 MRA1
$email_receiver1 = array(
//    "二区" => ["niech@bbac.com.cn", "zhaojg@bbac.com.cn", "jiaoj@bbac.com.cn"],
    "二区" => ["zyt15833737572@163.com"],
//    "快修" => ["niech@bbac.com.cn", "xinrzh@bbac.com.cn", "liup@bbac.com.cn"],
    "快修" => ["zyt15833737572@163.com"],
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

if ($update_result) {
    // 修改成功发送邮件
    $query = mysqli_query($conn, "select * from car_retention where enable = true and id = " . $id);
    if ($query->num_rows != 0) {
        $row = mysqli_fetch_all($query, MYSQLI_ASSOC)[0];
        // 标题
        $title = "新增电检即时提醒";
        $content = "<h3>新增电检即时提醒</h3>";
        $content .= "工厂：" . $row["factory_name"]."<br/>";
        $content .= "车型：" . $row["car_type"]."<br/>";
        $content .= "ECU：" . $row["ecu"]."<br/>";
        $content .= "DTC：" . $row["dtc"]."<br/>";
        $content .= "检测点：" . $row["detection_point"]."<br/>";
        $content .= "备注：" . $row["remark"]."<br/>";
        // 获取工厂的email 接收人数组
        $email_receiver = $factory_email[$row["factory_name"]];
        // 地区
        if ($row['region']) {
            $region_arr = explode(",", $row['region']);
            $receiver = [];
            for ($l = 0; $l < count($region_arr); $l++) {
                $receiver = array_merge($receiver, $email_receiver[$region_arr[$l]]);
            }
            include("api/send_e-mail.php");
        }

//    fwrite($log_file, "update_enable nemail title: " . $title) . "\r\n";
//    fwrite($log_file, "update_enable nemail receiver: " . json_encode($email_receiver)) . "\r\n";
//    fwrite($log_file, "update_enable nemail message: " . $content) . "\r\n";
    }
    echo json_encode(array("code" => 20000));
} else {
    echo json_encode(array("code" => -1, "error_data" => mysqli_error($conn), "sql" => $update_sql));
}
