<?php
// footer.php
?>
    <footer class="footer-custom mt-5">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">
                        <i class="fas fa-database me-2"></i>
                        SIP BANAR
                    </h5>
                    <p class="footer-text">
                        Sistem Informasi Pengadaan Barang dan Rekap (SIP BANAR) adalah platform digital untuk mengelola dan melacak pengadaan barang secara efisien dan transparan.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-md-2 mb-4">
                    <h6 class="footer-subtitle">Menu Utama</h6>
                    <ul class="footer-links">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="rekap-pengadaan.php">Rekap Pengadaan</a></li>
                        <li><a href="kategori.php">Kategori</a></li>
                        <li><a href="laporan.php">Laporan</a></li>
                        <li><a href="tentang.php">Tentang</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div class="col-md-2 mb-4">
                    <h6 class="footer-subtitle">Layanan</h6>
                    <ul class="footer-links">
                        <li><a href="pengadaan.php">Pengadaan Barang</a></li>
                        <li><a href="stok-barang.php">Manajemen Stok</a></li>
                        <li><a href="laporan-keuangan.php">Laporan Keuangan</a></li>
                        <li><a href="analytics.php">Analisis Data</a></li>
                        <li><a href="export-data.php">Export Data</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-md-2 mb-4">
                    <h6 class="footer-subtitle">Dukungan</h6>
                    <ul class="footer-links">
                        <li><a href="help.php">Bantuan</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="panduan.php">Panduan</a></li>
                        <li><a href="kontak.php">Kontak</a></li>
                        <li><a href="download.php">Download</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-md-2 mb-4">
                    <h6 class="footer-subtitle">Kontak</h6>
                    <div class="contact-info">
                        <p class="contact-item">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Jakarta, Indonesia
                        </p>
                        <p class="contact-item">
                            <i class="fas fa-phone me-2"></i>
                            +62 21 123-4567
                        </p>
                        <p class="contact-item">
                            <i class="fas fa-envelope me-2"></i>
                            info@sipbanar.co.id
                        </p>
                        <p class="contact-item">
                            <i class="fas fa-clock me-2"></i>
                            24/7 Online
                        </p>
                    </div>
                </div>
            </div>

            <hr class="footer-divider">

            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">
                        &copy; <?php echo date('Y'); ?> SIP BANAR. Seluruh hak cipta dilindungi.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links">
                        <a href="privacy.php">Kebijakan Privasi</a>
                        <span class="separator">|</span>
                        <a href="terms.php">Syarat & Ketentuan</a>
                        <span class="separator">|</span>
                        <a href="sitemap.php">Peta Situs</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button id="backToTop" class="back-to-top" onclick="scrollToTop()">
            <i class="fas fa-chevron-up"></i>
        </button>
    </footer>

    <style>
        .footer-custom {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            padding: 50px 0 20px 0;
            position: relative;
        }

        .footer-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
        }

        .footer-title {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .footer-subtitle {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .footer-text {
            color: #bdc3c7;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 8px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: #dc3545;
            padding-left: 5px;
        }

        .social-links {
            margin-top: 20px;
        }

        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            text-align: center;
            line-height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-link:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-3px);
        }

        .contact-info .contact-item {
            color: #bdc3c7;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .contact-info .contact-item i {
            color: #dc3545;
            width: 20px;
        }

        .footer-divider {
            border-color: #455a64;
            margin: 40px 0 20px 0;
        }

        .copyright {
            color: #95a5a6;
            margin: 0;
            font-size: 0.9rem;
        }

        .footer-bottom-links {
            text-align: right;
        }

        .footer-bottom-links a {
            color: #95a5a6;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: #dc3545;
        }

        .separator {
            color: #7f8c8d;
            margin: 0 10px;
        }

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .back-to-top:hover {
            background: #c82333;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 768px) {
            .footer-bottom-links {
                text-align: center;
                margin-top: 15px;
            }
            
            .social-links {
                text-align: center;
            }
            
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }
        }
    </style>

    <script>
        // Back to top functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/hide back to top button
        window.addEventListener('scroll', function() {
            const backToTopButton = document.getElementById('backToTop');
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Current year update
        document.addEventListener('DOMContentLoaded', function() {
            const currentYear = new Date().getFullYear();
            const copyrightElement = document.querySelector('.copyright');
            if (copyrightElement) {
                copyrightElement.innerHTML = copyrightElement.innerHTML.replace(/\d{4}/, currentYear);
            }
        });
    </script>

</body>
</html>