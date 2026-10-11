<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
Auth::requireAuth();
$pageTitle = 'Pikvero — Referral Bonuses';
$headExtras = !empty($isCustomerReferralPage) ? [] : ['datatables'];
require_once __DIR__ . '/../../includes/head.php';
?>
<?php if (!empty($isCustomerReferralPage)): ?>
<link rel="stylesheet" href="/pikvero/assets/css/player-profile.css?v=<?= filemtime(__DIR__.'/../../assets/css/player-profile.css') ?>">
<link rel="stylesheet" href="/pikvero/assets/css/player-referrals.css?v=<?= filemtime(__DIR__.'/../../assets/css/player-referrals.css') ?>">
<?php endif; ?>
<?php if (empty($isCustomerReferralPage)): ?>
<link rel="stylesheet" href="/pikvero/assets/css/admin-referrals.css?v=<?= filemtime(__DIR__.'/../../assets/css/admin-referrals.css') ?>">
<?php endif; ?>
<style>
.referral-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0}
.referral-panel{padding:22px;margin-bottom:20px}.referral-code{font-family:monospace;font-size:1.5rem;letter-spacing:2px;font-weight:800}
.referral-table-wrap{overflow-x:auto}.referral-table{width:100%;border-collapse:collapse;font-size:.85rem}.referral-table th,.referral-table td{padding:12px;text-align:left;border-bottom:1px solid #ddd}.referral-table th{white-space:nowrap}
.referral-input{padding:10px;border:2px solid var(--ink);border-radius:8px;max-width:100%;box-sizing:border-box}.referral-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.referral-muted{font-size:.85rem;color:#50645b}
</style>
</head><body class="<?= !empty($isCustomerReferralPage) ? 'customer-portal player-profile player-referrals' : 'admin-referrals' ?>">
<aside id="sidebar-container"></aside><header id="navbar-container"></header>
<main class="portal-main">
<?php if (!empty($isCustomerReferralPage)): ?>
<div class="profile-masthead"><a href="/pikvero/public/customer/profile.php" aria-label="Back to profile"><i class="bi bi-chevron-left"></i></a><div><strong>Pikvero</strong><span>Find. Book. Rally.</span></div><a href="/pikvero/public/notifications.php" aria-label="Your notifications"><i class="bi bi-bell"></i></a></div>
<a class="referral-back" href="/pikvero/public/customer/profile.php"><i class="bi bi-chevron-left"></i> Back to profile</a>
<?php endif; ?>
<div class="eyebrow">REFER AN OWNER</div><h1>Referral Bonuses</h1>
<p>Earn ₱150 when an owner uses your code and successfully purchases a paid package. One reward per owner.</p>
<section class="card-streetside referral-panel referral-share">
<h2>Your referral code</h2><div id="referral-code" class="referral-code">Loading…</div>
<div class="referral-actions"><button class="button lime" id="copy-code" disabled>Copy code</button><button class="button sand" id="copy-link" disabled>Copy referral link</button></div>
<p class="referral-muted">Share your code or signup link. Free trials and complimentary upgrades do not earn a bonus. Payment is checked before cash can be claimed.</p>
</section>
<div class="referral-grid" id="referral-summary"></div>
<section class="card-streetside referral-panel referral-history"><h2 id="history-title">Your referral history</h2>
<p id="admin-help" class="referral-muted" hidden>Check the owner’s package payment receipt before verifying a bonus. After handing over the cash, record the payout reference to close the claim.</p>
<div class="referral-table-wrap"><table id="referral-table" class="referral-table"><thead><tr><th>Owner / Referrer</th><th>Package payment</th><th>Bonus</th><th>Status</th><th>Claim / Payout</th></tr></thead><tbody id="referral-rows"><tr><td colspan="5">Loading…</td></tr></tbody></table></div>
</section>
<dialog id="referral-dialog" style="border:2px solid var(--ink);border-radius:14px;padding:24px;width:min(420px,85vw)"><form id="referral-action-form"><h2 id="referral-action-title"></h2><p id="referral-action-description"></p><label id="payout-label" hidden>Payout receipt / reference<input class="referral-input" id="payout-reference" minlength="3" maxlength="150"></label><div class="referral-actions"><button type="button" class="button sand" onclick="document.getElementById('referral-dialog').close()">Cancel</button><button type="submit" class="button lime" id="referral-confirm">Confirm</button></div></form></dialog>
<footer id="footer-container"></footer></main>
<?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
<script src="/pikvero/assets/js/components/referrals.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/referrals.js') ?>"></script>
</body></html>
