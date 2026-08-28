<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<div class="container">
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="title">Ganti Password</h5>
        </div>
        <div class="card-body">
          <form class="myForm" enctype="multipart/form-data"
            action="<?php echo base_url('home/proses_new_password'); ?>" method="post">
            <div class="row">
              <div class="col-md-12 pr-1">
                <div class="form-group">
                  <label>USERID</label>
                  <input readonly required type="text" class="form-control" name="Username"
                    value="<?= session()->get('Username'); ?>" placeholder="TransactionNo">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 pr-1">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" class="form-control" name="Password1" required id="Password1">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 pr-1">
                <div class="form-group">
                  <label>New Password</label>
                  <input type="password" class="form-control" name="Password2" id="Password2">
                </div>
              </div>
            </div>
            <div class="button-container">
              <button style="width: 200px;" class="btn btn-success mb-3">Simpan</button>
            </div>
        </div>
        </form>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="title">Ganti Avatar</h5>
        </div>
        <div class="card-body">

          <label for="">Current Avatar</label>
          <div class="row">
            <?php if (is_array($Avatar)) { ?>
              <?php foreach ($Avatar as $dd): ?>
                <div class="col-md-12 pr-1">
                  <?php if ($dd->Avatar == ''): ?>
                    <img style="width: 200px; height: 200px;" src='<?= base_url() ?>/upload/userdefault.jpg'>
                  <?php else: ?>
                    <img style="width: 200px; height: 200px;" src='<?= base_url() ?>/upload/<?= $dd->Avatar; ?>'>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php } else { ?>
            <?php } ?>
          </div>
          <form class="myForm" enctype="multipart/form-data" action="<?php echo base_url('home/avatar'); ?>"
            method="post">
            <div class="row">
              <div class="col-md-12 pr-1">
                <div class="form-group">
                  <input hidden readonly required type="text" class="form-control" name="Username"
                    value="<?= session()->get('Username'); ?>" placeholder="Username">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 pr-1">
                <div class="form-group">
                  <label>Foto</label>
                  <input type="file" required class="form-control" name="Avatar">
                </div>
              </div>
            </div>
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

<script>
  var password1 = document.getElementById("Password1"),
    password2 = document.getElementById("Password2");

  function validatePassword() {
    if (password1.value != password2.value) {
      password2.setCustomValidity("Password Tidak Sama");
    } else {
      password2.setCustomValidity('');
    }
  }

  password1.onchange = validatePassword;
  password2.onkeyup = validatePassword;
</script>


<?php if (session()->getFlashdata('msg_berhasil')) { ?>
  <script>
    Swal.fire("Berhasil!", "Password Berhasil Diubah!", "success");
  </script>
<?php } ?>

<?php if (session()->getFlashdata('msg_berhasil1')) { ?>
  <script>
    Swal.fire("Berhasil!", "Avatar Berhasil Diubah!", "success");
  </script>
<?php } ?>

</html>

<?= $this->include('Footer'); ?>