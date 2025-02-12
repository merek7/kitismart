$(document).ready(function() {
    $('form').on('submit', function(e) {
        e.preventDefault();
        console.log("Formulaire soumis"); // Vérifiez si ce log apparaît

        // Réinitialiser les messages d'erreur
        $('#error-container').empty().hide();
        
        $.ajax({
            url: '/login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: $('#email').val(),
                password: $('#password').val(),
                csrf_token: $('input[name="csrf_token"]').val()
            }),
            success: function(response) {
                console.log('Réponse reçue:', response);
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    $('#error-container')
                        .html(`<div class="alert alert-danger">${response.message}</div>`)
                        .show();
                }
            },
            error: function(xhr) {
                console.log('Erreur:', xhr);
                let message = 'Une erreur est survenue lors de la connexion';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                $('#error-container')
                    .html(`<div class="alert alert-danger">${message}</div>`)
                    .fadeIn();
            }
        });
    });
});
