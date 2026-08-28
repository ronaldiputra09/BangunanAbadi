<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Sync System AOL</title>
    <link rel="icon" href="<?php echo base_url(); ?>upload/logo.png">

    <!-- Bootstrap 4 CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css" />
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" />
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url("https://fonts.googleapis.com/css?family=Maven+Pro:400,500,600,700&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Maven Pro", sans-serif;
        }

        body {
            height: 100vh;
            background-color: rgb(19, 2, 171);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-container h1 {
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            height: 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding-left: 15px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .btn-login {
            width: 100%;
            height: 45px;
            border-radius: 8px;
            background-color: rgb(222, 129, 36);
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
             background-color: rgb(208, 104, 0);
        }

        .login-container img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }


        @media (max-width: 500px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <img
            src='<?= base_url() ?>upload/logo.png'>
            <br>
        <form action="<?php echo base_url('login/proses_login') ?>" method="post" autocomplete="off">
            <div class="form-group">
                <input type="text" name="Username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="Password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>

    <!-- SweetAlert Flash Messages -->
    <?php if (session()->getFlashdata('Harus_Login')) { ?>
        <script>
            Swal.fire("Session Expired!", "Anda Harus Login Kembali!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('pesangagal')) { ?>
        <script>
            Swal.fire("Gagal Login!", "Username Atau Password Salah!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('pesangagal1')) { ?>
        <script>
            Swal.fire("Gagal Login!", "Username Sudah Tidak Aktif!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('pesangagal2')) { ?>
        <script>
            Swal.fire("Gagal Login!", "Username Atau Password Salah!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('gagal_regis')) { ?>
        <script>
            Swal.fire("Registrasi Gagal!", "Username Sudah Ada!", "error");
        </script>
    <?php } ?>

    <?php if (session()->getFlashdata('berhasil_regis')) { ?>
        <script>
            Swal.fire("Berhasil Registrasi Akun!", "Silahkan Login!", "success");
        </script>
    <?php } ?>

</body>

</html>