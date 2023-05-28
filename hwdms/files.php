<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:*');
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,X-PINGOTHER,Content-Type');

require_once('api/conn.php');
date_default_timezone_set('Asia/Shanghai');

$method = isset($_GET["method"]) ? $_GET["method"] : null;
$ecu = isset($_GET["ecu"]) ? $_GET["ecu"] : null;

//// 文件上传路径，在MOE公共盘下
//$upload_dir = "\\172.16.18.84\moe-drive$\09_Public\hwdms\digital_diagram_file\";
$upload_dir = DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR."172.16.18.84".DIRECTORY_SEPARATOR."moe-drive$".DIRECTORY_SEPARATOR."09_Public".DIRECTORY_SEPARATOR."hwdms".DIRECTORY_SEPARATOR."digital_diagram_file".DIRECTORY_SEPARATOR;

$ecu_desc = array(
    "DCDC48EVO1" => "DCDC48EVO1, 48V车载电器系统直流转换器控制单元, N83/12",
    "ecu2" => "ecu2 的描述",
    "ecu3" => "ecu3 的描述",
    "ecu4" => "ecu4 的描述",
    "ecu5" => "ecu5 的描述"
);

$url_prefix = "http://huawei/hwdms/files.php?method=getFile&id=";

if ($method == "getFile") {
    // 根据id查询数据库获取文件路径读取后返回文件流
    $id = isset($_GET["id"]) ? $_GET["id"] : null;
    // 根据id查询对应文件名
    $query_sql = "select * from files where id = " . $id;
    $mysqli_result = mysqli_query($conn, $query_sql);
    if ($mysqli_result && mysqli_num_rows($mysqli_result) > 0) {
        $row = mysqli_fetch_assoc($mysqli_result);
        $filename = $row["file_name"];
        $extension = $row["extension"];
        $path = $row["path"];
        // 设置响应头，指定内容类型为 application/octet-stream，表示二进制流文件
        // 根据扩展名设置对应的 MIME 类型
        $mime_type = '';
        switch ($extension) {
            case 'pdf':
                $mime_type = 'application/pdf';
                break;
            case 'jpg':
            case 'jpeg':
                $mime_type = 'image/jpeg';
                break;
            case 'png':
                $mime_type = 'image/png';
                break;
            // 添加其他文件类型的处理...
            default:
                // 如果扩展名不在已知的类型中，默认使用通用的二进制流类型
                $mime_type = 'application/octet-stream';
                break;
        }

        // 设置响应头，指定正确的 MIME 类型
        header('Content-Type: ' . $mime_type);        // 设置响应头，指定内容-Disposition为attachment，表示附件下载
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        // 读取文件并输出到输出缓冲区
        readfile($path);
    }
}

if ($method == "upload") {
    if (isset($_FILES["file"])) {
        $file = $_FILES["file"];

        // 获取上传文件的信息
        $file_name = $file["name"];
        $file_tmp = $file["tmp_name"];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        // 拼接完整的文件路径
        $new_file_path = $upload_dir . DIRECTORY_SEPARATOR . $ecu;
        // 创建文件夹（如果不存在）
        if (!is_dir($new_file_path)) {
            mkdir($new_file_path, 0777, true);
        }
        $new_file_path = $new_file_path . DIRECTORY_SEPARATOR . $file_name;

        // 将上传的文件移动到指定路径
        if (move_uploaded_file($file_tmp, $new_file_path)) {
            // 将文件信息插入数据库
            $insert_sql = "INSERT INTO files (id, file_name, extension, ecu, path) VALUES (null, '$file_name', '$file_extension', '" . $ecu . "', '$new_file_path')";
            $result = mysqli_query($conn, $insert_sql);
            if ($result) {
                $inserted_id = mysqli_insert_id($conn);
                // 文件上传和数据库记录添加成功
                $response = array(
                    "code" => 20000,
                    "data" => $url_prefix . $inserted_id, // 返回
                    "message" => "File uploaded and record added successfully."
                );
            } else {
                // 数据库记录添加失败
                $response = array(
                    "code" => -1,
                    "message" => "Failed to add record to the database."
                );
            }
        } else {
            // 文件移动失败
            $response = array(
                "code" => -1,
                "message" => "Failed to move the uploaded file."
            );
        }
    } else {
        // 未找到上传的文件
        $response = array(
            "code" => -1,
            "message" => "No file uploaded."
        );
    }

    // 返回 JSON 响应
    header('Content-Type: application/json');
    echo json_encode($response);
}

if ($method == "list") {
    // 获取要查询的 ECU
    $ecu = isset($_GET['ecu']) ? $_GET['ecu'] : '';

    // 查询数据库
    $query = "SELECT id, title, file_url, ecu FROM ecu_files WHERE ecu = '".$ecu."' AND id IN (SELECT MAX(id) FROM ecu_files WHERE ecu = '".$ecu."'     GROUP BY title )";
    $result = mysqli_query($conn, $query);

    // 构建文件列表
    $fileList = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $title = $row['title'];
        $fileUrl = $row['file_url'];
        if (!isset($fileList[$title])) {
            $fileList[$title] = array();
        }

        // 解析URL获取查询字符串
        $queryString = parse_url($fileUrl, PHP_URL_QUERY);
        // 解析查询字符串并获取参数
        parse_str($queryString, $params);
        // 获取id参数的值
        $id = $params['id'];
        $query_detail_sql = "SELECT extension FROM files WHERE id = ".$id;
        $query_detail_result = mysqli_query($conn, $query_detail_sql);
        $detail = mysqli_fetch_assoc($query_detail_result);
        $extension = $detail["extension"];

        // 将文件信息添加到文件列表
        $fileList[$title]["files"] = array(
            $fileUrl
        );
        // 将文件信息添加到文件列表
        $fileList[$title]["extension"] = $extension;
    }

    // 构建响应数据
    $response = array();
    foreach ($fileList as $title => $files) {
        $response[] = array(
            "title" => $title,
            "files" => $files["files"],
            "extension"=>$files["extension"]
        );
    }

    // 输出响应数据
    $output = array(
        "code" => 20000,
        "data" => array(
            "files" => $response,
            "descriptions" => $ecu_desc[$ecu]
        )
    );

    echo json_encode($output);
}


if ($method == "save") {
    // 获取要保存的文件URL、标题和ECU
    $fileUrls = isset($_POST['file_urls']) ? $_POST['file_urls'] : array();
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $ecu = isset($_POST['ecu']) ? $_POST['ecu'] : '';

    // 执行插入查询
    $insertedIds = array();
    foreach ($fileUrls as $fileUrl) {
        $insertSql = "INSERT INTO ecu_files (file_url, title, ecu) VALUES ('$fileUrl', '$title', '$ecu')";
        $result = mysqli_query($conn, $insertSql);

        // 检查查询结果
        if ($result) {
            $insertedId = mysqli_insert_id($conn);
            $insertedIds[] = $insertedId;
        } else {
            $errorMessage = mysqli_error($conn);

            $response = array(
                "code" => -1,
                "message" => "文件记录保存失败: " . $errorMessage
            );

            // 将响应转换为JSON格式并输出
            echo json_encode($response);
            exit; // 停止执行后续代码
        }
    }

    $response = array(
        "code" => 20000,
        "message" => "文件记录保存成功",
        "inserted_ids" => $insertedIds
    );

    // 将响应转换为JSON格式并输出
    echo json_encode($response);
}
