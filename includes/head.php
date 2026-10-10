<?php
/**
 * Shared <head> partial — include on every portal page.
 *
 * Usage:
 *   <?php
 *   $pageTitle = 'Pikvero — Court Manager';
 *   $headExtras = ['select2', 'datatables', 'chartjs'];   // optional
 *   require_once __DIR__ . '/../includes/head.php';
 *   ?>
 *
 * Available $headExtras flags:
 *   'select2'    — jQuery + Select2 CSS + JS
 *   'datatables' — jQuery + DataTables CSS + JS
 *   'chartjs'    — Chart.js CDN
 *   'jquery'     — jQuery alone (already implied by select2/datatables)
 *
 * Notes:
 *   • jQuery is loaded automatically if 'select2' or 'datatables' is requested.
 *   • loader.js is always included and runs before any other script so the
 *     page-load animation fires as early as possible.
 *   • Core scripts (toast, modal, ajax, auth, navbar, sidebar, footer) are
 *     always included.  Put page-specific <script> tags after this include.
 */

$pageTitle   = $pageTitle   ?? 'Pikvero';
$headExtras  = $headExtras  ?? [];
$v           = time();   // cache-bust token

$needsJquery = array_intersect(['jquery', 'select2', 'datatables'], $headExtras);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <?php
  $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png';
  ?>
  <!-- ── Favicon & Browser Tab Icon ────────────────────────── -->
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= $v ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= $v ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= $v ?>">

  <!-- ── Core Styles ───────────────────────────────────────────── -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= $v ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css?v=<?= $v ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css?v=<?= $v ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/loader.css?v=<?= $v ?>">

<?php if (in_array('datatables', $headExtras)): ?>
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<?php endif; ?>

<?php if (in_array('select2', $headExtras)): ?>
  <!-- Select2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<?php endif; ?>

  <!-- ── Page Loader (must be first script for early animation) ── -->
  <script src="/pikvero/assets/js/core/loader.js?v=<?= $v ?>"></script>
  <?php require_once __DIR__ . '/subscription-gate.php'; ?>
