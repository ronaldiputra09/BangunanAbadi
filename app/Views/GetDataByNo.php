<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<style>
    .chip {
        display: inline-flex;
        align-items: center;
        background-color: #007bff;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        margin: 3px;
        font-size: 14px;
        transition: background 0.3s ease;
    }

    .chip .close-btn {
        margin-left: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .chip:hover {
        background-color: #0056b3;
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
                                <h3 class="box-title">Get Data Manually by No</h3>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Sales Invoice & Receipt</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="syncForm" action="<?= base_url('get_InvoicePenjualan_no') ?>" method="POST">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label>Dari Tanggal</label>
                                                        <input type="date" id="start_date" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label>Sampai Tanggal</label>
                                                        <input type="date" id="end_date" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group mt-3">
                                                    <label>Pilih Nomor</label>
                                                    <select multiple id="dataDropdown" class="form-control" style="height: 250px;"></select>
                                                    <div id="chipContainer" class="mt-2"></div>
                                                    <div id="hiddenInputsContainer"></div>
                                                </div>

                                                <button class="btn btn-info" id="submitBtn">Get Data</button>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title">Sales Receipt</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="syncForm1" action="<?= base_url('sync_one_sales_receipt_byNo') ?>" method="POST">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label>Dari Tanggal</label>
                                                        <input type="date" id="start_date1" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label>Sampai Tanggal</label>
                                                        <input type="date" id="end_date1" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Pilih Nomor</label>
                                                    <select multiple id="dataDropdown1" class="form-control" style="height: 250px;"></select>
                                                    <div id="chipContainer1" class="mt-2"></div>
                                                    <div id="hiddenInputsContainer1"></div>
                                                </div>
                                                <button class="btn btn-info" id="submitBtn">Get Data</button>
                                            </form>
                                        </div>
                                    </div>
                                </div> -->
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
                    title: "Singkron Selesai!",
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
        function initChipSelector(suffix, fetchUrl) {
            let selectedTransactions = [];

            function renderChips() {
                $(`#chipContainer${suffix}`).html('');
                $(`#hiddenInputsContainer${suffix}`).html('');
                selectedTransactions.forEach(val => {
                    $(`#chipContainer${suffix}`).append(`
                    <span class="chip">
                        ${val}
                        <span class="close-btn" data-value="${val}">&times;</span>
                    </span>
                `);

                    $(`#hiddenInputsContainer${suffix}`).append(`
                    <input type="hidden" name="transactionNo[]" value="${val}">
                `);
                });
            }

            function fetchJournalNumbers() {
                const start = $(`#start_date${suffix}`).val();
                const end = $(`#end_date${suffix}`).val();
                if (!start || !end) return;

                $.ajax({
                    url: fetchUrl,
                    method: 'POST',
                    data: {
                        start_date: start,
                        end_date: end
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'ok') {
                            const $dropdown = $(`#dataDropdown${suffix}`);
                            $dropdown.empty();
                            Object.values(response.data).forEach(item => {
                                $dropdown.append(`<option value="${item.number}">${item.number}</option>`);
                            });
                        }
                    }
                });
            }

            $(() => {
                $(`#start_date${suffix}, #end_date${suffix}`).on('change', fetchJournalNumbers);

                $(`#dataDropdown${suffix}`).on("change", function() {
                    let selectedOptions = $(this).val();
                    selectedOptions?.forEach(val => {
                        if (!selectedTransactions.includes(val)) {
                            selectedTransactions.push(val);
                            $(this).find(`option[value="${val}"]`).remove();
                        }
                    });
                    renderChips();
                    $(this).val(""); // Reset
                });

                $(`#chipContainer${suffix}`).on('click', '.close-btn', function() {
                    const val = $(this).data('value');
                    selectedTransactions = selectedTransactions.filter(item => item !== val);
                    $(`#dataDropdown${suffix}`).append(`<option value="${val}">${val}</option>`);
                    renderChips();
                });
            });
        }

        // ✅ Panggil fungsi untuk setiap jenis transaksi:
        initChipSelector('', '<?= base_url("get_salesInvoice_no") ?>'); // Untuk transaksi pertama (tanpa suffix)
        initChipSelector('1', '<?= base_url("get_transaksi_list3") ?>'); // Untuk transaksi kedua

        $('form[id^="syncForm"]').on('submit', function(e) {
            e.preventDefault(); // Mencegah reload

            const $form = $(this);
            const formData = $form.serialize();
            const $submitBtn = $form.find('.submitBtn'); // tombol di dalam form ini

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).text('Mengirim...');

                    // SweetAlert loading
                    Swal.fire({
                        title: 'Memproses...',
                        html: 'Sinkronisasi sedang berjalan, mohon tunggu...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if (response.status === 'success' || response.status === 'partial') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sinkronisasi Selesai!',
                            html: `✅ <strong>${response.berhasil}</strong> berhasil<br>❌ <strong>${response.gagal}</strong> gagal`,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message || 'Terjadi kesalahan saat sinkronisasi.'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.statusText || 'Terjadi kesalahan pada permintaan.'
                    });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Sync Sales Invoice');
                }
            });
        });
    </script>