<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<style>
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }

    .badge-warning {
        background-color: #ffc107;
        color: black;
    }

    .badge-success {
        background-color: #28a745;
        color: white;
    }

    .badge-info {
        background-color: #17a2b8;
        color: white;
    }

    .badge-primary {
        background-color: #007bff;
        color: white;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }

    .badge-purple {
        background-color: #6f42c1;
        color: white;
    }

    .badge-pink {
        background-color: rgb(214, 51, 228);
        color: white;
    }

    .badge-army {
        background-color: rgb(13, 161, 104);
        color: white;
    }

    .badge-donker {
        background-color: rgb(43, 36, 187);
        color: white;
    }

    .badge-orange {
        background-color: rgb(232, 74, 16);
        color: white;
    }
</style>

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
                                <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>Hasil Import</h3>
                            </div>
                            <div class="table-responsive">
                                <a type="button" id="buttondelete" href="<?php echo base_url('DeleteLog') ?>" style="width: 200px;" class="btn btn-danger mb-3 btn-delete"><i class="fas fa-trash"></i> Delete All Log</a>
                                <table id="example1" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>TransactionNo</th>
                                            <th>Status</th>
                                            <th>Pesan</th>
                                            <th>Jenis Transaksi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($DataLog as $log): ?>
                                            <tr>
                                                <td><?= $log->TransactionNo ?></td>
                                                <td>
                                                    <?php if ($log->Status == 'Gagal'): ?>
                                                        <div class="badge badge-danger">Gagal</div>
                                                    <?php else: ?>
                                                        <div class="badge badge-success">Berhasil</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $log->Message ?></td>
                                                <td>
                                                    <?php if ($log->TransactionType == 'Sales_Order'): ?>
                                                        <div class="badge badge-warning">SALES ORDER</div>
                                                    <?php elseif ($log->TransactionType == 'Delivery_Order'):  ?>
                                                        <div class="badge badge-success">DELIVERY ORDER</div>
                                                    <?php elseif ($log->TransactionType == 'Sales_Invoice'):  ?>
                                                        <div class="badge badge-info">SALES INVOICE</div>
                                                    <?php elseif ($log->TransactionType == 'Sales_Receipt'):  ?>
                                                        <div class="badge badge-primary">SALES RECEIPT</div>
                                                    <?php elseif ($log->TransactionType == 'Sales_Return'):  ?>
                                                        <div class="badge badge-secondary">SALES RETURN</div>
                                                    <?php elseif ($log->TransactionType == 'Purchase_Order'):  ?>
                                                        <div class="badge badge-purple">PURCHASE ORDER</div>
                                                    <?php elseif ($log->TransactionType == 'Receive_Item'):  ?>
                                                        <div class="badge badge-pink">RECEIVE ITEM</div>
                                                    <?php elseif ($log->TransactionType == 'Purchase_Invoice'):  ?>
                                                        <div class="badge badge-army">PURCHASE INVOICE</div>
                                                    <?php elseif ($log->TransactionType == 'Purchase_Return'):  ?>
                                                        <div class="badge badge-donker">PURCHASE RETURN</div>
                                                    <?php elseif ($log->TransactionType == 'Journal_Voucher'):  ?>
                                                        <div class="badge badge-orange">JOURNAL VOUCHER</div>
                                                    <?php elseif ($log->TransactionType == 'Other_Payment'):  ?>
                                                        <div class="badge badge-orange">OTHER PAYMENT</div>
                                                    <?php elseif ($log->TransactionType == 'Other_Deposit'):  ?>
                                                        <div class="badge badge-orange">OTHER DEPOSIT</div>
                                                    <?php elseif ($log->TransactionType == 'Bank_Transfer'):  ?>
                                                        <div class="badge badge-orange">BANK TRANSFER</div>
                                                    <?php elseif ($log->TransactionType == 'Item_Transfer'):  ?>
                                                        <div class="badge badge-orange">ITEM TRANSFER</div>
                                                    <?php else: ?>
                                                        <div class="badge badge-danger">N/A</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $log->Created ?></td>

                                            </tr>
                                        <?php endforeach; ?>
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
            jQuery(document).ready(function($) {
                $('.btn-delete').on('click', function(e) {
                    e.preventDefault();
                    var getLink = $(this).attr('href');
                    var username = $(this).data('username');

                    Swal.fire({
                        title: 'Hapus Log',
                        text: 'Yakin ingin menghapus semua Log?',
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
                    'ordering': true,
                    'autoWidth': false,
                    'pageLength': 50
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