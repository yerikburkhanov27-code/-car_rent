<?php require __DIR__ . '/includes/header.php'; ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-black text-sand" data-i18n="rentals.title">Жалдау жазбалары</h1>
    <button type="button" id="openBookBtn" class="btn-primary" data-i18n="rentals.new">Жаңа жалдау</button>
</div>
<div class="mt-6 overflow-x-auto card-surface">
    <table class="data" id="rentalsTable"></table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
