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

                        <!-- /.box -->
                        <div class="card">
                            <div class="card-body">
                                <div class="container-fluid">
                                    <div class="box-body">
                                        <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>Daftar Pelanggan</h3>
                                    </div>
                                    <form id="formPelanggan" method="post" action="<?php echo base_url('insert_masterPelanggan') ?>">
                                        <?php foreach ($pelanggan as $dd): ?>
                                            <input hidden type="text" class="form-control" name="id_pelanggan[]" value="<?= $dd['id'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="kode_pelanggan[]" value="<?= $dd['kode'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="nama_pelanggan[]" value="<?= $dd['nama'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="kategori_pelanggan[]" value="<?= $dd['kategori'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="kontakperson_pelanggan[]" value="<?= $dd['kontakperson'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="alamat_pelanggan[]" value="<?= $dd['alamat'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="notelp_pelanggan[]" value="<?= $dd['notelp'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="termin_pelanggan[]" value="<?= $dd['termin'] ?? '-' ?>">
                                            <input hidden type="text" name="tanggal[]" class="form-control" value="<?php echo date('d/m/Y') ?>">

                                        <?php endforeach ?>
                                        <div class="button-container">
                                            <button style="width: 200px;" class="btn btn-success mb-3">Insert Pelanggan</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive">
                                        <?php if (session()->getFlashdata('error')): ?>
                                            <div class="alert alert-danger"><?= session()->getFlashdata('error'); ?></div>
                                        <?php endif; ?>

                                        <table id="example1" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Kode</th>
                                                    <th>Nama</th>
                                                    <th>Kategori</th>
                                                    <th>Kontak Person</th>
                                                    <th>Alamat</th>
                                                    <th>Telp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pelanggan as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <?= $item['id'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['kode'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['nama'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['kategori'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['kontakperson'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['alamat'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['notelp'] ?? '-' ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="card">
                            <div class="card-body">
                                <div class="container-fluid">
                                    <div class="box-body">
                                        <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>Daftar Item</h3>
                                    </div>
                                    <form id="formPelanggan" method="post" action="<?php echo base_url('insert_masterItem') ?>">
                                        <?php foreach ($items as $dd): ?>
                                            <input hidden type="text" class="form-control" name="id_item[]" value="<?= $dd['id'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="kode_item[]" value="<?= $dd['kode'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="barcode[]" value="<?= $dd['barcode'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="nama_item[]" value="<?= $dd['nama'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="satuan_item[]" value="<?= $dd['satuan'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="hargabeli[]" value="<?= $dd['hargabeli'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="hargajual1[]" value="<?= $dd['hargajual1'] ?? '-' ?>">
                                            <input hidden type="text" class="form-control" name="namakategori[]" value="<?= $dd['namakategori'] ?? '-' ?>">

                                        <?php endforeach ?>
                                        <div class="button-container">
                                            <button style="width: 200px;" class="btn btn-success mb-3">Insert Item</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive">
                                        <?php if (session()->getFlashdata('error')): ?>
                                            <div class="alert alert-danger"><?= session()->getFlashdata('error'); ?></div>
                                        <?php endif; ?>

                                        <table id="example2" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Kode Item</th>
                                                    <th>Barcode</th>
                                                    <th>Nama</th>
                                                    <th>Satuan</th>
                                                    <th>Harga Beli</th>
                                                    <th>Harga Jual</th>
                                                    <th>Nama Kategori</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <?= $item['id'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['kode'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['barcode'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['nama'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['satuan'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['hargabeli'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['hargajual1'] ?? '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= $item['namakategori'] ?? '-' ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?= $this->include('Footer'); ?>


        <script>
            jQuery(document).ready(function($) {
                $('.btn-delete').on('click', function() {
                    var getLink = $(this).attr('href');
                    swal({
                        title: 'Hapus Data',
                        text: 'Yakin Ingin Menghapus Data ?',
                        html: true,
                        confirmButtonColor: '#d9534f',
                        showCancelButton: true,
                    }, function() {
                        window.location.href = getLink
                    });
                    return false;
                });
            });
            $(function() {
                $('#example1').DataTable({
                    'paging': true,
                    'lengthChange': true,
                    'searching': true,
                    'info': true,
                    'ordering': true,
                    'autoWidth': false
                })
            });
             $(function() {
                $('#example2').DataTable({
                    'paging': true,
                    'lengthChange': true,
                    'searching': true,
                    'info': true,
                    'ordering': true,
                    'autoWidth': false
                })
            });
        </script>