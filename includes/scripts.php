<?php
/**
 * Shared bottom-of-<body> scripts partial.
 *
 * Usage (right before your own <script> block):
 *   <?php require_once __DIR__ . '/../includes/scripts.php'; ?>
 *   <script>
 *     // your page JS here
 *   </script>
 *
 * $headExtras must be set before including head.php so this file can read it.
 * Same optional flags as head.php: 'select2', 'datatables', 'chartjs', 'jquery'.
 */

$headExtras  = $headExtras  ?? [];
$v           = time();

$needsJquery = array_intersect(['jquery', 'select2', 'datatables'], $headExtras);
?>
  <!-- ── Core Scripts ──────────────────────────────────────────── -->
<?php if ($needsJquery): ?>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php endif; ?>

<?php if (in_array('datatables', $headExtras)): ?>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<?php endif; ?>

<?php if (in_array('select2', $headExtras)): ?>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php endif; ?>

<?php if (in_array('chartjs', $headExtras)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

  <script src="/pikvero/assets/js/core/toast.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/core/modal.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/core/ajax.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/core/auth.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/components/footer.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/components/upgrade-plan-modal.js?v=<?= $v ?>"></script>
  <script src="/pikvero/assets/js/components/payment-success-modal.js?v=<?= $v ?>"></script>
  <?php require_once __DIR__ . '/subscription-gate.php'; ?>
