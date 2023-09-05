<?php
include_once("../helper/koneksi.php");
include_once("../helper/function.php");


$login = cekSession();
if ($login == 0) {
    redirect("login.php");
}


if (isset($_POST['ambilkontak'])) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'd');
    curl_setopt($ch, CURLOPT_URL, url_wa() . 'getcontact');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    curl_close($ch);
    $k = json_decode($result, true);

    if ($k['status'] == true) {
        $ce = $k['response'][2];
        foreach ($ce as $dd) {
            $data = $dd[1];
            if (array_key_exists('name', $data)) {

                $nama = $data['name'];
                if (strpos($data['jid'], '@g.us') == false) {
                    $type = 'Personal';
                    $number = preg_replace("/\D/", "", $data['jid']);
                } else {
                    $type = 'Group';
                    $number = $data['jid'];
                }
                $cek = mysqli_query($koneksi, "SELECT * FROM contacts WHERE  number = '$number'");
                if ($cek->num_rows > 0) {
                    toastr_set("error", "Kontak dari nomor wa sender tersebut sudah ada di tabase");
                } else {

                    $insert = mysqli_query($koneksi, "INSERT INTO contacts VALUES('','$number','$nama','$type')");
                    toastr_set("success", "Berhasil Ambil Kontak");
                }
            }
        }
    } else {
        $respon = $k['response'];
        toastr_set("error", $respon);
    }
}

if (post("nama")) {
    $nama = post("nama");
    $nomor = post("nomor");
    $pesan = post("pesan");
    $u = $_SESSION['username'];


    $count = countDB("nomor", "nomor", $nomor);

    if ($count == 0) {
        $q = mysqli_query($koneksi, "INSERT INTO nomor(`nama`, `nomor`,`pesan`, `make_by`)
            VALUES('$nama', '$nomor','$pesan', '$u')");
        toastr_set("success", "Sukses input nomor");
    } else {
        toastr_set("error", "Nomor telah ada sebelumnya");
    }
}

if (get("act") == "hapus") {
    $id = get("id");

    $q = mysqli_query($koneksi, "DELETE FROM contacts WHERE id='$id'");
    toastr_set("success", "Sukses hapus nomor");
    redirect("kontak.php");
}

if (get("act") == "delete_all") {
    $q = mysqli_query($koneksi, "DELETE FROM contacts");
    toastr_set("success", "Sukses hapus semua nomor");
    redirect("kontak.php");
}
require_once('../templates/header.php');
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- DataTales Example -->
    <br>
    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary" style="display: contents">Data Kontak</h6>
            <a class="btn btn-danger float-right" href="kontak.php?act=delete_all" style="margin:5px">Hapus Semua</a>
            <a class="btn btn-info float-right" href="../exportgroup.php" style="margin:5px">Export Kontak group </a>
            <a class="btn btn-warning float-right" href="../exportpersonal.php" style="margin:5px">Export Kontak personal </a>
            <form action="" method="POST">
                <!-- <button type="submit" name="ambilkontak" class="btn btn-success " style="margin:5px">
                    Ambil Contact
                </button> -->
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Nomor / ID Group</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($koneksi, "SELECT * FROM contacts");
                        while ($row = mysqli_fetch_assoc($q)) {
                            echo '<tr>';
                            echo '<td>' . $row['id'] . '</td>';
                            echo '<td>' . $row['name'] . '</td>';
                            echo '<td>' . $row['number'] . '</td>';
                            echo '<td>' . $row['type'] . '</td>';

                            echo '<td><a class="btn btn-danger" href="kontak.php?act=hapus&id=' . $row['id'] . '">Hapus</a></td>';
                            echo '</tr>';
                        }

                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<!-- Footer -->
<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright &copy; <a href="https://web.facebook.com/mycodingxd">HambaAllah</a></span>
        </div>
    </div>
</footer>
<!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Tambah Nomor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <label> Nama </label>
                    <input type="text" name="nama" required class="form-control">
                    <br>
                    <label> Nomor Telepon </label>
                    <input type="text" name="nomor" required class="form-control" placeholder="08xxxxxxxx">
                    <br>
                    <label>Pesan </label>
                    <input type="text" name="pesan" required class="form-control" placeholder="pesan">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="import" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Import Nomor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="import_excel.php" method="POST" enctype="multipart/form-data">
                    <label> File (.xlsx) </label>
                    <input type="file" name="file" required class="form-control">
                    <br>
                    <label> Mulai dari Baris ke </label>
                    <input type="text" name="a" required class="form-control" value="2">
                    <br>
                    <label> Kolom Nama ke </label>
                    <input type="text" name="b" required class="form-control" value="1">
                    <br>
                    <label> Kolom Nomor ke </label>
                    <input type="text" name="c" required class="form-control" value="2">
                    <br>
                    <label> Kolom pesan ke </label>
                    <input type="text" name="d" required class="form-control" value="3">
                    <br>
                    <p> Download file contoh <a href="excel/contoh.xlsx" target="_blank">disini</a> </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="../js/demo/datatables-demo.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous"></script>
<script>
    <?php

    toastr_show();

    ?>
</script>
</body>

</html>