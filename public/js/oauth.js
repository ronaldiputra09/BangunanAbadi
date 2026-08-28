window.onload = function() {
    // Ambil hash fragment dari URL
    const hash = window.location.hash.substr(1);
    const params = new URLSearchParams(hash);
    
    if (params.has("access_token")) {
        const accessToken = params.get("access_token");

        // Kirim access_token ke backend
        fetch("/auth/store-token", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ access_token: accessToken })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Token saved:", data);
            // Redirect ke halaman dashboard atau home
            window.location.href = "/dashboard";
        })
        .catch(error => console.error("Error saving token:", error));
    }
};
