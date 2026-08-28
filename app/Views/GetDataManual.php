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
                                <h3 class="box-title">Get Data Manually by date</h3>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Purchase Order</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" id="start_date_po" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Sampai Tanggal</label>
                                                        <input required type="date" name="start_date2" id="start_date_po2" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncPembelian()" style="width: 200px;" class="btn btn-success mb-3">Get Purchase Order</button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Receive Item</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" id="start_date_pb" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Sampai Tanggal</label>
                                                        <input required type="date" name="start_date2" id="start_date_pb2" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncPenerimaan()" style="width: 200px;" class="btn btn-success mb-3">Get Receive Item</button>
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
                                            <h5 class="title">Purchase Invoice</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" id="start_date_ip" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Sampai Tanggal</label>
                                                        <input required type="date" name="start_date2" id="start_date_ip2" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="button-container">
                                                <button onclick="syncInvoicePembelian()" style="width: 200px;" class="btn btn-success mb-3">Get Purchase Invoice</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Purchase Return</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" id="start_date_pr" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Sampai Tanggal</label>
                                                        <input required type="date" name="start_date2" id="start_date_pr2" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncReturPembelian()" style="width: 200px;" class="btn btn-success mb-3">Get Purchase Return</button>
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
                                            <h5 class="title">Sales Invoice</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" id="start_date_si" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Sampai Tanggal</label>
                                                        <input required type="date" name="start_date2" id="start_date_si2" class="form-control"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncInvoicePenjualan()" style="width: 200px;" class="btn btn-success mb-3">Get Sales Invoice</button>
                                            </div>
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
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_pay1"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date2" class="form-control" id="start_date_pay2"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>  
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncSalesReceipt()" style="width: 200px;" class="btn btn-success mb-3">Get Sales Receipt</button>
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
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date" class="form-control" id="start_date_sr"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Dari Tanggal</label>
                                                        <input required type="date" name="start_date2" class="form-control" id="start_date_sr2"
                                                            placeholder="Tanggal">
                                                    </div>
                                                </div>  
                                            </div>
                                            <div class="button-container">
                                                <button onclick="syncSalesReturn()" style="width: 200px;" class="btn btn-success mb-3">Get Sales Return</button>
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
    </div>
    <?= $this->include('Footer'); ?>


    <script>
        function syncData(apiUrl, judul, inputId1, inputId2) {
            const tanggal = document.getElementById(inputId1).value;
            const tanggal2 = document.getElementById(inputId2).value;
            if (!tanggal) {
                Swal.fire('Tanggal kosong', 'Silakan isi tanggal terlebih dahulu.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Memuat data...',
                text: `Mengambil data ${judul}. Mohon tunggu...`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(apiUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "start_date=" + encodeURIComponent(tanggal) + "&start_date2=" + encodeURIComponent(tanggal2)
                })
                .then(response => response.json())
                .then(res => {
                    Swal.fire({
                        title: `${judul} Selesai!`,
                        html: `Transaksi berhasil: <b>${res.berhasil}</b><br>Transaksi gagal: <b>${res.gagal}</b>`,
                        icon: res.gagal > 0 ? 'warning' : 'success'
                    });
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal memproses permintaan.', 'error');
                    console.error(err);
                });
        }

        // Fungsi pemanggil spesifik
        function syncPembelian() {
            syncData("<?= base_url('get_pembelian') ?>", "Purchase Order", "start_date_po", "start_date_po2");
        }

        function syncPenerimaan() {
            syncData("<?= base_url('get_penerimaanBarang') ?>", "Receive Item", "start_date_pb", "start_date_pb2");
        }

        function syncInvoicePembelian() {
            syncData("<?= base_url('get_invoicePembelian') ?>", "Purchase Invoice", "start_date_ip", "start_date_ip2");
        }

        function syncReturPembelian() {
            syncData("<?= base_url('get_returPembelian') ?>", "Purchase Retur", "start_date_pr","start_date_pr2");
        }

        function syncInvoicePenjualan() {
            syncData("<?= base_url('get_InvoicePenjualan') ?>", "Sales Invoice", "start_date_si","start_date_si2");
        }

        function syncSalesReturn() {
            syncData("<?= base_url('get_ReturPenjualan') ?>", "Sales Return", "start_date_sr","start_date_sr2");
        }

         function syncSalesReceipt() {
            syncData("<?= base_url('get_Payment') ?>", "Sales Receipt", "start_date_pay1","start_date_pay2");
        }
    </script>