<!DOCTYPE html>
<html lang="id">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONTOH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* Navbar Styles */
      

        .dropdown-item {
            padding: 10px 20px;
            color: #333;
            transition: all 0.3s ease;
            position: relative;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #b71c1c;
            transform: translateX(5px);
        }

        /* Nested Dropdown Styles */
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -1px;
            border-radius: 8px;
        }

        .dropdown-submenu:hover .dropdown-menu {
            display: block;
        }

        .dropdown-submenu > .dropdown-item::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            float: right;
            margin-left: 10px;
            color: #999;
            font-size: 0.8rem;
        }

        .dropdown-submenu:hover > .dropdown-item::after {
            color: #b71c1c;
        }

        /* Search Box */
        .search-container {
            position: relative;
        }

        .search-input {
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 25px;
            padding: 8px 40px 8px 20px;
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.8);
        }

        .search-input:focus {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.6);
            box-shadow: none;
            outline: none;
            color: white;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: white;
            padding: 5px 10px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #b71c1c 0%, #d32f2f 100%);
            color: white;
            padding: 80px 0 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 20%, transparent 21%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 15%, transparent 16%),
                radial-gradient(circle at 40% 60%, rgba(255,255,255,0.05) 25%, transparent 26%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 80px;
            margin-top: 30px;
        }

        .stat-item h2 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-item p {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 600;
        }

        /* Kategori Section */
        .kategori {
            padding: 60px 0;
            background: #f8f9fa;
        }

        .kategori h2 {
            color: #333;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 50px;
        }

        .kategori-card {
            background: #78909c;
            color: white;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .kategori-card:hover {
            background: #607d8b;
            transform: translateY(-5px);
            color: white;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .kategori-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .kategori-title {
            font-size: 0.85rem;
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .kategori-subtitle {
            font-size: 0.7rem;
            opacity: 0.9;
            text-align: center;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .search-input {
                width: 180px;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .stats-container {
                flex-direction: column;
                gap: 30px;
            }
            
            .stat-item h2 {
                font-size: 2.5rem;
            }

            /* Mobile nested dropdown adjustments */
            .dropdown-submenu .dropdown-menu {
                position: static;
                float: none;
                width: 100%;
                margin-top: 0;
                border-top: 1px solid #eee;
                box-shadow: none;
            }
        }

        @media (max-width: 992px) {
         
        }
    </style>
</head>
<body>
   
                
                <!-- Search Form -->
                <form class="d-flex search-container">
                    <input class="search-input" type="search" placeholder="Cari data..." aria-label="Search">
                    <button class="search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Sistem Informasi Pengadaan Barang Dan Jasa Banjarmasin Maju Dan Sejahtera</h1>
                <div class="stats-container">
                    <div class="stat-item">
                        <h2>2749</h2>
                        <p>DATA</p>
                    </div>
                    <div class="stat-item">
                        <h2>107</h2>
                        <p>MAKRO</p>
                    </div>
                    <div class="stat-item">
                        <h2>2642</h2>
                        <p>SEKTORAL</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kategori Section -->
    <section class="kategori">
        <div class="container">
            <h2 class="text-center mb-5">KATEGORI</h2>
            <div class="row">
                <!-- Baris Pertama -->
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="tender/dummy" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="kategori-title">EKONOMI</div>
                        <div class="kategori-subtitle">420 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="kategori-title">KEPENDUDUKAN</div>
                        <div class="kategori-subtitle">233 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="{{ route('rekapitulasi.import') }}" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="kategori-title">KESEHATAN</div>
                        <div class="kategori-subtitle">185 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="{{ route('rekappengadaan.rup.seluruhpengadaan') }}" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="kategori-title">INI</div>
                        <div class="kategori-subtitle">142 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-masks-theater"></i>
                        </div>
                        <div class="kategori-title">PARIWISATA BUDAYA</div>
                        <div class="kategori-subtitle">98 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="kategori-title">PEMERINTAHAN</div>
                        <div class="kategori-subtitle">76 DATA</div>
                    </a>
                </div>

                <!-- Baris Kedua -->
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="kategori-title">PENANGGULANGAN BENCANA</div>
                        <div class="kategori-subtitle">54 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="swakelola/tes" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="kategori-title">PENDIDIKAN</div>
                        <div class="kategori-subtitle">321 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="rekapitulasi/input" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="kategori-title">PERTANIAN</div>
                        <div class="kategori-subtitle">167 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-people-group"></i>
                        </div>
                        <div class="kategori-title">SOSIAL</div>
                        <div class="kategori-subtitle">89 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="kategori-title">TENAGA KERJA</div>
                        <div class="kategori-subtitle">112 DATA</div>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                    <a href="#" class="kategori-card">
                        <div class="kategori-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="kategori-title">TRANSPORTASI</div>
                        <div class="kategori-subtitle">45 DATA</div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth hover effects for kategori cards
            const cards = document.querySelectorAll('.kategori-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Search input focus effects
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('focus', function() {
                this.style.width = '300px';
            });
            
            searchInput.addEventListener('blur', function() {
                if (window.innerWidth > 768) {
                    this.style.width = '250px';
                }
            });

            // Handle nested dropdown on mobile and desktop
            const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu');
            dropdownSubmenus.forEach(submenu => {
                const submenuToggle = submenu.querySelector('.dropdown-item');
                const submenuDropdown = submenu.querySelector('.dropdown-menu');
                
                if (window.innerWidth <= 768) {
                    submenuToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Toggle the submenu
                        if (submenu.classList.contains('show')) {
                            submenu.classList.remove('show');
                            submenuDropdown.style.display = 'none';
                        } else {
                            // Hide other submenus
                            dropdownSubmenus.forEach(otherSubmenu => {
                                if (otherSubmenu !== submenu) {
                                    otherSubmenu.classList.remove('show');
                                    otherSubmenu.querySelector('.dropdown-menu').style.display = 'none';
                                }
                            });
                            submenu.classList.add('show');
                            submenuDropdown.style.display = 'block';
                        }
                    });
                } else {
                    // Desktop hover behavior with improved timing
                    let hoverTimeout;
                    
                    submenu.addEventListener('mouseenter', function() {
                        clearTimeout(hoverTimeout);
                        submenuDropdown.style.display = 'block';
                    });
                    
                    submenu.addEventListener('mouseleave', function() {
                        hoverTimeout = setTimeout(() => {
                            submenuDropdown.style.display = 'none';
                        }, 150);
                    });
                }
            });

            // Close all submenus when clicking outside
            document.addEventListener('click', function() {
                dropdownSubmenus.forEach(submenu => {
                    submenu.classList.remove('show');
                    submenu.querySelector('.dropdown-menu').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>