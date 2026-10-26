// Écran de caisse : recherche et scan, filtres catégorie / marque, panier, encaissement et reçu.
// Catalogue, catégories, marques et textes sont fournis par la page (#pos-data) : tout se passe
// dans le navigateur jusqu'à la validation, envoyée en AJAX (la caisse reste ouverte).
import "../css/pos.css";

const dataElement = document.getElementById("pos-data");
if (dataElement) {
    initPos(JSON.parse(dataElement.textContent));
}

function initPos({ config, items, categories, brands, top }) {
    // Vignettes affichées au plus : au-delà, le caissier précise la recherche
    const MAX_TILES = 120;

    const $ = (id) => document.getElementById(id);
    const root = $("pos");
    const search = $("pos-search");
    const multiplierBadge = $("pos-multiplier");
    const grid = $("pos-grid");
    const gridNote = $("pos-grid-note");
    const categoryBar = $("pos-categories");
    const subcategoryBar = $("pos-subcategories");
    const brandBar = $("pos-brands");
    const lines = $("pos-lines");
    const cartEmpty = $("pos-cart-empty");
    const toastElement = $("pos-toast");
    const modalElement = $("pos-payment");
    const form = $("pos-form");
    const receivedInput = $("pos-received");
    const discountInput = $("pos-discount");
    const validateButton = $("pos-validate");
    const payError = $("pos-pay-error");
    const printFrame = $("pos-print-frame");
    const finePointer = window.matchMedia("(pointer: fine)").matches;

    // --- Outils -------------------------------------------------------------------------------

    const t = (key, params = {}) =>
        Object.entries(params).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, value),
            config.i18n[key] ?? key,
        );
    const escape = (value) =>
        String(value ?? "").replace(
            /[&<>"']/g,
            (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[char],
        );
    // Comparaison sans accents ni majuscules : « Mèche » est trouvée en tapant « meche »
    const normalize = (value) =>
        String(value ?? "")
            .normalize("NFD")
            .replace(/[̀-ͯ]/g, "")
            .toLowerCase();

    const numberFormat = new Intl.NumberFormat(config.locale, { maximumFractionDigits: 2 });
    const money = (amount) => {
        const number = numberFormat.format(amount);
        return config.currencyPosition === "before" ? `${config.currency} ${number}` : `${number} ${config.currency}`;
    };
    // Séparateurs de la langue : « 20 000,50 » en français, « 20,000.50 » en anglais
    const separator = (type, sample) => numberFormat.formatToParts(sample).find((part) => part.type === type)?.value;
    const groupSeparator = separator("group", 10000) ?? ",";
    const decimalSeparator = separator("decimal", 1.5) ?? ".";
    // Montant saisi (« 20 000 », « 20000 », « 12,50 »...) ; null si vide ou illisible
    const parseAmount = (value) => {
        let cleaned = String(value ?? "").replace(/[\s  ]/g, "");
        if (cleaned === "") return null;
        if (!/\s/.test(groupSeparator)) cleaned = cleaned.replaceAll(groupSeparator, "");
        cleaned = cleaned.replace(decimalSeparator, ".");
        const amount = Number(cleaned);
        return Number.isFinite(amount) && amount >= 0 ? amount : null;
    };

    // Réglages et panier mémorisés sur ce poste (le stockage peut être indisponible : on continue sans)
    const storage = {
        get(key, fallback) {
            try {
                const value = localStorage.getItem(`${config.storageKey}.${key}`);
                return value === null ? fallback : JSON.parse(value);
            } catch {
                return fallback;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(`${config.storageKey}.${key}`, JSON.stringify(value));
            } catch {
                // stockage plein ou bloqué : sans conséquence pour la vente
            }
        },
    };
    const prefs = { sound: storage.get("sound", true), autoPrint: storage.get("autoPrint", true) };

    let audio;
    // Bip court (article ajouté) ou grave (refus), comme un terminal de caisse
    function beep(ok = true) {
        if (!prefs.sound) return;
        try {
            audio ??= new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audio.createOscillator();
            const gain = audio.createGain();
            oscillator.type = ok ? "sine" : "square";
            oscillator.frequency.value = ok ? 1046 : 196;
            gain.gain.value = 0.06;
            oscillator.connect(gain).connect(audio.destination);
            oscillator.start();
            oscillator.stop(audio.currentTime + (ok ? 0.07 : 0.25));
        } catch {
            // pas de son disponible
        }
    }

    let toastTimer;
    function toast(message, type = "info") {
        toastElement.textContent = message;
        toastElement.className = `pos-toast is-visible is-${type}`;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastElement.classList.remove("is-visible"), 3500);
    }
    function warn(message) {
        beep(false);
        toast(message, "error");
    }

    // --- Catalogue ----------------------------------------------------------------------------

    const categoryById = new Map(categories.map((category) => [category.id, category]));
    const brandById = new Map(brands.map((brand) => [brand.id, brand]));
    const itemById = new Map(items.map((item) => [item.id, item]));

    items.forEach((item) => {
        item.haystack = normalize(
            [
                item.name,
                item.variant,
                item.sku,
                brandById.get(item.brand)?.name,
                ...item.categories.map((id) => categoryById.get(id)?.name),
            ].join(" "),
        );
        item.skuKey = normalize(item.sku);
        item.nameKey = normalize(item.name);
    });

    const label = (item) => (item.variant ? `${item.name} — ${item.variant}` : item.name);
    const countIn = (categoryId) => items.filter((item) => item.categories.includes(categoryId)).length;
    const childrenOf = (parentId) =>
        categories.filter((category) => category.parent_id === parentId && countIn(category.id) > 0);
    const rootOf = (categoryId) => {
        let category = categoryById.get(categoryId);
        while (category?.parent_id && categoryById.has(category.parent_id)) {
            category = categoryById.get(category.parent_id);
        }
        return category;
    };
    const rootCategories = categories.filter(
        (category) => (!category.parent_id || !categoryById.has(category.parent_id)) && countIn(category.id) > 0,
    );
    const usedBrands = brands.filter((brand) => items.some((item) => item.brand === brand.id));

    // --- État ---------------------------------------------------------------------------------

    const defaultCategory = top.length ? "top" : null;
    const state = {
        cart: [], // [{ id, qty }] dans l'ordre de saisie
        selected: -1, // ligne du panier pilotée au clavier
        category: defaultCategory, // "top", null (tout) ou id de catégorie
        brand: null,
        results: [],
        highlight: 0, // vignette choisie par Entrée
        flash: -1,
        busy: false,
        sale: null, // dernière vente enregistrée
    };

    // Panier restauré après un rechargement de page (quantités ramenées au stock connu)
    const savedCart = storage.get("cart", []);
    if (Array.isArray(savedCart)) {
        state.cart = savedCart
            .filter((line) => itemById.get(line?.id)?.stock > 0 && Number.isInteger(line.qty) && line.qty > 0)
            .map((line) => ({ id: line.id, qty: Math.min(line.qty, itemById.get(line.id).stock) }));
        state.selected = state.cart.length - 1;
    }

    const cartTotal = () => state.cart.reduce((sum, line) => sum + itemById.get(line.id).price * line.qty, 0);
    const cartCount = () => state.cart.reduce((sum, line) => sum + line.qty, 0);

    // « 3*noir » ou « 3* » puis un scan : 3 exemplaires du prochain article
    function parseQuery(raw) {
        const match = raw.match(/^\s*(\d{1,4})\s*\*\s*(.*)$/);
        return match ? { qty: Math.max(1, Number(match[1])), text: match[2] } : { qty: 1, text: raw };
    }

    // --- Recherche et filtres -----------------------------------------------------------------

    function computeResults() {
        const { text } = parseQuery(search.value);
        const tokens = normalize(text).split(/\s+/).filter(Boolean);
        const showTop = state.category === "top" && tokens.length === 0;
        const base = showTop ? top.map((id) => itemById.get(id)).filter(Boolean) : items;
        const categoryId = typeof state.category === "number" ? state.category : null;

        const results = base.filter(
            (item) =>
                (categoryId === null || item.categories.includes(categoryId)) &&
                (state.brand === null || item.brand === state.brand) &&
                tokens.every((token) => item.haystack.includes(token)),
        );

        // Code exact, puis nom qui commence par la saisie ; les ruptures en dernier (tri stable)
        const first = tokens[0] ?? "";
        const exactKey = normalize(text.trim());
        const score = (item) =>
            (item.stock > 0 ? 0 : 10) +
            (first === "" ? 0 : item.skuKey === exactKey ? 0 : item.nameKey.startsWith(first) ? 1 : 2);
        state.results = results.sort((a, b) => score(a) - score(b));
        state.highlight = Math.min(state.highlight, Math.max(0, state.results.length - 1));
    }

    function chip(labelText, active, data, count = null) {
        const attributes = Object.entries(data)
            .map(([name, value]) => `data-${name}="${escape(value)}"`)
            .join(" ");
        return `<button type="button" class="pos-chip${active ? " is-active" : ""}" ${attributes} aria-pressed="${active}" tabindex="-1">
            ${labelText}${count === null ? "" : ` <span class="pos-chip-count">${count}</span>`}</button>`;
    }

    function renderFilters() {
        const selectedRoot = typeof state.category === "number" ? rootOf(state.category) : null;

        categoryBar.innerHTML = [
            top.length
                ? chip(`<i class="bi bi-star-fill" aria-hidden="true"></i> ${escape(t("top_sellers"))}`, state.category === "top", { category: "top" })
                : "",
            chip(escape(t("all")), state.category === null, { category: "" }, items.length),
            ...rootCategories.map((category) =>
                chip(escape(category.name), selectedRoot?.id === category.id, { category: category.id }, countIn(category.id)),
            ),
        ].join("");

        // Sous-catégories de la catégorie choisie (ou ses voisines si elle n'en a pas)
        let parentId = null;
        if (typeof state.category === "number") {
            parentId = childrenOf(state.category).length ? state.category : categoryById.get(state.category)?.parent_id ?? null;
        }
        const children = parentId ? childrenOf(parentId) : [];
        subcategoryBar.hidden = children.length === 0;
        subcategoryBar.innerHTML = children.length
            ? [
                  chip(`${escape(t("all"))} · ${escape(categoryById.get(parentId).name)}`, state.category === parentId, { category: parentId }),
                  ...children.map((category) =>
                      chip(escape(category.name), state.category === category.id, { category: category.id }, countIn(category.id)),
                  ),
              ].join("")
            : "";

        brandBar.hidden = usedBrands.length === 0;
        brandBar.innerHTML = usedBrands.length
            ? [
                  `<span class="pos-chips-label">${escape(t("brands"))}</span>`,
                  chip(escape(t("all_brands")), state.brand === null, { brand: "" }),
                  ...usedBrands.map((brand) => chip(escape(brand.name), state.brand === brand.id, { brand: brand.id })),
              ].join("")
            : "";
    }

    function renderGrid() {
        const quantities = new Map(state.cart.map((line) => [line.id, line.qty]));
        const searching = search.value.trim() !== "";
        const shown = state.results.slice(0, MAX_TILES);

        grid.innerHTML = shown
            .map((item, index) => {
                const out = item.stock <= 0;
                const low = !out && item.alert > 0 && item.stock <= item.alert;
                const inCart = quantities.get(item.id);
                const classes = ["pos-tile", out && "is-out", searching && index === state.highlight && "is-highlighted"]
                    .filter(Boolean)
                    .join(" ");
                return `<button type="button" class="${classes}" data-id="${item.id}" tabindex="-1"${out ? ' aria-disabled="true"' : ""}>
                    <span class="pos-tile-name">${escape(item.name)}</span>
                    ${item.variant ? `<span class="pos-tile-variant">${escape(item.variant)}</span>` : ""}
                    <span class="pos-tile-foot">
                        <span class="pos-tile-price">${escape(money(item.price))}</span>
                        <span class="pos-tile-stock${out ? " is-out" : low ? " is-low" : ""}">${escape(out ? t("out_of_stock") : t("in_stock", { count: item.stock }))}</span>
                    </span>
                    ${inCart ? `<span class="pos-tile-qty" title="${escape(t("in_cart"))}">${inCart}</span>` : ""}
                </button>`;
            })
            .join("");

        const hidden = state.results.length - shown.length;
        gridNote.hidden = state.results.length > 0 && hidden <= 0;
        gridNote.textContent = state.results.length === 0 ? t("no_results") : t("more_results", { count: hidden });

        grid.querySelector(".is-highlighted")?.scrollIntoView({ block: "nearest" });
    }

    function renderMultiplier() {
        const { qty } = parseQuery(search.value);
        const active = /^\s*\d{1,4}\s*\*/.test(search.value);
        multiplierBadge.hidden = !active;
        multiplierBadge.textContent = `× ${qty}`;
    }

    // --- Panier -------------------------------------------------------------------------------

    function renderCart() {
        lines.innerHTML = state.cart
            .map((line, index) => {
                const item = itemById.get(line.id);
                const classes = ["pos-line", index === state.selected && "is-selected", index === state.flash && "is-flash"]
                    .filter(Boolean)
                    .join(" ");
                return `<li class="${classes}" data-index="${index}">
                    <div class="pos-line-info">
                        <span class="pos-line-name">${escape(item.name)}</span>
                        ${item.variant ? `<span class="pos-line-variant">${escape(item.variant)}</span>` : ""}
                        <span class="pos-line-unit">${escape(money(item.price))}</span>
                    </div>
                    <div class="pos-line-qty">
                        <button type="button" class="btn" data-action="dec" aria-label="${escape(t("decrease"))}" tabindex="-1"${line.qty <= 1 ? " disabled" : ""}>−</button>
                        <input type="text" inputmode="numeric" class="form-control" value="${line.qty}" data-action="qty" aria-label="${escape(t("quantity"))} — ${escape(label(item))}">
                        <button type="button" class="btn" data-action="inc" aria-label="${escape(t("increase"))}" tabindex="-1"${line.qty >= item.stock ? " disabled" : ""}>+</button>
                    </div>
                    <strong class="pos-line-total">${escape(money(item.price * line.qty))}</strong>
                    <button type="button" class="btn pos-line-remove" data-action="remove" aria-label="${escape(t("remove"))} — ${escape(label(item))}" tabindex="-1">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </li>`;
            })
            .join("");
        state.flash = -1;

        const empty = state.cart.length === 0;
        cartEmpty.hidden = !empty;
        lines.hidden = empty;
        $("pos-count").textContent = cartCount();
        $("pos-total").textContent = money(cartTotal());
        $("pos-pay").disabled = empty;
        $("pos-cancel").disabled = empty;

        lines.querySelector(".is-selected")?.scrollIntoView({ block: "nearest" });
        storage.set("cart", state.cart);
    }

    function render() {
        computeResults();
        renderFilters();
        renderGrid();
        renderMultiplier();
        renderCart();
    }

    function addToCart(id, qty = 1) {
        const item = itemById.get(id);
        if (!item) return false;
        if (item.stock <= 0) {
            warn(t("sold_out", { name: label(item) }));
            return false;
        }

        let index = state.cart.findIndex((line) => line.id === id);
        const current = index >= 0 ? state.cart[index].qty : 0;
        if (current + qty > item.stock) {
            warn(t("not_enough_stock", { name: label(item), count: item.stock }));
            return false;
        }

        if (index >= 0) {
            state.cart[index].qty += qty;
        } else {
            state.cart.push({ id, qty });
            index = state.cart.length - 1;
        }
        state.selected = index;
        state.flash = index;
        beep();
        return true;
    }

    function setQuantity(index, qty) {
        const line = state.cart[index];
        if (!line) return;
        const item = itemById.get(line.id);
        if (!Number.isInteger(qty) || qty < 1) {
            render(); // saisie invalide : on réaffiche la quantité actuelle
            return;
        }
        if (qty > item.stock) {
            warn(t("not_enough_stock", { name: label(item), count: item.stock }));
            qty = item.stock;
        }
        line.qty = qty;
        state.selected = index;
        render();
    }

    function removeLine(index) {
        const line = state.cart[index];
        if (!line) return;
        state.cart.splice(index, 1);
        state.selected = Math.min(index, state.cart.length - 1);
        toast(t("removed", { name: label(itemById.get(line.id)) }));
        render();
    }

    function focusSearch() {
        if (finePointer) {
            search.focus();
            search.select();
        }
    }

    function clearSearch() {
        search.value = "";
        state.highlight = 0;
    }

    // Entrée dans la recherche : code exact (scan), sinon la vignette mise en avant
    function submitSearch() {
        const { qty, text } = parseQuery(search.value);
        const key = normalize(text.trim());
        if (key === "") return;

        const item = items.find((candidate) => candidate.skuKey && candidate.skuKey === key) ?? state.results[state.highlight];
        if (!item) {
            warn(t("not_found", { query: text.trim() }));
            search.select(); // le prochain scan remplace la saisie
            return;
        }
        if (addToCart(item.id, qty)) {
            clearSearch();
        } else {
            search.select();
        }
        render();
    }

    async function cancelSale() {
        if (state.cart.length === 0) return;
        const confirmed = window.Swal
            ? (
                  await window.Swal.fire({
                      icon: "warning",
                      title: t("confirm_cancel"),
                      text: t("confirm_cancel_text"),
                      showCancelButton: true,
                      confirmButtonText: t("confirm_cancel_yes"),
                      cancelButtonText: t("confirm_cancel_no"),
                      confirmButtonColor: "#dc3545",
                      focusCancel: true, // Entrée par réflexe ne vide pas le panier
                  })
              ).isConfirmed
            : window.confirm(t("confirm_cancel"));
        if (confirmed) {
            state.cart = [];
            state.selected = -1;
            resetPayment();
            render();
        }
        focusSearch();
    }

    // --- Encaissement -------------------------------------------------------------------------

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    const method = () => form.elements.payment_method.value;
    const isMobile = () => method() === "mobile_money";
    const referenceInput = $("pos-reference");
    let lastQuickDue = null;

    function showStep(step) {
        $("pos-step-pay").hidden = step !== "pay";
        $("pos-step-done").hidden = step !== "done";
    }

    function paymentFigures() {
        const gross = cartTotal();
        const discount = parseAmount(discountInput.value) ?? 0;
        const due = Math.max(0, gross - discount);
        const received = method() === "cash" ? parseAmount(receivedInput.value) : null;
        return { gross, discount, due, received };
    }

    // Sommes rondes qu'un client tend vraisemblablement (arrondis à 1, 2, 5 × 10ⁿ au-dessus du montant dû,
    // sans dépasser 5 fois ce montant), pour encaisser en une touche
    function quickAmounts(due) {
        const values = new Set();
        for (let power = 1; power <= 1e7; power *= 10) {
            for (const unit of [1, 2, 5]) {
                const step = unit * power;
                if (step < due / 50) continue;
                const value = Math.ceil(due / step) * step;
                if (value > due && value <= due * 5) values.add(value);
            }
        }
        return [...values].sort((a, b) => a - b).slice(0, 4);
    }

    function renderQuickCash(due) {
        if (due === lastQuickDue) return;
        lastQuickDue = due;
        $("pos-quick-cash").innerHTML = [
            `<button type="button" class="btn btn-outline-secondary" data-amount="${due}">${escape(t("exact_amount"))} · ${escape(money(due))}</button>`,
            ...quickAmounts(due).map(
                (value) => `<button type="button" class="btn btn-outline-secondary" data-amount="${value}">${escape(money(value))}</button>`,
            ),
        ].join("");
    }

    function updatePayment() {
        const { gross, discount, due, received } = paymentFigures();
        const discountInvalid = (parseAmount(discountInput.value) === null && discountInput.value.trim() !== "") || discount > gross;
        discountInput.classList.toggle("is-invalid", discountInvalid);

        $("pos-subtotal").textContent = money(gross);
        $("pos-due").textContent = money(due);
        $("pos-cash").hidden = method() !== "cash";
        receivedInput.disabled = method() !== "cash";
        receivedInput.placeholder = numberFormat.format(due); // champ vide = montant exact
        renderQuickCash(due);

        // Mobile Money : l'opérateur et le numéro de transaction sont exigés pour valider
        $("pos-mobile").hidden = !isMobile();
        form.querySelectorAll("#pos-mobile input").forEach((input) => (input.disabled = !isMobile()));
        const mobileValid = !isMobile() || (form.elements.payment_operator.value !== "" && referenceInput.value.trim() !== "");

        const change = $("pos-change");
        let valid = !discountInvalid && mobileValid;
        if (method() !== "cash" || received === null) {
            change.className = "pos-change";
            change.textContent = method() === "cash" ? t("paid_exact") : "";
        } else if (received < due) {
            valid = false;
            change.className = "pos-change is-missing";
            change.innerHTML = `<span>${escape(t("missing"))}</span><strong>${escape(money(due - received))}</strong>`;
        } else {
            change.className = "pos-change is-change";
            change.innerHTML = `<span>${escape(t("change_due"))}</span><strong>${escape(money(received - due))}</strong>`;
        }
        validateButton.disabled = !valid || state.busy;
    }

    function openPayment() {
        // Une quantité en cours de saisie est validée avant de calculer le montant dû
        if (document.activeElement?.dataset?.action === "qty") document.activeElement.blur();
        if (state.cart.length === 0) {
            warn(t("cart_empty_pay"));
            return;
        }
        showStep("pay");
        payError.classList.add("d-none");
        receivedInput.value = "";
        updatePayment();
        modal.show();
    }

    function resetPayment() {
        discountInput.value = "0";
        receivedInput.value = "";
        form.elements.payment_method[0].checked = true;
        form.querySelectorAll('[name="payment_operator"]').forEach((input) => (input.checked = false));
        referenceInput.value = "";
        form.elements.new_customer_name.value = "";
        form.elements.new_customer_phone.value = "";
        window.bootstrap.Collapse.getInstance($("new-customer"))?.hide();
        if (window.jQuery) window.jQuery("#customer-select").val("").trigger("change");
        lastQuickDue = null;
    }

    function setBusy(busy) {
        state.busy = busy;
        validateButton.querySelector(".spinner-border").classList.toggle("d-none", !busy);
        updatePayment();
    }

    function firstError(data) {
        const errors = Object.values(data?.errors ?? {}).flat();
        return errors[0] ?? data?.message ?? null;
    }

    async function submitSale() {
        if (state.busy || validateButton.disabled || state.cart.length === 0) return;

        const { discount, received } = paymentFigures();
        const body = new FormData(form);
        body.set("discount", String(discount));
        if (received === null) body.delete("amount_received");
        else body.set("amount_received", String(received));
        state.cart.forEach((line, index) => {
            body.append(`products[${index}][product_color_id]`, line.id);
            body.append(`products[${index}][quantity]`, line.qty);
        });

        setBusy(true);
        payError.classList.add("d-none");
        try {
            let response;
            try {
                response = await fetch(config.storeUrl, {
                    method: "POST",
                    body,
                    credentials: "same-origin",
                    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                });
            } catch {
                throw new Error(t("network_error"));
            }
            const data = await response.json().catch(() => ({}));
            if (response.status === 401 || response.status === 419) throw new Error(t("session_expired"));
            if (!response.ok) throw new Error(`${t("sale_failed")} : ${firstError(data) ?? response.statusText}`);
            completeSale(data);
        } catch (error) {
            beep(false);
            payError.textContent = error.message;
            payError.classList.remove("d-none");
        } finally {
            setBusy(false);
        }
    }

    function completeSale(sale) {
        state.sale = sale;
        Object.entries(sale.stocks ?? {}).forEach(([id, stock]) => {
            const item = itemById.get(Number(id));
            if (item) item.stock = stock;
        });

        // La vente est enregistrée : le panier est vidé tout de suite, même si l'écran reste ouvert
        state.cart = [];
        state.selected = -1;
        resetPayment();
        render();

        const operator = sale.payment_operator ? ` · ${config.operators[sale.payment_operator] ?? ""} ${sale.payment_reference ?? ""}` : "";
        $("pos-done-ref").textContent = `${sale.reference} · ${money(sale.total_net)} · ${config.methods[sale.payment_method] ?? ""}${operator}`;
        $("pos-done-link").href = sale.show_url;
        const change = $("pos-done-change");
        if (sale.payment_method === "cash" && sale.change_due > 0) {
            change.innerHTML = `<span>${escape(t("change_due"))}</span><strong>${escape(money(sale.change_due))}</strong>`;
        } else {
            change.innerHTML = `<span>${escape(sale.payment_method === "cash" ? t("paid_exact") : config.methods[sale.payment_method] ?? "")}</span>`;
        }
        showStep("done");
        beep();
        $("pos-new-sale").focus();

        if (prefs.autoPrint) printReceipt();
    }

    // Le reçu est chargé dans un cadre invisible puis imprimé : la caisse ne change pas de page
    function printReceipt() {
        if (!state.sale) return;
        const url = state.sale.receipt_url;
        const print = () => {
            const active = document.activeElement;
            try {
                printFrame.contentWindow.print();
            } catch {
                window.open(`${url}?print=1`, "_blank");
            }
            // Le clavier revient à la caisse (sinon il reste dans le cadre du reçu)
            active?.focus();
        };
        if (printFrame.dataset.url === url) {
            print();
            return;
        }
        printFrame.onload = print;
        printFrame.dataset.url = url;
        printFrame.src = url;
    }

    // --- Événements ---------------------------------------------------------------------------

    search.addEventListener("input", () => {
        state.highlight = 0;
        computeResults();
        renderGrid();
        renderMultiplier();
    });

    grid.addEventListener("click", (event) => {
        const tile = event.target.closest(".pos-tile");
        if (!tile) return;
        const { qty } = parseQuery(search.value);
        if (addToCart(Number(tile.dataset.id), qty) && qty > 1) clearSearch();
        render();
        focusSearch(); // la saisie reste sélectionnée : le prochain scan la remplace
    });

    root.querySelector(".pos-filters").addEventListener("click", (event) => {
        const button = event.target.closest(".pos-chip");
        if (!button) return;
        if ("category" in button.dataset) {
            const value = button.dataset.category;
            state.category = value === "" ? null : value === "top" ? "top" : Number(value);
        }
        if ("brand" in button.dataset) {
            state.brand = button.dataset.brand === "" ? null : Number(button.dataset.brand);
        }
        state.highlight = 0;
        render();
        focusSearch();
    });

    lines.addEventListener("click", (event) => {
        const row = event.target.closest(".pos-line");
        if (!row) return;
        const index = Number(row.dataset.index);
        const action = event.target.closest("[data-action]")?.dataset.action;
        const line = state.cart[index];

        if (action === "qty") {
            // Ne pas redessiner : la saisie de la quantité est en cours
            state.selected = index;
            lines.querySelectorAll(".pos-line").forEach((element, i) => element.classList.toggle("is-selected", i === index));
            event.target.select();
            return;
        }
        if (action === "inc") setQuantity(index, line.qty + 1);
        else if (action === "dec") setQuantity(index, line.qty - 1);
        else if (action === "remove") removeLine(index);
        else {
            state.selected = index;
            renderCart();
        }
        focusSearch();
    });

    lines.addEventListener("change", (event) => {
        if (event.target.dataset.action !== "qty") return;
        setQuantity(Number(event.target.closest(".pos-line").dataset.index), Number(event.target.value.trim()));
    });
    lines.addEventListener("keydown", (event) => {
        if (event.target.dataset.action === "qty" && (event.key === "Enter" || event.key === "Escape")) {
            event.preventDefault();
            if (event.key === "Escape") event.target.value = state.cart[Number(event.target.closest(".pos-line").dataset.index)]?.qty ?? "";
            search.focus(); // la sortie du champ valide la quantité (événement change)
        }
    });

    function moveSelection(delta) {
        if (state.cart.length === 0) return;
        const from = state.selected < 0 ? state.cart.length : state.selected;
        state.selected = Math.min(state.cart.length - 1, Math.max(0, from + delta));
        renderCart();
    }

    function moveHighlight(delta) {
        const count = Math.min(state.results.length, MAX_TILES);
        if (count === 0) return;
        state.highlight = (state.highlight + delta + count) % count;
        renderGrid();
    }

    search.addEventListener("keydown", (event) => {
        const empty = search.value.trim() === "";
        switch (event.key) {
            case "Enter":
                event.preventDefault();
                submitSearch();
                break;
            case "ArrowDown":
            case "ArrowUp":
                event.preventDefault();
                if (empty) moveSelection(event.key === "ArrowDown" ? 1 : -1);
                else moveHighlight(event.key === "ArrowDown" ? 1 : -1);
                break;
            case "+":
            case "-":
                // Champ vide : + et − changent la quantité de la ligne choisie (pavé numérique)
                if (empty && state.cart[state.selected]) {
                    event.preventDefault();
                    setQuantity(state.selected, state.cart[state.selected].qty + (event.key === "+" ? 1 : -1));
                }
                break;
            case "Delete":
                if (empty && state.selected >= 0) {
                    event.preventDefault();
                    removeLine(state.selected);
                }
                break;
            case "Escape":
                event.preventDefault();
                if (!empty) {
                    clearSearch();
                } else {
                    state.category = defaultCategory;
                    state.brand = null;
                }
                render();
                break;
        }
    });

    document.addEventListener("keydown", (event) => {
        const paymentOpen = modalElement.classList.contains("show");
        const dialogOpen = window.Swal?.isVisible();

        if (paymentOpen) {
            // Écran « vente terminée » : P réimprime le reçu, Entrée passe au client suivant
            if (!$("pos-step-done").hidden && !event.ctrlKey && !event.metaKey && !event.altKey) {
                if (event.key.toLowerCase() === "p") {
                    event.preventDefault();
                    printReceipt();
                } else if (event.key === "Enter" && !event.target.closest("button, a")) {
                    event.preventDefault();
                    modal.hide();
                }
            }
            return;
        }
        if (dialogOpen) return;

        if (event.key === "F2") {
            event.preventDefault();
            search.focus();
            search.select();
        } else if (event.key === "F4") {
            event.preventDefault();
            cancelSale();
        } else if (event.key === "F9") {
            event.preventDefault();
            openPayment();
        } else if (
            // Une saisie (ou un scan) hors d'un champ est redirigée vers la recherche : aucun code perdu.
            // L'espace reste à un bouton qui a le focus.
            event.key.length === 1 &&
            event.key !== " " &&
            !event.ctrlKey &&
            !event.altKey &&
            !event.metaKey &&
            !event.target.closest("input, textarea, select, [contenteditable], .dropdown-menu")
        ) {
            search.focus();
        }
    });

    $("pos-pay").addEventListener("click", openPayment);
    $("pos-cancel").addEventListener("click", cancelSale);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitSale();
    });
    form.addEventListener("input", (event) => {
        if (event.target === receivedInput || event.target === discountInput || event.target === referenceInput) updatePayment();
    });
    form.addEventListener("change", (event) => {
        if (event.target.name === "payment_method") {
            updatePayment();
            if (method() === "cash") receivedInput.focus();
            if (isMobile()) (form.elements.payment_operator.value ? referenceInput : form.querySelector('[name="payment_operator"]')).focus();
        }
        if (event.target.name === "payment_operator") {
            updatePayment();
            referenceInput.focus();
        }
    });
    $("pos-quick-cash").addEventListener("click", (event) => {
        const button = event.target.closest("[data-amount]");
        if (!button) return;
        receivedInput.value = numberFormat.format(Number(button.dataset.amount));
        updatePayment();
        validateButton.focus();
    });

    modalElement.addEventListener("shown.bs.modal", () => {
        if (!$("pos-step-pay").hidden) {
            (method() === "cash" ? receivedInput : validateButton).focus();
        }
    });
    modalElement.addEventListener("hidden.bs.modal", () => {
        showStep("pay");
        focusSearch();
    });
    $("pos-print").addEventListener("click", printReceipt);
    $("pos-new-sale").addEventListener("click", () => modal.hide());

    // Réglages du poste
    const soundSwitch = $("pos-pref-sound");
    const printSwitch = $("pos-pref-print");
    soundSwitch.checked = prefs.sound;
    printSwitch.checked = prefs.autoPrint;
    soundSwitch.addEventListener("change", () => storage.set("sound", (prefs.sound = soundSwitch.checked)));
    printSwitch.addEventListener("change", () => storage.set("autoPrint", (prefs.autoPrint = printSwitch.checked)));

    // Sur grand écran, la caisse occupe toute la hauteur visible : rien à faire défiler hors des listes
    function fitHeight() {
        if (window.matchMedia("(min-width: 992px)").matches) {
            const top = root.getBoundingClientRect().top + window.scrollY;
            root.style.height = `${Math.max(480, window.innerHeight - top - 12)}px`;
        } else {
            root.style.height = "";
        }
    }
    window.addEventListener("resize", fitHeight);
    fitHeight();

    render();
    if (state.cart.length) toast(t("cart_restored"));
    focusSearch();
}
