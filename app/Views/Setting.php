<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="title">Setting Aplikasi</h5>
                </div>
                <div class="card-body">
                    <form class="myForm" enctype="multipart/form-data"
                        action="<?php echo base_url('home/simpan_settings'); ?>" method="post">
                        <div class="row">
                            <div class="col-md-12 pr-1">
                                <div class="form-group">
                                    <label>USERID</label>
                                    <input readonly required type="text" class="form-control" name="Username"
                                        value="<?= session()->get('Username'); ?>" placeholder="Username">
                                </div>
                            </div>
                        </div>
                        
                        <?php foreach ($DataClient as $dd){ ?>
                        <div class="row">
                            <div class="col-md-12 pr-1">
                                <div class="form-group">
                                    <label>ClientID</label>
                                    <input type="text" value="<?= $dd->ClientID; ?>" class="form-control" name="ClientID" required id="ClientID">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 pr-1">
                                <div class="form-group">
                                    <label>Client Secret</label>
                                    <input type="text" value="<?= $dd->ClientSecret; ?>" class="form-control" name="ClientSecret" id="ClientSecret">
                                </div>
                            </div>
                        </div>
                        <?php }?>
                        <div class="button-container">
                            <button style="width: 200px;" class="btn btn-success mb-3">Simpan</button>
                        </div>
                        
                        
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>


<?php if (session()->getFlashdata('pesan_berhasil')) { ?>
    <script>
        Swal.fire("Berhasil!", "Data Berhasil Disimpan!", "success");
    </script>
<?php } ?>



</html>

<?= $this->include('Footer'); ?>