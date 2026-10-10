<?php require __DIR__ . '/includes/header.php'; ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-black text-sand" data-i18n="payments.title">Төлемдер</h1>
    <button type="button" id="addPaymentBtn" class="btn-primary" data-i18n="payments.new">Төлем қосу</button>
</div>
<div class="mt-6 overflow-x-auto card-surface">
    <table class="data" id="paymentsTable"></table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
