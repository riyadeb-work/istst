/**
 * @file
 * ISTST theme behaviors: navbar scroll state, mobile menu, mobile
 * accordion dropdowns, hero slider, and scroll-reveal animations.
 *
 * Ported from the static site's inline <script>, wrapped in
 * Drupal.behaviors + once() so it re-attaches safely after AJAX
 * (e.g. exposed filters on the Members List view in Task 2/5).
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.iststTheme = {
    attach: function (context) {

      // --- Navbar background + announcement bar on scroll ---
      once('istst-navbar-scroll', 'body', context).forEach(function () {
        var navbar = document.getElementById('navbar');
        var navContainer = document.getElementById('nav-container');
        var announcementBar = document.getElementById('announcement-bar');
        if (!navbar) {
          return;
        }

        function updateScrollState() {
          if (window.scrollY > 20) {
            navbar.classList.add('bg-white/95');
            navbar.classList.remove('bg-white/90');
            if (navContainer) {
              navContainer.classList.remove('h-20');
              navContainer.classList.add('h-16');
            }
            if (announcementBar) {
              announcementBar.style.maxHeight = '0px';
              announcementBar.style.opacity = '0';
              announcementBar.style.borderTopWidth = '0px';
            }
          }
          else {
            navbar.classList.add('bg-white/90');
            navbar.classList.remove('bg-white/95');
            if (navContainer) {
              navContainer.classList.add('h-20');
              navContainer.classList.remove('h-16');
            }
            if (announcementBar) {
              announcementBar.style.maxHeight = '50px';
              announcementBar.style.opacity = '1';
              announcementBar.style.borderTopWidth = '1px';
            }
          }
        }

        // Run once immediately so the header's initial state always
        // matches the real scroll position on load (fixes a stuck
        // half-collapsed announcement bar when the page loads
        // already scrolled, e.g. in some admin-toolbar sessions),
        // then keep it in sync on every scroll.
        updateScrollState();
        window.addEventListener('scroll', updateScrollState);
      });

      // --- Mobile menu (drawer) toggle ---
      once('istst-mobile-menu', '#mobile-menu-btn', context).forEach(function (btn) {
        var mobileMenu = document.getElementById('mobile-menu');
        var menuOverlay = document.getElementById('menu-overlay');
        var closeBtn = document.getElementById('close-menu-btn');

        function toggleMenu() {
          if (mobileMenu) {
            mobileMenu.classList.toggle('open');
          }
          if (menuOverlay) {
            menuOverlay.classList.toggle('hidden');
          }
          document.body.classList.toggle('overflow-hidden');
        }

        btn.addEventListener('click', toggleMenu);
        if (closeBtn) {
          closeBtn.addEventListener('click', toggleMenu);
        }
        if (menuOverlay) {
          menuOverlay.addEventListener('click', toggleMenu);
        }
      });

      // --- Mobile accordion dropdowns (Organization / Member etc.) ---
      once('istst-mobile-dropdown', '.mobile-dropdown-toggle', context).forEach(function (toggle) {
        toggle.addEventListener('click', function () {
          var panel = toggle.nextElementSibling;
          if (panel) {
            panel.classList.toggle('hidden');
          }
        });
      });

      // --- Hero slider (home page only) ---
      once('istst-hero-slider', '#slider-container', context).forEach(function (container) {
        var slides = container.querySelectorAll('.slide');
        var dots = document.querySelectorAll('.slider-dot');
        if (!slides.length) {
          return;
        }
        var currentSlide = 0;
        var slideInterval = 5000;
        var sliderTimer;

        function showSlide(index) {
          slides.forEach(function (slide) {
            slide.classList.remove('active');
          });
          dots.forEach(function (dot) {
            dot.classList.remove('opacity-100');
            dot.classList.add('opacity-40');
          });
          slides[index].classList.add('active');
          if (dots[index]) {
            dots[index].classList.remove('opacity-40');
            dots[index].classList.add('opacity-100');
          }
          currentSlide = index;
        }

        function nextSlide() {
          showSlide((currentSlide + 1) % slides.length);
        }

        sliderTimer = setInterval(nextSlide, slideInterval);

        dots.forEach(function (dot, index) {
          dot.addEventListener('click', function () {
            clearInterval(sliderTimer);
            showSlide(index);
            sliderTimer = setInterval(nextSlide, slideInterval);
          });
        });
      });

      // --- Scroll reveal animations ---
      once('istst-reveal', '.reveal', context).forEach(function (el) {
        var revealOnScroll = new IntersectionObserver(function (entries, observer) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
              return;
            }
            entry.target.classList.add('active');
            observer.unobserve(entry.target);
          });
        }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
        revealOnScroll.observe(el);
      });

    }
  };

})(Drupal, once);
