<?php
session_start();

$page_title = "Login - Dashboard SIPBANAR";
include 'navbar/header.php';
?>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow-x: hidden;
    }

    /* Animated Background Elements */
    body::before {
        content: '';
        position: absolute;
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        top: -250px;
        left: -250px;
        animation: float 20s infinite ease-in-out;
        z-index: 0;
    }

    body::after {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        bottom: -200px;
        right: -200px;
        animation: float 15s infinite ease-in-out reverse;
        z-index: 0;
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        33% { transform: translate(30px, -50px) rotate(120deg); }
        66% { transform: translate(-20px, 30px) rotate(240deg); }
    }

    .login-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        z-index: 1;
    }

    .login-box {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 1000px;
        width: 100%;
        display: grid;
        grid-template-columns: 45% 55%;
        backdrop-filter: blur(10px);
    }

    .login-left {
        background: linear-gradient(135deg, #dc3545 0%, #a72332 100%);
        padding: 60px 40px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-left::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        top: -100px;
        right: -100px;
    }

    .login-left::after {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        bottom: -50px;
        left: -50px;
    }

    .logo-wrapper {
        width: 140px;
        height: 140px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        position: relative;
        z-index: 1;
        animation: pulse 3s infinite ease-in-out;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .login-left img {
        width: 90px;
        height: auto;
    }

    .login-left h2 {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 15px;
        line-height: 1.2;
        position: relative;
        z-index: 1;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .login-left p {
        font-size: 1rem;
        opacity: 0.95;
        line-height: 1.7;
        position: relative;
        z-index: 1;
        max-width: 320px;
    }

    .login-right {
        padding: 60px 50px;
        background: white;
    }

    .login-header {
        margin-bottom: 40px;
    }

    .login-header h3 {
        font-size: 2rem;
        color: #1a1a1a;
        margin-bottom: 8px;
        font-weight: 800;
    }

    .login-header p {
        color: #6c757d;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 28px;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: #333;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group label i {
        color: #dc3545;
        font-size: 1rem;
    }

    .input-wrapper {
        position: relative;
    }

    .form-control {
        width: 100%;
        padding: 15px 50px 15px 18px;
        border: 2px solid #e8ecef;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
        background: #f8f9fa;
    }

    .form-control::placeholder {
        color: #adb5bd;
        opacity: 1;
    }

    .form-control:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        background: white;
        color: #1a1a1a;
    }

    .password-toggle {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #9ca3af;
        font-size: 1.1rem;
        transition: color 0.3s;
    }

    .password-toggle:hover {
        color: #dc3545;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 25px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.4s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-danger {
        background: #fee;
        color: #c00;
        border: 1px solid #fcc;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .btn-login {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #dc3545 0%, #a72332 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(220, 53, 69, 0.4);
    }

    .btn-login:active {
        transform: translateY(-1px);
    }

    .forgot-password {
        text-align: center;
        margin-top: 25px;
    }

    .forgot-password a {
        color: #dc3545;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .forgot-password a:hover {
        color: #a72332;
        text-decoration: underline;
    }

    @media (max-width: 900px) {
        .login-box {
            grid-template-columns: 1fr;
            max-width: 500px;
        }

        .login-left {
            padding: 50px 30px;
        }

        .login-left h2 {
            font-size: 1.8rem;
        }

        .login-right {
            padding: 50px 35px;
        }
    }

    @media (max-width: 500px) {
        .login-container {
            padding: 20px 10px;
        }

        .login-right {
            padding: 40px 25px;
        }

        .login-header h3 {
            font-size: 1.6rem;
        }

        .logo-wrapper {
            width: 120px;
            height: 120px;
        }

        .login-left img {
            width: 75px;
        }
    }
</style>

<div class="login-container">
    <div class="login-box">
        <div class="login-left">
            <div class="logo-wrapper">
                <img src="https://bagianpbj.sidoarjokab.go.id/public/uploads/settings/thumbs/1639041632_982896673090d9d74e9c.png" alt="Logo">
            </div>
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
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <div class="input-wrapper">
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
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="input-wrapper">
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