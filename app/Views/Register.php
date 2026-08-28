<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DRAGON</title>
    <link rel="icon" href="<?php echo base_url(); ?>upload/drg.png">
    <!-- Bootstrap 4 CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css" />
    <!-- Fontawesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <style>
        @import url("https://fonts.googleapis.com/css?family=Maven+Pro:400,500,600,700,800,900&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Maven Pro", sans-serif;
        }

        html,
        body {
            height: 100vh;
            background: url('/upload/autumn.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        #bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .wrapper {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            width: 400px;
            padding: 30px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .login-container h1 {
            color: white;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-container .input-group {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .login-container .input-group i {
            color: white;
            margin-right: 10px;
        }

        .login-container input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            color: white;
            font-size: 16px;
        }

        .login-container input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .login-container .myBtn {
            width: 100%;
            border-radius: 50px;
            font-weight: bold;
            font-size: 18px;
            padding: 12px;
            border: none;
            background-image: linear-gradient(to right, #0acffe 0%, #495aff 100%);
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-container .myBtn:hover {
            background-image: linear-gradient(to right, #495aff 0%, #0acffe 100%);
        }

        .login-container .myHr {
            height: 2px;
            border-radius: 100px;
            background-color: white;
            border: none;
            margin: 20px 0;
        }

        @media (max-width: 500px) {
            .login-container {
                width: 90%;
            }
        }
    </style>


</head>

<body>
    <div class="wrapper">
        <div class="login-container">
            <h1>Register</h1>
            <hr class="myHr">
            <form action="<?php echo base_url('login/proses_register') ?>" method="post" autocomplete="off">
                <div class="input-group">
                    <input type="text" name="Username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <input type="password" name="Password"  id="Password" placeholder="Password" required>
                </div>
                <div class="input-group">
                    <input type="password" name="Password2"   id="Password2" placeholder="Ulangi Password" required>
                </div>
                <button type="submit" class="myBtn">Register</button>
            </form>
            <br>
            <a class="myBtn" href="<?php echo base_url('login') ?>" type="button">Halaman Utama</a>
        </div>
    </div>
    <!-- jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
        $(function() {
            $("#register-link").click(function() {
                $("#login-box").hide();
                $("#register-box").show();
            });
            $("#login-link").click(function() {
                $("#login-box").show();
                $("#register-box").hide();
            });
            $("#forgot-link").click(function() {
                $("#login-box").hide();
                $("#forgot-box").show();
            });
            $("#back-link").click(function() {
                $("#login-box").show();
                $("#forgot-box").hide();
            });
        });
    </script>
</body>

<script>
  var password1 = document.getElementById("Password"),
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

<?php if (session()->getFlashdata('Harus_Login')) { ?>
    <script>
        Swal.fire("Session Expired!", "Anda Harus Login!", "error");
    </script>
<?php } ?>

<?php if (session()->getFlashdata('pesangagal')) { ?>
    <script>
        Swal.fire("Gagal Login!", "Username Atau Password Salah!", "error");
    </script>
<?php } ?>


</html>