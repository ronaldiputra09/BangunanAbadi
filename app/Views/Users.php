<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<main id="page-top">
    <div class="content-wrapper">
        <!-- Main content -->
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
                <div class="container-fluid">
                    <!-- general form elements -->
                    <div class="box box-primary" style="width:94%;">
                        <div class="box-header with-border">
                        </div>

                        <div class="card card-tasks">
                            <div class="card-body">
                            </div>
                            <!-- /.box -->
                            <div class="container-fluid">
                                <div class="box-body">
                                    <h3 class="box-title"><i aria-hidden="true"></i>Users List</h3>
                                </div>
                                <a type="button" data-toggle="modal" data-target="#add_user" class="btn btn-success mb-3"><i
                                        class="fa fa-plus"></i> Add User</a>
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Tanggal Dibuat</th>
                                                <th>Hak Akses</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <?php foreach ($DataUsers as $data): ?>
                                                    <td><?= $data->Username; ?></td>
                                                    <td><?= $data->CreatedTime; ?></td>
                                                    <td><a type="button" class="btn btn-info" href="<?= base_url('authorization/' . urlencode($data->Username)) ?>" data-user="<?php echo $data->Username; ?>" name="btn_update" style="margin:auto;"><i class="fa fa-pen" aria-hidden="true"></i></a></td>
                                                    <td>
                                                        <a type="button" name="UserID" class="btn btn-danger btn-delete"
                                                            href="<?= base_url('delete_user/' . $data->Username) ?>" data-username="<?php echo $data->Username; ?>"
                                                            id="buttondelete" style="margin:auto;height:20%"><i
                                                                class="fa fa-trash" aria-hidden="true"></i></a>
                                                    </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Modal -->
                                <div class="modal fade" id="add_user" tabindex="-1" aria-labelledby="exampleModalLabel">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Add User</h5>
                                            </div>
                                            <div class="modal-body">
                                                <div class="col-12 grid-margin stretch-card">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <form action="<?php echo base_url('login/proses_register') ?>" method="post" autocomplete="off">
                                                                <div class="row">
                                                                    <div class="col-md-12 pr-1">
                                                                        <div class="form-group">
                                                                            <input type="text" name="Username" placeholder="Username" required class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 pr-1">
                                                                        <div class="form-group">
                                                                            <input type="password" name="Password" id="Password" placeholder="Password" required class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 pr-1">
                                                                        <div class="form-group">
                                                                            <input type="password" name="Password2" id="Password2" placeholder="Ulangi Password" required class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <button type="submit" class="btn btn-primary mr-2">Submit</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <script>
                        var password1 = document.getElementById("Password"),
                            password2 = document.getElementById("Password2");

                        function validatePassword() {
                            if (password1.value != password2.value) {
                                password2.setCustomValidity("Password Tidak Sama");
                            } else {
                                password2.setCustomValidity('');
                            }
                        }

                        password1.onchange = validatePassword;
                        password2.onkeyup = validatePassword;
                    </script>
                    <script>
                        jQuery(document).ready(function($) {
                            $('.btn-delete').on('click', function(e) {
                                e.preventDefault();
                                var getLink = $(this).attr('href');
                                var username = $(this).data('username');

                                Swal.fire({
                                    title: 'Hapus User',
                                    text: 'Yakin ingin menghapus user "' + username + '"?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Ya, hapus!',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = getLink;
                                    }
                                });
                            });
                        });

                        $(function() {
                            $('#example1').DataTable({
                                'paging': true,
                                'lengthChange': true,
                                'searching': true,
                                'info': true,
                                'ordering': false,
                                'autoWidth': false
                            })
                        });
                    </script>

                    <?php if (session()->getFlashdata('pesan_berhasil')) : ?>
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: "<?= session()->getFlashdata('pesan_berhasil') ?>",
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('pesan_gagal')) : ?>
                        <script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: "<?= session()->getFlashdata('pesan_gagal') ?>",
                            });
                        </script>
                    <?php endif; ?>

                    <?= $this->include('Footer'); ?>