/* ================================================================
   DEORIS — Landing Page JavaScript
   ================================================================ */
(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ── Throttle ──────────────────────────────────────────── */
  function throttle(fn, wait) {
    let last = 0, timer;
    return function (...args) {
      const now = Date.now();
      const remaining = wait - (now - last);
      if (remaining <= 0) {
        if (timer) { clearTimeout(timer); timer = null; }
        last = now; fn.apply(this, args);
      } else if (!timer) {
        timer = setTimeout(() => { last = Date.now(); timer = null; fn.apply(this, args); }, remaining);
      }
    };
  }

  /* ── Navbar: transparent → solid on scroll ─────────────── */
  const navbar = document.getElementById('navbar');
  function updateNavbar() {
    if (!navbar) return;
    navbar.classList.toggle('scrolled', window.scrollY > 40);
  }
  updateNavbar();
  window.addEventListener('scroll', throttle(updateNavbar, 100), { passive: true });

  /* ── Active nav link on scroll ─────────────────────────── */
  const sections = document.querySelectorAll('section[id], div[id]');
  const navLinks = document.querySelectorAll('.nav-link');
  function updateActiveLink() {
    let current = '';
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - 120) current = sec.id;
    });
    navLinks.forEach(a => {
      a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
  }
  window.addEventListener('scroll', throttle(updateActiveLink, 120), { passive: true });

  /* ── Mobile hamburger ───────────────────────────────────── */
  const hamburger = document.getElementById('mobileMenuBtn');
  const navList   = document.getElementById('navLinks');
  if (hamburger && navList) {
    const setOpen = open => {
      navList.classList.toggle('active', open);
      hamburger.classList.toggle('open', open);
      hamburger.setAttribute('aria-expanded', open);
      hamburger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };
    hamburger.addEventListener('click', () => setOpen(!navList.classList.contains('active')));
    navList.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('click', e => {
      if (!navbar.contains(e.target)) setOpen(false);
    });
  }

  /* ── Smooth scroll for anchor links ────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (!target) return;
      e.preventDefault();
      const offset = target.getBoundingClientRect().top + window.scrollY - (parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 70);
      window.scrollTo({ top: offset, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
  });

  /* ── Scroll-to-top button ───────────────────────────────── */
  const fabScroll = document.getElementById('scrollTop');
  function updateFab() {
    if (!fabScroll) return;
    fabScroll.classList.toggle('visible', window.scrollY > 400);
  }
  updateFab();
  window.addEventListener('scroll', throttle(updateFab, 150), { passive: true });
  if (fabScroll) {
    fabScroll.addEventListener('click', () =>
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' })
    );
  }

  /* ── Accordion ──────────────────────────────────────────── */
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', function () {
      const item    = this.closest('.accordion-item');
      const isOpen  = item.classList.contains('active');
      // close all
      document.querySelectorAll('.accordion-item.active').forEach(el => {
        el.classList.remove('active');
        el.querySelector('.accordion-header').setAttribute('aria-expanded', 'false');
      });
      // open clicked if it was closed
      if (!isOpen) {
        item.classList.add('active');
        this.setAttribute('aria-expanded', 'true');
      }
    });
    header.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); header.click(); }
    });
  });

  /* ── Support Drawer ─────────────────────────────────────── */
  const drawerToggle = document.getElementById('customerServicesToggle');
  const drawer       = document.getElementById('csDrawer');
  const backdrop     = document.getElementById('csBackdrop');
  const closeBtn     = document.getElementById('csClose');

  function openDrawer() {
    if (!drawer) return;
    drawer.classList.add('open');
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
    closeBtn && closeBtn.focus();
  }
  function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove('open');
    backdrop.classList.remove('open');
    document.body.style.overflow = '';
    drawerToggle && drawerToggle.focus();
  }

  drawerToggle && drawerToggle.addEventListener('click', () =>
    drawer.classList.contains('open') ? closeDrawer() : openDrawer()
  );
  closeBtn  && closeBtn.addEventListener('click', closeDrawer);
  backdrop  && backdrop.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) closeDrawer();
  });

  /* ── Support form validation ────────────────────────────── */
  const csForm = document.getElementById('csForm');
  if (csForm) {
    csForm.addEventListener('submit', function (e) {
      e.preventDefault();
      let valid = true;

      const setErr = (id, msg) => {
        const el = document.getElementById(id + 'Error');
        const inp = document.getElementById(id);
        if (el) el.textContent = msg;
        if (inp) inp.style.borderColor = msg ? 'var(--clr-danger)' : '';
        if (msg) valid = false;
      };
      const clearErr = id => setErr(id, '');

      ['csFullName','csEmail','csSubject','csMessage'].forEach(clearErr);

      const name    = (csForm.querySelector('#csFullName')?.value || '').trim();
      const email   = (csForm.querySelector('#csEmail')?.value || '').trim();
      const subject = (csForm.querySelector('#csSubject')?.value || '').trim();
      const message = (csForm.querySelector('#csMessage')?.value || '').trim();

      if (!name)    setErr('csFullName', 'Full name is required.');
      if (!email)   setErr('csEmail', 'Email is required.');
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) setErr('csEmail', 'Enter a valid email address.');
      if (!subject) setErr('csSubject', 'Please select a subject.');
      if (!message) setErr('csMessage', 'Message is required.');
      else if (message.length < 10) setErr('csMessage', 'Message must be at least 10 characters.');

      const status = document.getElementById('csFormStatus');
      if (valid && status) {
        status.innerHTML = '<div class="success-msg"><i class="fas fa-check-circle"></i> Message sent! We\'ll get back to you soon.</div>';
        csForm.reset();
        setTimeout(() => { status.innerHTML = ''; }, 6000);
      }
    });
  }

  /* ── Lightbox ───────────────────────────────────────────── */
  const lightboxModal   = document.getElementById('lightboxModal');
  const lightboxOverlay = document.getElementById('lightboxOverlay');
  const lightboxClose   = document.getElementById('lightboxClose');
  const lightboxImage   = document.getElementById('lightboxImage');
  const lightboxCaption = document.getElementById('lightboxCaption');

  if (lightboxModal && lightboxImage) {
    const openLightbox = img => {
      lightboxImage.src = img.src;
      lightboxImage.alt = img.alt;
      const cap = img.closest('figure')?.querySelector('figcaption');
      lightboxCaption.textContent = cap ? cap.textContent.trim() : '';
      lightboxModal.classList.add('active');
      document.body.style.overflow = 'hidden';
      lightboxClose && lightboxClose.focus();
    };
    const closeLightbox = () => {
      lightboxModal.classList.remove('active');
      document.body.style.overflow = '';
    };

    document.querySelectorAll('.gallery-card img').forEach(img => {
      img.style.cursor = 'pointer';
      img.setAttribute('tabindex', '0');
      img.setAttribute('role', 'button');
      img.addEventListener('click', () => openLightbox(img));
      img.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLightbox(img); }
      });
    });

    lightboxClose   && lightboxClose.addEventListener('click', closeLightbox);
    lightboxOverlay && lightboxOverlay.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && lightboxModal.classList.contains('active')) closeLightbox();
    });
  }

  /* ── Reveal animations (IntersectionObserver) ───────────── */
  const revealEls = document.querySelectorAll('[data-reveal]');
  if (!prefersReducedMotion && 'IntersectionObserver' in window && revealEls.length) {
    const revealObs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(el => revealObs.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  /* ── Animated counters ──────────────────────────────────── */
  function animateCounter(el, target, duration) {
    if (prefersReducedMotion) { el.textContent = target; return; }
    const start = performance.now();
    function step(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = Math.floor(eased * target);
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }
    requestAnimationFrame(step);
  }

  const counterEls = document.querySelectorAll('.metric-num[data-target]');
  if ('IntersectionObserver' in window && counterEls.length) {
    const counterObs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          animateCounter(el, parseInt(el.dataset.target, 10), 1800);
          counterObs.unobserve(el);
        }
      });
    }, { threshold: 0.5 });
    counterEls.forEach(el => counterObs.observe(el));
  }

  /* ── AOS init ───────────────────────────────────────────── */
  if (typeof AOS !== 'undefined') {
    AOS.init({
      duration: 700,
      once: true,
      offset: 60,
      easing: 'ease-out-cubic',
      disable: prefersReducedMotion,
    });
  }

})();
