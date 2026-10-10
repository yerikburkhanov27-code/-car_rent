<?php require __DIR__ . '/includes/header.php'; ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-sand" data-i18n="nav.cars">Автокөліктер</h1>
        <p class="text-slate-400" data-i18n="tagline">Қазақстан бойынша сенімді автокөлік жалға алу</p>
    </div>
    <button type="button" id="openBookBtn" class="btn-primary" data-i18n="hero.book">Қазір брондау</button>
</div>

<form id="carFilters" class="mt-6 grid gap-3 md:grid-cols-4 card-surface p-4">
    <label class="text-sm text-slate-400"><span data-i18n="filter.status">Мәртебе</span>
        <select name="status" class="field mt-1">
            <option value="" data-i18n="filter.all">Барлығы</option>
            <option value="available" data-i18n="filter.available">Бос</option>
            <option value="rented" data-i18n="filter.rented">Жалдаулы</option>
        </select>
    </label>
    <label class="text-sm text-slate-400"><span data-i18n="filter.brand">Марка</span>
        <select name="brand" id="brandFilter" class="field mt-1">
            <option value="" data-i18n="filter.all">Барлығы</option>
        </select>
    </label>
    <label class="text-sm text-slate-400"><span data-i18n="filter.maxPrice">Макс. күндік тариф</span>
        <input name="max_price" type="number" min="0" step="100" class="field mt-1">
    </label>
    <div class="flex items-end gap-2">
        <button class="btn-primary flex-1" type="submit" data-i18n="filter.apply">Сүзгілеу</button>
        <button class="btn-ghost" type="reset" data-i18n="filter.reset">Тазалау</button>
    </div>
</form>

<div id="fleetGrid" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
