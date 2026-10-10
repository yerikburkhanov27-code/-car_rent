<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$admin = current_admin();
$page = page_name();
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NurDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#071018',
                        panel: '#101a24',
                        line: '#243445',
                        sand: '#e8d7b5',
                        copper: '#c9842a'
                    },
                    fontFamily: {
                        sans: ['"Segoe UI"', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        window.I18N = <?= i18n_json() ?>;
        window.APP_ADMIN = <?= json_encode($admin ? $admin['username'] : null, JSON_UNESCAPED_UNICODE) ?>;
        window.CURRENT_PAGE = <?= json_encode($page, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="bg-ink text-slate-100 min-h-screen">
<header class="sticky top-0 z-40 border-b border-line/80 bg-ink/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
        <a href="index.php" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-copper text-ink font-black">N</span>
            <span>
                <strong class="block text-sand" data-i18n="brand">NurDrive</strong>
                <span class="text-xs text-slate-400" data-i18n="tagline">Қазақстан бойынша сенімді автокөлік жалға алу</span>
            </span>
        </a>
        <button id="navToggle" class="md:hidden rounded-lg border border-line px-3 py-2" type="button" aria-label="Menu">☰</button>
        <nav id="mainNav" class="hidden md:flex flex-1 items-center justify-end gap-1">
            <a data-nav="index.php" href="index.php" class="nav-link" data-i18n="nav.home">Басты бет</a>
            <a data-nav="cars.php" href="cars.php" class="nav-link" data-i18n="nav.cars">Автокөліктер</a>
            <a data-nav="rentals.php" href="rentals.php" class="nav-link" data-i18n="nav.rentals">Жалға алу</a>
            <a data-nav="clients.php" href="clients.php" class="nav-link" data-i18n="nav.clients">Клиенттер</a>
            <a data-nav="payments.php" href="payments.php" class="nav-link" data-i18n="nav.payments">Төлемдер</a>
            <a data-nav="admin.php" href="admin.php" class="nav-link" data-i18n="nav.admin">Әкімші</a>
            <?php if ($admin): ?>
                <form action="logout.php" method="post" class="inline">
                    <button class="nav-link" type="submit" data-i18n="nav.logout">Шығу</button>
                </form>
            <?php else: ?>
                <a data-nav="login.php" href="login.php" class="nav-link" data-i18n="nav.login">Кіру</a>
            <?php endif; ?>
            <div class="ml-3 flex overflow-hidden rounded-full border border-line text-xs font-semibold">
                <button type="button" class="lang-btn px-3 py-2" data-lang="kk">KK</button>
                <button type="button" class="lang-btn px-3 py-2" data-lang="ru">RU</button>
                <button type="button" class="lang-btn px-3 py-2" data-lang="en">EN</button>
            </div>
        </nav>
    </div>
    <div id="mobileNav" class="hidden border-t border-line px-4 py-3 md:hidden space-y-2"></div>
</header>
<main class="mx-auto max-w-7xl px-4 py-8">
