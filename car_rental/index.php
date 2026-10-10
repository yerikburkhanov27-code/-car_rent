<?php require __DIR__ . '/includes/header.php'; ?>
<section class="grid gap-8 lg:grid-cols-2 items-center">
    <div>
        <p class="text-copper font-semibold tracking-wide uppercase text-sm" data-i18n="brand">NurDrive</p>
        <h1 class="mt-3 text-4xl md:text-5xl font-black leading-tight text-sand" data-i18n="hero.title">Жолға шығыңыз. Дұрыс көлікті таңдаңыз.</h1>
        <p class="mt-4 text-slate-300 text-lg" data-i18n="hero.subtitle">Камриден Lexus RX-ке дейін — күндік тариф, тікелей брондау және анық төлем.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="cars.php" class="btn-primary" data-i18n="hero.cta">Автопаркқа өту</a>
            <button type="button" class="btn-ghost" id="heroBookBtn" data-i18n="hero.book">Қазір брондау</button>
        </div>
    </div>
    <div class="card-surface p-6">
        <h2 class="text-xl font-bold text-sand" data-i18n="calc.title">Жалдау калькуляторы</h2>
        <div class="mt-4 grid gap-3">
            <label class="text-sm text-slate-400"><span data-i18n="calc.car">Автокөлік</span>
                <select id="calcCar" class="field mt-1"></select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="text-sm text-slate-400"><span data-i18n="calc.start">Басталу күні</span>
                    <input id="calcStart" type="date" class="field mt-1">
                </label>
                <label class="text-sm text-slate-400"><span data-i18n="calc.end">Аяқталу күні</span>
                    <input id="calcEnd" type="date" class="field mt-1">
                </label>
            </div>
            <div class="flex justify-between text-slate-200">
                <span data-i18n="calc.days">Күн саны</span>
                <strong id="calcDays">1</strong>
            </div>
            <div class="flex justify-between text-sand text-xl">
                <span data-i18n="calc.total">Жалпы сома</span>
                <strong id="calcTotal">0 ₸</strong>
            </div>
            <button type="button" id="calcBookBtn" class="btn-primary w-full" data-i18n="car.book">Брондау</button>
        </div>
    </div>
</section>

<section class="mt-12">
    <div class="flex items-center justify-between gap-4 mb-5">
        <h2 class="text-2xl font-bold" data-i18n="nav.cars">Автокөліктер</h2>
        <a href="cars.php" class="text-copper font-semibold" data-i18n="hero.cta">Автопаркқа өту</a>
    </div>
    <div id="homeFleet" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
