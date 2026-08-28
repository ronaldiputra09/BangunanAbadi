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
                                <h3 class="box-title"><i class="fa fa-table" aria-hidden="true"></i>Item Sync</h3>
                            </div>
                            <form id="formPelanggan" method="post" action="<?php echo base_url('sync_masterpelanggan') ?>">
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
                                    <button style="width: 200px;" class="btn btn-success mb-3">Sync Pelanggan</button>
                                </div>
                            </form>

                            <form id="formPemasok" method="post" action="<?php echo base_url('sync_masterpemasok') ?>">
                                <?php foreach ($pemasok as $dd): ?>
                                    <input hidden type="text" class="form-control" name="id_pemasok[]" value="<?= $dd['id'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="kode_pemasok[]" value="<?= $dd['kode'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="nama_pemasok[]" value="<?= $dd['nama'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="kategori_pemasok[]" value="<?= $dd['kategori'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="kontakperson_pemasok[]" value="<?= $dd['kontakperson'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="alamat_pemasok[]" value="<?= $dd['alamat'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="notelp_pemasok[]" value="<?= $dd['notelp'] ?? '-' ?>">
                                    <input hidden type="text" class="form-control" name="termin_pemasok[]" value="<?= $dd['termin'] ?? '-' ?>">
                                    <input hidden type="text" name="tanggalpemasok[]" class="form-control" value="<?php echo date('d/m/Y') ?>">
                                <?php endforeach ?>
                                <div class="button-container">
                                    <button style="width: 200px;" class="btn btn-success mb-3">Sync pemasok</button>
                                </div>
                            </form>

                             <form id="formItems" method="post" action="<?php echo base_url('sync_masteritem') ?>">
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
                                    <button style="width: 200px;" class="btn btn-success mb-3">Sync Item</button>
                                </div>
                            </form>
                            
                             
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
            async function syncForm(form) {
                if (!form) return;

                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                    });
                    const data = await response.json();
                    console.log('Sinkronisasi sukses:', form.id, data);
                } catch (error) {
                    console.error('Sinkronisasi gagal:', form.id, error);
                }
            }

            async function autoSyncSequential(formSelectors) {
                for (const selector of formSelectors) {
                    const form = document.querySelector(selector);
                    if (!form) continue;
                    await syncForm(form); // tunggu sampai selesai sinkronisasi form ini baru lanjut ke form berikutnya
                }
                console.log('Semua sinkronisasi berurutan selesai. Reload halaman...');
                location.reload();
            }

            // Contoh daftar form id
            const formIds = [
                '#formPelanggan',
                '#formPemasok',
                '#formItems',

            ];

            // Jalankan setiap 5 menit
            setInterval(() => {
                autoSyncSequential(formIds);
            }, 15 * 60 * 1000);
        </script>