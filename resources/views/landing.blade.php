<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="DEORIS Portal is the Deor & Dune Academe Inc. Information System for secure, role-based school operations.">
  <meta name="theme-color" content="#7C3041">
  <title>DEORIS Portal | Deor &amp; Dune Academe Inc.</title>
  <link rel="icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6">
  <link rel="shortcut icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css" crossorigin="anonymous">
  <link rel="stylesheet" href="{{ asset('landing/index.css') }}?v={{ filemtime(public_path('landing/index.css')) }}">
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <!-- PAGE LOADER -->
  <div id="pageLoader" class="page-loader" aria-busy="true" aria-live="polite">
    <div class="loader-inner">
      <img src="{{ asset('landing/assets/images/logo.png') }}" alt="" width="72" height="72" class="loader-logo" decoding="async">
      <p class="loader-text">DEORIS Portal</p>
      <div class="loader-bar"><div class="loader-bar-fill"></div></div>
    </div>
  </div>

  <!-- NAVBAR -->
  <header class="navbar" id="navbar" role="banner">
    <div class="nav-inner">
      <a href="#home" class="nav-brand" aria-label="DEORIS Portal home">
        <img src="{{ asset('landing/assets/images/logo.png') }}" alt="DEORIS Portal" class="nav-logo" width="40" height="40" decoding="async">
        <div class="nav-brand-text">
          <span class="nav-brand-name">DEORIS Portal</span>
          <span class="nav-brand-sub">Deor &amp; Dune Academe Inc.</span>
        </div>
      </a>

      <nav aria-label="Main navigation">
        <ul class="nav-links" id="navLinks" role="list">
          <li><a href="#home" class="nav-link">Home</a></li>
          <li><a href="#programs" class="nav-link">Programs</a></li>
          <li><a href="#features" class="nav-link">Features</a></li>
          <li><a href="#testimonials" class="nav-link">Community</a></li>
          <li><a href="#faq" class="nav-link">FAQ</a></li>
          <li class="nav-mobile-auth"><a href="/login" class="nav-link nav-link--login">Log In</a></li>
          <li class="nav-mobile-auth"><a href="/register" class="nav-link nav-link--signup"><i class="fas fa-user-plus" aria-hidden="true"></i> Sign Up</a></li>
        </ul>
      </nav>

      <div class="nav-actions">
        <a href="/login" class="btn-ghost-nav">Log In</a>
        <a href="/register" class="btn-gold-nav">
          <i class="fas fa-user-plus" aria-hidden="true"></i> Sign Up
        </a>
      </div>

      <div class="nav-mobile-pills" aria-label="Account links">
        <a href="/login" class="nav-pill nav-pill--login">
          <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
          <span>Login</span>
        </a>
      </div>

      <button class="hamburger" id="mobileMenuBtn" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu" type="button">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <main id="main-content">

    <!-- HERO -->
    <section class="hero" id="home">
      <div class="hero-bg">
        <div class="hero-bg-img" style="background-image: url('{{ asset('landing/assets/images/background.jpg') }}');"></div>
        <div class="hero-bg-overlay"></div>
        <div class="hero-particles" aria-hidden="true">
          <span></span><span></span><span></span><span></span>
          <span></span><span></span><span></span><span></span>
        </div>
      </div>

      <div class="hero-body">
        <div class="hero-eyebrow" data-aos="fade-down" data-aos-duration="700">
          <i class="fas fa-shield-halved"></i>
          <span>Est. 2026 &nbsp;&bull;&nbsp; Academic Excellence &nbsp;&bull;&nbsp; Philippines</span>
        </div>

        <h1 class="hero-title" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
          DEORIS Portal for<br><em>School Operations</em>
        </h1>

        <p class="hero-desc" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
          DEORIS Portal is the integrated information system for Deor &amp; Dune Academe Inc., bringing together enrollment, academic records, grading, payments, library, health, voting, and administrative workflows in one secure, role-based environment.
        </p>

        <div class="hero-cta-row" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
          <a href="#features" class="btn-hero-primary">
            <i class="fas fa-rocket"></i> Explore the System
          </a>
          <a href="#programs" class="btn-hero-ghost">
            <i class="fas fa-play-circle"></i> Watch Demo
          </a>
        </div>

        <div class="hero-stats" data-aos="fade-up" data-aos-duration="800" data-aos-delay="420">
          <div class="hero-stat"><strong>10+</strong><span>Modules</span></div>
          <div class="hero-stat-divider" aria-hidden="true"></div>
          <div class="hero-stat"><strong>99.9%</strong><span>Uptime</span></div>
          <div class="hero-stat-divider" aria-hidden="true"></div>
          <div class="hero-stat"><strong>24/7</strong><span>Support</span></div>
          <div class="hero-stat-divider" aria-hidden="true"></div>
          <div class="hero-stat"><strong>100%</strong><span>Secure</span></div>
        </div>
      </div>

      <a href="#programs" class="hero-scroll-hint" aria-label="Scroll down">
        <i class="fas fa-chevron-down"></i>
      </a>
    </section>

    <!-- TRUST BAR -->
    <div class="trust-bar">
      <div class="trust-inner">
        <span class="trust-label">Trusted by</span>
        <div class="trust-items">
          <span><i class="fas fa-university"></i> Academic Institutions</span>
          <span><i class="fas fa-chalkboard-teacher"></i> Faculty &amp; Staff</span>
          <span><i class="fas fa-user-graduate"></i> Students &amp; Parents</span>
          <span><i class="fas fa-building"></i> School Administrators</span>
        </div>
      </div>
    </div>

    <!-- PROGRAMS / VIDEO + GALLERY -->
    <section class="section-programs" id="programs">
      <div class="container">
        <div class="section-tag" data-aos="fade-up">Platform Overview</div>
        <div class="programs-grid">

          <div class="programs-left" data-aos="fade-right" data-aos-duration="800">
            <h2 class="section-heading">Centralized Operations.<br><span>Across Every Department.</span></h2>
            <p class="section-body">DEORIS combines EntryEase, EnrollEase, GradeTrack, AssessPay, ClearCheck, LibrarySys, MediTrack, VoteSys, and TaskFlow in one managed environment designed for educational institutions.</p>

            <ul class="programs-checklist">
              <li><i class="fas fa-check-circle"></i> Real-time updates across student services, records, and administration</li>
              <li><i class="fas fa-check-circle"></i> Role-based dashboards for administrators, faculty, staff, students, and parents</li>
              <li><i class="fas fa-check-circle"></i> Structured workflows for clearances, grading, payments, and reporting</li>
              <li><i class="fas fa-check-circle"></i> Secure, modular infrastructure built for institutional use</li>
            </ul>

            <figure class="video-card" aria-label="DEORIS product demo video">
              <div class="video-wrap">
                <video controls preload="metadata" playsinline
                  poster="{{ asset('landing/assets/images/background.jpg') }}"
                  aria-label="DEORIS Centralized product video">
                  <source src="{{ asset('landing/assets/videos/main.mp4') }}" type="video/mp4">
                  Your browser does not support the video element.
                </video>
              </div>
              <figcaption>DEORIS Centralized — unified school information in one secure experience.</figcaption>
            </figure>
          </div>

          <div class="programs-right" id="gallery" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">
            <div class="gallery-header">
              <h3>Campus Gallery</h3>
              <p>Tap any image to enlarge</p>
            </div>
            <div class="gallery-grid">
              <figure class="gallery-card gallery-card--tall">
                <img src="{{ asset('landing/assets/images/support.jpg') }}" alt="Support desk and student services" loading="lazy" decoding="async">
                <figcaption><i class="fas fa-school"></i> Grades 7–12 · TVL Track</figcaption>
              </figure>
              <figure class="gallery-card">
                <img src="{{ asset('landing/assets/images/support1.jpg') }}" alt="Guidance and assistance" loading="lazy" decoding="async">
                <figcaption><i class="fas fa-clipboard-list"></i> Registration</figcaption>
              </figure>
              <figure class="gallery-card">
                <img src="{{ asset('landing/assets/images/backround2.jpg') }}" alt="Academic environment" loading="lazy" decoding="async">
                <figcaption><i class="fas fa-star"></i> Visual Identity</figcaption>
              </figure>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="section-features" id="features">
      <div class="container">
        <div class="section-tag" data-aos="fade-up">Why DEORIS</div>
        <h2 class="section-heading centered" data-aos="fade-up" data-aos-delay="60">
          Built for the Way<br><span>Educational Institutions Operate</span>
        </h2>
        <p class="section-body centered" data-aos="fade-up" data-aos-delay="100">
          Every feature is designed around academic and administrative workflows used in real school operations.
        </p>
        <div class="features-grid">
          <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
            <div class="feature-card-icon"><i class="fas fa-robot"></i></div>
            <h3>Process Automation</h3>
            <p>Automates recurring operations across admissions, grading, clearances, payments, and service requests so staff can work with greater consistency and speed.</p>
            <div class="feature-card-tag">Efficiency</div>
          </div>
          <div class="feature-card" data-aos="fade-up" data-aos-delay="60">
            <div class="feature-card-icon"><i class="fas fa-database"></i></div>
            <h3>Centralized Records</h3>
            <p>Maintains a single source of truth for student, academic, financial, and operational data with synchronized updates across modules.</p>
            <div class="feature-card-tag">Records</div>
          </div>
          <div class="feature-card" data-aos="fade-up" data-aos-delay="120">
            <div class="feature-card-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Role-Based Access</h3>
            <p>Assigns permissions by role so administrators, faculty, staff, students, and parents access the information relevant to their responsibilities.</p>
            <div class="feature-card-tag">Security</div>
          </div>
          <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
            <div class="feature-card-icon"><i class="fas fa-expand-arrows-alt"></i></div>
            <h3>Scalable Architecture</h3>
            <p>Supports institutional growth with modular deployment, allowing departments to expand their use of DEORIS without disrupting core operations.</p>
            <div class="feature-card-tag">Infrastructure</div>
          </div>
          <div class="feature-card" data-aos="fade-up" data-aos-delay="60">
            <div class="feature-card-icon"><i class="fas fa-mobile-alt"></i></div>
            <h3>Accessible on Any Device</h3>
            <p>Delivers reliable access across desktop, tablet, and mobile devices so school services remain available wherever users need them.</p>
            <div class="feature-card-tag">Accessibility</div>
          </div>
          <div class="feature-card" data-aos="fade-up" data-aos-delay="120">
            <div class="feature-card-icon"><i class="fas fa-chart-pie"></i></div>
            <h3>Operational Reporting</h3>
            <p>Provides dashboards, summaries, and status tracking to help leadership monitor performance, service levels, and institutional activity.</p>
            <div class="feature-card-tag">Insights</div>
          </div>
        </div>
      </div>
    </section>

    <!-- METRICS BANNER -->
    <div class="metrics-banner">
      <div class="container">
        <div class="metrics-grid">
          <div class="metric-item" data-aos="zoom-in" data-aos-delay="0">
            <strong class="metric-num" data-target="10">0</strong><span class="metric-plus">+</span>
            <p>System Modules</p>
          </div>
          <div class="metric-item" data-aos="zoom-in" data-aos-delay="80">
            <strong class="metric-num" data-target="80">0</strong><span class="metric-plus">%</span>
            <p>Less Manual Work</p>
          </div>
          <div class="metric-item" data-aos="zoom-in" data-aos-delay="160">
            <strong class="metric-num" data-target="99">0</strong><span class="metric-plus">.9% Uptime</span>
            <p>Guaranteed SLA</p>
          </div>
          <div class="metric-item" data-aos="zoom-in" data-aos-delay="240">
            <strong class="metric-num" data-target="24">0</strong><span class="metric-plus">/7</span>
            <p>Technical Support</p>
          </div>
        </div>
      </div>
    </div>

    <!-- TESTIMONIALS -->
    <section class="section-testimonials" id="testimonials">
      <div class="container">
        <div class="section-tag light" data-aos="fade-up">Community</div>
        <h2 class="section-heading centered light" data-aos="fade-up" data-aos-delay="60">
          Trusted by the<br><span>Academic Community</span>
        </h2>
        <p class="section-body centered light" data-aos="fade-up" data-aos-delay="100">
          Hear directly from administrators, faculty, and students who use DEORIS every day.
        </p>
        <div class="testimonials-grid">
          <div class="testimonial-card" data-aos="fade-up" data-aos-delay="0">
            <div class="testimonial-quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text">DEORIS helped us consolidate student records, enrollment, and department workflows into one dependable system. It improved visibility and reduced manual processing across the school.</p>
            <div class="testimonial-footer">
              <div class="testimonial-avatar">DR</div>
              <div class="testimonial-meta"><strong>Dr. Rebecca Santos</strong><span>School Administrator</span></div>
              <div class="testimonial-stars" aria-label="5 out of 5 stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <div class="testimonial-card testimonial-card--featured" data-aos="fade-up" data-aos-delay="80">
            <div class="testimonial-quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text">GradeTrack and TaskFlow gave faculty a clearer way to monitor academic progress and class responsibilities without the usual paperwork burden.</p>
            <div class="testimonial-footer">
              <div class="testimonial-avatar">JM</div>
              <div class="testimonial-meta"><strong>Prof. James Mitchell</strong><span>Senior Faculty Member</span></div>
              <div class="testimonial-stars" aria-label="5 out of 5 stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <div class="testimonial-card" data-aos="fade-up" data-aos-delay="160">
            <div class="testimonial-quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text">EnrollEase made registration faster and easier to follow, which improved the student experience and reduced processing delays for the office.</p>
            <div class="testimonial-footer">
              <div class="testimonial-avatar">AC</div>
              <div class="testimonial-meta"><strong>Amanda Cruz</strong><span>Student, Class of 2026</span></div>
              <div class="testimonial-stars" aria-label="4.5 out of 5 stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="section-faq" id="faq">
      <div class="container">
        <div class="faq-layout">
          <div class="faq-left" data-aos="fade-right">
            <div class="section-tag">FAQ</div>
            <h2 class="section-heading">Got Questions?<br><span>We Have Answers.</span></h2>
            <p class="section-body">Everything you need to know about DEORIS and how it supports secure, organized school operations.</p>
            <a href="mailto:info@deoris.edu" class="btn-outline-primary">
              <i class="fas fa-envelope"></i> Contact Support
            </a>
          </div>
          <div class="faq-right" data-aos="fade-left" data-aos-delay="100">
            <div class="accordion-list">
              <div class="accordion-item">
                <button class="accordion-header" type="button" aria-expanded="false">
                  <span>What is DEORIS?</span><i class="fas fa-plus accordion-icon" aria-hidden="true"></i>
                </button>
                <div class="accordion-body">
                  <p>DEORIS is a modular school information system built for educational institutions. It centralizes administrative, academic, financial, and student-service workflows into a unified platform.</p>
                </div>
              </div>
              <div class="accordion-item">
                <button class="accordion-header" type="button" aria-expanded="false">
                  <span>Is DEORIS secure?</span><i class="fas fa-plus accordion-icon" aria-hidden="true"></i>
                </button>
                <div class="accordion-body">
                  <p>Yes. DEORIS is designed with role-based access, controlled workflows, and protected data handling so academic and personal records remain secured within institutional operations.</p>
                </div>
              </div>
              <div class="accordion-item">
                <button class="accordion-header" type="button" aria-expanded="false">
                  <span>Can DEORIS be customized for our institution?</span><i class="fas fa-plus accordion-icon" aria-hidden="true"></i>
                </button>
                <div class="accordion-body">
                  <p>Yes. DEORIS is modular, allowing institutions to configure workflows, enable relevant modules, and align system usage with existing policies and operational needs.</p>
                </div>
              </div>
              <div class="accordion-item">
                <button class="accordion-header" type="button" aria-expanded="false">
                  <span>What kind of support is available?</span><i class="fas fa-plus accordion-icon" aria-hidden="true"></i>
                </button>
                <div class="accordion-body">
                  <p>Support is available through formal contact channels, with assistance structured around implementation, operational use, and system maintenance so institutions can continue running efficiently.</p>
                </div>
              </div>
              <div class="accordion-item">
                <button class="accordion-header" type="button" aria-expanded="false">
                  <span>How long does implementation take?</span><i class="fas fa-plus accordion-icon" aria-hidden="true"></i>
                </button>
                <div class="accordion-body">
                  <p>Implementation depends on institutional size, module selection, and data migration requirements. The deployment process is structured in phases to minimize disruption and support a stable rollout.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA BANNER -->
    <section class="cta-banner" id="contact">
      <div class="cta-banner-bg" aria-hidden="true"></div>
      <div class="container">
        <div class="cta-banner-inner" data-aos="zoom-in" data-aos-duration="700">
          <div class="cta-banner-icon"><i class="fas fa-graduation-cap"></i></div>
          <h2>Ready to Modernize Your School Operations?</h2>
          <p>Join institutions that rely on DEORIS to manage academic records, student services, and administrative workflows in one secure system.</p>
          <div class="cta-banner-actions">
            <a href="/register" class="btn-cta-primary"><i class="fas fa-rocket"></i> Get Started Today</a>
            <a href="/login" class="btn-cta-ghost"><i class="fas fa-sign-in-alt"></i> Log In</a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- LIGHTBOX -->
  <div class="lightbox-modal" id="lightboxModal" role="dialog" aria-modal="true" aria-labelledby="lightboxCaption">
    <div class="lightbox-overlay" id="lightboxOverlay"></div>
    <div class="lightbox-container">
      <button class="lightbox-close" id="lightboxClose" type="button" aria-label="Close lightbox">
        <i class="fas fa-times"></i>
      </button>
      <div class="lightbox-content">
        <img id="lightboxImage" src="" alt="" class="lightbox-image">
        <p class="lightbox-caption" id="lightboxCaption"></p>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="footer" id="footer">
    <div class="footer-top">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-col footer-brand-col">
            <div class="footer-brand">
              <img src="{{ asset('landing/assets/images/logo.png') }}" alt="DEORIS Portal" width="44" height="44" decoding="async">
              <span>DEORIS Portal</span>
            </div>
            <p>Deor &amp; Dune Academe Inc. Information System for secure, role-based academic operations.</p>
            <address class="footer-address">
              <i class="fas fa-map-marker-alt"></i>
              123 Academic Avenue, Education City, Philippines
            </address>
            <div class="footer-socials">
              <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
              <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
              <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
              <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
          </div>
          <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
              <li><a href="#home">Home</a></li>
              <li><a href="#programs">Programs</a></li>
              <li><a href="#features">Features</a></li>
              <li><a href="#gallery">Gallery</a></li>
              <li><a href="#testimonials">Community</a></li>
              <li><a href="#faq">FAQ</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Contact</h4>
            <ul class="footer-contact-list">
              <li><i class="fas fa-envelope"></i><a href="mailto:info@deoris.edu">info@deoris.edu</a></li>
              <li><i class="fas fa-phone"></i><a href="tel:+1234567890">+1 (234) 567-890</a></li>
              <li><i class="fas fa-clock"></i><span>Mon – Fri, 8:00 AM – 5:00 PM</span></li>
              <li><i class="fas fa-headset"></i><span>24/7 Technical Support</span></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; {{ date('Y') }} <strong>Deor &amp; Dune Academe Inc.</strong> All rights reserved. &nbsp;|&nbsp; DEORIS Portal</p>
      </div>
    </div>
  </footer>

  <!-- FLOATING BUTTONS -->
  <button class="fab-scroll" id="scrollTop" type="button" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
  </button>
  <button class="fab-support" id="customerServicesToggle" type="button" aria-label="Customer Support">
    <i class="fas fa-headset"></i>
    <span class="fab-support-label">Support</span>
  </button>

  <!-- SUPPORT DRAWER -->
  <div class="cs-backdrop" id="csBackdrop"></div>
  <aside class="cs-drawer" id="csDrawer" role="dialog" aria-modal="true" aria-label="Customer support">
    <div class="cs-drawer-head">
      <div class="cs-drawer-title"><i class="fas fa-headset"></i> Customer Support</div>
      <button class="cs-close" id="csClose" type="button" aria-label="Close support panel"><i class="fas fa-times"></i></button>
    </div>
    <div class="cs-drawer-body">
      <p class="cs-intro">Send us your concern and our team will respond as soon as possible.</p>
      <form class="cs-form" id="csForm" novalidate>
        <div class="cs-field">
          <label for="csFullName">Full Name <span aria-hidden="true">*</span></label>
          <input type="text" id="csFullName" name="fullName" required placeholder="Your full name">
          <span class="cs-error" id="csFullNameError"></span>
        </div>
        <div class="cs-field">
          <label for="csEmail">Email <span aria-hidden="true">*</span></label>
          <input type="email" id="csEmail" name="email" required placeholder="name@example.com">
          <span class="cs-error" id="csEmailError"></span>
        </div>
        <div class="cs-field">
          <label for="csSubject">Subject <span aria-hidden="true">*</span></label>
          <select id="csSubject" name="subject" required>
            <option value="">Select a subject</option>
            <option value="demo">Schedule a Demo</option>
            <option value="support">Technical Support</option>
            <option value="pricing">Pricing Information</option>
            <option value="other">Other</option>
          </select>
          <span class="cs-error" id="csSubjectError"></span>
        </div>
        <div class="cs-field">
          <label for="csMessage">Message <span aria-hidden="true">*</span></label>
          <textarea id="csMessage" name="message" required placeholder="How can we help you?"></textarea>
          <span class="cs-error" id="csMessageError"></span>
        </div>
        <div class="cs-status" id="csFormStatus" aria-live="polite"></div>
        <button type="submit" class="cs-submit">Send Message <i class="fas fa-paper-plane"></i></button>
      </form>
      <div class="cs-quick-links">
        <a href="mailto:info@deoris.edu" class="cs-quick-link"><i class="fas fa-envelope"></i> Email Us</a>
        <a href="tel:+1234567890" class="cs-quick-link"><i class="fas fa-phone"></i> Call Us</a>
      </div>
    </div>
  </aside>

  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js" crossorigin="anonymous"></script>
  <script src="{{ asset('landing/index.js') }}?v={{ filemtime(public_path('landing/index.js')) }}"></script>
  <script>
    window.addEventListener('load', function () {
      var el = document.getElementById('pageLoader');
      if (el) { el.classList.add('is-done'); el.setAttribute('aria-busy','false'); }
    });
  </script>
</body>
</html>
