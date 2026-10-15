<?php $appearance = \App\Core\View\PortalTheme::context()['mode']; ?>
<fieldset style="border:0;padding:0;margin:0 0 28px;min-width:0;">
  <legend style="font-size:1.05rem;font-weight:800;margin-bottom:6px;">ADMIN APPEARANCE</legend>
  <p style="margin:0;color:var(--portal-muted);font-size:.85rem;">Choose a complete light or dark palette for your management pages. Preview instantly, then save your settings to keep it.</p>
  <div class="theme-choices">
    <?php foreach (['light' => ['Light', 'bi-sun', 'Warm white surfaces with forest green accents.'], 'dark' => ['Dark', 'bi-moon-stars', 'Deep green surfaces, soft text, and clear status colors.']] as $mode => $details): ?>
    <label class="theme-option">
      <input type="radio" name="theme_mode" value="<?= $mode ?>" <?= $appearance === $mode ? 'checked' : '' ?>>
      <strong><i class="bi <?= $details[1] ?>" aria-hidden="true"></i> <?= $details[0] ?></strong>
      <span class="theme-sample" data-preview="<?= $mode ?>" aria-hidden="true"><span class="theme-sample-side"></span><span class="theme-sample-content"><span class="theme-sample-line"></span><span class="theme-sample-card"></span></span></span>
      <small><?= $details[2] ?></small>
    </label>
    <?php endforeach; ?>
  </div>
</fieldset>
