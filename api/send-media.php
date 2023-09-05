<?php

include_once("../helper/koneksi.php");
include_once("../helper/function.php");


// Takes raw data from the request
$data = json_decode(file_get_contents('php://input'), true);

$nomor = $data['number'];
$caption = $data['message'];
$api_key = $data['api_key'];
$url = $data['url'];
header('Content-Type: application/json');


if (!isset($nomor)  || !isset($api_key)  || !isset($url)) {
    $ret['status'] = false;
    $ret['msg'] = "Parameter salah!";
    echo json_encode($ret, true);
    exit;
}
if ($api_key != api_key()) {
    $ret['status'] = false;
    $ret['msg'] = "Api key salah";
    echo json_encode($ret, true);
    exit;
}

$a = explode('/', $url);
$filename = $a[count($a) - 1];
$a2 = explode('.', $filename);
$namefile = $a2[count($a2) - 2];
$ext = $a2[count($a2) - 1];

if ($ext != 'jpg' && $ext != 'pdf' && $ext != 'jpeg' && $ext != 'png') {
    $ret['status'] = false;
    $ret['msg'] = "Hanya support jpg,jpeg,png dan pdf";
    echo json_encode($ret, true);
    exit;
}


$res = sendMedia($nomor, $caption, $ext, $namefile, $url);
if ($res['status'] == "true") {
    $ret['status'] = true;
    $ret['msg'] = "Pesan berhasil dikirim";
    echo json_encode($ret, true);
    exit;
} else {
    $ret['status'] = false;
    $ret['msg'] = $res['message'];
    echo json_encode($ret, true);
    exit;
}
