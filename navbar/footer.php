<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer SIP BANAR - Dengan Shapes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: #f8f9fa;
        }

        /* Footer Styles dengan Background Sasirangan */
        .main-footer {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.88) 0%, rgba(52, 73, 94, 0.88) 100%),
                url('/images/sasirangan.webp') center center;
            background-size: cover;
            background-attachment: scroll;
            background-repeat: no-repeat;
            color: white;
            padding: 50px 0 20px 0;
            margin-top: 50px;
            position: relative;
            overflow: hidden;
        }

        .main-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
            z-index: 3;
        }

        .footer-section {
            margin-bottom: 30px;
        }

        .footer-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-title i {
            color: #dc3545;
            font-size: 28px;
        }

        /* TAMBAHAN: Shape Background untuk Subtitle Merah */
        .footer-subtitle {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #ffffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            display: inline-block;
            padding: 10px 20px;
            z-index: 2;
        }

        /* Opsi 1: Background dengan Rounded Rectangle */
        .footer-subtitle.style-rounded {
            background: rgba(220, 53, 69, 0.15);
            border-left: 4px solid #dc3545;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.19);
        }

        

        .footer-subtitle.style-corner::before,
        .footer-subtitle.style-corner::after {
            content: '';
            position: absolute;
            width: 15px;
            height: 15px;
            border: 3px solid #dc3545;
        }

        .footer-subtitle.style-corner::before {
            top: -3px;
            left: -3px;
            border-right: none;
            border-bottom: none;
        }

        .footer-subtitle.style-corner::after {
            bottom: -3px;
            right: -3px;
            border-left: none;
            border-top: none;
        }

        .footer-text {
            color: #bdc3c7;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-social {
            display: flex;
            gap: 10px;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 2px solid #dc3545;
            border-radius: 50%;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #dc3545;
            transform: translateX(5px);
        }

        .footer-links a i {
            width: 16px;
            font-size: 12px;
        }

        .footer-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #bdc3c7;
            font-size: 14px;
        }

        .info-item i {
            color: #dc3545;
            width: 16px;
            font-size: 14px;
        }

        .footer-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 40px 0 20px 0;
        }

        .footer-bottom {
            color: #bdc3c7;
        }

        .footer-bottom p {
            margin: 0;
            line-height: 1.5;
        }

        .small-text {
            font-size: 12px;
            margin-top: 5px !important;
        }

        .footer-links-inline {
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .footer-links-inline a {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-links-inline a:hover {
            color: #dc3545;
        }

        .separator {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Demo Section */
        .demo-section {
            background: white;
            padding: 40px;
            margin: 40px auto;
            max-width: 1200px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .demo-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }

        .style-preview {
            background: #2c3e50;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .style-label {
            display: block;
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 10px;
            font-weight: 600;
        }

        /* Responsive Footer */
        @media (max-width: 768px) {
            .main-footer {
                padding: 40px 0 20px 0;
            }

            .footer-links-inline {
                justify-content: center;
                margin-top: 20px;
            }

            .info-item {
                font-size: 13px;
            }

            .footer-text {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <!-- Footer dengan Background Sasirangan -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5 class="footer-title">
                            <i class="fas fa-database"></i>
                            SIP BANAR
                        </h5>
                        <p class="footer-text">
                            Sistem Informasi Pengadaan Barang dan Jasa Kalimantan Selatan.
                            Platform untuk transparansi dan akuntabilitas pengadaan publik.
                        </p>
                        <div class="footer-social">
                            <a href="#" class="social-link" title="Website Resmi">
                                <i class="fas fa-globe"></i>
                            </a>
                            <a href="#" class="social-link" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="#" class="social-link" title="Telepon">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <!-- Pilih salah satu style dengan menambahkan class: style-rounded, style-skewed, style-underline, style-pill, style-hexagon, atau style-corner -->
                        <h6 class="footer-subtitle style-rounded">MENU UTAMA</h6>
                        <ul class="footer-links">
                            <li><a href="/"><i class="fas fa-home"></i> Beranda</a></li>
                            <li><a href="/pengadaan"><i class="fas fa-list"></i> Data Pengadaan</a></li>
                            <li><a href="/rekap"><i class="fas fa-chart-bar"></i> Rekap Data</a></li>
                            <li><a href="/laporan"><i class="fas fa-file-alt"></i> Laporan</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="footer-subtitle style-rounded">KATEGORI</h6>
                        <ul class="footer-links">
                            <li><a href="/kategori/barang"><i class="fas fa-box"></i> Pengadaan Barang</a></li>
                            <li><a href="/kategori/jasa"><i class="fas fa-handshake"></i> Jasa Lainnya</a></li>
                            <li><a href="/kategori/konstruksi"><i class="fas fa-building"></i> Konstruksi</a></li>
                            <li><a href="/kategori/langsung"><i class="fas fa-bolt"></i> Pengadaan Langsung</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="footer-subtitle style-rounded">INFORMASI</h6>
                        <div class="footer-info">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Kalimantan Selatan, Indonesia</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Last Update: 01 Des 2024, 10:30 WITA</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-database"></i>
                                <span>Data dari LPSE Kalimantan Selatan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="footer-divider">

            <div class="row">
                <div class="col-md-8">
                    <div class="footer-bottom">
                        <p>&copy; 2024 SIP BANAR - Sistem Informasi Pengadaan. All rights reserved.</p>
                        <p class="small-text">
                            Dikembangkan untuk transparansi pengadaan publik di Kalimantan Selatan
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="footer-links-inline">
                        <a href="/privacy">Kebijakan Privasi</a>
                        <span class="separator">|</span>
                        <a href="/terms">Syarat & Ketentuan</a>
                        <span class="separator">|</span>
                        <a href="/help">Bantuan</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>