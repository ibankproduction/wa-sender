<?php
include_once("../helper/koneksi.php");
include_once("../helper/function.php");


$login = cekSession();
if ($login == 0) {
    redirect("login.php");
}

if ($_SESSION['level'] != 1) {
    echo "Tidak diizinkan akses halaman ini";
    exit;
}

if (post("username")) {
    $u = post("username");
    $p = sha1(post("password"));
    $l = post("level");

    $count = countDB("account", "username", $u);

    if ($count == 0) {
        $q = mysqli_query($koneksi, "INSERT INTO account(`username`, `password`, `level`)
        VALUES('$u', '$p', '$l')");
        toastr_set("success", "Sukses membuat user");
    } else {
        toastr_set("error", "Username telah terpakai");
    }
}

if (get("act") == "hapus") {
    $id = get("id");

    $q = mysqli_query($koneksi, "DELETE FROM account WHERE id='$id'");
    toastr_set("success", "Sukses hapus user");
}

if (post("chunk")) {
    $chunk = post("chunk");
    $wa = post("webhook");
    $wagr = post("webhookgroup");
    $api_key = post("api_key");
    $nomor = post("nomor");
    mysqli_query($koneksi, "UPDATE pengaturan SET chunk = '$chunk', hook_group = '$wagr', api_key='$api_key', nomor='$nomor' , callback = '$wa' WHERE id='1'");
    toastr_set("success", "Sukses edit pengaturan");
}

if (get("act") == "gapi") {
    $api_key = sha1(date("Y-m-d H:i:s") . rand(100000, 999999));
    mysqli_query($koneksi, "UPDATE pengaturan SET api_key='$api_key' WHERE id='1'");
    toastr_set("success", "Sukses generate api key baru");
}
require_once('../templates/header.php');
?>


<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- DataTales Example -->
    <div class="row">
        <div class="col-md-6">
            <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#exampleModal">
                Tambah User
            </button>
            <br>
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data User</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($koneksi, "SELECT * FROM account");
                                while ($row = mysqli_fetch_assoc($q)) {
                                    echo '<tr>';
                                    echo '<td>' . $row['id'] . '</td>';
                                    echo '<td>' . $row['username'] . '</td>';
                                    if ($row['level'] == 1) {
                                        echo '<td>Admin</td>';
                                    } else {
                                        echo '<td>CS</td>';
                                    }
                                    echo '<td><a class="btn btn-danger" href="pengaturan.php?act=hapus&id=' . $row['id'] . '">Hapus</a></td>';
                                    echo '</tr>';
                                }

                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pengaturan</h6>
                </div>
                <div class="row mb-5">
                    <div class="card shadow offset-1 col-10" style="width: 18rem;">
                        <div id="cardimg">
                        </div>
                        <div class="card-body">
                            <div id="cardimg" class="text-center p-3">

                            </div>
                            <h5 class="card-title"><span class="text-dark">Status :</span>
                                <p class="log"></p>
                            </h5>
                            <div class="text-center">

                                <button id="logout" href="#" class="btn btn-danger mt-6">logout</button>
                                <button id="scanqrr" href="#" class="btn btn-primary mt-6">Scan qr</button>
                                <button id="cekstatus" href="#" class="btn btn-success mt-6">Cek Koneksi</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <hr>
                    <form action="" method="post">
                        <label> Nomor Whatsapp Yang Terkoneksi </label>
                        <input type="text" class="form-control" name="nomor" value="<?= getSingleValDB("pengaturan", "id", "1", "nomor") ?>">
                        <p class="text-muted">*isi nomor yang di scan di atas ( gunakan 62 )</p>
                        <br>
                        <label> Batas Pengiriman per menit </label>
                        <input type="text" class="form-control" name="chunk" value="<?= getSingleValDB("pengaturan", "id", "1", "chunk") ?>">
                        <br>
                        <label> API Key </label>
                        <input type="text" class="form-control" name="api_key" value="<?= getSingleValDB("pengaturan", "id", "1", "api_key") ?>">
                        <br>
                        <label> Webhook URL </label>
                        <input type="text" class="form-control" name="webhook" value="<?= getSingleValDB("pengaturan", "id", "1", "callback") ?>">
                        <p class="text-muted">*isi url webhook (opsional)</p>
                        <br>
                        <!-- <label> Webhook Group URL </label>
                        <input type="hidden" class="form-control" name="webhookgroup" value="<?= getSingleValDB("pengaturan", "id", "1", "hook_group") ?>">
                        <p class="text-muted">*isi url webhook group(opsional)</p>
                        <br> -->
                        <button class="btn btn-success"> Simpan </button>
                        <!-- <a class="btn btn-primary" href="pengaturan.php?act=gapi"> Generate New API Key </a> -->
                    </form>
                </div>
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
                <h5 class="modal-title" id="exampleModalLabel">Tambah User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <label> Username </label>
                    <input type="text" name="username" required class="form-control">
                    <br>
                    <label> Password </label>
                    <input type="password" name="password" required class="form-control">
                    <br>
                    <label for="exampleFormControlSelect1">Level</label>
                    <select class="form-control" id="exampleFormControlSelect1" name="level">
                        <option value="1">Admin</option>
                        <option value="2">CS</option>
                    </select>
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

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/3.1.0/socket.io.js" integrity="sha512-+l9L4lMTFNy3dEglQpprf7jQBhQsQ3/WvOnjaN/+/L4i0jOstgScV0q2TjfvRF4V+ZePMDuZYIQtg5T4MKr+MQ==" crossorigin="anonymous"></script>
<script>
    <?php

    toastr_show();

    ?>
    // ini untuk di hosting
    var socket = io()
    // ini untuk di local
    // var socket = io('http://localhost:3000', {
    //     transports: ['websocket',
    //         'polling',
    //         'flashsocket'
    //     ]
    // });

    socket.emit('ready', 'sdf');
    socket.on('loader', function() {
        $('#cardimg').html(`<img src="loading.gif" class="card-img-top center" alt="cardimg" id="qrcode"  style="height:250px; width:250px;">`);
    })
    socket.on('message', function(msg) {
        $('.log').html(`<li>` + msg + `</li>`);
    })
    socket.on('qr', function(src) {
        $('#cardimg').html(` <img src="` + src + `" class="card-img-top" alt="cardimg" id="qrcode" style="height:250px; width:250px;">`);
    });


    // ketika terhubung
    socket.on('authenticated', function(src) {
        const nomors = src.jid;
        const nomor = nomors.replace(/\D/g, '');
        // console.log(src.imgUrl);
        $('#cardimg').html(` <img src="` + src.imgUrl + `" class="card-img-top" alt="foto profil" id="qrcode" style="height:250px; width:250px;"><br><br>
            <ul>
            <li> Nama : ${src.name}</li>
            <li> Nomor Wa : ${nomor}</li>
            <li> Phone : ${src.phone.device_model}</li>
            <li> WA Versi : ${src.phone.wa_version}</li>
            </ul>
            
            `);
        //  $('#cardimg').html(`<h2 class="text-center text-success mt-4">Whatsapp Connected.<br>` + src + `<h2>`);

    });

    socket.on('profile', function(y) {
        json.parse()
    })

    socket.on('close', function(src) {
        $('#cardimg').html(`<h2 class="text-center text-danger mt-4">` + src + `<h2>`);
    })
    $('#logout').click(function() {
        $('#cardimg').html(`<h2 class="text-center text-dark mt-4">Please wait..<h2>`);
        $('.log').html(`<li>Connecting..</li>`);
        socket.emit('logout', 'delete');
    })

    $('#scanqrr').click(function() {
        socket.emit('scanqr', 'scanqr');
    })
    $('#cekstatus').click(function() {
        socket.emit('cekstatus', 'cekstatus');
    })

    socket.on('isdelete', function(msg) {
        $('#cardimg').html(msg);
    })
</script>
</body>

</html>