$(document).ready(function () {
  // S'assurer que le modal est caché au démarrage
  $("#editExpenseModal").modal("hide");

  // Configuration des animations
  const animationDuration = 300;
  const fadeOutOptions = { opacity: 0, transform: "scale(0.95)" };
  const fadeInOptions = { opacity: 1, transform: "scale(1)" };

  // Fonction pour filtrer les dépenses
  function filterExpenses() {
    const selectedCategory = $("#filter-category").val();
    const selectedStatus = $("#filter-status").val();
    const selectedDate = $("#filter-date").val();
    const currentSort = $("#sort-expenses").val();
    let visibleCards = 0;

    // Parcourir toutes les cartes de dépenses
    $(".expense-cards").each(function () {
      const card = $(this);
      let showCard = true;

      // Filtre par catégorie
      if (selectedCategory && card.data("category") !== selectedCategory) {
        showCard = false;
      }

      // Filtre par statut
      if (selectedStatus && card.data("status") !== selectedStatus) {
        showCard = false;
      }

      // Filtre par date
      if (selectedDate) {
        const cardDate = new Date(card.data("date"))
          .toISOString()
          .split("T")[0];
        if (cardDate !== selectedDate) {
          showCard = false;
        }
      }

      // Animation et comptage
      if (showCard) {
        visibleCards++;
        card.css("display", "flex").animate(fadeInOptions, animationDuration);
      } else {
        card.animate(fadeOutOptions, animationDuration, function () {
          $(this).hide();
        });
      }
    });

    // Afficher message si aucune dépense trouvée
    const noResultsMsg = $(".no-results-message");
    if (visibleCards === 0) {
      if (noResultsMsg.length === 0) {
        const msg = $('<div class="no-results-message alert-infos">').text(
          "Aucune dépense ne correspond aux critères de filtrage sélectionnés."
        );
        $(".expenses-grids").append(msg);
        msg.hide().fadeIn(animationDuration);
      }
    } else {
      noResultsMsg.fadeOut(animationDuration, function () {
        $(this).remove();
      });
    }

    // Trier après filtrage
    sortExpenses(currentSort);

    // Mettre à jour les statistiques
    updateStats();
  }

  // Fonction de tri des dépenses
  function sortExpenses(sortBy) {
    const cards = $(".expense-cards:visible").get();

    cards.sort(function (a, b) {
      const $a = $(a);
      const $b = $(b);

      switch (sortBy) {
        case "date-asc":
          return new Date($a.data("date")) - new Date($b.data("date"));
        case "date-desc":
          return new Date($b.data("date")) - new Date($a.data("date"));
        case "amount-asc":
          return parseFloat($a.data("amount")) - parseFloat($b.data("amount"));
        case "amount-desc":
          return parseFloat($b.data("amount")) - parseFloat($a.data("amount"));
        default:
          return 0;
      }
    });

    // Réinsérer les cartes triées avec animation
    const container = $(".expenses-grids");
    cards.forEach(function (card) {
      const $card = $(card);
      $card
        .css(fadeOutOptions)
        .detach()
        .appendTo(container)
        .animate(fadeInOptions, animationDuration);
    });
  }

  // Fonction pour exporter les données filtrées
  function exportFilteredData(format) {
    const filteredData = [];

    $(".expense-cards:visible").each(function () {
      const card = $(this);
      filteredData.push({
        description: card.find(".expense-titles").text(),
        montant: card.data("amount"),
        categorie: card.data("category"),
        status: card.data("status"),
        date: new Date(card.data("date")).toLocaleDateString(),
      });
    });

    switch (format) {
      case "csv":
        exportToCSV(filteredData);
        break;
      case "excel":
        exportToExcel(filteredData);
        break;
      case "pdf":
        exportToPDF(filteredData);
        break;
    }
  }

  // Fonctions d'export
  function exportToCSV(data) {
    let csv = "Description;Montant;Catégorie;Status;Date\n";
    data.forEach((row) => {
      csv += `${row.description};${row.montant};${row.categorie};${row.status};${row.date}\n`;
    });

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "depenses_filtrees.csv";
    link.click();
  }

  function exportToExcel(data) {
    // Utiliser une bibliothèque comme SheetJS pour l'export Excel
    // Code à implémenter selon vos besoins
    console.log("Export Excel:", data);
  }

  function exportToPDF(data) {
    // Utiliser une bibliothèque comme jsPDF pour l'export PDF
    // Code à implémenter selon vos besoins
    console.log("Export PDF:", data);
  }

  // Ajouter les éléments de tri et d'export au DOM
  function initializeControls() {
    // Ajouter le sélecteur de tri
    const sortSelect = `
            <div class="filter-group">
                <label for="sort-expenses">Trier par</label>
                <select id="sort-expenses">
                    <option value="">Aucun tri</option>
                    <option value="date-desc">Date (récent → ancien)</option>
                    <option value="date-asc">Date (ancien → récent)</option>
                    <option value="amount-desc">Montant (élevé → bas)</option>
                    <option value="amount-asc">Montant (bas → élevé)</option>
                </select>
            </div>
        `;

    // Ajouter le bouton d'export
    const exportButton = `
            <div class="filter-group">
                <label>Exporter</label>
                <div class="export-buttons">
                    <button class="btns btn-sms btn-primarys" data-export="csv">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btns btn-sms btn-primarys" data-export="excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btns btn-sms btn-primarys" data-export="pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        `;

    $(".filter-controls").append(sortSelect, exportButton);
  }

  // Écouteurs d'événements
  function initializeEventListeners() {
    // Filtres existants
    $("#filter-category, #filter-status, #filter-date").on(
      "change",
      filterExpenses
    );

    // Tri
    $("#sort-expenses").on("change", function () {
      sortExpenses($(this).val());
    });

    // Export
    $(".export-buttons .btns").on("click", function () {
      const format = $(this).data("export");
      exportFilteredData(format);
    });
  }

  // Initialisation
  initializeControls();
  initializeEventListeners();
  updateStats();

  function updateStats() {
    let totalAmount = 0;
    let pendingAmount = 0;
    let paidAmount = 0;
    let visibleCount = 0;

    $(".expense-cards:visible").each(function () {
      const amount = parseFloat($(this).data("amount"));
      const status = $(this).data("status");

      totalAmount += amount;
      if (status === "pending") {
        pendingAmount += amount;
      } else if (status === "paid") {
        paidAmount += amount;
      }
      visibleCount++;
    });

    // Mettre à jour l'affichage des statistiques
    $("#total-amount").text(formatMoney(totalAmount));
    $("#pending-amount").text(formatMoney(pendingAmount));
    $("#paid-amount").text(formatMoney(paidAmount));
    $("#expenses-count").text(visibleCount);
  }

  // Fonction pour formater les montants en FCFA
  function formatMoney(amount) {
    return (
      amount
        .toFixed(2)
        .replace(/\d(?=(\d{3})+\.)/g, "$& ")
        .replace(".", ",") + " FCFA"
    );
  }

  // Écouteurs d'événements pour les filtres
  $("#filter-category, #filter-status, #filter-date").on("change", function () {
    filterExpenses();
  });

  // Bouton pour réinitialiser les filtres
  function resetFilters() {
    $("#filter-category").val("");
    $("#filter-status").val("");
    $("#filter-date").val("");
    filterExpenses();
  }

  // Gestion du status (marquer comme payé)
  $(document).on("click", ".mark-paid-btn", function () {
    const expenseId = $(this).data("id");
    const card = $(this).closest(".expense-cards");

    // Ici, vous devrez ajouter votre appel AJAX pour mettre à jour le statut
    $.ajax({
      url: "/expenses/mark-paid/" + expenseId, // Ajustez l'URL selon votre route
      method: "POST",
      success: function (response) {
        if (response.success) {
          // Mettre à jour l'apparence de la carte
          card.data("status", "paid");
          card
            .find(".status-badges")
            .removeClass("badge-warnings")
            .addClass("badge-successs")
            .text("Payé");

          // Masquer le bouton "Marquer payé"
          card.find(".mark-paid-btn").remove();

          // Mettre à jour les statistiques
          updateStats();
        }
      },
      error: function (xhr, status, error) {
        alert("Une erreur est survenue lors de la mise à jour du statut.");
      },
    });
  });

  // Initialisation des stats au chargement
  updateStats();

  // Ouvrir le modal lors du clic sur "Modifier"
  $(document).on("click", ".edit-expense-btn", function () {
    const expenseId = $(this).data("id");
    const card = $(this).closest(".expense-cards");

    // Récupérer les données de la carte
    const description = card.find(".expense-titles").text().trim();
    const amount = parseFloat(card.data("amount"));
    const category = card.data("category");
    const categoryname = card.data("categoryname");
    const date = card.data("date").split(" ")[0]; // Obtenir seulement la date
    const status = card.data("status");

    // Remplir le formulaire dans le modal
    $("#edit-expense-id").val(expenseId);
    $("#edit-description").val(description);
    $("#edit-amount").val(amount);
    $("#edit-category").val(categoryname);
    $("#edit-date").val(date);
    $("#edit-status").val(status);

    // Afficher le modal
    $("#editExpenseModal").modal("show");
  });

  // Enregistrer les modifications
  $("#save-expense-edit").on("click", function () {
    const expenseId = $("#edit-expense-id").val();
    const formData = {
      description: $("#edit-description").val(),
      amount: parseFloat($("#edit-amount").val()),
      category_type: $("#edit-category").val(),
      paid_at: $("#edit-date").val(),
      status: $("#edit-status").val(),
    };

    // Envoyer la requête AJAX pour mettre à jour la dépense
    $.ajax({
      url: `/expenses/update/${expenseId}`,
      method: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (response) {
        if (response.success) {
          // Mettre à jour l'affichage de la carte
          const card = $(`.expense-cards[data-id="${expenseId}"]`);

          card.data("category", formData.category_type);
          card.data("amount", formData.amount);
          card.data("date", formData.payment_date);
          card.data("status", formData.status);

          // Mettre à jour l'affichage
          card.find(".expense-titles").text(formData.description);
          card
            .find(".expense-amounts")
            .text(formData.amount.toFixed(2) + " FCFA");
          card
            .find(".expense-dates")
            .text(new Date(formData.payment_date).toLocaleDateString());

          const statusClass =
            formData.status === "paid" ? "successs" : "warnings";
          const statusText = formData.status === "paid" ? "Payé" : "En attente";

          card
            .find(".status-badges")
            .removeClass("badge-successs badge-warnings")
            .addClass(`badge-${statusClass}`)
            .text(statusText);

          // Si le statut est passé à "payé", masquer le bouton "Marquer payé"
          if (formData.status === "paid") {
            card.find(".mark-paid-btn").remove();
          }

          // Fermer le modal
          $("#editExpenseModal").modal("hide");

          // Mettre à jour les statistiques
          updateStats();

          // Afficher un message de succès
          showNotification("Dépense mise à jour avec succès", "success");
        } else {
          showNotification("Erreur : " + response.message, "error");
        }
      },
      error: function () {
        showNotification("Erreur de communication avec le serveur", "error");
      },
    });
  });

  // Fonction pour afficher des notifications
  function showNotification(message, type = "info") {
    // Si vous avez déjà un système de notification, utilisez-le ici
    // Sinon, créons une notification simple
    const notificationId = "notification-" + Date.now();
    const notificationClass =
      type === "success"
        ? "alert-success"
        : type === "error"
        ? "alert-danger"
        : "alert-info";

    const notification = `
      <div id="${notificationId}" class="alert ${notificationClass} notification-toast">
        ${message}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
    `;

    // Ajouter la notification au DOM
    if (!$(".notifications-container").length) {
      $("body").append('<div class="notifications-container"></div>');
    }

    $(".notifications-container").append(notification);

    // Faire disparaître la notification après 5 secondes
    setTimeout(function () {
      $(`#${notificationId}`).fadeOut(500, function () {
        $(this).remove();
      });
    }, 5000);
  }
});
