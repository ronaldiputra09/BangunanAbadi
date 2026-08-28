<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>


<body class="">
    <div class="main-panel" id="main-panel">
        <div class="panel-header panel-header-sm">
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-tasks">
                                <div class="card-header ">
                                    <h4 class="card-title">Koneksi Database</h4>
                                </div>
                                <div class="card-body">
                                    <form class="forms-sample" id="dateForm" action="<?= base_url('auth/openDatabase') ?>" method="post">
                                        <div class="form-group">
                                            <div class="row">
                                                <input hidden required type="text" value="<?php echo session()->get('Username'); ?>" class="form-control" name="Username" id="Username">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Database</label>
                                                        <select style="height: auto; width:auto" id="db_id" name="db_id" class="form-control">
                                                            <option value="">Pilih Database</option> <!-- Opsi kosong -->
                                                            <?php foreach ($databases as $db): ?>
                                                                <option value="<?= $db['id']; ?>"
                                                                    <?= (session()->get('selected_db') == $db['id']) ? 'selected' : ''; ?>>
                                                                    <?= $db['alias']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" id="submit" class="btn btn-primary">
                                                <h7 style="color:black;">Proses</h7>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>


    <?php if (session()->getFlashdata('berhasil')) { ?>
        <script>
            Swal.fire("Berhasil!", "Berhasil Tersambung KeDatabase!", "success");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('gagal')) { ?>
        <script>
            Swal.fire("GAGAL!", "Gagal Tersambung KeDatabase! Periksa Database Anda!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('pilihdb')) { ?>
        <script>
            Swal.fire("PERINGATAN!", "Pilih Database Terlebih Dahulu!", "warning");
        </script>
    <?php } ?>


    <?= $this->include('Footer'); ?>