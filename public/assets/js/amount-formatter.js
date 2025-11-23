/**
 * Formatage des montants avec séparateur de milliers
 * Affiche le séparateur pendant la saisie mais envoie la valeur brute au serveur
 */
(function() {
    'use strict';

    // Formater un nombre avec séparateur de milliers (espace)
    function formatNumber(value) {
        if (!value && value !== 0) return '';

        // Séparer partie entière et décimale
        let parts = value.toString().split('.');
        let intPart = parts[0];
        let decPart = parts[1];

        // Ajouter les séparateurs de milliers (espace)
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

        // Reconstruire avec la partie décimale si présente
        return decPart !== undefined ? intPart + ',' + decPart : intPart;
    }

    // Extraire la valeur numérique (enlever les espaces et remplacer virgule par point)
    function parseNumber(formattedValue) {
        if (!formattedValue) return '';
        return formattedValue.replace(/\s/g, '').replace(',', '.');
    }

    // Initialiser le formatage sur un champ
    function initAmountField(input) {
        // Sauvegarder le nom original
        const originalName = input.name;
        const originalId = input.id || 'amount_' + Math.random().toString(36).substr(2, 9);

        // Créer un champ hidden pour stocker la vraie valeur
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = originalName;
        hiddenInput.id = originalId + '_raw';

        // Changer le champ visible en texte et retirer son name
        input.type = 'text';
        input.removeAttribute('name');
        input.setAttribute('inputmode', 'decimal');
        input.setAttribute('data-amount-field', 'true');
        input.setAttribute('autocomplete', 'off');

        // Insérer le champ hidden après l'input visible
        input.parentNode.insertBefore(hiddenInput, input.nextSibling);

        // Si une valeur existe déjà, la formater
        if (input.value) {
            hiddenInput.value = input.value;
            input.value = formatNumber(input.value);
        }

        // Gérer la saisie
        input.addEventListener('input', function(e) {
            let cursorPos = this.selectionStart;
            let oldLength = this.value.length;

            // Garder seulement les chiffres, espaces, virgule et point
            let cleanValue = this.value.replace(/[^\d\s,.]/g, '');

            // Remplacer le point par une virgule pour l'affichage
            cleanValue = cleanValue.replace('.', ',');

            // Ne garder qu'une seule virgule
            let parts = cleanValue.split(',');
            if (parts.length > 2) {
                cleanValue = parts[0] + ',' + parts.slice(1).join('');
            }

            // Extraire la valeur numérique (sans espaces)
            let numericValue = parseNumber(cleanValue);

            // Valider que c'est un nombre valide
            if (numericValue && !isNaN(numericValue)) {
                // Limiter les décimales à 2
                let numParts = numericValue.split('.');
                if (numParts[1] && numParts[1].length > 2) {
                    numParts[1] = numParts[1].substring(0, 2);
                    numericValue = numParts.join('.');
                }

                hiddenInput.value = numericValue;
                // Déclencher un événement change pour les listeners
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                // Formater pour l'affichage
                let displayParts = numericValue.split('.');
                let formatted = formatNumber(displayParts[0]);
                if (displayParts[1] !== undefined) {
                    formatted += ',' + displayParts[1];
                }

                this.value = formatted;
            } else if (cleanValue === '' || cleanValue === ',') {
                hiddenInput.value = '';
                this.value = cleanValue === ',' ? '0,' : '';
            }

            // Ajuster la position du curseur
            let newLength = this.value.length;
            let diff = newLength - oldLength;
            this.setSelectionRange(cursorPos + diff, cursorPos + diff);
        });

        // Formater proprement quand le champ perd le focus
        input.addEventListener('blur', function() {
            if (hiddenInput.value) {
                let num = parseFloat(hiddenInput.value);
                if (!isNaN(num)) {
                    // Formater avec 2 décimales si nécessaire
                    let formatted = formatNumber(Math.floor(num));
                    let decimals = hiddenInput.value.split('.')[1];
                    if (decimals) {
                        formatted += ',' + decimals.padEnd(2, '0').substring(0, 2);
                    }
                    this.value = formatted;
                }
            }
        });

        // S'assurer que le hidden est mis à jour avant soumission
        const form = input.closest('form');
        if (form && !form.hasAttribute('data-amount-formatter-init')) {
            form.setAttribute('data-amount-formatter-init', 'true');
            form.addEventListener('submit', function() {
                // Tous les champs montant sont déjà synchronisés via les events
            });
        }
    }

    // Initialiser tous les champs montant au chargement
    function initAllAmountFields() {
        // Sélectionner les champs de type number qui sont des montants
        const selectors = [
            'input[type="number"][name="amount"]',
            'input[type="number"][name="amount[]"]',
            'input[type="number"][name="montant"]',
            'input[type="number"][name="montant[]"]',
            'input[type="number"][id*="amount"]',
            'input[type="number"][id*="montant"]',
            'input[type="number"].amount-field',
            'input[type="number"].amount-input',
            'input[data-format-amount="true"]'
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(function(input) {
            if (!input.hasAttribute('data-amount-field')) {
                initAmountField(input);
            }
        });
    }

    // Exposer les fonctions globalement
    window.AmountFormatter = {
        init: initAllAmountFields,
        initField: initAmountField,
        format: formatNumber,
        parse: parseNumber
    };

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllAmountFields);
    } else {
        initAllAmountFields();
    }

    // Réinitialiser si du contenu est ajouté dynamiquement (MutationObserver)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                setTimeout(initAllAmountFields, 100);
            }
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
