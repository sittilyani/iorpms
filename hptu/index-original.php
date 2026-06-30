<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HPTU - LMIS</title>

    <!-- Bootstrap CSS (local) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">

    <style>
        body {font-family: Arial, sans-serif; background:none; margin:0; padding:0;}
        .header {background:#DDEEFF; color:#011f88; padding:10px 0; border-bottom: solid; border-color: #011f88;}
        .navbar-brand {color:#011f88 !important; font-size:1.5em;}
        .nav-link {color:#011f88 !important;}
        .auth-buttons .btn {margin-left:10px;}

        .main-section {padding:40px 0; background:#e9ecef; text-align:center;}
        .gallery-wrapper {position:relative; max-width:100%; max-height: 30%; margin:0 auto;}
        .gallery {
            display:flex; overflow-x:auto; gap:20px; padding:20px 0;
            scroll-behavior:smooth; /* smooth scrolling */
            scrollbar-width:none;
        }
        .gallery::-webkit-scrollbar {display:none;}

        .gallery-item {
            max-width:400px; background:#fff; border-radius:10px;
            box-shadow:0 0 10px rgba(0,0,0,.1); padding:20px;
            flex:0 0 auto; text-align:left;
        }
        .gallery-item img {max-width:100%; border-radius:5px;}
        .gallery-item p {margin:10px 0 0; color:#6c757d;}
        .gallery-item a {color:#ffffff; text-decoration:none;}

        /* Arrow buttons */
        .scroll-btn {
            position:absolute; top:50%; transform:translateY(-50%);
            background:#011f88;; color:#fff; border:none;
            width:40px; height:40px; border-radius:50%;
            font-size:1.5rem; cursor:pointer; z-index:10;
            display:flex; align-items:center; justify-content:center;
        }
        .scroll-left  {left:10px;}
        .scroll-right {right:10px;}

        .footer {
            background:#011f88; color:#fff; text-align:center;
            padding:10px 0; width:100%; bottom:0;
        }
        .social-media a {color:#ffffff; margin:0 10px; text-decoration:none;}
         #btn-see-profile{ background: #011f88; border: none;}
         .navbar-container{display:flex; align-items:center; justify-content:space-between; height:auto; padding:0 20px;}
        .logo{display:flex;align-items:center;gap:15px;margin-right:30px;}
        .logo h2{margin:0;white-space:nowrap;}
        .navbar-links{display:flex;list-style:none;align-items:center; margin:0 0 0 auto;padding:0;gap:20px;}
        .navbar-links .nav-item{margin:0;}
        .auth-buttons{display:flex;gap:10px;}
        .auth-buttons .btn{margin-left:0;}

    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <nav class="navbar-container">
        <div class="logo">
            <!--<img src="assets/images/Logo-round-nobg-2.png" width="156" height="152" alt="">--><!--  -->
            <img src="assets/images/Mombasa-HPTU-logo.png" width="128" height="122" alt="">

        <h2>Mombasa County HPTU Logistics</h2>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

            <ul class="navbar-links">
                <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Strategic Plan</a></li>
                <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                <li class="nav-item auth-buttons">
                    <a href="public/login.php" class="btn btn-light btn-sm">Login</a>
                    <a href="user_redirect.php" class="btn btn-light btn-sm">Register</a>
                </li>
            </ul>
        </div>
    </nav>
</header>

<!-- Main Section -->
<section class="main-section">
    <h1 style="color:#011f88;">Health Products and Technologies Unit (HPTU)</h1>
    <p style="color:#6c757d;">
        The Health Products and Technologies Unit (HPTU) serves as the central coordinating body dedicated to ensuring that all citizens have consistent access to safe, effective, and quality essential medicines and health technologies.
    </p>
    <p style="color:#6c757d;">
        Established within the health sector, the HPTU's primary focus is to address challenges within the health supply chain, which traditionally include fragmentation, inadequate inventory controls, and stock losses due to pilferage or expiry.
    </p>

    <!-- Gallery with arrows -->
    <div class="gallery-wrapper container">
        <button class="scroll-btn scroll-left" aria-label="Scroll left">&lt;</button>

        <div class="gallery" id="gallery">
            <!-- ==== 20+ cards can be added here ==== -->
            <div class="gallery-item">
                <img src="assets/pictures/photo_iorpms_all_training.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_iorpms_demo.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_Dr_Hanif.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_Dr_hanif2.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_Dr_hanif3.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_Dr_Lyani.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_Dr_Lyani2.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_iorpms_Dr_Mackenzie.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_iorpms_mwakazi.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_training.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_training2.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_training3.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>
            <div class="gallery-item">
                <img src="assets/pictures/photo_IORPMS_training4.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Lyani Sitti</h4>
                        <p class="card-text">Sitti is a senior pharmacist with over 17 years experience in both public and private sector with a special interest in Health Informatics. He currently works with USAID Stawisha Pwani project as a supply chain specialist.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>

            <!-- Duplicate the block above for as many staff members as you need -->
            <!-- Example of a second card -->
            <div class="gallery-item">
                <img src="assets/pictures/photo_iorpms_all.jpeg" alt="Staff">

                <div class="card mt-2" style="width:100%;">
                    <div class="card-body">
                        <h4 class="card-title">Dr. Jane Doe</h4>
                        <p class="card-text">Jane leads the quality assurance team and ensures compliance with international standards.</p>
                        <a href="#" class="btn btn-primary" id="btn-see-profile">See Profile</a>
                    </div>
                </div>
            </div>

            <!-- Add more .gallery-item blocks … -->
            <!-- ... up to 20+ ... -->

        </div>

        <button class="scroll-btn scroll-right" aria-label="Scroll right">&gt;</button>
    </div>
</section>

<!-- Footer -->


<!-- Bootstrap JS (local) -->
<script src="../assets/js/bootstrap.min.js"></script>

<script>
    const gallery = document.getElementById('gallery');
    const leftBtn  = document.querySelector('.scroll-left');
    const rightBtn = document.querySelector('.scroll-right');

    const scrollAmount = 400;   // pixels to scroll per click

    leftBtn.addEventListener('click', () => {
        gallery.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    rightBtn.addEventListener('click', () => {
        gallery.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    // Optional: hide arrows when at the edges
    gallery.addEventListener('scroll', () => {
        leftBtn.style.opacity  = gallery.scrollLeft <= 0 ? '0.4' : '1';
        const maxScroll = gallery.scrollWidth - gallery.clientWidth;
        rightBtn.style.opacity = gallery.scrollLeft >= maxScroll - 1 ? '0.4' : '1';
    });
</script>

</body>
</html>
<?php include 'includes/footer.php'; ?>