<?php
session_start();


$page_title = "Login - Dashboard SIPBANAR";
include 'navbar/header.php';
?>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', 'Roboto', sans-serif;
        background: white;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .login-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .login-box {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 900px;
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .login-left {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        padding: 60px 40px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .login-left img {
        width: 120px;
        height: auto;
        margin-bottom: 30px;
        filter: brightness(0) invert(1);
    }

    .login-left h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .login-left p {
        font-size: 0.95rem;
        opacity: 0.9;
        line-height: 1.6;
    }

    .login-right {
        padding: 60px 40px;
    }

    .login-header {
        margin-bottom: 40px;
    }

    .login-header h3 {
        font-size: 1.8rem;
        color: #1a1a1a;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .login-header p {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 1.1rem;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
        font-size: 1.1rem;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .forgot-password {
        text-align: center;
        margin-top: 20px;
    }

    .forgot-password a {
        color: #dc3545;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .forgot-password a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .login-box {
            grid-template-columns: 1fr;
        }

        .login-left {
            padding: 40px 30px;
        }

        .login-left h2 {
            font-size: 1.5rem;
        }

        .login-right {
            padding: 40px 30px;
        }
    }
</style>

<div class="login-container">
    <div class="login-box">
        <div class="login-left">
            <img src="https://bagianpbj.sidoarjokab.go.id/public/uploads/settings/thumbs/1639041632_982896673090d9d74e9c.png" alt="Logo">
            <h2>Dashboard SIPBANAR</h2>
            <p>Sistem Informasi Pengadaan Banjarmasin - Transparansi Data untuk Pembangunan Kota yang Lebih Baik</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h3>Selamat Datang!</h3>
                <p>Silakan masuk ke akun Anda</p>
            </div>

            <div id="alertMessage"></div>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Masukkan username" 
                               required 
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Masukkan password" 
                               required 
                               autocomplete="current-password">
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>

                <div class="forgot-password">
                    <a href="forgot_password.php">Lupa Password?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    // Form Submit
    const loginForm = document.getElementById('loginForm');
    const alertMessage = document.getElementById('alertMessage');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(loginForm);

        try {
            const response = await fetch('api/login_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alertMessage.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> ${result.message}
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = result.redirect || 'index.php';
                }, 1000);
            } else {
                alertMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> ${result.message}
                    </div>
                `;
            }
        } catch (error) {
            alertMessage.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Terjadi kesalahan. Silakan coba lagi.
                </div>
            `;
        }
    });
});
</script>

<?php include 'navbar/footer.php'; ?>