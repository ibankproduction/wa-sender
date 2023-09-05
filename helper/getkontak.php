<?php
require('koneksi.php');
require('function.php');
// ------------------------------------------------------------------//
header('content-type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$sender =  preg_replace("/\D/", "", $data['id']);


foreach ($data['data'] as $d) {
    if (array_key_exists('name', $d)) {
        $nama = $d['name'];
        if (strpos($d['jid'], '@g.us') == false) {
            $type = 'Personal';
            $number = preg_replace("/\D/", "", $d['jid']);
        } else {
            $type = 'Group';
            $number = $d['jid'];
        }
        $cek = mysqli_query($koneksi, "SELECT * FROM contacts WHERE number = '$number'");
        if (mysqli_num_rows($cek) < 1) {

            $insert = mysqli_query($koneksi, "INSERT INTO contacts VALUES('','$number','$nama','$type')");
            toastr_set("success", "Berhasil Ambil Kontak");
        }
    }
}
