<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
require __DIR__ . '/includes/header.php';
?>
<h1 class="text-3xl font-black text-sand" data-i18n="admin.title">Әкімші панелі</h1>
<div id="metrics" class="mt-6 grid gap-4 sm:grid-cols-3"></div>
<div class="mt-8 flex flex-wrap gap-2" id="adminTabs">
    <button class="btn-ghost admin-tab" data-tab="cars" data-i18n="admin.tab.cars">Көліктер</button>
    <button class="btn-ghost admin-tab" data-tab="clients" data-i18n="admin.tab.clients">Клиенттер</button>
    <button class="btn-ghost admin-tab" data-tab="employees" data-i18n="admin.tab.employees">Қызметкерлер</button>
    <button class="btn-ghost admin-tab" data-tab="rentals" data-i18n="admin.tab.rentals">Жалдау</button>
    <button class="btn-ghost admin-tab" data-tab="payments" data-i18n="admin.tab.payments">Төлемдер</button>
    <button class="btn-ghost admin-tab" data-tab="admins" data-i18n="admin.tab.admins">Әкімшілер</button>
</div>
<div class="mt-4 flex justify-end">
    <button type="button" id="adminCreateBtn" class="btn-primary" data-i18n="form.create">Қосу</button>
</div>
<div class="mt-4 overflow-x-auto card-surface">
    <table class="data" id="adminTable"></table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
