const fs = require('node:fs');
const assert = require('node:assert/strict');
const css = fs.readFileSync('assets/css/portal-theme.css', 'utf8');
function tokens(block) { return Object.fromEntries([...block.matchAll(/--([a-z-]+):\s*(#[a-f\d]{6})/gi)].map(match => [match[1], match[2]])); }
const light = tokens(css.match(/html\[data-portal-theme\]\s*\{([^}]+)\}/)[1]);
const dark = tokens(css.match(/html\[data-portal-theme="dark"\]\s*\{([^}]+)\}/)[1]);
function lum(hex) { return hex.slice(1).match(/../g).map(v => parseInt(v, 16) / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4).reduce((sum, v, i) => sum + v * [.2126, .7152, .0722][i], 0); }
function contrast(a, b) { const x = lum(a), y = lum(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); }
for (const [name, palette] of [['light', light], ['dark', dark]]) {
  for (const foreground of ['portal-text', 'portal-muted']) {
    for (const background of ['portal-bg', 'portal-surface', 'portal-raised']) assert(contrast(palette[foreground], palette[background]) >= 4.5, `${name} ${foreground}/${background} fails normal text contrast`);
  }
  for (const status of ['success', 'danger', 'warning', 'info']) assert(contrast(palette['portal-' + status], palette['portal-' + status + '-bg']) >= 4.5, `${name} ${status} status fails normal text contrast`);
  assert(contrast(palette['portal-accent'], palette['portal-on-accent']) >= 4.5, `${name} primary action fails contrast`);
}
assert(contrast('#10271c', '#dfff4f') >= 4.5);
assert(contrast('#3b1c14', '#ff745c') >= 4.5);
for (const name of ['amenities','calendar','open-play','products']) {
  const page = fs.readFileSync(`public/admin/${name}.php`, 'utf8');
  assert(page.includes('PortalTheme::attributes()') && page.includes('portal-theme-assets.php'), `${name} misses early theme initialization`);
}
console.log('PASS: text, muted text, action/status contrast in both palettes and standalone admin page coverage.');
