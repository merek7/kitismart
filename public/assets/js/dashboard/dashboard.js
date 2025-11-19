$(document).ready(function() {
    // ================================
    // Animation des stat cards au chargement
    // ================================
    $('.stat-card').each(function(index) {
        const delay = $(this).data('delay') || 0;
        const card = $(this);

        setTimeout(() => {
            card.addClass('animate-in');
        }, delay);
    });

    // ================================
    // Animation des compteurs
    // ================================
    function animateCounter(element) {
        const $element = $(element);
        const targetValue = parseFloat($element.data('value'));
        const isCurrency = $element.text().includes('€') || $element.text().includes('FCFA');
        const currency = $element.text().includes('€') ? ' €' : ' FCFA';
        const duration = 2000; // 2 secondes
        const increment = targetValue / (duration / 16); // 60fps
        let currentValue = 0;

        const timer = setInterval(() => {
            currentValue += increment;

            if (currentValue >= targetValue) {
                currentValue = targetValue;
                clearInterval(timer);
            }

            // Formater le nombre avec 2 décimales et des espaces pour les milliers
            const formatted = currentValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            $element.text(formatted + (isCurrency ? currency : ''));
        }, 16);
    }

    // Animer tous les compteurs
    $('.stat-value[data-value]').each(function() {
        animateCounter(this);
    });

    // ================================
    // Animation de la barre de progression
    // ================================
    function animateProgressBar() {
        const $progressBar = $('.progress-bar-fill');
        const targetProgress = parseFloat($progressBar.data('progress'));

        setTimeout(() => {
            $progressBar.css('width', targetProgress + '%');
        }, 500);
    }

    animateProgressBar();

    // ================================
    // Effet hover sur les stat cards
    // ================================
    $('.stat-card').hover(
        function() {
            $(this).find('.stat-icon').addClass('bounce');
        },
        function() {
            $(this).find('.stat-icon').removeClass('bounce');
        }
    );

    // ================================
    // Gestion de la déconnexion
    // ================================
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();

        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            window.location.href = '/logout';
        }
    });
});
