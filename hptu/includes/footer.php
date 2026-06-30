<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <style>
       
        .footer {
            background: linear-gradient(135deg, #011f88 0%, #1a399f 100%);
            color: white;
            padding: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            border-top: 3px solid #B2CCFF;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            width: 100%;
            padding: 0 20px;
            gap: 20px;
        }

        .footer-text {
            font-family: inherit;
            font-size: 16px;
            line-height: 1.4;
            text-align: center;
            flex: 1;
        }

        .social-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-shrink: 0;
        }

        .social-links a {
            color: white;
            font-size: 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .social-links a:hover {
            transform: translateY(-3px) scale(1.1);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Specific hover colors for each platform */
        .social-links a:nth-child(1):hover { background: #1877F2; } /* Facebook */
        .social-links a:nth-child(2):hover { background: #FF0000; } /* YouTube */
        .social-links a:nth-child(3):hover { background: #1DA1F2; } /* Twitter */
        .social-links a:nth-child(4):hover { background: #E4405F; } /* Instagram */
        .social-links a:nth-child(5):hover { background: #0A66C2; } /* LinkedIn */

        /* Large Desktop Styles */
        @media screen and (min-width: 1201px) {
            .footer-content {
                padding: 0 40px;
            }
        }

        /* Desktop Styles */
        @media screen and (max-width: 1200px) {
            .footer-content {
                max-width: 100%;
                padding: 0 30px;
            }
        }

        /* Tablet Styles */
        @media screen and (max-width: 1024px) {
            .footer-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .footer-text {
                font-size: 15px;
                order: 2;
            }

            .social-links {
                order: 1;
                gap: 12px;
            }

            .social-links a {
                width: 38px;
                height: 38px;
                font-size: 18px;
            }

            body {
                padding-bottom: 100px; /* More space for taller footer */
            }
        }

        /* Small Tablet Styles */
        @media screen and (max-width: 768px) {
            .footer {
                padding: 15px 0;
            }

            .footer-content {
                padding: 0 20px;
                gap: 12px;
            }

            .footer-text {
                font-size: 14px;
                line-height: 1.3;
            }

            .social-links {
                gap: 10px;
            }

            .social-links a {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            body {
                padding-bottom: 90px;
            }
        }

        /* Mobile Styles */
        @media screen and (max-width: 600px) {
            .footer {
                padding: 12px 0;
            }

            .footer-content {
                padding: 0 15px;
                gap: 10px;
            }

            .footer-text {
                font-size: 13px;
                line-height: 1.2;
            }

            .social-links {
                gap: 8px;
            }

            .social-links a {
                width: 34px;
                height: 34px;
                font-size: 15px;
            }

            body {
                padding-bottom: 80px;
            }
        }

        /* Small Mobile Styles */
        @media screen and (max-width: 480px) {
            .footer {
                padding: 10px 0;
            }

            .footer-content {
                padding: 0 10px;
                gap: 8px;
            }

            .footer-text {
                font-size: 12px;
            }

            .social-links {
                gap: 6px;
            }

            .social-links a {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            body {
                padding-bottom: 70px;
            }
        }

        /* Extra Small Mobile Styles */
        @media screen and (max-width: 360px) {
            .footer-text {
                font-size: 11px;
            }

            .social-links {
                gap: 4px;
            }

            .social-links a {
                width: 30px;
                height: 30px;
                font-size: 13px;
            }
        }

        /* Print Styles */
        @media print {
            .footer {
                display: none;
            }

            body {
                padding-bottom: 0;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .footer {
                background: #000;
                border-top: 3px solid #fff;
            }

            .social-links a {
                background: #333;
                border: 1px solid #fff;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .social-links a {
                transition: none;
            }

            .social-links a:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <!-- Your content goes here -->
    <div class="main-content">
        <!-- Page content will be here -->
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-text">
                Health Products and Technologies Unit (HPTU)&nbsp;<?php echo date('Y');?> - &copy; Supported by USAID Stawisha Pwani - LVCT Health
            </div>
            <div class="social-links">
                <a href="https://web.facebook.com/MombasaHealth/?_rdc=1&_rdr#" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Visit our Facebook page">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://www.youtube.com/@health.mombasa/" target="_blank" rel="noopener noreferrer" title="YouTube" aria-label="Visit our YouTube channel">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://x.com/DOHMombasa/" target="_blank" rel="noopener noreferrer" title="X (Twitter)" aria-label="Visit our X (Twitter) page">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.instagram.com/health.mombasa/?hl=en" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Visit our Instagram page">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.linkedin.com/company/department-of-health-services-mombasa-county/posts/?feedView=all" target="_blank" rel="noopener noreferrer" title="LinkedIn" aria-label="Visit our LinkedIn page">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Optional: Add intersection observer to hide footer when content is too short
        document.addEventListener('DOMContentLoaded', function() {
            const footer = document.querySelector('.footer');
            const mainContent = document.querySelector('.main-content');

            if (footer && mainContent) {
                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                footer.style.position = 'relative';
                            } else {
                                footer.style.position = 'fixed';
                            }
                        });
                    },
                    {
                        rootMargin: '-100px 0px 0px 0px',
                        threshold: 0
                    }
                );

                observer.observe(mainContent);
            }

            // Add loading state for social links
            const socialLinks = document.querySelectorAll('.social-links a');
            socialLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Optional: Add analytics tracking here
                    console.log('Social link clicked:', this.href);
                });
            });
        });
    </script>
</body>
</html>