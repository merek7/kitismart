$(document).ready(function () {
  let categoryToDelete = null;

  // Gestion du clic sur le bouton de suppression
  $('.delete-category-btn').on('click', function () {
    categoryToDelete = $(this).data('id');
    const categoryName = $(this).data('name');

    // Mettre à jour le modal avec le nom de la catégorie
    $('#category-name-to-delete').text(categoryName);

    // Afficher le modal de confirmation
    $('#deleteModal').modal('show');
  });

  // Gestion de la confirmation de suppression
  $('#confirm-delete').on('click', function () {
    if (!categoryToDelete) return;

    const $button = $(this);
    const originalText = $button.html();

    // Désactiver le bouton pendant la requête
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');

    // Requête AJAX de suppression
    $.ajax({
      url: `/categories/${categoryToDelete}`,
      method: 'DELETE',
      contentType: 'application/json',
      success: function (response) {
        if (response.success) {
          // Fermer le modal
          $('#deleteModal').modal('hide');

          // Retirer la carte de la catégorie avec animation
          $(`.category-card[data-id="${categoryToDelete}"]`)
            .fadeOut(300, function () {
              $(this).remove();

              // Vérifier s'il reste des catégories
              if ($('.category-card').length === 0) {
                $('.categories-grid').html(
                  '<div class="alert-infos" role="alert">' +
                  'Aucune catégorie trouvée. Créez votre première catégorie personnalisée en cliquant sur "Nouvelle Catégorie".' +
                  '</div>'
                );
              }

              // Mettre à jour le compteur
              updateCategoryCount();
            });

          // Afficher le message de succès
          showMessage('Catégorie supprimée avec succès', 'success');
        } else {
          showMessage(response.message || 'Erreur lors de la suppression', 'error');
        }
      },
      error: function (xhr) {
        let errorMessage = 'Erreur lors de la suppression de la catégorie';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        showMessage(errorMessage, 'error');
      },
      complete: function () {
        // Réactiver le bouton
        $button.prop('disabled', false).html(originalText);
        categoryToDelete = null;
      }
    });
  });

  // Fermeture du modal
  $('#deleteModal').on('hidden.bs.modal', function () {
    categoryToDelete = null;
  });

  // Fonction pour afficher les messages
  function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
      <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    `;

    $('body').append(alertHtml);

    // Auto-fermeture après 5 secondes
    setTimeout(function () {
      $('.alert').fadeOut(300, function () {
        $(this).remove();
      });
    }, 5000);
  }

  // Fonction pour mettre à jour le compteur de catégories
  function updateCategoryCount() {
    const count = $('.category-card').length;
    $('.summary-value').first().text(count);
  }

  // Animation au survol des cartes
  $('.category-card').hover(
    function () {
      $(this).find('.category-actions').css('opacity', '1');
    },
    function () {
      $(this).find('.category-actions').css('opacity', '0.9');
    }
  );
});
