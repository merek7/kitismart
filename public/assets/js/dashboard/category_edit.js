$(document).ready(function () {
  let selectedIcon = $('#icon').val();
  let selectedColor = $('#color').val();
  const categoryId = $('#category-edit-form').data('id');

  // Gestion de la sélection d'icône
  $('.icon-option').on('click', function () {
    // Retirer la sélection précédente
    $('.icon-option').removeClass('selected');

    // Ajouter la sélection à l'icône cliquée
    $(this).addClass('selected');

    // Mettre à jour la valeur
    selectedIcon = $(this).data('icon');
    $('#icon').val(selectedIcon);

    // Mettre à jour l'aperçu
    updatePreview();
  });

  // Gestion de la sélection de couleur
  $('.color-option').on('click', function () {
    // Retirer la sélection précédente
    $('.color-option').removeClass('selected');

    // Ajouter la sélection à la couleur cliquée
    $(this).addClass('selected');

    // Mettre à jour la valeur
    selectedColor = $(this).data('color');
    $('#color').val(selectedColor);

    // Mettre à jour l'aperçu
    updatePreview();
  });

  // Mise à jour de l'aperçu lors de la saisie
  $('#name').on('input', function () {
    updatePreview();
  });

  $('#description').on('input', function () {
    updatePreview();
  });

  // Fonction pour mettre à jour l'aperçu
  function updatePreview() {
    const name = $('#name').val() || 'Nom de la catégorie';
    const description = $('#description').val() || 'Aucune description';

    $('#preview-name').text(name);
    $('#preview-description').text(description);
    $('#preview-icon').css('background-color', selectedColor);
    $('#preview-icon i').attr('class', `fas ${selectedIcon}`);
  }

  // Soumission du formulaire
  $('#category-edit-form').on('submit', function (e) {
    e.preventDefault();

    const formData = {
      name: $('#name').val().trim(),
      icon: selectedIcon,
      color: selectedColor,
      description: $('#description').val().trim()
    };

    // Validation côté client
    if (formData.name.length < 2) {
      showMessage('Le nom de la catégorie doit contenir au moins 2 caractères', 'error');
      return;
    }

    const $submitBtn = $(this).find('button[type="submit"]');
    const originalText = $submitBtn.html();

    // Désactiver le bouton pendant la requête
    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

    // Requête AJAX
    $.ajax({
      url: `/categories/${categoryId}`,
      method: 'PUT',
      contentType: 'application/json',
      data: JSON.stringify(formData),
      success: function (response) {
        if (response.success) {
          showMessage('Catégorie mise à jour avec succès', 'success');

          // Rediriger vers la liste après 1 seconde
          setTimeout(function () {
            window.location.href = '/categories';
          }, 1000);
        } else {
          showMessage(response.message || 'Erreur lors de la mise à jour', 'error');
          $submitBtn.prop('disabled', false).html(originalText);
        }
      },
      error: function (xhr) {
        let errorMessage = 'Erreur lors de la mise à jour de la catégorie';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        showMessage(errorMessage, 'error');
        $submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Fonction pour afficher les messages
  function showMessage(message, type) {
    const $messageDiv = $('#global-message');
    $messageDiv
      .removeClass('success error')
      .addClass(type)
      .text(message)
      .slideDown(300);

    // Auto-fermeture après 5 secondes pour les succès
    if (type === 'success') {
      setTimeout(function () {
        $messageDiv.slideUp(300);
      }, 5000);
    }
  }

  // Animation au focus des inputs
  $('.form-control').on('focus', function () {
    $(this).parent().addClass('focused');
  });

  $('.form-control').on('blur', function () {
    $(this).parent().removeClass('focused');
  });

  // Initialiser l'aperçu avec les valeurs actuelles
  updatePreview();
});
