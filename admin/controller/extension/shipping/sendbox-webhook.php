<?php
header('Content-Type: application/json');
$request = file_get_contents('php://input');

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    echo "You are not authorized to do it";
    die();
}

$request_data = json_decode($request, true);

$get_state = $_GET["state"];
$get_code = $_GET["code"];

$new_url = $get_state . "/code=" . $get_code;
$brand_new_url = str_replace('#', '&', $new_url);

var_dump($request_data);

//you need to pass the url, into this location ,
//header('Location: http://localhost/shop/opencart/upload/admin/index.php?route=extension/shipping/sendbox&user_token=mLAGSXgm1B7C58HD8A1mxklclNAfSsjf&code='.$get_code.'');