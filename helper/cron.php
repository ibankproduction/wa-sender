<?php
include_once("../helper/koneksi.php");
include_once("../helper/function.php");

$count = 0;
$now = strtotime(date("Y-m-d H:i:s"));

$q = mysqli_query($koneksi, "SELECT * FROM pesan WHERE status='MENUNGGU JADWAL' AND tiap_bulan='0' ORDER BY id ASC LIMIT 100");
//var_dump(mysqli_fetch_assoc($q2));
while ($row = mysqli_fetch_assoc($q)) {
    if ($row['media'] == null) {
        $jadwal = strtotime($row['jadwal']);
        if ($jadwal < $now) {

            $nomor = $row['nomor'];
            $pesan = utf8_decode($row['pesan']);
            $send = sendMSG($nomor, $pesan);
            if ($send['status'] == "true") {
                updateStatusMSG($row['id'], "TERKIRIM");
            } else {
                updateStatusMSG($row['id'], "GAGAL");
            }
        } else {
            $media = $row['media'];
            $nomor = $row['nomor'];
            $pesan = $row['pesan'];
            $a = explode('/', $media);
            $filename = $a[count($a) - 1];
            $a2 = explode('.', $filename);
            $namefile = $a2[count($a2) - 2];
            $filetype = $a2[count($a2) - 1];
            $send = sendMedia($nomor, $pesan, $filetype, $namefile, $media);

            if ($send['status'] == "true") {
                updateStatusMSG($row['id'], "TERKIRIM");
            } else {
                updateStatusMSG($row['id'], "GAGAL");
            }
        }
    }
    $count++;
}

var_dump($send);

echo "sukses kirim " . $count . " pesan";
