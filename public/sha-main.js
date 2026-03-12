/* ══════════════════════════════════════════════════════════════
   Smart Home Architects — Shared JS
   Nav scroll, mobile nav, FAQ accordion, scroll reveal
   ══════════════════════════════════════════════════════════════ */

(function() {
    'use strict';

    // ─── NAV SCROLL ───
    var nav = document.getElementById('mainNav');
    if (nav) {
        var isHomepage = nav.classList.contains('nav--transparent-init');
        function handleScroll() {
            if (window.scrollY > 24) {
                nav.classList.add('nav--solid');
            } else if (isHomepage) {
                nav.classList.remove('nav--solid');
            }
        }
        if (!isHomepage) {
            nav.classList.add('nav--solid');
        }
        window.addEventListener('scroll', handleScroll, { passive: true });
        // Hover: solid on enter, remove on leave if not scrolled
        nav.addEventListener('mouseenter', function() {
            nav.classList.add('nav--solid');
        });
        nav.addEventListener('mouseleave', function() {
            if (window.scrollY <= 24 && isHomepage) {
                nav.classList.remove('nav--solid');
            }
        });
        handleScroll();
    }

    // ─── MOBILE NAV ───
    var toggle = document.getElementById('navToggle');
    var mobileNav = document.getElementById('mobileNav');
    if (toggle && mobileNav) {
        toggle.addEventListener('click', function() {
            toggle.classList.toggle('active');
            mobileNav.classList.toggle('active');
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
        });
        // Close on link click
        var mLinks = mobileNav.querySelectorAll('a');
        for (var i = 0; i < mLinks.length; i++) {
            mLinks[i].addEventListener('click', function() {
                toggle.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    }

    // ─── FAQ ACCORDION ───
    var faqItems = document.querySelectorAll('.faq-item__question');
    for (var j = 0; j < faqItems.length; j++) {
        faqItems[j].addEventListener('click', function() {
            var item = this.closest('.faq-item');
            var wasActive = item.classList.contains('active');
            // Close all
            var all = document.querySelectorAll('.faq-item.active');
            for (var k = 0; k < all.length; k++) {
                all[k].classList.remove('active');
            }
            // Toggle current
            if (!wasActive) {
                item.classList.add('active');
            }
        });
    }

    // ─── SCROLL REVEAL ───
    var reveals = document.querySelectorAll('.reveal');
    if (reveals.length > 0 && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        reveals.forEach(function(el) {
            observer.observe(el);
        });
    }
})();
