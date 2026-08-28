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
                        <div class="container-fluid">
                            <div class="box-body">
                                <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>List Purchase Order</h3>
                            </div>
                            <div class="table-responsive">
                                <?php if (session()->getFlashdata('error')): ?>
                                    <div class="alert alert-danger"><?= session()->getFlashdata('error'); ?></div>
                                <?php endif; ?>

                                <table id="example1" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kode</th>
                                            <th>Barcode</th>
                                            <th>Nama</th>
                                            <th>Satuan</th>
                                            <th>Harga Beli</th>
                                            <th>Harga Jual</th>
                                            <th>Diskon Jual</th>
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
                                                    <?= $item['diskonjual1'] ?? '-' ?>
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
                    'ordering': false,
                    'autoWidth': false
                })
            });
        </script>