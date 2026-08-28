<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

 


<?php if (session()->getFlashdata('berhasilreset')) { ?>
  <script>
    Swal.fire("Berhasil!", "Koneksi Telah DiReset!", "success");
  </script>
<?php } ?>

<?php if (session()->getFlashdata('msg_berhasil1')) { ?>
  <script>
    Swal.fire("Berhasil!", "Avatar Berhasil Diubah!", "success");
  </script>
<?php } ?>

<?php if (session()->getFlashdata('clientgagal')) { ?>
  <script>
    Swal.fire("PERINGATAN!", "Mohon Periksa ClientID atau ClientID Tidak Ditemukan!", "warning");
  </script>
<?php } ?>

<?= $this->include('Footer'); ?>

