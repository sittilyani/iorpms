<?php
// 1. Include the config file to get the database connection ($conn)
include 'includes/config.php';

// 2. Fetch all gallery items from the database
$gallery_items = [];
// Updated query to match the database structure
$sql = "SELECT id, title, image_path, description, image_data FROM tblgallery ORDER BY uploaded_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $gallery_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HPTU - LMIS</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {box-sizing: border-box; margin: 0; padding: 0;}
        body {font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5;}

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #DDEEFF 0%, #B2CCFF 100%);
            border-bottom: 3px solid #011f88;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .logo h2 {
            color: #011f88;
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
        }

        /* Mobile Menu Toggle */
        .navbar-toggler {
            display: none;
            background: #011f88;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .navbar-links {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 25px;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            margin: 0;
        }

        .nav-link {
            color: #011f88;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            display: inline-block;
        }

        .nav-link:hover {
            background: rgba(1, 31, 136, 0.1);
            color: #011f88;
            transform: translateY(-2px);
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
            margin-left: 15px;
            padding-left: 15px;
            border-left: 2px solid #011f88;
        }

        .auth-buttons .btn {
            background: #011f88;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .auth-buttons .btn:hover {
            background: #5a0099;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 31, 136, 0.3);
        }

        /* Main Section */
        .main-section {
            padding: 60px 20px 40px;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            text-align: center;
        }

        .main-section h1 {
            color: #011f88;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .main-section p {
            color: #6c757d;
            font-size: 1.1rem;
            line-height: 1.8;
            max-width: 900px;
            margin: 0 auto 20px;
        }

        /* Gallery Styles */
        .gallery-wrapper {
            position: relative;
            max-width: 1400px;
            margin: 50px auto 0;
            padding: 0 60px;
        }

        .gallery {
            display: flex;
            overflow-x: auto;
            gap: 25px;
            padding: 20px 0;
            scroll-behavior: smooth;
            scrollbar-width: none;
        }

        .gallery::-webkit-scrollbar {display: none;}

        .gallery-item {
            min-width: 350px;
            max-width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #f8f9fa; /* Fallback background */
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            color: #011f88;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        #btn-see-profile {
            background: #011f88;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }

        #btn-see-profile:hover {
            background: #5a0099;
            transform: translateY(-2px);
        }

        /* Scroll Buttons */
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: #011f88;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .scroll-btn:hover {
            background: #5a0099;
            transform: translateY(-50%) scale(1.1);
        }

        .scroll-left {left: 5px;}
        .scroll-right {right: 5px;}

        /* Loading placeholder */
        .image-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .navbar-container {padding: 15px 20px;}
            .navbar-links {gap: 20px;}
            .logo h2 {font-size: 1.1rem;}
        }

        @media (max-width: 992px) {
            .logo h2 {font-size: 1rem;}
            .navbar-links {gap: 15px;}
            .nav-link {padding: 6px 12px; font-size: 0.9rem;}
            .auth-buttons .btn {padding: 6px 15px; font-size: 0.9rem;}
            .main-section h1 {font-size: 2rem;}
        }

        @media (max-width: 768px) {
            .navbar-toggler {display: block;}

            .navbar-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                padding: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                display: none;
            }

            .navbar-links.active {display: flex;}

            .nav-item {
                width: 100%;
                text-align: center;
                border-bottom: 1px solid #e9ecef;
            }

            .nav-link {
                display: block;
                padding: 12px;
            }

            .auth-buttons {
                width: 100%;
                flex-direction: column;
                gap: 10px;
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                border-top: 2px solid #011f88;
                padding-top: 15px;
                margin-top: 10px;
            }

            .auth-buttons .btn {width: 100%;}

            .logo img {width: 60px; height: 60px;}
            .logo h2 {font-size: 0.9rem;}

            .main-section {padding: 40px 15px 30px;}
            .main-section h1 {font-size: 1.6rem;}
            .main-section p {font-size: 1rem;}

            .gallery-wrapper {padding: 0 50px;}
            .gallery-item {min-width: 300px; max-width: 300px;}
        }

        @media (max-width: 576px) {
            .navbar-container {padding: 10px 15px;}
            .logo img {width: 50px; height: 50px;}
            .logo h2 {font-size: 0.8rem;}

            .main-section h1 {font-size: 1.4rem;}
            .main-section p {font-size: 0.95rem;}

            .gallery-wrapper {padding: 0 45px;}
            .gallery-item {min-width: 280px; max-width: 280px;}

            .scroll-btn {
                width: 38px;
                height: 38px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 400px) {
            .logo h2 {display: none;}
            .gallery-item {min-width: 260px; max-width: 260px;}
        }
    </style>
</head>
<body>

<header class="header">
    <nav class="navbar-container">
        <div class="logo">
            <img src="assets/images/LOGO_HEALTH_PNG-removebg-preview.png" alt="HPTU Logo">
            <h2>Mombasa County HPTU Logistics</h2>
        </div>

        <button class="navbar-toggler" id="navToggle" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="navbar-links" id="navLinks">
            <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Strategic Plan</a></li>
            <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
            <li class="nav-item auth-buttons">
                <a href="public/login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="user_redirect.php" class="btn">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </li>
        </ul>
    </nav>
</header>

<section class="main-section">
    <h1>Health Products and Technologies Unit (HPTU)</h1>
    <p>
        The Health Products and Technologies Unit (HPTU) serves as the central coordinating body dedicated to ensuring that all citizens have consistent access to safe, effective, and quality essential medicines and health technologies.
    </p>
    <p>
        Established within the health sector, the HPTU's primary focus is to address challenges within the health supply chain, which traditionally include fragmentation, inadequate inventory controls, and stock losses due to pilferage or expiry.
    </p>

    <div class="gallery-wrapper">
        <button class="scroll-btn scroll-left" aria-label="Scroll left">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="gallery" id="gallery">
            <?php if (empty($gallery_items)): ?>
                <div class="gallery-item" style="min-width: 100%; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-images" style="font-size: 4rem; color: #6c757d; margin-bottom: 20px;"></i>
                    <p class="text-muted" style="font-size: 1.1rem;">No gallery items have been uploaded yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($gallery_items as $item): ?>
                    <div class="gallery-item">
                        <?php
                        // Try file path first, fallback to database
                        if (!empty($item['image_path']) && file_exists($item['image_path'])):
                        ?>
                            <img src="<?= htmlspecialchars($item['image_path']) ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="image-loading"
                                 onload="this.classList.remove('image-loading')"
                                 onerror="this.onerror=null; this.src='public/display_image.php?id=<?= $item['id'] ?>';">
                        <?php else: ?>
                            <img src="public/display_image.php?id=<?= $item['id'] ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="image-loading"
                                 onload="this.classList.remove('image-loading')">
                        <?php endif; ?>

                        <div class="card-body">
                            <h4 class="card-title"><?= htmlspecialchars($item['title']) ?></h4>
                            <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                            <a href="#" class="btn btn-primary" id="btn-see-profile">
                                <i class="fas fa-eye"></i> See Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button class="scroll-btn scroll-right" aria-label="Scroll right">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/bootstrap.min.js"></script>
<script>
    // Mobile Navigation Toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        const icon = navToggle.querySelector('i');
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
            navLinks.classList.remove('active');
            navToggle.querySelector('i').classList.add('fa-bars');
            navToggle.querySelector('i').classList.remove('fa-times');
        }
    });

    // Gallery Scroll Functionality
    const gallery = document.getElementById('gallery');
    const leftBtn = document.querySelector('.scroll-left');
    const rightBtn = document.querySelector('.scroll-right');
    const scrollAmount = 400;

    leftBtn.addEventListener('click', () => {
        gallery.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    rightBtn.addEventListener('click', () => {
        gallery.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    // Update arrow opacity based on scroll position
    gallery.addEventListener('scroll', () => {
        leftBtn.style.opacity = gallery.scrollLeft <= 0 ? '0.4' : '1';
        const maxScroll = gallery.scrollWidth - gallery.clientWidth;
        rightBtn.style.opacity = gallery.scrollLeft >= maxScroll - 1 ? '0.4' : '1';
    });

    // Initial arrow state
    leftBtn.style.opacity = '0.4';

    // Check if gallery is initially scrollable
    setTimeout(() => {
        const maxScroll = gallery.scrollWidth - gallery.clientWidth;
        rightBtn.style.opacity = maxScroll <= 1 ? '0.4' : '1';
    }, 100);

    // Lazy loading for images
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.gallery-item img');

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    // The image will load naturally since src is already set
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.1
        });

        images.forEach(img => imageObserver.observe(img));
    });
</script>

</body>
</html>