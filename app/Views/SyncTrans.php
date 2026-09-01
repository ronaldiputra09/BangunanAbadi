<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<style>
    .lds-dual-ring {
        display: inline-block;
        width: 40px;
        height: 40px;
    }

    .lds-dual-ring:after {
        content: " ";
        display: block;
        width: 32px;
        height: 32px;
        margin: 4px;
        border-radius: 50%;
        border: 4px solid #6c63ff;
        border-color: #6c63ff transparent #6c63ff transparent;
        animation: lds-dual-ring 1.2s linear infinite;
    }

    @keyframes lds-dual-ring {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
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
                                <h3 class="box-title">Transaction Sync by date</h3>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Purchase Order</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="syncPO" method="post" action="<?php echo base_url('sync_PurchaseOrder') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_PO"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_PO"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncPO()" style="width: 200px;" class="btn btn-success mb-3">Sync PO</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Receive Item</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="formPO" method="post" action="<?php echo base_url('sync_ReceiveItem') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_RI"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_RI"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncRI()" style="width: 200px;" class="btn btn-success mb-3">Sync Receive Item</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Purchase Invoice</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="formPO" method="post" action="<?php echo base_url('sync_PurchaseInvoice') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_PI"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_PI"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncPI()" style="width: 200px;" class="btn btn-success mb-3">Sync Purchase Invoice</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Purchase Return</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="formPO" method="post" action="<?php echo base_url('sync_PurchaseReturn') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_purchaseR"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_purchaseR"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncPurchaseReturn()" style="width: 200px;" class="btn btn-success mb-3">Sync Purchase Return</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Sales Invoice</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="formPO" method="post" action="<?php echo base_url('sync_SalesInvoice') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_invoice"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_invoice"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncInvoice()" style="width: 200px;" class="btn btn-success mb-3">Sync Sales Invoice</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Sales Receipt</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_receipt"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date" class="form-control" id="end_date_receipt"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncReceipt()" style="width: 200px;" class="btn btn-success mb-3">Sync Sales Receipt</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Sales Return</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- <form id="formPO" method="post" action="<?php echo base_url('sync_SalesReturn') ?>"> -->
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Transaction Date</label>
                                                        <input required type="date" name="start_date_SalesR" class="form-control" id="start_date_SalesR"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pl-1">
                                                    <div class="form-group">
                                                        <label>Due Date</label>
                                                        <input required type="date" name="end_date_SalesR" class="form-control" id="end_date_SalesR"
                                                            placeholder="Jatuh Tempo">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="ambilDataDanSyncSalesReturn()" style="width: 200px;" class="btn btn-success mb-3">Sync Sales Return</button>
                                            </div>
                                            <!-- </form> -->
                                        </div>
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
                    'ordering': false,
                    'autoWidth': false
                })
            });
        </script>

        <?php
        $successCount = session()->getFlashdata('successCount') ?? 0;
        $failCount = session()->getFlashdata('failCount') ?? 0;
        $showAlert = session()->getFlashdata('showAlert') ?? false;
        ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let showAlert = <?= json_encode($showAlert) ?>;
                let successCount = <?= json_encode($successCount) ?>;
                let failCount = <?= json_encode($failCount) ?>;

                if (showAlert) {
                    Swal.fire({
                        title: "Import Selesai!",
                        text: `✅ Berhasil: ${successCount} Transaksi\n❌ Gagal: ${failCount} Transaksi`,
                        icon: "info",
                        showCancelButton: true,
                        confirmButtonText: "Lihat Detail Log",
                        cancelButtonText: "Tutup"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "<?= base_url('home/log') ?>";
                        }
                    });
                }
            });
        </script>

        <?php if (session()->getFlashdata('berhasil')) { ?>
            <script>
                Swal.fire("BERHASIL!", "Data berhasil diimport dan dikirim ke Accurate API!", "success");
            </script>
        <?php } ?>

        <script>
            function syncAll(transactions, syncUrl, title) {
                let current = 0;
                let total = transactions.length;
                let success = 0;
                let failed = 0;
                let errorMessages = [];

                function escapeHtml(value) {
                    const element = document.createElement('div');
                    element.textContent = String(value);
                    return element.innerHTML;
                }

                Swal.fire({
                    title: `Sinkronisasi ${title} dimulai...`,
                    html: `0 dari ${total} transaksi<br>
               ✅ Berhasil: 0 | ❌ Gagal: 0`,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                function processNext() {
                    if (current >= total) {
                        const errorDetails = errorMessages.length > 0
                            ? `<hr><div class="text-left"><small>${errorMessages.slice(0, 5).map(escapeHtml).join('<br>')}</small></div>`
                            : '';

                        Swal.fire({
                            title: `Sinkronisasi ${title} Selesai!`,
                            html: `✅ Berhasil: <b>${success}</b><br>❌ Gagal: <b>${failed}</b>${errorDetails}`,
                            icon: failed > 0 ? 'warning' : 'success',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    let trx = transactions[current];

                    fetch(syncUrl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "transactionNo=" + encodeURIComponent(trx.transactionNo)
                        })
                        .then(async response => {
                            const responseText = await response.text();
                            let result;

                            try {
                                result = JSON.parse(responseText);
                            } catch (parseError) {
                                throw new Error(
                                    response.ok
                                        ? 'Respons server bukan JSON yang valid'
                                        : `HTTP ${response.status}: server tidak mengembalikan detail error JSON`
                                );
                            }

                            if (!response.ok) {
                                const serverMessage = result.message
                                    || result.error
                                    || result.title
                                    || result.messages?.error;

                                throw new Error(serverMessage || `HTTP ${response.status}`);
                            }

                            return result;
                        })
                        .then(res => {
                            const parsedSuccess = Number(res.berhasil ?? 0);
                            const parsedFailed = Number(res.gagal ?? 0);
                            const responseSuccess = Number.isFinite(parsedSuccess) ? Math.max(0, parsedSuccess) : 0;
                            const responseFailed = Number.isFinite(parsedFailed) ? Math.max(0, parsedFailed) : 0;

                            if (responseSuccess > 0) {
                                success += responseSuccess;
                            }

                            if (responseFailed > 0) {
                                failed += responseFailed;
                            }

                            // Respons lama/error awal tidak selalu membawa counter.
                            // Setiap request yang selesai harus tetap dihitung sebagai berhasil atau gagal.
                            if (responseSuccess === 0 && responseFailed === 0) {
                                if (res.status === 'success') {
                                    success++;
                                } else {
                                    failed++;
                                }
                            }

                            if ((res.status === 'error' || responseFailed > 0) && res.message) {
                                errorMessages.push(`${trx.transactionNo}: ${res.message}`);
                            }
                        })
                        .catch(error => {
                            failed++;
                            errorMessages.push(`${trx.transactionNo}: ${error.message || 'Respons server tidak valid'}`);
                        })
                        .finally(() => {
                            current++;

                            // 🔄 Update realtime progress di SweetAlert
                            Swal.update({
                                html: `${current} dari ${total} transaksi<br>
                       ✅ Berhasil: ${success} | ❌ Gagal: ${failed}`
                            });

                            setTimeout(processNext, 250); // jeda biar server tidak overload
                        });
                }

                processNext();
            }


            function ambilDataDanSyncInvoice() {
                const start = document.getElementById('start_date_invoice').value;
                const end = document.getElementById('end_date_invoice').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Sales Invoice...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_list') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_sales_invoice') ?>", "Sales Invoice");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncReceipt() {
                const start = document.getElementById('start_date_receipt').value;
                const end = document.getElementById('end_date_receipt').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Sales Receipt...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_list2') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_sales_receipt') ?>", "Sales Receipt");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncPO() {
                const start = document.getElementById('start_date_PO').value;
                const end = document.getElementById('end_date_PO').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Purchase Order...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_PO') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_purchase_order') ?>", "Purchase Order");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncRI() {
                const start = document.getElementById('start_date_RI').value;
                const end = document.getElementById('end_date_RI').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Receive Item...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_RI') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_receive_item') ?>", "Receive Item");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncPI() {
                const start = document.getElementById('start_date_PI').value;
                const end = document.getElementById('end_date_PI').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Purchase Invoice...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_PI') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_purchase_invoice') ?>", "Purchase Invoice");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncSalesReturn() {
                const start = document.getElementById('start_date_SalesR').value;
                const end = document.getElementById('end_date_SalesR').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Sales Return...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_SalesReturn') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_sales_return') ?>", "Sales Return");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }

            function ambilDataDanSyncPurchaseReturn() {
                const start = document.getElementById('start_date_purchaseR').value;
                const end = document.getElementById('end_date_purchaseR').value;

                if (!start || !end) {
                    Swal.fire('Tanggal kosong', 'Silakan pilih tanggal mulai dan akhir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Mengambil daftar Purchase Return...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url('get_transaksi_PurchaseReturn') ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            Swal.fire('Kosong', 'Tidak ada transaksi ditemukan.', 'info');
                        } else {
                            syncAll(data, "<?= base_url('sync_one_purchase_return') ?>", "Purchase Return");
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal mengambil data transaksi.', 'error'));
            }
        </script>
