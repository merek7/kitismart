$(document).ready(function () {
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
});
