<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_admin()) {
    header('Location: admin.php');
    exit;
}
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-md card-surface p-6">
    <h1 class="text-2xl font-black text-sand" data-i18n="login.title">Әкімші кіруі</h1>
    <p class="mt-2 text-sm text-slate-400" data-i18n="login.hint">Әдепкі: admin / admin123</p>
    <form id="loginForm" class="mt-5 grid gap-3">
        <label class="text-sm text-slate-400"><span data-i18n="form.username">Пайдаланушы аты</span>
            <input name="username" class="field mt-1" required>
        </label>
        <label class="text-sm text-slate-400"><span data-i18n="form.password">Құпиясөз</span>
            <input name="password" type="password" class="field mt-1" required>
        </label>
        <button class="btn-primary" type="submit" data-i18n="login.submit">Кіру</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
