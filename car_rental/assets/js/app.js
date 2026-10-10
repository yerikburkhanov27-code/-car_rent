(() => {
  const DEFAULT_LANG = "kk";
  const LANGS = ["kk", "ru", "en"];

  const state = {
    lang: localStorage.getItem("nurdrive_lang") || DEFAULT_LANG,
    cars: [],
    brands: [],
    clients: [],
    employees: [],
    rentals: [],
    payments: [],
    adminTab: "cars",
  };

  function t(key) {
    const dict = window.I18N?.[state.lang] || window.I18N?.[DEFAULT_LANG] || {};
    return dict[key] || window.I18N?.[DEFAULT_LANG]?.[key] || key;
  }

  function applyI18n() {
    document.documentElement.lang = state.lang;
    document.querySelectorAll("[data-i18n]").forEach((el) => {
      el.textContent = t(el.getAttribute("data-i18n"));
    });
    document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
      el.setAttribute("placeholder", t(el.getAttribute("data-i18n-placeholder")));
    });
    document.querySelectorAll(".lang-btn").forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.lang === state.lang);
    });
    const title = t("brand");
    document.title = `${title} · ${t("tagline")}`;
  }

  function setLang(lang) {
    if (!LANGS.includes(lang)) return;
    state.lang = lang;
    localStorage.setItem("nurdrive_lang", lang);
    applyI18n();
    refreshCurrentPage();
  }

  function toast(message, kind = "ok") {
    const host = document.getElementById("toastHost");
    if (!host) return;
    const el = document.createElement("div");
    el.className = `toast ${kind}`;
    el.textContent = message;
    host.appendChild(el);
    setTimeout(() => el.remove(), 3200);
  }

  function closeModal() {
    document.getElementById("modalHost").innerHTML = "";
  }

  function openModal(title, bodyHtml, footerHtml = "") {
    const host = document.getElementById("modalHost");
    host.innerHTML = `
      <div class="modal-backdrop" id="modalBackdrop">
        <div class="modal-panel">
          <div class="flex items-start justify-between gap-3 mb-4">
            <h3 class="text-xl font-bold text-sand">${title}</h3>
            <button type="button" class="btn-ghost px-3 py-1" id="modalClose">${t("btn.close")}</button>
          </div>
          <div>${bodyHtml}</div>
          ${footerHtml}
        </div>
      </div>`;
    document.getElementById("modalClose").onclick = closeModal;
    document.getElementById("modalBackdrop").addEventListener("click", (e) => {
      if (e.target.id === "modalBackdrop") closeModal();
    });
  }

  async function api(path, options = {}) {
    const opts = { headers: { "Content-Type": "application/json" }, ...options };
    if (opts.body && typeof opts.body !== "string") {
      opts.body = JSON.stringify(opts.body);
    }
    const res = await fetch(path, opts);
    let json;
    try {
      json = await res.json();
    } catch {
      throw new Error(t("toast.error"));
    }
    if (!json.ok) {
      throw new Error(json.message || t("toast.error"));
    }
    return json.data;
  }

  function money(n) {
    return `${Number(n).toLocaleString("kk-KZ")} ₸`;
  }

  function daysBetween(start, end) {
    if (!start || !end) return 1;
    const a = new Date(start);
    const b = new Date(end);
    const diff = Math.round((b - a) / 86400000);
    return Math.max(1, diff);
  }

  function todayISO(offset = 0) {
    const d = new Date();
    d.setDate(d.getDate() + offset);
    return d.toISOString().slice(0, 10);
  }

  function navInit() {
    const page = window.CURRENT_PAGE;
    document.querySelectorAll("[data-nav]").forEach((a) => {
      a.classList.toggle("active", a.getAttribute("data-nav") === page);
    });
    const toggle = document.getElementById("navToggle");
    const mobile = document.getElementById("mobileNav");
    const main = document.getElementById("mainNav");
    if (toggle && mobile && main) {
      mobile.innerHTML = main.innerHTML;
      toggle.onclick = () => mobile.classList.toggle("hidden");
    }
    document.querySelectorAll(".lang-btn").forEach((btn) => {
      btn.onclick = () => setLang(btn.dataset.lang);
    });
  }

  function carCard(car) {
    const available = car.status === "available";
    return `
      <article class="card-surface p-5 flex flex-col gap-3">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-lg font-bold">${car.brand} ${car.model}</h3>
            <p class="text-slate-400 text-sm">${t("car.plate")}: ${car.license_plate}</p>
          </div>
          <span class="text-xs rounded-full px-2 py-1 ${available ? "bg-emerald-900/60 text-emerald-200" : "bg-rose-900/50 text-rose-200"}">
            ${t("status." + car.status)}
          </span>
        </div>
        <p class="text-2xl font-black text-sand">${money(car.daily_rate)} <span class="text-sm font-medium text-slate-400">/ ${t("car.rate")}</span></p>
        <button type="button" class="btn-primary" data-book="${car.car_id}" ${available ? "" : "disabled"}>
          ${available ? t("car.book") : t("car.unavailable")}
        </button>
      </article>`;
  }

  async function loadCars(params = {}) {
    const qs = new URLSearchParams(params);
    const data = await api("api/cars.php?" + qs.toString());
    state.cars = data.cars;
    state.brands = data.brands;
    return data;
  }

  function fillBrandFilter() {
    const sel = document.getElementById("brandFilter");
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = `<option value="">${t("filter.all")}</option>` +
      state.brands.map((b) => `<option value="${b}">${b}</option>`).join("");
    sel.value = current;
  }

  function fillCarSelect(selectId, onlyAvailable = false) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const list = onlyAvailable ? state.cars.filter((c) => c.status === "available") : state.cars;
    sel.innerHTML = list.map((c) =>
      `<option value="${c.car_id}" data-rate="${c.daily_rate}">${c.brand} ${c.model} — ${money(c.daily_rate)}</option>`
    ).join("");
  }

  function bindOnce(el, event, fn) {
    if (!el) return;
    const key = "bound_" + event;
    if (el.dataset[key]) return;
    el.dataset[key] = "1";
    el.addEventListener(event, fn);
  }

  function bindBookButtons(root = document) {
    root.querySelectorAll("[data-book]").forEach((btn) => {
      btn.onclick = () => openBookingModal(btn.getAttribute("data-book"));
    });
  }

  function updateCalc() {
    const sel = document.getElementById("calcCar");
    const start = document.getElementById("calcStart")?.value;
    const end = document.getElementById("calcEnd")?.value;
    const daysEl = document.getElementById("calcDays");
    const totalEl = document.getElementById("calcTotal");
    if (!sel || !daysEl || !totalEl) return;
    const opt = sel.selectedOptions[0];
    const rate = Number(opt?.dataset.rate || 0);
    const days = daysBetween(start, end);
    daysEl.textContent = `${days} ${t("days.unit")}`;
    totalEl.textContent = money(days * rate);
  }

  async function openBookingModal(carId = "") {
    if (!state.cars.length) await loadCars();
    if (!state.clients.length) state.clients = await api("api/clients.php");
    if (!state.employees.length) state.employees = await api("api/employees.php");
    const cars = state.cars.filter((c) => c.status === "available");
    if (!cars.length) {
      toast(t("car.unavailable"), "err");
      return;
    }
    const carOptions = cars.map((c) =>
      `<option value="${c.car_id}" data-rate="${c.daily_rate}" ${String(c.car_id) === String(carId) ? "selected" : ""}>${c.brand} ${c.model}</option>`
    ).join("");
    const clientOptions = state.clients.map((c) =>
      `<option value="${c.client_id}">${c.first_name} ${c.last_name} — ${c.phone}</option>`
    ).join("");
    const empOptions = state.employees.map((e) =>
      `<option value="${e.employee_id}">${e.first_name} ${e.last_name}</option>`
    ).join("");

    openModal(t("modal.book.title"), `
      <form id="bookForm" class="grid gap-3">
        <label class="text-sm text-slate-400">${t("calc.car")}
          <select name="car_id" id="bookCar" class="field mt-1">${carOptions}</select>
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label class="text-sm text-slate-400">${t("calc.start")}
            <input type="date" name="start_date" id="bookStart" class="field mt-1" value="${todayISO()}" required>
          </label>
          <label class="text-sm text-slate-400">${t("calc.end")}
            <input type="date" name="end_date" id="bookEnd" class="field mt-1" value="${todayISO(2)}" required>
          </label>
        </div>
        <p class="flex justify-between"><span>${t("calc.days")}</span><strong id="bookDays">1</strong></p>
        <p class="flex justify-between text-sand"><span>${t("calc.total")}</span><strong id="bookTotal">0 ₸</strong></p>
        <label class="text-sm text-slate-400">${t("form.employee")}
          <select name="employee_id" class="field mt-1">${empOptions}</select>
        </label>
        <div class="flex gap-2">
          <button type="button" class="btn-ghost flex-1" id="modeExisting">${t("modal.client.existing")}</button>
          <button type="button" class="btn-ghost flex-1" id="modeNew">${t("modal.client.new")}</button>
        </div>
        <div id="existingClient">
          <label class="text-sm text-slate-400">${t("form.client")}
            <select name="client_id" class="field mt-1">${clientOptions}</select>
          </label>
        </div>
        <div id="newClient" class="hidden grid gap-2">
          <input name="first_name" class="field" placeholder="${t("form.firstName")}">
          <input name="last_name" class="field" placeholder="${t("form.lastName")}">
          <input name="phone" class="field" placeholder="${t("form.phone")}">
          <input name="driver_license" class="field" placeholder="${t("form.license")}">
        </div>
        <button class="btn-primary" type="submit">${t("form.submit")}</button>
      </form>
    `);

    let useNew = false;
    const syncBook = () => {
      const car = document.getElementById("bookCar");
      const rate = Number(car.selectedOptions[0]?.dataset.rate || 0);
      const days = daysBetween(document.getElementById("bookStart").value, document.getElementById("bookEnd").value);
      document.getElementById("bookDays").textContent = `${days} ${t("days.unit")}`;
      document.getElementById("bookTotal").textContent = money(days * rate);
    };
    ["bookCar", "bookStart", "bookEnd"].forEach((id) => {
      document.getElementById(id).addEventListener("change", syncBook);
      document.getElementById(id).addEventListener("input", syncBook);
    });
    document.getElementById("modeNew").onclick = () => {
      useNew = true;
      document.getElementById("newClient").classList.remove("hidden");
      document.getElementById("existingClient").classList.add("hidden");
    };
    document.getElementById("modeExisting").onclick = () => {
      useNew = false;
      document.getElementById("newClient").classList.add("hidden");
      document.getElementById("existingClient").classList.remove("hidden");
    };
    syncBook();
    document.getElementById("bookForm").onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = Object.fromEntries(fd.entries());
      if (useNew) {
        delete payload.client_id;
      } else {
        delete payload.first_name;
        delete payload.last_name;
        delete payload.phone;
        delete payload.driver_license;
      }
      try {
        await api("api/rentals.php", { method: "POST", body: payload });
        closeModal();
        toast(t("toast.booked"));
        await refreshCurrentPage();
      } catch (err) {
        toast(err.message, "err");
      }
    };
  }

  async function openClientModal() {
    openModal(t("clients.add"), `
      <form id="clientForm" class="grid gap-3">
        <input name="first_name" class="field" placeholder="${t("form.firstName")}" required>
        <input name="last_name" class="field" placeholder="${t("form.lastName")}" required>
        <input name="phone" class="field" placeholder="${t("form.phone")}" required>
        <input name="driver_license" class="field" placeholder="${t("form.license")}" required>
        <button class="btn-primary" type="submit">${t("form.save")}</button>
      </form>
    `);
    document.getElementById("clientForm").onsubmit = async (e) => {
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(e.target).entries());
      try {
        await api("api/clients.php", { method: "POST", body: payload });
        closeModal();
        toast(t("toast.saved"));
        await refreshCurrentPage();
      } catch (err) {
        toast(err.message, "err");
      }
    };
  }

  async function openHistory(clientId) {
    const data = await api("api/clients.php?client_id=" + clientId);
    const rows = data.history.map((r) => `
      <tr>
        <td>${r.rental_id}</td>
        <td>${r.brand} ${r.model}</td>
        <td>${r.start_date} → ${r.end_date}</td>
        <td>${money(r.total_amount)}</td>
        <td>${t("status." + r.status)}</td>
      </tr>`).join("") || `<tr><td colspan="5">${t("table.empty")}</td></tr>`;
    openModal(`${t("clients.historyTitle")}: ${data.client.first_name} ${data.client.last_name}`, `
      <table class="data">
        <thead><tr>
          <th>${t("table.id")}</th><th>${t("nav.cars")}</th><th>${t("calc.start")}</th>
          <th>${t("calc.total")}</th><th>${t("filter.status")}</th>
        </tr></thead>
        <tbody>${rows}</tbody>
      </table>
    `);
  }

  async function openPaymentModal(rentalId = "") {
    if (!state.rentals.length) state.rentals = await api("api/rentals.php");
    const opts = state.rentals.map((r) =>
      `<option value="${r.rental_id}" ${String(r.rental_id) === String(rentalId) ? "selected" : ""}>#${r.rental_id} ${r.client_name} / ${r.car_name} (${money(r.total_amount)})</option>`
    ).join("");
    openModal(t("payments.new"), `
      <form id="payForm" class="grid gap-3">
        <label class="text-sm text-slate-400">${t("form.rental")}
          <select name="rental_id" class="field mt-1">${opts}</select>
        </label>
        <label class="text-sm text-slate-400">${t("form.amount")}
          <input name="amount" type="number" min="1" step="0.01" class="field mt-1" required>
        </label>
        <label class="text-sm text-slate-400">${t("form.method")}
          <select name="payment_method" class="field mt-1">
            <option value="card">${t("method.card")}</option>
            <option value="cash">${t("method.cash")}</option>
            <option value="transfer">${t("method.transfer")}</option>
          </select>
        </label>
        <button class="btn-primary" type="submit">${t("form.submit")}</button>
      </form>
    `);
    document.getElementById("payForm").onsubmit = async (e) => {
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(e.target).entries());
      try {
        const data = await api("api/payments.php", { method: "POST", body: payload });
        closeModal();
        toast(t("toast.paid"));
        await refreshCurrentPage();
        if (data?.payment_id) await openReceipt(data.payment_id);
      } catch (err) {
        toast(err.message, "err");
      }
    };
  }

  async function openReceipt(paymentId) {
    const p = await api("api/payments.php?payment_id=" + paymentId);
    openModal(t("payments.receipt"), `
      <div class="receipt-print space-y-2" id="receiptBox">
        <h4 class="text-2xl font-black">NurDrive</h4>
        <p>#${p.payment_id} · ${p.payment_date}</p>
        <p>${p.client_name} · ${p.phone}</p>
        <p>${p.car_name} · ${p.license_plate}</p>
        <p>${p.start_date} → ${p.end_date}</p>
        <p class="text-xl font-bold">${money(p.amount)} · ${t("method." + p.payment_method)}</p>
      </div>
      <div class="mt-4 no-print">
        <button type="button" class="btn-primary" id="printReceipt">${t("btn.print")}</button>
      </div>
    `);
    document.getElementById("printReceipt").onclick = () => window.print();
  }

  async function pageHome() {
    const homeFleet = document.getElementById("homeFleet");
    if (!homeFleet) return;
    await loadCars({ status: "available" });
    fillCarSelect("calcCar", true);
    const start = document.getElementById("calcStart");
    const end = document.getElementById("calcEnd");
    if (start && !start.value) start.value = todayISO();
    if (end && !end.value) end.value = todayISO(3);
    ["calcCar", "calcStart", "calcEnd"].forEach((id) => {
      const el = document.getElementById(id);
      bindOnce(el, "change", updateCalc);
      bindOnce(el, "input", updateCalc);
    });
    updateCalc();
    homeFleet.innerHTML = state.cars.slice(0, 6).map(carCard).join("");
    bindBookButtons(homeFleet);
    bindOnce(document.getElementById("heroBookBtn"), "click", () => openBookingModal());
    bindOnce(document.getElementById("calcBookBtn"), "click", () => {
      openBookingModal(document.getElementById("calcCar")?.value);
    });
  }

  async function pageCars() {
    const grid = document.getElementById("fleetGrid");
    if (!grid) return;
    const form = document.getElementById("carFilters");
    const render = async () => {
      const fd = new FormData(form);
      const params = {};
      for (const [k, v] of fd.entries()) {
        if (v) params[k] = v;
      }
      await loadCars(params);
      fillBrandFilter();
      grid.innerHTML = state.cars.map(carCard).join("") || `<p class="text-slate-400">${t("table.empty")}</p>`;
      bindBookButtons(grid);
    };
    bindOnce(form, "submit", (e) => {
      e.preventDefault();
      render();
    });
    bindOnce(form, "reset", () => setTimeout(render, 0));
    bindOnce(document.getElementById("openBookBtn"), "click", () => openBookingModal());
    await render();
  }

  async function pageRentals() {
    const table = document.getElementById("rentalsTable");
    if (!table) return;
    state.rentals = await api("api/rentals.php");
    table.innerHTML = `
      <thead><tr>
        <th>${t("table.id")}</th><th>${t("form.client")}</th><th>${t("nav.cars")}</th>
        <th>${t("calc.start")}</th><th>${t("calc.end")}</th><th>${t("calc.total")}</th>
        <th>${t("filter.status")}</th><th>${t("table.actions")}</th>
      </tr></thead>
      <tbody>
        ${state.rentals.map((r) => `
          <tr>
            <td>${r.rental_id}</td>
            <td>${r.client_name}</td>
            <td>${r.car_name}<div class="text-xs text-slate-400">${r.license_plate}</div></td>
            <td>${r.start_date}</td>
            <td>${r.end_date}</td>
            <td>${money(r.total_amount)}</td>
            <td>${t("status." + r.status)}</td>
            <td class="space-x-2 whitespace-nowrap">
              <button class="text-copper" data-pay="${r.rental_id}">${t("btn.pay")}</button>
              ${r.status === "active" ? `<button class="text-emerald-300" data-complete="${r.rental_id}">${t("btn.complete")}</button>
              <button class="text-rose-300" data-cancel="${r.rental_id}">${t("btn.cancelRental")}</button>` : ""}
            </td>
          </tr>`).join("") || `<tr><td colspan="8">${t("table.empty")}</td></tr>`}
      </tbody>`;
    table.querySelectorAll("[data-pay]").forEach((b) => b.onclick = () => openPaymentModal(b.dataset.pay));
    table.querySelectorAll("[data-complete]").forEach((b) => b.onclick = async () => {
      try {
        await api("api/rentals.php", { method: "PUT", body: { rental_id: b.dataset.complete, status: "completed" } });
        toast(t("toast.saved"));
        pageRentals();
      } catch (err) { toast(err.message, "err"); }
    });
    table.querySelectorAll("[data-cancel]").forEach((b) => b.onclick = async () => {
      try {
        await api("api/rentals.php", { method: "PUT", body: { rental_id: b.dataset.cancel, status: "cancelled" } });
        toast(t("toast.saved"));
        pageRentals();
      } catch (err) { toast(err.message, "err"); }
    });
    bindOnce(document.getElementById("openBookBtn"), "click", () => openBookingModal());
  }

  async function pageClients() {
    const table = document.getElementById("clientsTable");
    if (!table) return;
    const q = document.getElementById("clientSearch")?.value || "";
    state.clients = await api("api/clients.php" + (q ? "?q=" + encodeURIComponent(q) : ""));
    table.innerHTML = `
      <thead><tr>
        <th>${t("table.id")}</th><th>${t("form.firstName")}</th><th>${t("form.lastName")}</th>
        <th>${t("form.phone")}</th><th>${t("form.license")}</th><th>${t("table.actions")}</th>
      </tr></thead>
      <tbody>
        ${state.clients.map((c) => `
          <tr>
            <td>${c.client_id}</td><td>${c.first_name}</td><td>${c.last_name}</td>
            <td>${c.phone}</td><td>${c.driver_license}</td>
            <td><button class="text-copper" data-hist="${c.client_id}">${t("btn.history")}</button></td>
          </tr>`).join("") || `<tr><td colspan="6">${t("table.empty")}</td></tr>`}
      </tbody>`;
    table.querySelectorAll("[data-hist]").forEach((b) => b.onclick = () => openHistory(b.dataset.hist));
    bindOnce(document.getElementById("addClientBtn"), "click", openClientModal);
    const search = document.getElementById("clientSearch");
    if (search && !search.dataset.bound) {
      search.dataset.bound = "1";
      search.addEventListener("input", () => {
        clearTimeout(search._t);
        search._t = setTimeout(pageClients, 250);
      });
    }
  }

  async function pagePayments() {
    const table = document.getElementById("paymentsTable");
    if (!table) return;
    state.payments = await api("api/payments.php");
    table.innerHTML = `
      <thead><tr>
        <th>${t("table.id")}</th><th>${t("form.rental")}</th><th>${t("form.client")}</th>
        <th>${t("form.amount")}</th><th>${t("form.method")}</th><th>${t("table.actions")}</th>
      </tr></thead>
      <tbody>
        ${state.payments.map((p) => `
          <tr>
            <td>${p.payment_id}</td>
            <td>#${p.rental_id} ${p.car_name || ""}</td>
            <td>${p.client_name || ""}</td>
            <td>${money(p.amount)}</td>
            <td>${t("method." + p.payment_method)}</td>
            <td><button class="text-copper" data-receipt="${p.payment_id}">${t("payments.receipt")}</button></td>
          </tr>`).join("") || `<tr><td colspan="6">${t("table.empty")}</td></tr>`}
      </tbody>`;
    table.querySelectorAll("[data-receipt]").forEach((b) => b.onclick = () => openReceipt(b.dataset.receipt));
    bindOnce(document.getElementById("addPaymentBtn"), "click", () => openPaymentModal());
  }

  const ADMIN_SCHEMA = {
    cars: {
      endpoint: "api/cars.php",
      id: "car_id",
      fields: [
        { key: "brand", label: "filter.brand" },
        { key: "model", label: "form.model" },
        { key: "license_plate", label: "car.plate" },
        { key: "daily_rate", label: "form.dailyRate", type: "number" },
        { key: "status", label: "filter.status", type: "select", options: ["available", "rented"] },
      ],
    },
    clients: {
      endpoint: "api/clients.php",
      id: "client_id",
      fields: [
        { key: "first_name", label: "form.firstName" },
        { key: "last_name", label: "form.lastName" },
        { key: "phone", label: "form.phone" },
        { key: "driver_license", label: "form.license" },
      ],
    },
    employees: {
      endpoint: "api/employees.php",
      id: "employee_id",
      fields: [
        { key: "first_name", label: "form.firstName" },
        { key: "last_name", label: "form.lastName" },
        { key: "position", label: "form.position" },
        { key: "phone", label: "form.phone" },
      ],
    },
    rentals: {
      endpoint: "api/rentals.php",
      id: "rental_id",
      fields: [
        { key: "client_id", label: "form.client", type: "number" },
        { key: "car_id", label: "nav.cars", type: "number" },
        { key: "employee_id", label: "form.employee", type: "number" },
        { key: "start_date", label: "calc.start", type: "date" },
        { key: "end_date", label: "calc.end", type: "date" },
        { key: "total_amount", label: "form.amount", type: "number" },
        { key: "status", label: "filter.status", type: "select", options: ["active", "completed", "cancelled"] },
      ],
    },
    payments: {
      endpoint: "api/payments.php",
      id: "payment_id",
      fields: [
        { key: "rental_id", label: "form.rental", type: "number" },
        { key: "payment_date", label: "calc.start" },
        { key: "amount", label: "form.amount", type: "number" },
        { key: "payment_method", label: "form.method", type: "select", options: ["card", "cash", "transfer"] },
      ],
    },
    admins: {
      endpoint: "api/admins.php",
      id: "admin_id",
      fields: [
        { key: "username", label: "form.username" },
        { key: "password", label: "form.password", type: "password" },
      ],
      hideOnTable: ["password"],
    },
  };

  function adminFormFields(schema, row = {}) {
    return schema.fields.map((f) => {
      const val = row[f.key] ?? "";
      if (f.type === "select") {
        return `<label class="text-sm text-slate-400">${t(f.label)}
          <select name="${f.key}" class="field mt-1">
            ${f.options.map((o) => `<option value="${o}" ${o === val ? "selected" : ""}>${t("status." + o) !== "status." + o ? t("status." + o) : (t("method." + o) !== "method." + o ? t("method." + o) : o)}</option>`).join("")}
          </select></label>`;
      }
      return `<label class="text-sm text-slate-400">${t(f.label)}
        <input class="field mt-1" name="${f.key}" type="${f.type || "text"}" value="${f.type === "password" ? "" : val}" ${f.type === "password" && row[schema.id] ? "" : (f.key === "password" ? "required" : "")}>
      </label>`;
    }).join("");
  }

  function openAdminForm(tab, row = null) {
    const schema = ADMIN_SCHEMA[tab];
    openModal(row ? t("form.edit") : t("form.create"), `
      <form id="adminForm" class="grid gap-3">
        ${row ? `<input type="hidden" name="${schema.id}" value="${row[schema.id]}">` : ""}
        ${adminFormFields(schema, row || {})}
        <button class="btn-primary" type="submit">${t("form.save")}</button>
      </form>
    `);
    document.getElementById("adminForm").onsubmit = async (e) => {
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(e.target).entries());
      if (tab === "admins" && row && !payload.password) delete payload.password;
      try {
        await api(schema.endpoint, { method: row ? "PUT" : "POST", body: payload });
        closeModal();
        toast(t("toast.saved"));
        pageAdmin();
      } catch (err) {
        toast(err.message, "err");
      }
    };
  }

  async function pageAdmin() {
    const table = document.getElementById("adminTable");
    if (!table) return;
    const metrics = await api("api/dashboard.php");
    document.getElementById("metrics").innerHTML = `
      <div class="card-surface p-5"><p class="text-slate-400" data-i18n="admin.metrics.fleet">${t("admin.metrics.fleet")}</p><p class="text-3xl font-black text-sand">${metrics.fleet}</p></div>
      <div class="card-surface p-5"><p class="text-slate-400">${t("admin.metrics.active")}</p><p class="text-3xl font-black text-sand">${metrics.active_rentals}</p></div>
      <div class="card-surface p-5"><p class="text-slate-400">${t("admin.metrics.revenue")}</p><p class="text-3xl font-black text-sand">${money(metrics.revenue)}</p></div>
    `;
    document.querySelectorAll(".admin-tab").forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.tab === state.adminTab);
      btn.onclick = () => {
        state.adminTab = btn.dataset.tab;
        pageAdmin();
      };
    });
    const schema = ADMIN_SCHEMA[state.adminTab];
    let rows = await api(schema.endpoint);
    if (rows && rows.cars) rows = rows.cars;
    const keys = schema.fields.map((f) => f.key).filter((k) => !(schema.hideOnTable || []).includes(k));
    table.innerHTML = `
      <thead><tr>
        <th>${t("table.id")}</th>
        ${keys.map((k) => `<th>${t(schema.fields.find((f) => f.key === k).label)}</th>`).join("")}
        <th>${t("table.actions")}</th>
      </tr></thead>
      <tbody>
        ${(rows || []).map((row) => `
          <tr>
            <td>${row[schema.id]}</td>
            ${keys.map((k) => `<td>${row[k] ?? ""}</td>`).join("")}
            <td class="whitespace-nowrap space-x-2">
              <button class="text-copper" data-edit="${row[schema.id]}">${t("form.edit")}</button>
              <button class="text-rose-300" data-del="${row[schema.id]}">${t("form.delete")}</button>
            </td>
          </tr>`).join("") || `<tr><td colspan="${keys.length + 2}">${t("table.empty")}</td></tr>`}
      </tbody>`;
    table.querySelectorAll("[data-edit]").forEach((b) => {
      b.onclick = () => {
        const row = rows.find((r) => String(r[schema.id]) === String(b.dataset.edit));
        openAdminForm(state.adminTab, row);
      };
    });
    table.querySelectorAll("[data-del]").forEach((b) => {
      b.onclick = async () => {
        if (!confirm(t("confirm.delete"))) return;
        try {
          await api(schema.endpoint, { method: "DELETE", body: { [schema.id]: b.dataset.del } });
          toast(t("toast.deleted"));
          pageAdmin();
        } catch (err) {
          toast(err.message, "err");
        }
      };
    });
    const createBtn = document.getElementById("adminCreateBtn");
    if (createBtn) createBtn.onclick = () => openAdminForm(state.adminTab, null);
  }

  async function pageLogin() {
    const form = document.getElementById("loginForm");
    if (!form) return;
    form.onsubmit = async (e) => {
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(form).entries());
      try {
        await api("api/auth.php", { method: "POST", body: payload });
        toast(t("toast.loginOk"));
        window.location.href = "admin.php";
      } catch {
        toast(t("toast.loginFail"), "err");
      }
    };
  }

  async function refreshCurrentPage() {
    const page = window.CURRENT_PAGE;
    if (page === "index.php") await pageHome();
    if (page === "cars.php") await pageCars();
    if (page === "rentals.php") await pageRentals();
    if (page === "clients.php") await pageClients();
    if (page === "payments.php") await pagePayments();
    if (page === "admin.php") await pageAdmin();
  }

  document.addEventListener("DOMContentLoaded", async () => {
    navInit();
    applyI18n();
    try {
      const page = window.CURRENT_PAGE;
      if (page === "index.php") await pageHome();
      else if (page === "cars.php") await pageCars();
      else if (page === "rentals.php") await pageRentals();
      else if (page === "clients.php") await pageClients();
      else if (page === "payments.php") await pagePayments();
      else if (page === "admin.php") await pageAdmin();
      else if (page === "login.php") await pageLogin();
    } catch (err) {
      toast(err.message || t("toast.error"), "err");
    }
  });
})();
