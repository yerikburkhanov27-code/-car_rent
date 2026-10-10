<?php require __DIR__ . '/includes/header.php'; ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-black text-sand" data-i18n="clients.title">Клиенттер</h1>
    <button type="button" id="addClientBtn" class="btn-primary" data-i18n="clients.add">Клиент тіркеу</button>
</div>
<div class="mt-4">
    <input id="clientSearch" class="field max-w-md" data-i18n-placeholder="form.search" placeholder="Іздеу">
</div>
<div class="mt-6 overflow-x-auto card-surface">
    <table class="data" id="clientsTable"></table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
