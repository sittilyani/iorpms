<?php
/**
 * index.php — IORPMS Public Landing Page
 * ========================================
 * Multilingual (EN / FR / PT) landing page.
 * Translations are embedded in page JS for instant switching — no API calls
 * needed on a static marketing page. App pages use translate_proxy.php instead.
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IORPMS — Integrated Outpatient Rehabilitation &amp; Pharmacy Management System</title>
<meta name="description" content="Cloud-based MAT clinic management: methadone pump dispensing, client records, prison module, KHIS reporting.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{
  --primary:#2C3162;--primary-dark:#1a1d3d;
  --accent:#82b543;--accent-dark:#6a9438;
  --light:#f4f7fb;--text:#263238;--muted:#607d8b;
  --white:#fff;--border:#dde3f0;
}
html{scroll-behavior:smooth;}
body{font-family:'Inter',sans-serif;color:var(--text);background:var(--white);line-height:1.6;}
a{text-decoration:none;color:inherit;}

/* ── Navbar ── */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;
  background:rgba(44,49,98,.97);backdrop-filter:blur(8px);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 6%;height:68px;box-shadow:0 2px 20px rgba(0,0,0,.25);}
.nav-logo{display:flex;align-items:center;gap:10px;color:#fff;}
.nav-logo-icon{width:38px;height:38px;background:var(--accent);border-radius:8px;
  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;color:#fff;}
.nav-logo-text{font-size:1.1rem;font-weight:700;}
.nav-links{display:flex;gap:26px;align-items:center;}
.nav-links a{color:rgba(255,255,255,.8);font-size:.9rem;font-weight:500;transition:color .2s;position:relative;}
.nav-links a:hover{color:#fff;}
.nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;width:0;height:2px;background:var(--accent);transition:width .2s;}
.nav-links a:hover::after{width:100%;}
.nav-right{display:flex;align-items:center;gap:10px;}
.lang-switcher{display:flex;gap:3px;}
.lang-btn{background:transparent;border:1px solid rgba(255,255,255,.3);color:rgba(255,255,255,.8);
  padding:4px 8px;border-radius:5px;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .2s;}
.lang-btn:hover,.lang-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.btn-login{background:transparent;border:1.5px solid var(--accent);color:var(--accent);
  padding:7px 16px;border-radius:7px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;}
.btn-login:hover{background:var(--accent);color:#fff;}
.btn-demo{background:var(--accent);border:1.5px solid var(--accent);color:#fff;
  padding:7px 16px;border-radius:7px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;}
.btn-demo:hover{background:var(--accent-dark);}
.nav-toggle{display:none;background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;}

/* ── Hero ── */
.hero{min-height:100vh;
  background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 50%,#3d5a8a 100%);
  display:flex;align-items:center;padding:100px 6% 80px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;top:-40%;right:-10%;width:700px;height:700px;
  border-radius:50%;background:radial-gradient(circle,rgba(130,181,67,.15) 0%,transparent 70%);}
.hero-content{max-width:640px;position:relative;z-index:1;}
.hero-badge{display:inline-flex;align-items:center;gap:7px;
  background:rgba(130,181,67,.18);border:1px solid rgba(130,181,67,.4);color:var(--accent);
  padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;margin-bottom:22px;}
.hero h1{font-size:clamp(2rem,4vw,3.2rem);font-weight:800;color:#fff;line-height:1.2;margin-bottom:18px;}
.hero h1 span{color:var(--accent);}
.hero p{font-size:1rem;color:rgba(255,255,255,.75);margin-bottom:32px;max-width:520px;}
.hero-btns{display:flex;gap:14px;flex-wrap:wrap;}
.btn-primary{background:var(--accent);color:#fff;padding:13px 26px;border-radius:9px;
  font-size:.95rem;font-weight:700;border:none;cursor:pointer;transition:all .25s;
  display:inline-flex;align-items:center;gap:8px;}
.btn-primary:hover{background:var(--accent-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(130,181,67,.4);}
.btn-outline{background:transparent;color:#fff;padding:13px 26px;border-radius:9px;
  font-size:.95rem;font-weight:600;border:1.5px solid rgba(255,255,255,.5);cursor:pointer;transition:all .25s;
  display:inline-flex;align-items:center;gap:8px;}
.btn-outline:hover{background:rgba(255,255,255,.08);border-color:#fff;}
.hero-stats{display:flex;gap:36px;margin-top:50px;flex-wrap:wrap;}
.hero-stat-num{font-size:1.8rem;font-weight:800;color:var(--accent);}
.hero-stat-lbl{font-size:.78rem;color:rgba(255,255,255,.6);margin-top:2px;}

/* ── Sections ── */
section{padding:90px 6%;}
.section-label{font-size:.75rem;font-weight:700;letter-spacing:1.5px;color:var(--accent);text-transform:uppercase;margin-bottom:10px;}
.section-title{font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:var(--primary);margin-bottom:14px;}
.section-sub{font-size:.98rem;color:var(--muted);max-width:560px;}
.centered{text-align:center;}.centered .section-sub{margin:0 auto;}

/* ── Features ── */
#features{background:var(--light);}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-top:48px;}
.feature-card{background:#fff;border-radius:14px;padding:26px;border:1px solid var(--border);transition:all .25s;}
.feature-card:hover{transform:translateY(-4px);box-shadow:0 12px 35px rgba(44,49,98,.1);border-color:var(--primary);}
.feature-icon{width:50px;height:50px;border-radius:12px;
  background:linear-gradient(135deg,var(--primary),#3d5a8a);
  display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;margin-bottom:16px;}
.feature-card h3{font-size:.98rem;font-weight:700;color:var(--primary);margin-bottom:7px;}
.feature-card p{font-size:.86rem;color:var(--muted);line-height:1.6;}

/* ── How it works ── */
#how{background:#fff;}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:28px;margin-top:48px;}
.step{text-align:center;padding:18px;}
.step-num{width:54px;height:54px;border-radius:50%;background:var(--primary);color:#fff;
  font-size:1.3rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
.step h3{font-size:.96rem;font-weight:700;color:var(--primary);margin-bottom:7px;}
.step p{font-size:.85rem;color:var(--muted);}

/* ── Pricing ── */
#pricing{background:var(--light);}
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(275px,1fr));gap:22px;margin-top:48px;align-items:start;}
.pricing-card{background:#fff;border-radius:16px;padding:32px 28px;border:2px solid var(--border);transition:all .25s;position:relative;}
.pricing-card.featured{border-color:var(--primary);box-shadow:0 16px 50px rgba(44,49,98,.15);transform:scale(1.03);}
.pricing-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);
  background:var(--primary);color:#fff;padding:4px 16px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.pricing-tier{font-size:.75rem;font-weight:700;letter-spacing:1px;color:var(--accent);text-transform:uppercase;margin-bottom:7px;}
.pricing-name{font-size:1.25rem;font-weight:800;color:var(--primary);margin-bottom:5px;}
.pricing-desc{font-size:.84rem;color:var(--muted);margin-bottom:22px;}
.pricing-price{display:flex;align-items:baseline;gap:4px;margin-bottom:22px;}
.pricing-amount{font-size:2.3rem;font-weight:800;color:var(--primary);}
.pricing-period{font-size:.84rem;color:var(--muted);}
.pricing-custom{font-size:1.4rem;font-weight:700;color:var(--primary);}
.pricing-features{list-style:none;margin-bottom:26px;}
.pricing-features li{font-size:.86rem;color:var(--text);padding:6px 0;
  border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.pricing-features li:last-child{border-bottom:none;}
.pricing-features li i{color:var(--accent);font-size:.82rem;min-width:14px;}
.pricing-features li.unavail{color:var(--muted);}
.pricing-features li.unavail i{color:#ccc;}
.btn-pricing{display:block;width:100%;padding:12px;border-radius:9px;
  font-size:.88rem;font-weight:700;cursor:pointer;text-align:center;transition:all .25s;border:none;}
.btn-pricing-primary{background:var(--primary);color:#fff;}
.btn-pricing-primary:hover{background:var(--primary-dark);}
.btn-pricing-outline{background:transparent;color:var(--primary);border:2px solid var(--primary) !important;}
.btn-pricing-outline:hover{background:var(--primary);color:#fff;}

/* ── Compliance ── */
.compliance{background:linear-gradient(135deg,var(--primary-dark),var(--primary));
  padding:60px 6%;color:#fff;text-align:center;}
.compliance h2{font-size:1.7rem;font-weight:800;margin-bottom:10px;}
.compliance p{color:rgba(255,255,255,.75);max-width:520px;margin:0 auto 26px;}
.compliance-logos{display:flex;justify-content:center;gap:22px;flex-wrap:wrap;margin-top:26px;}
.compliance-tag{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);
  color:#fff;padding:6px 16px;border-radius:8px;font-size:.8rem;font-weight:600;}

/* ── Footer ── */
footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:50px 6% 28px;}
.footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:36px;margin-bottom:36px;}
.footer-brand{color:#fff;font-size:1.05rem;font-weight:700;margin-bottom:11px;display:flex;align-items:center;gap:8px;}
.footer-brand-icon{width:30px;height:30px;background:var(--accent);border-radius:6px;
  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;color:#fff;}
footer p{font-size:.84rem;line-height:1.7;margin-bottom:14px;}
.footer-col h4{color:#fff;font-size:.88rem;font-weight:700;margin-bottom:13px;}
.footer-col ul{list-style:none;}
.footer-col ul li{margin-bottom:8px;}
.footer-col ul li a{font-size:.83rem;color:rgba(255,255,255,.6);transition:color .2s;}
.footer-col ul li a:hover{color:var(--accent);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:20px;
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:.81rem;}

/* ── Modal ── */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,.6);z-index:2000;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal-box{background:#fff;border-radius:16px;padding:36px;max-width:480px;width:100%;
  position:relative;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.modal-close{position:absolute;top:13px;right:15px;background:none;border:none;
  font-size:1.4rem;color:#999;cursor:pointer;}
.modal-close:hover{color:#333;}
.modal-icon{width:52px;height:52px;border-radius:13px;
  background:linear-gradient(135deg,var(--primary),#3d5a8a);
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;margin-bottom:16px;}
.modal-box h2{font-size:1.3rem;font-weight:800;color:var(--primary);margin-bottom:5px;}
.modal-box .subtitle{font-size:.86rem;color:var(--muted);margin-bottom:22px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-field{margin-bottom:14px;}
.form-field label{display:block;font-size:.8rem;font-weight:600;color:var(--primary);margin-bottom:4px;}
.form-field input,.form-field select{width:100%;height:41px;padding:8px 11px;border-radius:8px;
  border:1.5px solid var(--border);font-size:.88rem;outline:none;font-family:inherit;transition:border-color .2s;}
.form-field input:focus,.form-field select:focus{border-color:var(--primary);}
.btn-submit{width:100%;height:44px;background:var(--primary);color:#fff;border:none;
  border-radius:9px;font-size:.92rem;font-weight:700;cursor:pointer;transition:all .25s;
  display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-submit:hover{background:var(--primary-dark);}
.btn-submit:disabled{opacity:.7;cursor:not-allowed;}
.form-note{font-size:.76rem;color:var(--muted);text-align:center;margin-top:11px;}
.alert-box{border-radius:8px;padding:11px 15px;font-size:.86rem;margin-bottom:14px;display:none;}
.alert-success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
.alert-error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}

/* ── Responsive ── */
@media(max-width:900px){
  .nav-links{display:none;}.nav-toggle{display:block;}
  .footer-grid{grid-template-columns:1fr 1fr;}
  .pricing-card.featured{transform:none;}
}
@media(max-width:600px){
  .navbar{padding:0 4%;}section{padding:70px 4%;}
  .hero{padding:100px 4% 60px;}.form-row{grid-template-columns:1fr;}
  .footer-grid{grid-template-columns:1fr;}
  .footer-bottom{flex-direction:column;text-align:center;}
  .nav-right{gap:6px;}.btn-login,.btn-demo{padding:6px 10px;font-size:.78rem;}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-logo">
    <div class="nav-logo-icon">IO</div>
    <span class="nav-logo-text">IORPMS</span>
  </div>
  <div class="nav-links" id="navLinks">
    <a href="#features" data-i18n="nav_features">Features</a>
    <a href="#how"      data-i18n="nav_how">How It Works</a>
    <a href="#pricing"  data-i18n="nav_pricing">Pricing</a>
    <a href="#contact"  data-i18n="nav_contact">Contact</a>
  </div>
  <div class="nav-right">
    <div class="lang-switcher">
      <button class="lang-btn active" onclick="setLang('en')">EN</button>
      <button class="lang-btn"        onclick="setLang('fr')">FR</button>
      <button class="lang-btn"        onclick="setLang('pt')">PT</button>
    </div>
    <a href="public/login.php"><button class="btn-login" data-i18n="nav_login">Login</button></a>
    <button class="btn-demo" onclick="openModal()" data-i18n="nav_demo">Request Demo</button>
    <button class="nav-toggle" onclick="toggleNav()"><i class="fa fa-bars"></i></button>
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-content">
    <div class="hero-badge"><i class="fa fa-shield-halved"></i>&nbsp;<span data-i18n="hero_badge">Trusted by MAT Clinics in Kenya</span></div>
    <h1 data-i18n="hero_h1">The Smarter Way to<br><span>Manage MAT Clinics</span></h1>
    <p data-i18n="hero_sub">From automated methadone pump dispensing to KHIS monthly reporting — IORPMS handles your entire clinic workflow in one secure platform.</p>
    <div class="hero-btns">
      <button class="btn-primary" onclick="openModal()">
        <i class="fa fa-rocket"></i>&nbsp;<span data-i18n="hero_cta1">Request Demo / Access</span>
      </button>
      <a href="public/login.php">
        <button class="btn-outline">
          <i class="fa fa-right-to-bracket"></i>&nbsp;<span data-i18n="hero_cta2">Log In</span>
        </button>
      </a>
    </div>
    <div class="hero-stats">
      <div><div class="hero-stat-num">5,000+</div><div class="hero-stat-lbl" data-i18n="stat_clients">Clients Managed</div></div>
      <div><div class="hero-stat-num">99.9%</div><div class="hero-stat-lbl" data-i18n="stat_uptime">Uptime</div></div>
      <div><div class="hero-stat-num">KHIS</div><div class="hero-stat-lbl" data-i18n="stat_khis">Integrated</div></div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features">
  <div class="centered">
    <div class="section-label" data-i18n="feat_label">What We Offer</div>
    <h2 class="section-title" data-i18n="feat_title">Everything Your MAT Clinic Needs</h2>
    <p class="section-sub" data-i18n="feat_sub">Built specifically for Medication Assisted Treatment clinics — dispensing, records, reporting and compliance in one platform.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-syringe"></i></div><h3 data-i18n="f1_title">Automated Pump Dispensing</h3><p data-i18n="f1_desc">Masterflex pump integration for precise methadone dosing. Local and remote pump support with calibration management.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-users"></i></div><h3 data-i18n="f2_title">Client Management</h3><p data-i18n="f2_desc">Complete MAT client profiles — photos, dosage history, missed-day tracking, and comorbidity (HIV, TB, Hep-C) records.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-building-columns"></i></div><h3 data-i18n="f3_title">Prison Module</h3><p data-i18n="f3_desc">Sequential multi-day bulk dispensing for incarcerated patients with the automated pump — one date at a time.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-file-medical"></i></div><h3 data-i18n="f4_title">KHIS Reporting</h3><p data-i18n="f4_desc">Automated monthly report generation and direct posting to Kenya Health Information System (KHIS/DHIS2).</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-fingerprint"></i></div><h3 data-i18n="f5_title">Biometric Verification</h3><p data-i18n="f5_desc">Fingerprint-based patient identity verification before dispensing to prevent fraud and ensure safety.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-boxes-stacked"></i></div><h3 data-i18n="f6_title">Stock Management</h3><p data-i18n="f6_desc">Real-time drug inventory tracking, stock movement history, and low-stock alerts to prevent shortages.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-calendar-check"></i></div><h3 data-i18n="f7_title">Multi-Day Dispensing (MDD)</h3><p data-i18n="f7_desc">Dispense multiple doses for stable patients in one visit — with clinician-approved dose schedules.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa fa-chart-line"></i></div><h3 data-i18n="f8_title">Analytics &amp; Dashboards</h3><p data-i18n="f8_desc">Real-time dashboards for dispensing trends, missed days, appointment compliance, and stock consumption.</p></div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how">
  <div class="centered">
    <div class="section-label" data-i18n="how_label">Simple Process</div>
    <h2 class="section-title" data-i18n="how_title">How It Works</h2>
    <p class="section-sub" data-i18n="how_sub">From patient enrolment to KHIS reporting — a seamless end-to-end workflow.</p>
  </div>
  <div class="steps">
    <div class="step"><div class="step-num">1</div><h3 data-i18n="step1_title">Enrol Patient</h3><p data-i18n="step1_desc">Register the client with demographics, photo, drug &amp; dosage, comorbidities, and biometrics.</p></div>
    <div class="step"><div class="step-num">2</div><h3 data-i18n="step2_title">Clinician Review</h3><p data-i18n="step2_desc">Clinician sets dose schedule, updates medical history, and approves multi-day dispensing where applicable.</p></div>
    <div class="step"><div class="step-num">3</div><h3 data-i18n="step3_title">Daily Dispensing</h3><p data-i18n="step3_desc">Pharmacist searches the patient, verifies identity, and dispenses methadone via automated pump — one click.</p></div>
    <div class="step"><div class="step-num">4</div><h3 data-i18n="step4_title">Auto-Reporting</h3><p data-i18n="step4_desc">Monthly aggregates compile automatically and post to KHIS — no manual spreadsheet work required.</p></div>
  </div>
</section>

<!-- PRICING -->
<section id="pricing">
  <div class="centered">
    <div class="section-label" data-i18n="price_label">Transparent Pricing</div>
    <h2 class="section-title" data-i18n="price_title">Choose Your Plan</h2>
    <p class="section-sub" data-i18n="price_sub">Flexible plans for single clinics, networks, and government programmes. All plans include training and support.</p>
  </div>
  <div class="pricing-grid">
    <!-- Starter -->
    <div class="pricing-card">
      <div class="pricing-tier"  data-i18n="tier1">Starter</div>
      <div class="pricing-name"  data-i18n="tier1_name">Clinic Basic</div>
      <div class="pricing-desc"  data-i18n="tier1_desc">Ideal for single-site MAT clinics just getting started.</div>
      <div class="pricing-price"><span class="pricing-amount">$99</span><span class="pricing-period" data-i18n="per_month">/ month</span></div>
      <ul class="pricing-features">
        <li><i class="fa fa-check"></i><span data-i18n="pf_c50">Up to 50 active clients</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_pump">Pump dispensing</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_stock">Stock management</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_khis">KHIS reporting</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_email_sup">Email support</span></li>
        <li class="unavail"><i class="fa fa-times"></i><span data-i18n="pf_bio">Biometrics</span></li>
        <li class="unavail"><i class="fa fa-times"></i><span data-i18n="pf_prison">Prison module</span></li>
        <li class="unavail"><i class="fa fa-times"></i><span data-i18n="pf_multi">Multi-site</span></li>
      </ul>
      <button class="btn-pricing btn-pricing-outline" onclick="openModal()" data-i18n="btn_start">Get Started</button>
    </div>
    <!-- Pro (featured) -->
    <div class="pricing-card featured">
      <div class="pricing-badge" data-i18n="most_pop">Most Popular</div>
      <div class="pricing-tier"  data-i18n="tier2">Professional</div>
      <div class="pricing-name"  data-i18n="tier2_name">Clinic Pro</div>
      <div class="pricing-desc"  data-i18n="tier2_desc">For established clinics with larger caseloads and full features.</div>
      <div class="pricing-price"><span class="pricing-amount">$249</span><span class="pricing-period" data-i18n="per_month">/ month</span></div>
      <ul class="pricing-features">
        <li><i class="fa fa-check"></i><span data-i18n="pf_c200">Up to 200 active clients</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_pump">Pump dispensing</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_stock">Stock management</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_khis">KHIS reporting</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_bio">Biometric verification</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_prison">Prison module (MDD)</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_phone_sup">Phone &amp; email support</span></li>
        <li class="unavail"><i class="fa fa-times"></i><span data-i18n="pf_multi">Multi-site</span></li>
      </ul>
      <button class="btn-pricing btn-pricing-primary" onclick="openModal()" data-i18n="btn_start">Get Started</button>
    </div>
    <!-- Enterprise -->
    <div class="pricing-card">
      <div class="pricing-tier"  data-i18n="tier3">Enterprise</div>
      <div class="pricing-name"  data-i18n="tier3_name">Network / Government</div>
      <div class="pricing-desc"  data-i18n="tier3_desc">For county health programmes, NGOs, and multi-facility networks.</div>
      <div class="pricing-price"><span class="pricing-custom" data-i18n="price_custom">Custom</span></div>
      <ul class="pricing-features">
        <li><i class="fa fa-check"></i><span data-i18n="pf_unlim">Unlimited clients</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_pump">Pump dispensing</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_stock">Stock management</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_khis">KHIS reporting</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_bio">Biometric verification</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_prison">Prison module (MDD)</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_multi">Multi-site dashboard</span></li>
        <li><i class="fa fa-check"></i><span data-i18n="pf_sla">Dedicated support &amp; SLA</span></li>
      </ul>
      <button class="btn-pricing btn-pricing-outline" onclick="openModal()" data-i18n="btn_sales">Contact Sales</button>
    </div>
  </div>
  <p style="text-align:center;margin-top:22px;color:var(--muted);font-size:.83rem;" data-i18n="price_note">
    All prices in USD. Annual billing available with 20% discount. On-premise deployment available for government institutions.
  </p>
</section>

<!-- COMPLIANCE / CONTACT -->
<div class="compliance" id="contact">
  <h2 data-i18n="comp_title">Built for Regulatory Compliance</h2>
  <p data-i18n="comp_sub">IORPMS is designed around Kenya Ministry of Health standards and NACADA guidelines for MAT programme management.</p>
  <button class="btn-primary" onclick="openModal()" style="display:inline-flex;margin:0 auto;">
    <i class="fa fa-envelope"></i>&nbsp;<span data-i18n="comp_cta">Talk to Us</span>
  </button>
  <div class="compliance-logos">
    <span class="compliance-tag">KHIS / DHIS2</span>
    <span class="compliance-tag">MOH Kenya</span>
    <span class="compliance-tag">NACADA</span>
    <span class="compliance-tag">WHO MAT Guidelines</span>
    <span class="compliance-tag">Data Privacy</span>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand"><div class="footer-brand-icon">IO</div>IORPMS</div>
      <p data-i18n="footer_about">Integrated Outpatient Rehabilitation &amp; Pharmacy Management System. Built for MAT clinics across Africa.</p>
      <p style="font-size:.79rem;" data-i18n="footer_copy">&copy; 2025 IORPMS. All rights reserved.</p>
    </div>
    <div class="footer-col">
      <h4 data-i18n="footer_product">Product</h4>
      <ul>
        <li><a href="#features" data-i18n="nav_features">Features</a></li>
        <li><a href="#pricing"  data-i18n="nav_pricing">Pricing</a></li>
        <li><a href="#how"      data-i18n="nav_how">How It Works</a></li>
        <li><a href="public/login.php" data-i18n="nav_login">Login</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4 data-i18n="footer_support">Support</h4>
      <ul>
        <li><a href="mailto:support@iorpms.health">support@iorpms.health</a></li>
        <li><a href="#" onclick="openModal()" data-i18n="nav_demo">Request Demo</a></li>
        <li><a href="#" data-i18n="footer_docs">Documentation</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span data-i18n="footer_copy">&copy; 2025 IORPMS. All rights reserved.</span>
    <span style="display:flex;gap:14px;align-items:center;">
      <span data-i18n="footer_lang">Language:</span>
      <a href="#" onclick="setLang('en');return false;" style="color:rgba(255,255,255,.6)">English</a>
      <a href="#" onclick="setLang('fr');return false;" style="color:rgba(255,255,255,.6)">Français</a>
      <a href="#" onclick="setLang('pt');return false;" style="color:rgba(255,255,255,.6)">Português</a>
    </span>
  </div>
</footer>

<!-- DEMO REQUEST MODAL -->
<div class="modal-overlay" id="demoModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal()">&times;</button>
    <div class="modal-icon"><i class="fa fa-rocket"></i></div>
    <h2 data-i18n="modal_title">Request Demo Access</h2>
    <p class="subtitle" data-i18n="modal_sub">Fill in your details and we'll set up your account and send login credentials by email.</p>
    <div class="alert-box alert-success" id="formSuccess">
      <i class="fa fa-check-circle"></i>&nbsp;<span data-i18n="form_success">Request submitted! Check your email for next steps.</span>
    </div>
    <div class="alert-box alert-error" id="formError"></div>
    <form id="demoForm" onsubmit="submitDemo(event)">
      <div class="form-row">
        <div class="form-field"><label data-i18n="field_fname">First Name *</label><input type="text" name="first_name" required placeholder="John"></div>
        <div class="form-field"><label data-i18n="field_lname">Last Name *</label><input type="text" name="last_name" required placeholder="Doe"></div>
      </div>
      <div class="form-field"><label data-i18n="field_clinic">Clinic / Organisation *</label><input type="text" name="clinic_name" required placeholder="Nairobi MAT Clinic"></div>
      <div class="form-row">
        <div class="form-field"><label data-i18n="field_email">Email Address *</label><input type="email" name="email" required placeholder="you@clinic.org"></div>
        <div class="form-field"><label data-i18n="field_phone">Phone / WhatsApp *</label><input type="tel" name="phone" required placeholder="+254 700 000 000"></div>
      </div>
      <div class="form-field">
        <label data-i18n="field_country">Country *</label>
        <select name="country" required>
          <option value="" data-i18n="sel_country">Select country…</option>
          <option>Kenya</option><option>Tanzania</option><option>Uganda</option>
          <option>Ethiopia</option><option>Rwanda</option><option>Nigeria</option>
          <option>South Africa</option><option>Mozambique</option><option>Angola</option>
          <option>DRC</option><option>Other</option>
        </select>
      </div>
      <div class="form-field">
        <label data-i18n="field_plan">Plan of Interest</label>
        <select name="plan">
          <option value="starter"      data-i18n="tier1_name">Clinic Basic</option>
          <option value="professional" data-i18n="tier2_name" selected>Clinic Pro</option>
          <option value="enterprise"   data-i18n="tier3_name">Network / Government</option>
        </select>
      </div>
      <button type="submit" class="btn-submit" id="submitBtn">
        <i class="fa fa-paper-plane"></i>&nbsp;<span data-i18n="btn_submit">Submit Request</span>
      </button>
      <p class="form-note" data-i18n="form_note">We'll respond within 24 hours. Your data is never shared.</p>
    </form>
  </div>
</div>

<!-- i18n + JS -->
<script>
const T = {
  en:{
    nav_features:"Features",nav_how:"How It Works",nav_pricing:"Pricing",nav_contact:"Contact",
    nav_login:"Login",nav_demo:"Request Demo",
    hero_badge:"Trusted by MAT Clinics in Kenya",
    hero_h1:"The Smarter Way to<br><span style='color:#82b543'>Manage MAT Clinics</span>",
    hero_sub:"From automated methadone pump dispensing to KHIS monthly reporting — IORPMS handles your entire clinic workflow in one secure platform.",
    hero_cta1:"Request Demo / Access",hero_cta2:"Log In",
    stat_clients:"Clients Managed",stat_uptime:"Uptime",stat_khis:"KHIS Integrated",
    feat_label:"What We Offer",feat_title:"Everything Your MAT Clinic Needs",
    feat_sub:"Built specifically for Medication Assisted Treatment clinics — dispensing, records, reporting and compliance in one platform.",
    f1_title:"Automated Pump Dispensing",f1_desc:"Masterflex pump integration for precise methadone dosing. Local and remote pump support with calibration management.",
    f2_title:"Client Management",f2_desc:"Complete MAT client profiles — photos, dosage history, missed-day tracking, and comorbidity (HIV, TB, Hep-C) records.",
    f3_title:"Prison Module",f3_desc:"Sequential multi-day bulk dispensing for incarcerated patients with the automated pump — one date at a time.",
    f4_title:"KHIS Reporting",f4_desc:"Automated monthly report generation and direct posting to Kenya Health Information System (KHIS/DHIS2).",
    f5_title:"Biometric Verification",f5_desc:"Fingerprint-based patient identity verification before dispensing to prevent fraud and ensure safety.",
    f6_title:"Stock Management",f6_desc:"Real-time drug inventory tracking, stock movement history, and low-stock alerts to prevent shortages.",
    f7_title:"Multi-Day Dispensing (MDD)",f7_desc:"Dispense multiple doses for stable patients in one visit — with clinician-approved dose schedules.",
    f8_title:"Analytics & Dashboards",f8_desc:"Real-time dashboards for dispensing trends, missed days, appointment compliance, and stock consumption.",
    how_label:"Simple Process",how_title:"How It Works",
    how_sub:"From patient enrolment to KHIS reporting — a seamless end-to-end workflow.",
    step1_title:"Enrol Patient",step1_desc:"Register the client with demographics, photo, drug & dosage, comorbidities, and biometrics.",
    step2_title:"Clinician Review",step2_desc:"Clinician sets dose schedule, updates medical history, and approves multi-day dispensing.",
    step3_title:"Daily Dispensing",step3_desc:"Pharmacist searches the patient, verifies identity, and dispenses via automated pump — one click.",
    step4_title:"Auto-Reporting",step4_desc:"Monthly aggregates compile automatically and post to KHIS — no manual spreadsheet work.",
    price_label:"Transparent Pricing",price_title:"Choose Your Plan",
    price_sub:"Flexible plans for single clinics, networks, and government programmes. All plans include training and support.",
    tier1:"Starter",tier1_name:"Clinic Basic",tier1_desc:"Ideal for single-site MAT clinics just getting started.",
    tier2:"Professional",tier2_name:"Clinic Pro",tier2_desc:"For established clinics with larger caseloads and full features.",
    tier3:"Enterprise",tier3_name:"Network / Government",tier3_desc:"For county health programmes, NGOs, and multi-facility networks.",
    per_month:"/ month",price_custom:"Custom",most_pop:"Most Popular",
    pf_c50:"Up to 50 active clients",pf_c200:"Up to 200 active clients",pf_unlim:"Unlimited clients",
    pf_pump:"Pump dispensing",pf_stock:"Stock management",pf_khis:"KHIS reporting",
    pf_bio:"Biometric verification",pf_prison:"Prison module (MDD)",pf_multi:"Multi-site dashboard",
    pf_email_sup:"Email support",pf_phone_sup:"Phone & email support",pf_sla:"Dedicated support & SLA",
    btn_start:"Get Started",btn_sales:"Contact Sales",
    price_note:"All prices in USD. Annual billing available with 20% discount. On-premise deployment available for government institutions.",
    comp_title:"Built for Regulatory Compliance",
    comp_sub:"IORPMS is designed around Kenya Ministry of Health standards and NACADA guidelines for MAT programme management.",
    comp_cta:"Talk to Us",
    footer_about:"Integrated Outpatient Rehabilitation & Pharmacy Management System. Built for MAT clinics across Africa.",
    footer_copy:"© 2025 IORPMS. All rights reserved.",footer_product:"Product",footer_support:"Support",
    footer_docs:"Documentation",footer_lang:"Language:",
    modal_title:"Request Demo Access",
    modal_sub:"Fill in your details and we'll set up your account and send login credentials by email.",
    form_success:"Request submitted! Check your email for next steps.",
    field_fname:"First Name *",field_lname:"Last Name *",field_clinic:"Clinic / Organisation *",
    field_email:"Email Address *",field_phone:"Phone / WhatsApp *",
    field_country:"Country *",field_plan:"Plan of Interest",sel_country:"Select country…",
    btn_submit:"Submit Request",form_note:"We'll respond within 24 hours. Your data is never shared.",
  },
  fr:{
    nav_features:"Fonctionnalités",nav_how:"Comment ça marche",nav_pricing:"Tarifs",nav_contact:"Contact",
    nav_login:"Connexion",nav_demo:"Demander une démo",
    hero_badge:"Approuvé par les cliniques MAT au Kenya",
    hero_h1:"La meilleure façon de<br><span style='color:#82b543'>gérer les cliniques MAT</span>",
    hero_sub:"De la distribution automatisée par pompe à la déclaration mensuelle KHIS — IORPMS gère tout votre flux de travail clinique sur une plateforme sécurisée.",
    hero_cta1:"Demander un accès / démo",hero_cta2:"Se connecter",
    stat_clients:"Clients gérés",stat_uptime:"Disponibilité",stat_khis:"KHIS Intégré",
    feat_label:"Ce que nous offrons",feat_title:"Tout ce dont votre clinique MAT a besoin",
    feat_sub:"Conçu pour les cliniques de traitement assisté par médicaments — dispensation, dossiers, rapports et conformité sur une seule plateforme.",
    f1_title:"Distribution automatisée",f1_desc:"Intégration de pompe Masterflex pour un dosage précis de la méthadone. Support local et distant avec gestion de calibration.",
    f2_title:"Gestion des clients",f2_desc:"Profils complets — photos, historique de dosage, jours manqués, et dossiers de comorbidité (VIH, TB, Hépatite C).",
    f3_title:"Module pénitentiaire",f3_desc:"Distribution séquentielle multi-jours pour patients incarcérés via la pompe automatisée.",
    f4_title:"Rapports KHIS",f4_desc:"Génération automatique de rapports mensuels et envoi direct au système KHIS/DHIS2.",
    f5_title:"Vérification biométrique",f5_desc:"Identification par empreinte digitale avant chaque dispensation pour prévenir la fraude.",
    f6_title:"Gestion des stocks",f6_desc:"Suivi en temps réel des stocks, historique des mouvements et alertes de stock bas.",
    f7_title:"Distribution multi-jours (MDD)",f7_desc:"Dispensation de plusieurs doses en une visite pour patients stables — avec calendrier de dosage approuvé.",
    f8_title:"Analyses et tableaux de bord",f8_desc:"Tableaux de bord en temps réel pour tendances de distribution, jours manqués et consommation de stock.",
    how_label:"Processus simple",how_title:"Comment ça marche",
    how_sub:"De l'inscription du patient à la déclaration KHIS — un flux de travail intégré de bout en bout.",
    step1_title:"Inscrire le patient",step1_desc:"Enregistrez le client avec données démographiques, photo, médicament, dosage, comorbidités et biométrie.",
    step2_title:"Évaluation clinique",step2_desc:"Le clinicien définit le calendrier de dosage, met à jour les antécédents et approuve la distribution multi-jours.",
    step3_title:"Distribution quotidienne",step3_desc:"Le pharmacien recherche, vérifie et distribue la méthadone via la pompe automatisée en un clic.",
    step4_title:"Rapport automatique",step4_desc:"Les agrégats mensuels sont compilés et envoyés au KHIS automatiquement — sans feuille de calcul manuelle.",
    price_label:"Tarification transparente",price_title:"Choisissez votre forfait",
    price_sub:"Forfaits flexibles pour cliniques individuelles, réseaux et programmes gouvernementaux. Formation et support inclus.",
    tier1:"Débutant",tier1_name:"Clinique Basique",tier1_desc:"Idéal pour les cliniques MAT à site unique qui débutent.",
    tier2:"Professionnel",tier2_name:"Clinique Pro",tier2_desc:"Pour les cliniques établies avec charge de cas importante et besoins complets.",
    tier3:"Entreprise",tier3_name:"Réseau / Gouvernement",tier3_desc:"Pour programmes de santé du comté, ONG et réseaux multi-établissements.",
    per_month:"/ mois",price_custom:"Sur mesure",most_pop:"Le plus populaire",
    pf_c50:"Jusqu'à 50 clients actifs",pf_c200:"Jusqu'à 200 clients actifs",pf_unlim:"Clients illimités",
    pf_pump:"Distribution par pompe",pf_stock:"Gestion des stocks",pf_khis:"Rapports KHIS",
    pf_bio:"Vérification biométrique",pf_prison:"Module pénitentiaire (MDD)",pf_multi:"Tableau de bord multi-sites",
    pf_email_sup:"Support par email",pf_phone_sup:"Support téléphonique & email",pf_sla:"Support dédié & SLA",
    btn_start:"Commencer",btn_sales:"Contacter les ventes",
    price_note:"Prix en USD. Facturation annuelle disponible avec 20% de réduction. Déploiement sur site possible pour institutions gouvernementales.",
    comp_title:"Conçu pour la conformité réglementaire",
    comp_sub:"IORPMS est conçu selon les normes du Ministère de la Santé du Kenya et les directives NACADA pour la gestion des programmes MAT.",
    comp_cta:"Nous contacter",
    footer_about:"Système Intégré de Gestion de Pharmacie et Réhabilitation Ambulatoire. Conçu pour les cliniques MAT en Afrique.",
    footer_copy:"© 2025 IORPMS. Tous droits réservés.",footer_product:"Produit",footer_support:"Support",
    footer_docs:"Documentation",footer_lang:"Langue :",
    modal_title:"Demander un accès démo",
    modal_sub:"Remplissez vos informations et nous créerons votre compte et vous enverrons vos identifiants par email.",
    form_success:"Demande envoyée ! Vérifiez votre email pour les prochaines étapes.",
    field_fname:"Prénom *",field_lname:"Nom *",field_clinic:"Clinique / Organisation *",
    field_email:"Adresse email *",field_phone:"Téléphone / WhatsApp *",
    field_country:"Pays *",field_plan:"Forfait souhaité",sel_country:"Sélectionnez un pays…",
    btn_submit:"Soumettre la demande",form_note:"Réponse sous 24 heures. Vos données ne sont jamais partagées.",
  },
  pt:{
    nav_features:"Funcionalidades",nav_how:"Como Funciona",nav_pricing:"Preços",nav_contact:"Contato",
    nav_login:"Entrar",nav_demo:"Solicitar Demo",
    hero_badge:"Confiado por clínicas MAT no Quénia",
    hero_h1:"A forma mais inteligente de<br><span style='color:#82b543'>gerir clínicas MAT</span>",
    hero_sub:"Da distribuição automatizada por bomba ao relatório mensal KHIS — o IORPMS gere todo o fluxo de trabalho da sua clínica numa plataforma segura.",
    hero_cta1:"Solicitar Demo / Acesso",hero_cta2:"Entrar",
    stat_clients:"Clientes Geridos",stat_uptime:"Disponibilidade",stat_khis:"KHIS Integrado",
    feat_label:"O que oferecemos",feat_title:"Tudo o que a sua clínica MAT precisa",
    feat_sub:"Criado para clínicas de Tratamento Assistido por Medicação — dispensação, registos, relatórios e conformidade numa só plataforma.",
    f1_title:"Dispensação automática por bomba",f1_desc:"Integração com bomba Masterflex para dosagem precisa de metadona. Suporte local e remoto com gestão de calibração.",
    f2_title:"Gestão de clientes",f2_desc:"Perfis completos — fotos, histórico de dosagem, rastreamento de dias perdidos, e registos de comorbilidades (VIH, TB, Hep-C).",
    f3_title:"Módulo prisional",f3_desc:"Dispensação em massa sequencial por múltiplos dias para pacientes encarcerados com a bomba automatizada.",
    f4_title:"Relatórios KHIS",f4_desc:"Geração automática de relatórios mensais e envio direto para o KHIS/DHIS2.",
    f5_title:"Verificação biométrica",f5_desc:"Identificação por impressão digital antes de cada dispensação para prevenir fraudes e garantir segurança.",
    f6_title:"Gestão de stock",f6_desc:"Rastreamento de inventário em tempo real, histórico de movimentos de stock e alertas de stock baixo.",
    f7_title:"Dispensação multi-dia (MDD)",f7_desc:"Distribuição de múltiplas doses para pacientes estáveis numa única visita — com horários de dosagem aprovados.",
    f8_title:"Análises e dashboards",f8_desc:"Dashboards em tempo real para tendências de dispensação, dias perdidos e relatórios de consumo de stock.",
    how_label:"Processo simples",how_title:"Como Funciona",
    how_sub:"Da inscrição do paciente ao relatório KHIS — um fluxo de trabalho integrado de ponta a ponta.",
    step1_title:"Inscrever paciente",step1_desc:"Registe o cliente com dados demográficos, foto, medicamento, dosagem, comorbilidades e biometria.",
    step2_title:"Avaliação clínica",step2_desc:"O clínico define o calendário de dosagem, atualiza o histórico médico e aprova a dispensação multi-dia.",
    step3_title:"Dispensação diária",step3_desc:"O farmacêutico pesquisa o paciente, verifica a identidade e dispensa metadona via bomba automatizada — um clique.",
    step4_title:"Relatório automático",step4_desc:"Os agregados mensais compilam-se automaticamente e são enviados ao KHIS — sem trabalho manual.",
    price_label:"Preços transparentes",price_title:"Escolha o seu plano",
    price_sub:"Planos flexíveis para clínicas individuais, redes e programas governamentais. Todos incluem formação e suporte.",
    tier1:"Básico",tier1_name:"Clínica Básica",tier1_desc:"Ideal para clínicas MAT de local único que estão a começar.",
    tier2:"Profissional",tier2_name:"Clínica Pro",tier2_desc:"Para clínicas estabelecidas com maior carga de casos e funcionalidades completas.",
    tier3:"Empresarial",tier3_name:"Rede / Governo",tier3_desc:"Para programas de saúde do condado, ONGs e redes multifacilidades.",
    per_month:"/ mês",price_custom:"Personalizado",most_pop:"Mais Popular",
    pf_c50:"Até 50 clientes ativos",pf_c200:"Até 200 clientes ativos",pf_unlim:"Clientes ilimitados",
    pf_pump:"Dispensação por bomba",pf_stock:"Gestão de stock",pf_khis:"Relatórios KHIS",
    pf_bio:"Verificação biométrica",pf_prison:"Módulo prisional (MDD)",pf_multi:"Dashboard multi-site",
    pf_email_sup:"Suporte por email",pf_phone_sup:"Suporte telefônico & email",pf_sla:"Suporte dedicado & SLA",
    btn_start:"Começar",btn_sales:"Contactar vendas",
    price_note:"Preços em USD. Faturação anual disponível com 20% de desconto. Implantação no local disponível para instituições governamentais.",
    comp_title:"Criado para conformidade regulatória",
    comp_sub:"O IORPMS foi concebido de acordo com as normas do Ministério da Saúde do Quénia e diretrizes NACADA para gestão de programas MAT.",
    comp_cta:"Fale connosco",
    footer_about:"Sistema Integrado de Gestão de Farmácia e Reabilitação Ambulatória. Criado para clínicas MAT em África.",
    footer_copy:"© 2025 IORPMS. Todos os direitos reservados.",footer_product:"Produto",footer_support:"Suporte",
    footer_docs:"Documentação",footer_lang:"Idioma:",
    modal_title:"Solicitar acesso de demonstração",
    modal_sub:"Preencha os seus dados e criaremos a sua conta e enviaremos as credenciais de acesso por email.",
    form_success:"Pedido enviado! Verifique o seu email para os próximos passos.",
    field_fname:"Nome *",field_lname:"Apelido *",field_clinic:"Clínica / Organização *",
    field_email:"Endereço de email *",field_phone:"Telefone / WhatsApp *",
    field_country:"País *",field_plan:"Plano de interesse",sel_country:"Selecione um país…",
    btn_submit:"Enviar pedido",form_note:"Responderemos em 24 horas. Os seus dados nunca são partilhados.",
  }
};

let lang = localStorage.getItem('iorpms_lang') || 'en';

function setLang(l) {
  lang = l;
  localStorage.setItem('iorpms_lang', l);
  document.querySelectorAll('.lang-btn').forEach(b =>
    b.classList.toggle('active', b.textContent.trim().toLowerCase() === l));
  applyT();
}

function applyT() {
  const t = T[lang] || T.en;
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const v = t[el.getAttribute('data-i18n')];
    if (v !== undefined) el.innerHTML = v;
  });
  document.documentElement.lang = lang;
}

function openModal()  { document.getElementById('demoModal').classList.add('open'); }
function closeModal() { document.getElementById('demoModal').classList.remove('open'); }
document.getElementById('demoModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

function submitDemo(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending…';
  const fd = new FormData(document.getElementById('demoForm'));
  fd.append('lang', lang);
  fetch('demo_request.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('formSuccess').style.display = 'block';
        document.getElementById('formError').style.display   = 'none';
        document.getElementById('demoForm').reset();
      } else {
        document.getElementById('formError').style.display   = 'block';
        document.getElementById('formError').textContent     = res.message || 'Something went wrong.';
        document.getElementById('formSuccess').style.display = 'none';
      }
    })
    .catch(() => {
      document.getElementById('formError').style.display = 'block';
      document.getElementById('formError').textContent   = 'Network error. Please try again.';
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-paper-plane"></i>&nbsp;<span data-i18n="btn_submit">Submit Request</span>';
      applyT();
    });
}

function toggleNav() {
  const nl = document.getElementById('navLinks');
  const open = nl.style.display === 'flex';
  Object.assign(nl.style, open
    ? {display:'none'}
    : {display:'flex',flexDirection:'column',position:'absolute',top:'68px',
       left:'0',right:'0',background:'#1a1d3d',padding:'16px 6%',gap:'16px',zIndex:'999'});
}

window.addEventListener('scroll', () => {
  document.getElementById('navbar').style.boxShadow =
    window.scrollY > 10 ? '0 4px 24px rgba(0,0,0,.4)' : '0 2px 20px rgba(0,0,0,.25)';
});

applyT();
</script>
</body>
</html>
