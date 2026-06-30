<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - HPTU</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:0;}
        .header {background:#DDEEFF; color:#011f88; padding:10px 0; border-bottom: solid; border-color: #011f88;}
        .navbar-brand {color:#011f88 !important; font-size:1.5em;}
        .nav-link {color:#011f88 !important;}
        .auth-buttons .btn {margin-left:10px;}
        .main-section {padding:40px 0; background:#e9ecef; text-align:center;}
        .navbar-container{display:flex; align-items:center; justify-content:space-between; height:auto; padding:0 20px;}
        .logo{display:flex;align-items:center;gap:15px;margin-right:30px;}
        .logo h2{margin:0;white-space:nowrap;}
        .navbar-links{display:flex;list-style:none;align-items:center; margin:0 0 0 auto;padding:0;gap:20px;}
        .navbar-links .nav-item{margin:0;}
        .auth-buttons{display:flex;gap:10px;}
        .auth-buttons .btn{margin-left:0;}
        h2{color:#011f88; font-size:28px; font-weight:bold; margin:30px 0 20px; text-align:center;}
        h3{color:#011f88; font-size:20px; font-weight:bold; margin:20px 0 15px;}

        .content {
            max-width:80%; margin:40px auto; padding:0 20px; background:white;
            border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.1);
        }

        .intro-section {
            padding:40px 30px; text-align:center; background:linear-gradient(135deg, #DDEEFF 0%, #ffffff 100%);
            border-radius:10px 10px 0 0;
        }

        .intro-section p {font-size:16px; line-height:1.8; color:#333; margin:15px 0;}

        .vision-mission {
            display:grid; grid-template-columns:1fr 1fr; gap:30px; padding:40px 30px;
            background:#f8f9fa; margin:0;
        }

        .vm-card {
            background:white; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08);
            text-align:center; transition:transform 0.3s;
        }

        .vm-card:hover {transform:translateY(-5px);}

        .vm-card i {font-size:48px; color:#011f88; margin-bottom:20px;}

        .mandate-section {padding:40px 30px;}

        .mandate-grid {
            display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
            gap:25px; margin-top:30px;
        }

        .mandate-card {
            background:#f8f9fa; padding:25px; border-radius:10px; border-left:4px solid #011f88;
            transition:all 0.3s; box-shadow:0 2px 8px rgba(0,0,0,0.05);
        }

        .mandate-card:hover {
            box-shadow:0 6px 20px rgba(1,31,136,0.15); transform:translateX(5px);
        }

        .mandate-card h3 {margin-top:0; display:flex; align-items:center; gap:10px;}

        .mandate-card i {color:#011f88; font-size:24px;}

        .mandate-card ul {list-style:none; padding-left:0; margin:15px 0;}

        .mandate-card li {
            padding:8px 0; padding-left:25px; position:relative; line-height:1.6;
        }

        .mandate-card li:before {
            content:"\2022"; position:absolute; left:0; color:#011f88; font-weight:bold; font-size: 30px;  /*\2022 is thr ound bullet point*/
        }

        .portal-section {
            background:linear-gradient(135deg, #011f88 0%, #0a3d99 100%);
            color:white; padding:50px 30px; margin:0; text-align:center;
        }

        .portal-section h2 {color:white; font-size:32px; margin-bottom:15px;}

        .portal-subtitle {
            font-size:18px; font-style:italic; margin-bottom:40px; opacity:0.9;
        }

        .portal-features {
            display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
            gap:25px; margin-top:40px;
        }

        .feature-card {
            background:rgba(255,255,255,0.1); backdrop-filter:blur(10px);
            padding:30px; border-radius:10px; border:1px solid rgba(255,255,255,0.2);
            transition:all 0.3s;
        }

        .feature-card:hover {
            background:rgba(255,255,255,0.15); transform:translateY(-5px);
        }

        .feature-card i {font-size:42px; margin-bottom:15px; color:#66CCFF;}

        .feature-card h4 {font-size:18px; margin:15px 0; font-weight:600;}

        .feature-card p {font-size:14px; line-height:1.6; opacity:0.9;}

        .expiry-redistribution {
            display:grid; grid-template-columns:1fr 1fr; gap:30px;
            padding:40px 30px; background:#f8f9fa;
        }

        .er-card {
            background:white; padding:30px; border-radius:10px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .er-card h3 {
            color:#011f88; display:flex; align-items:center; gap:10px;
            border-bottom:2px solid #DDEEFF; padding-bottom:15px; margin-bottom:20px;
        }

        .er-card ul {list-style:none; padding-left:0;}

        .er-card li {
            padding:10px 0; padding-left:30px; position:relative; line-height:1.6;
        }

        .er-card li:before {
            content:"\2022"; position:absolute; left:0; color: #66CCFF;
            font-weight:bold; font-size:30px;
        }

        .values-section {
            padding:40px 30px; text-align:center; background:white;
        }

        .values-grid {
            display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-top:30px;
        }

        .value-card {
            padding:25px; background:#DDEEFF; border-radius:10px;
            transition:all 0.3s;
        }

        .value-card:hover {background:#011f88; color:white;}

        .value-card i {font-size:36px; color:#011f88; margin-bottom:15px;}

        .value-card:hover i {color:white;}

        .value-card h4 {font-size:18px; font-weight:600; margin:10px 0;}

        .footer {
            background:#011f88; color:#fff; text-align:center;
            padding:30px 20px; margin-top:0;
        }

        .footer-content {max-width:1200px; margin:0 auto;}

        .social-links {margin-top:15px;}

        .social-links a {
            color:#fff; margin:0 12px; font-size:24px;
            transition:color 0.3s;
        }

        .social-links a:hover {color:#66CCFF;}

        @media (max-width: 768px) {
            .vision-mission, .expiry-redistribution {grid-template-columns:1fr;}
            .values-grid {grid-template-columns:repeat(2, 1fr);}
            .mandate-grid {grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <nav class="navbar-container">
        <div class="logo">
            <img src="assets/images/Mombasa-HPTU-logo.png" width="128" height="122" alt="HPTU Logo">
            <h2>Mombasa County HPTU Logistics</h2>
            <ul class="navbar-links">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
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
    <h1 style="color:#011f88;">About the Health Products and Technologies Unit</h1>
    <p style="color:#6c757d; max-width:800px; margin:20px auto;">
        Learn more about our mission, vision, and commitment to ensuring quality healthcare delivery across Mombasa County
    </p>
</section>

<section class="content">
    <!-- Introduction -->
    <div class="intro-section">
        <h2 style="margin-top:0;">Who We Are</h2>
        <p>The Mombasa County Health Products and Technologies Unit (HPTU) is the central coordinating structure responsible for organizing, monitoring, and supporting all supply chain functions that ensure uninterrupted availability of safe, effective, affordable, and quality health products across all county health facilities.</p>
        <p>Anchored within the Department of Health Services, the HPTU consolidates all supply chain functions into one strategic platform, strengthening governance, enhancing efficiency, promoting transparency, and safeguarding the quality of health products used by the people of Mombasa County.</p>
        <p><strong>Led by the County Pharmacist</strong>, the Unit comprises a dedicated multidisciplinary team committed to maintaining an uninterrupted flow of essential medicines, vaccines, laboratory supplies, medical equipment, and other critical commodities across all service delivery points.</p>
    </div>

    <!-- Vision & Mission -->
    <div class="vision-mission">
        <div class="vm-card">
            <i class="fas fa-eye"></i>
            <h2>Our Vision</h2>
            <p>A robust, resilient, and sustainable health products and technologies supply chain system for Mombasa County.</p>
        </div>
        <div class="vm-card">
            <i class="fas fa-bullseye"></i>
            <h2>Our Mission</h2>
            <p>To ensure consistent, adequate, and transparent provision of affordable, acceptable, and quality health products and technologies to all citizens of Mombasa County.</p>
        </div>
    </div>

    <!-- Mandate Section -->
    <div class="mandate-section">
        <h2>Our Mandate & Core Functions</h2>
        <div class="mandate-grid">
            <div class="mandate-card">
                <h3><i class="fas fa-users-cog"></i> Leadership & Governance</h3>
                <ul>
                    <li>Provide county-level stewardship and alignment with national HPT policies</li>
                    <li>Integrate HPT priorities into county strategic and annual work plans</li>
                    <li>Coordinate the Medicines and Therapeutics Committee (MTC) and other governance structures</li>
                </ul>
            </div>

            <div class="mandate-card">
                <h3><i class="fas fa-truck-loading"></i> Supply Chain Management</h3>
                <ul>
                    <li>Oversee forecasting, quantification, procurement, distribution, and inventory management</li>
                    <li>Monitor stock status, minimize wastage, and optimize redistribution</li>
                </ul>
            </div>

            <div class="mandate-card">
                <h3><i class="fas fa-shield-alt"></i> Quality Assurance & Safety</h3>
                <ul>
                    <li>Ensure safety, quality, and rational use of health products</li>
                    <li>Implement pharmacovigilance, post-market surveillance, and quality control protocols</li>
                    <li>Oversee compliance for donated or externally sourced HPTs</li>
                </ul>
            </div>

            <div class="mandate-card">
                <h3><i class="fas fa-chart-line"></i> Data-Driven Decision Making</h3>
                <ul>
                    <li>Utilize LMIS tools to track stock levels and monitor performance</li>
                    <li>Lead quarterly HPT data reviews, audits, and supportive supervision</li>
                </ul>
            </div>

            <div class="mandate-card">
                <h3><i class="fas fa-handshake"></i> Coordination & Capacity Building</h3>
                <ul>
                    <li>Strengthen technical capacity of HPT managers and supply chain teams</li>
                    <li>Facilitate multisectoral collaboration with regulatory bodies, partners, and professional associations</li>
                </ul>
            </div>

            <div class="mandate-card">
                <h3><i class="fas fa-lightbulb"></i> Innovation, Research & Technology</h3>
                <ul>
                    <li>Promote research and technological advancements in HPT management</li>
                    <li>Support innovations including digital tools, traditional medicines, and herbal therapies</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- HPTU Portal Section -->
    <div class="portal-section">
        <h2 style='color: #66CCFF;'>THE HPTU PORTAL</h2>
        <p class="portal-subtitle">A Digital Innovation for Real-Time Visibility, Accountability & Efficiency</p>
        <p style="max-width:900px; margin:0 auto 20px;">The HPTU Portal is a transformative digital platform designed to modernize, streamline, and strengthen health supply chain visibility across all Mombasa County health facilities.</p>

        <div class="portal-features">
            <div class="feature-card">
                <i class="fas fa-desktop"></i>
                <h4>Real-Time Stock Visibility</h4>
                <p>Centralized dashboards allow facility, sub-county, and county leadership to monitor stock levels at a glance.</p>
            </div>

            <div class="feature-card">
                <i class="fas fa-calendar-times"></i>
                <h4>Expiry Tracking & Alerts</h4>
                <p>Track expiry dates in real time, receive automated alerts for short-dated commodities, and enable proactive interventions.</p>
            </div>

            <div class="feature-card">
                <i class="fas fa-exchange-alt"></i>
                <h4>Internal Redistribution</h4>
                <p>Identify surpluses and deficits across facilities, enable timely redistribution requests and approvals.</p>
            </div>

            <div class="feature-card">
                <i class="fas fa-chart-bar"></i>
                <h4>Performance Dashboards</h4>
                <p>Monitor key HPT indicators, support evidence-based planning and guide strategic decision-making at all levels.</p>
            </div>

            <div class="feature-card">
                <i class="fas fa-file-alt"></i>
                <h4>Enhanced Coordination & Reporting</h4>
                <p>Streamlined communication with TWGs, partners, and regulatory bodies through a unified digital platform.</p>
            </div>
        </div>
    </div>

    <!-- Expiry & Redistribution -->
    <div class="expiry-redistribution">
        <div class="er-card">
            <h3><i class="fas fa-clock"></i> Expiry Tracking</h3>
            <ul>
                <li>Real-time monitoring of expiry dates</li>
                <li>Automated alerts for near-expiry items</li>
                <li>County-level dashboards for review and prioritization</li>
                <li>Tools to support targeted supportive supervision</li>
            </ul>
        </div>

        <div class="er-card">
            <h3><i class="fas fa-sync-alt"></i> Internal Redistribution</h3>
            <ul>
                <li>Match slow-moving commodities to high-consumption facilities</li>
                <li>Improve emergency response and stock equalization</li>
                <li>Strengthen commodity availability through coordinated inter-facility support</li>
            </ul>
        </div>
    </div>

    <!-- Core Values -->
    <div class="values-section">
        <h2>Our Service Promise</h2>
        <p style="margin-bottom:30px;">We commit to delivering efficient, transparent, and people-centered service guided by our core values:</p>
        <div class="values-grid">
            <div class="value-card">
                <i class="fas fa-award"></i>
                <h4>Integrity</h4>
            </div>
            <div class="value-card">
                <i class="fas fa-rocket"></i>
                <h4>Innovation</h4>
            </div>
            <div class="value-card">
                <i class="fas fa-user-check"></i>
                <h4>Competence</h4>
            </div>
            <div class="value-card">
                <i class="fas fa-users"></i>
                <h4>Teamwork</h4>
            </div>
        </div>
    </div>
</section>

<div class="footer">
<div class="footer-content">
        <span>Health Products and Technologies Unit (HPTU)&nbsp;<?php echo date('Y');?> - &copy; Supported by USAID Stawisha Pwani - LVCT Health</span>
        <div class="social-links">
                <a href="https://web.facebook.com/MombasaHealth/?_rdc=1&_rdr#" target="_blank" rel="noopener noreferrer" title="Facebook">
                        <i class="fab fa-facebook"></i>
                </a>
                <a href="https://www.youtube.com/@health.mombasa/" target="_blank" rel="noopener noreferrer" title="YouTube">
                        <i class="fab fa-youtube"></i>
                </a>
                <a href="https://x.com/DOHMombasa/" target="_blank" rel="noopener noreferrer" title="X (Twitter)">
                        <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.instagram.com/health.mombasa/?hl=en" target="_blank" rel="noopener noreferrer" title="Instagram">
                        <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.linkedin.com/company/department-of-health-services-mombasa-county/posts/?feedView=all" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                </a>
        </div>
</div>
</div>

</body>
</html>