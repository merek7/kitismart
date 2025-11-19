$(document).ready(function() {
    // ================================
    // Smooth scroll pour les ancres
    // ================================
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 800, 'swing');
        }
    });

    // ================================
    // Navbar scrolled effect
    // ================================
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('.navbar').addClass('scrolled');
        } else {
            $('.navbar').removeClass('scrolled');
        }
    });

    // ================================
    // Intersection Observer pour animations on scroll
    // ================================
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');

                // Si c'est un compteur de stats, l'animer
                if (entry.target.classList.contains('stat-number')) {
                    animateCounter(entry.target);
                }
            }
        });
    }, observerOptions);

    // Observer les feature cards
    document.querySelectorAll('.feature-card').forEach(card => {
        observer.observe(card);
    });

    // Observer les steps
    document.querySelectorAll('.step').forEach(step => {
        observer.observe(step);
    });

    // Observer les stat numbers
    document.querySelectorAll('.stat-number').forEach(stat => {
        observer.observe(stat);
    });

    // ================================
    // Animation des compteurs de stats
    // ================================
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 secondes
        const increment = target / (duration / 16); // 60fps
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }

            // Formatter le nombre avec des espaces pour les milliers
            const formatted = Math.floor(current).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            element.textContent = formatted + (element.getAttribute('data-target') === '98' ? '' : '');
        }, 16);
    }

    // ================================
    // Ajout de délais d'animation pour les feature cards
    // ================================
    $('.feature-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });

    // ================================
    // Ajout de délais d'animation pour les steps
    // ================================
    $('.step').each(function(index) {
        $(this).css('animation-delay', (index * 0.2) + 's');
    });

    // ================================
    // Effet parallax subtil sur le hero (optionnel)
    // ================================
    if ($('.hero').length) {
        $(window).on('scroll', function() {
            const scrolled = $(window).scrollTop();
            $('.hero').css('transform', 'translateY(' + (scrolled * 0.4) + 'px)');
        });
    }
});
