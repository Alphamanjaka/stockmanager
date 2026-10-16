// Bon de commande (purchases/_form.blade.php) : recherche de produits au clavier, lignes modifiables,
// coût proposé selon le fournisseur, ajout des ruptures, totaux et contrôles avant envoi.
// Données de la page : <script type="application/json" id="purchase-form-data"> (PurchaseController::formData).

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("purchase-form");
    if (!form) {
        return;
    }

    const data = JSON.parse(document.getElementById("purchase-form-data").textContent);
    const catalog = new Map(data.catalog.map((variant) => [variant.id, variant]));
    const searchIndex = data.catalog.map((variant) => ({ variant, text: `${variant.name} ${variant.sku ?? ""}`.toLowerCase() }));
    const MAX_RESULTS = 20;

    const search = document.getElementById("product-search");
    const results = document.getElementById("product-results");
    const lines = document.getElementById("purchase-lines");
    const empty = document.getElementById("lines-empty");
    const discount = document.getElementById("discount");
    const supplier = document.getElementById("supplier_id");
    const shortageButton = document.getElementById("add-shortages");

    let supplierPrices = {}; // derniers prix payés au fournisseur choisi : { variantId: prix }
    let nextIndex = 0; // index des champs products[i][...]
    let matches = []; // résultats affichés de la recherche
    let activeIndex = -1;
    let dirty = false; // modifications non enregistrées
    let submitting = false;

    // ---------- Outils ----------
    const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

    // Même format que money() côté serveur (devise et position des paramètres)
    const formatMoney = (amount) => {
        const value = Number(amount || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return data.currencyBefore ? `${data.currency} ${value}` : `${value} ${data.currency}`;
    };

    const rowOf = (variantId) => lines.querySelector(`tr[data-id="${variantId}"]`);
    const proposedCost = (variant) => supplierPrices[variant.id] ?? variant.cost ?? 0;
    const toast = (message) =>
        Swal.fire({ toast: true, position: "top-end", icon: "success", title: message, showConfirmButton: false, timer: 2500 });

    // ---------- Lignes ----------
    /**
     * Ajoute une ligne. Un produit déjà présent n'est pas ajouté deux fois : sa ligne est mise en évidence.
     * unitPrice null : coût proposé (et mis à jour si le fournisseur change, tant qu'il n'est pas saisi à la main).
     */
    function addLine(variantId, { quantity = 1, unitPrice = null, focus = true } = {}) {
        const variant = catalog.get(Number(variantId));
        if (!variant) {
            return;
        }

        const existing = rowOf(variant.id);
        if (existing) {
            existing.classList.remove("ui-flash");
            void existing.offsetWidth; // relance l'animation
            existing.classList.add("ui-flash");
            if (focus) {
                existing.querySelector(".line-qty").select();
            }
            return;
        }

        const i = nextIndex++;
        const auto = unitPrice === null;
        const low = variant.stock <= variant.alert;
        const row = document.createElement("tr");
        row.dataset.id = variant.id;
        row.innerHTML = `
            <td>
                <div class="ui-cell-title">${escapeHtml(variant.name)}</div>
                <div class="ui-cell-meta">${escapeHtml(variant.sku)}</div>
                <input type="hidden" name="products[${i}][product_color_id]" value="${variant.id}">
            </td>
            <td class="text-end" title="Alert threshold: ${variant.alert}">
                ${low ? `<span class="ui-badge ui-badge-dot ui-tone-danger">${variant.stock}</span>` : variant.stock}
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end line-qty" name="products[${i}][quantity]"
                    min="1" step="1" value="${Number(quantity) || 1}" aria-label="Quantity">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control text-end line-cost" name="products[${i}][unit_price]" min="0" step="0.01"
                        value="${auto ? proposedCost(variant) : unitPrice}" aria-label="Unit cost" ${auto ? 'data-auto="1"' : ""}>
                    <span class="input-group-text">${escapeHtml(data.currency)}</span>
                </div>
            </td>
            <td class="text-end fw-semibold line-subtotal"></td>
            <td class="text-end">
                <button type="button" class="btn btn-icon btn-icon-danger line-remove" title="Remove" aria-label="Remove">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
            </td>`;
        lines.appendChild(row);
        refresh();

        if (focus) {
            row.querySelector(".line-qty").select();
        }
    }

    /** Sous-totaux, totaux, compteur, état vide et bouton des ruptures. */
    function refresh() {
        let gross = 0;
        lines.querySelectorAll("tr").forEach((row) => {
            const subtotal = Number(row.querySelector(".line-qty").value || 0) * Number(row.querySelector(".line-cost").value || 0);
            row.querySelector(".line-subtotal").textContent = formatMoney(subtotal);
            gross += subtotal;
        });

        const count = lines.children.length;
        document.getElementById("line-count").textContent = count;
        document.getElementById("total-gross").textContent = formatMoney(gross);
        document.getElementById("total-net").textContent = formatMoney(gross - Number(discount.value || 0));
        empty.hidden = count > 0;
        discount.classList.toggle("is-invalid", Number(discount.value || 0) > gross);

        const pending = pendingShortages();
        document.getElementById("shortage-count").textContent = pending.length;
        shortageButton.disabled = pending.length === 0;

        return gross;
    }

    lines.addEventListener("input", (event) => {
        if (event.target.matches(".line-cost")) {
            delete event.target.dataset.auto; // coût saisi à la main : on n'y touche plus
        }
        dirty = true;
        refresh();
    });

    lines.addEventListener("click", (event) => {
        const remove = event.target.closest(".line-remove");
        if (remove) {
            remove.closest("tr").remove();
            dirty = true;
            refresh();
            search.focus();
        }
    });

    discount.addEventListener("input", () => {
        dirty = true;
        refresh();
    });

    // ---------- Recherche de produits (liste déroulante au clavier) ----------
    function renderResults() {
        const terms = search.value.toLowerCase().split(/\s+/).filter(Boolean);
        matches = terms.length
            ? searchIndex.filter((entry) => terms.every((term) => entry.text.includes(term))).slice(0, MAX_RESULTS).map((entry) => entry.variant)
            : [];
        activeIndex = matches.length ? 0 : -1;

        results.innerHTML = matches.length
            ? matches.map((variant, index) => `
                <div class="list-group-item list-group-item-action" role="option" id="product-option-${index}" data-index="${index}">
                    <span>
                        <span class="d-block">${escapeHtml(variant.name)}</span>
                        <span class="ui-cell-meta">${escapeHtml(variant.sku)}</span>
                    </span>
                    <span class="text-nowrap small text-muted">
                        ${rowOf(variant.id) ? '<span class="ui-badge ui-tone-info">In the list</span>' : `Stock ${variant.stock}`}
                    </span>
                </div>`).join("")
            : `<div class="list-group-item text-muted">${terms.length ? "No product found" : ""}</div>`;

        const open = terms.length > 0;
        results.hidden = !open;
        search.setAttribute("aria-expanded", String(open));
        highlight();
    }

    function highlight() {
        results.querySelectorAll("[role=option]").forEach((option, index) => {
            const active = index === activeIndex;
            option.classList.toggle("active", active);
            option.setAttribute("aria-selected", String(active));
            if (active) {
                option.scrollIntoView({ block: "nearest" });
                search.setAttribute("aria-activedescendant", option.id);
            }
        });
    }

    function closeResults() {
        results.hidden = true;
        search.setAttribute("aria-expanded", "false");
        search.removeAttribute("aria-activedescendant");
    }

    function choose(index) {
        const variant = matches[index];
        if (!variant) {
            return;
        }
        search.value = "";
        closeResults();
        addLine(variant.id);
        dirty = true;
    }

    search.addEventListener("input", renderResults);
    search.addEventListener("focus", () => search.value && renderResults());
    search.addEventListener("blur", () => setTimeout(closeResults, 150));
    search.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            if (results.hidden) {
                renderResults();
            }
            if (matches.length) {
                activeIndex = (activeIndex + (event.key === "ArrowDown" ? 1 : -1) + matches.length) % matches.length;
                highlight();
            }
        } else if (event.key === "Enter") {
            event.preventDefault();
            choose(activeIndex);
        } else if (event.key === "Escape") {
            closeResults();
        }
    });

    // mousedown : choisi avant que la recherche ne perde le focus
    results.addEventListener("mousedown", (event) => {
        const option = event.target.closest("[role=option]");
        if (option) {
            event.preventDefault();
            choose(Number(option.dataset.index));
        }
    });

    // F2 : retour à la recherche depuis n'importe où dans la page
    document.addEventListener("keydown", (event) => {
        if (event.key === "F2") {
            event.preventDefault();
            search.focus();
            search.select();
        }
    });

    // Entrée dans une ligne : pas d'envoi du formulaire, retour à la recherche pour la ligne suivante
    form.addEventListener("keydown", (event) => {
        const target = event.target;
        if (event.key !== "Enter" || target === search || target.tagName === "TEXTAREA" || target.tagName === "BUTTON") {
            return;
        }
        event.preventDefault();
        if (target.matches(".line-qty, .line-cost")) {
            search.focus();
        }
    });

    // ---------- Fournisseur : coûts proposés et ruptures ----------
    const shortagesOfSupplier = () => data.shortages[supplier.value] ?? [];
    const pendingShortages = () => shortagesOfSupplier().filter((shortage) => !rowOf(shortage.id));

    async function loadSupplierPrices() {
        supplierPrices = {};
        if (supplier.value) {
            try {
                const response = await fetch(data.pricesUrl.replace("__SUPPLIER__", supplier.value), { headers: { Accept: "application/json" } });
                supplierPrices = (await response.json()).prices ?? {};
            } catch {
                supplierPrices = {}; // on garde les prix d'achat des variantes
            }
        }

        // Les coûts proposés (non saisis à la main) suivent le fournisseur
        lines.querySelectorAll(".line-cost[data-auto]").forEach((input) => {
            input.value = proposedCost(catalog.get(Number(input.closest("tr").dataset.id)));
        });
        refresh();
    }

    // Select2 déclenche l'événement « change » de jQuery
    $(supplier)
        .select2({ theme: "bootstrap-5", width: "100%", placeholder: supplier.dataset.placeholder, allowClear: false })
        .on("change", () => {
            dirty = true;
            loadSupplierPrices();
        });

    shortageButton.addEventListener("click", () => {
        const pending = pendingShortages();
        pending.forEach((shortage) => addLine(shortage.id, {
            quantity: shortage.quantity,
            unitPrice: shortage.unit_price > 0 ? shortage.unit_price : null,
            focus: false,
        }));
        if (pending.length) {
            dirty = true;
            toast(`${pending.length} line(s) added`);
        }
    });

    // ---------- Contrôles avant envoi ----------
    function problem() {
        if (!supplier.value) {
            return { message: "Choose the supplier.", focus: () => $(supplier).select2("open") };
        }
        if (!lines.children.length) {
            return { message: "Add at least one product line.", focus: () => search.focus() };
        }
        for (const row of lines.children) {
            const quantity = row.querySelector(".line-qty");
            const cost = row.querySelector(".line-cost");
            if (!Number.isInteger(Number(quantity.value)) || Number(quantity.value) < 1) {
                return { message: "Each quantity must be a whole number of at least 1.", focus: () => quantity.select() };
            }
            if (cost.value === "" || Number(cost.value) < 0) {
                return { message: "Each line needs a unit cost (0 or more).", focus: () => cost.select() };
            }
        }
        if (Number(discount.value || 0) > refresh()) {
            return { message: "The discount cannot exceed the subtotal.", focus: () => discount.select() };
        }
        return null;
    }

    form.addEventListener("submit", (event) => {
        const issue = problem();
        if (issue) {
            // Avant la confirmation de resources/js/ui.js : le formulaire n'est pas envoyé
            event.preventDefault();
            event.stopPropagation();
            Swal.fire({ icon: "warning", text: issue.message }).then(() => issue.focus());
            return;
        }

        // Une fois l'envoi réellement parti (pas arrêté par la confirmation) : pas de double envoi
        setTimeout(() => {
            if (!event.defaultPrevented) {
                submitting = true;
                form.querySelectorAll("button[type=submit]").forEach((button) => (button.disabled = true));
            }
        });
    });

    window.addEventListener("beforeunload", (event) => {
        if (dirty && !submitting) {
            event.preventDefault();
            event.returnValue = "";
        }
    });

    // ---------- État initial : lignes de l'achat modifié, ou saisie renvoyée après une erreur ----------
    data.lines.forEach((line) => addLine(line.product_color_id, {
        quantity: line.quantity,
        unitPrice: line.unit_price === "" || line.unit_price == null ? null : Number(line.unit_price),
        focus: false,
    }));
    loadSupplierPrices();

    // Toute saisie (hors recherche) compte comme une modification à enregistrer
    form.addEventListener("input", (event) => {
        if (event.target !== search) {
            dirty = true;
        }
    });

    // Premier champ utile : le fournisseur, ou directement la recherche s'il est connu
    if (supplier.value) {
        search.focus();
    } else {
        supplier.nextElementSibling?.querySelector(".select2-selection")?.focus();
    }
});
