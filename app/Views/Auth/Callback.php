<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing OAuth Token</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            text-align: center;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .loader {
            border: 6px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 6px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .message {
            font-size: 18px;
            font-weight: bold;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const fragment = new URLSearchParams(window.location.hash.substring(1));
            const accessToken = fragment.get("access_token");

            if (accessToken) {
                fetch("<?= site_url('auth/store_token') ?>", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ access_token: accessToken })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        console.log("Token berhasil disimpan:", data.access_token);
                        window.location.href = "<?= site_url('auth/db-list') ?>";
                    } else {
                        document.querySelector(".message").textContent = "Gagal menyimpan token. Silakan coba lagi.";
                        console.error("Gagal menyimpan token:", data.error);
                    }
                })
                .catch(error => {
                    document.querySelector(".message").textContent = "Terjadi kesalahan. Silakan coba lagi.";
                    console.error("Error:", error);
                });
            } else {
                document.querySelector(".message").textContent = "Access token tidak ditemukan.";
                console.error("Access token tidak ditemukan.");
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="loader"></div>
        <p class="message">Mohon tunggu, sedang memproses akses...</p>
    </div>
</body>
</html>
