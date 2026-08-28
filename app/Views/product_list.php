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
                                <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>Daftar Tagihan
                                    Pajak</h3>
                            </div>
                            <div class="table-responsive">
                                <table id="purchaseOrderTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nomor PO</th>
                                            <th>Tanggal</th>
                                            <th>Supplier</th>
                                            <th>Total Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?= $this->include('Footer'); ?>
        </div>

        <script>
        $(document).ready(function() {
            $('#productTable').DataTable({
                "ajax": "<?= base_url('auth/getProductList') ?>",
                "columns": [
                    { "data": "id" },
                    { "data": "name" },
                    { "data": "no" },
                    { "data": "unitPrice" }
                ]
            });
        });
    </script>
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
        </script>