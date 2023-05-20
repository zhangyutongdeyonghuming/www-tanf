<?php
require_once('api/conn.php');
date_default_timezone_set('Asia/Shanghai');

$method = isset($_GET["method"]) ? $_GET["method"] : null;
$ecu = isset($_GET["ecu"]) ? $_GET["ecu"] : null;
// 文件上传路径
$upload_dir = "C:\\\\Users\\\\zyt15\\\\temp\\\\";
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
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
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
        $new_file_path = $upload_dir . "\\" . $ecu;
        // 创建文件夹（如果不存在）
        if (!is_dir($new_file_path)) {
            mkdir($new_file_path, 0777, true);
        }
        $new_file_path = $new_file_path . "\\\\" . $file_name;

        // 将上传的文件移动到指定路径
        if (move_uploaded_file($file_tmp, $new_file_path)) {
            // 将文件信息插入数据库
            $insert_sql = "INSERT INTO files (id, file_name, extension, ecu, path) VALUES (null, '$file_name', '$file_extension', '" . $ecu . "', '$new_file_path')";
            $result = mysqli_query($conn, $insert_sql);
            if ($result) {
                // 文件上传和数据库记录添加成功
                $response = array(
                    "code" => 20000,
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

    // 根据 ecu 查询数据库，获取对应的文件列表
    $query_sql = "SELECT * FROM files WHERE ecu = '$ecu'";
    $result = mysqli_query($conn, $query_sql);

    // 将查询结果转换为数组
    $files = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $files[] = $row;
    }

    // 返回 JSON 格式的文件列表
    echo json_encode(
        array(
            "code" => 20000,
            "data" => $files
        )
    );
}
