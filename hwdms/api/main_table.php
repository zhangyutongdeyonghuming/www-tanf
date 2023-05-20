<?php

include("conn.php");

$sql = $_GET['sql'];
$res = [];
if (stristr($sql, "select") != null) {
	// 查询
	$queryRes = mysqli_query($conn, $sql);
	if ($queryRes->num_rows != 0) {
		$res['data'] = mysqli_fetch_all($queryRes, MYSQLI_ASSOC);
	}
} else {
	// 增删改
	mysqli_query($conn, $sql);
}


$res['code'] = 20000;
echo json_encode($res);