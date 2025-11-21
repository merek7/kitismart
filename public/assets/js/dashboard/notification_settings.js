$(document).ready(function () {
  // Gestion de l'activation/désactivation globale
  $('#email_enabled').on('change', function () {
    const isEnabled = $(this).is(':checked');

    // Désactiver/activer tous les autres toggles
    $('input[type="checkbox"]').not('#email_enabled').prop('disabled', !isEnabled);
    $('#expense_alert_threshold, #summary_day').prop('disabled', !isEnabled);

    // Ajouter un effet visuel
    if (!isEnabled) {
      $('.settings-section:not(:first-child) .settings-card').css('opacity', '0.5');
    } else {
      $('.settings-section:not(:first-child) .settings-card').css('opacity', '1');
    }
  });

  // Trigger au chargement
  $('#email_enabled').trigger('change');

  // Gestion de l'activation de l'alerte de dépense
  $('#expense_alert_enabled').on('change', function () {
    const isEnabled = $(this).is(':checked');
    $('#expense_alert_threshold').prop('disabled', !isEnabled || !$('#email_enabled').is(':checked'));
  });

  // Gestion de l'activation du récapitulatif mensuel
  $('#monthly_summary').on('change', function () {
    const isEnabled = $(this).is(':checked');
    $('#summary_day').prop('disabled', !isEnabled || !$('#email_enabled').is(':checked'));
  });

  // Validation du seuil de dépense
  $('#expense_alert_threshold').on('input', function () {
    const value = parseInt($(this).val());
    if (value < 0) {
      $(this).val(0);
    }
  });

  // Soumission du formulaire
  $('#notification-settings-form').on('submit', function (e) {
    e.preventDefault();

    const formData = {
      email_enabled: $('#email_enabled').is(':checked') ? 1 : 0,
      budget_alert_80: $('#budget_alert_80').is(':checked') ? 1 : 0,
      budget_alert_100: $('#budget_alert_100').is(':checked') ? 1 : 0,
      expense_alert_enabled: $('#expense_alert_enabled').is(':checked') ? 1 : 0,
      expense_alert_threshold: parseInt($('#expense_alert_threshold').val()) || 0,
      monthly_summary: $('#monthly_summary').is(':checked') ? 1 : 0,
      summary_day: parseInt($('#summary_day').val()) || 1
    };

    const $submitBtn = $('.btn-save');
    const originalText = $submitBtn.html();

    // Désactiver le bouton pendant la requête
    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

    // Requête AJAX
    $.ajax({
      url: '/notifications/settings',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(formData),
      success: function (response) {
        if (response.success) {
          showMessage('Paramètres enregistrés avec succès', 'success');

          // Animation de confirmation
          $submitBtn.html('<i class="fas fa-check"></i> Enregistré !');

          setTimeout(function () {
            $submitBtn.prop('disabled', false).html(originalText);
          }, 2000);
        } else {
          showMessage(response.message || 'Erreur lors de l\'enregistrement', 'danger');
          $submitBtn.prop('disabled', false).html(originalText);
        }
      },
      error: function (xhr) {
        let errorMessage = 'Erreur lors de l\'enregistrement des paramètres';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        showMessage(errorMessage, 'danger');
        $submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Fonction pour afficher les messages
  function showMessage(message, type) {
    const $messageDiv = $('#feedback-message');
    $messageDiv
      .removeClass('alert-success alert-danger')
      .addClass(`alert-${type}`)
      .html(`<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`)
      .slideDown(300);

    // Scroll vers le message
    $('html, body').animate({
      scrollTop: $messageDiv.offset().top - 100
    }, 300);

    // Auto-fermeture après 5 secondes pour les succès
    if (type === 'success') {
      setTimeout(function () {
        $messageDiv.slideUp(300);
      }, 5000);
    }
  }

  // Animation au changement de toggle
  $('.switch input').on('change', function () {
    const $switch = $(this).parent();
    $switch.addClass('switch-animate');
    setTimeout(function () {
      $switch.removeClass('switch-animate');
    }, 300);
  });

  // Animation pour les inputs
  $('.form-control, .form-select').on('focus', function () {
    $(this).parent().addClass('input-focused');
  });

  $('.form-control, .form-select').on('blur', function () {
    $(this).parent().removeClass('input-focused');
  });

  // Afficher une info-bulle pour le seuil de dépense
  $('#expense_alert_threshold').on('focus', function () {
    if (!$('#threshold-tooltip').length) {
      $(this).parent().after(
        '<small id="threshold-tooltip" class="text-muted" style="display: block; margin-top: 0.5rem;">' +
        '<i class="fas fa-info-circle"></i> Vous serez alerté pour toute dépense supérieure à ce montant' +
        '</small>'
      );
    }
  });
});
