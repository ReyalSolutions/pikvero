/** Shared admin theme, including legacy inline styles and dynamically loaded content. */
(() => {
  const root = document.documentElement;
  if (!root.hasAttribute('data-portal-theme')) return;
  const originals = new Map();
  const datasets = new WeakMap();
  const preserved = '.theme-sample,[data-theme-preserve],.sidebar-logo-mark,.navbar-logo-mark,.logo-preview-box,canvas,svg,img,video,.fc-event,.button,.btn-streetside,.badge-streetside';
  let observer, scheduled = false;
  const pending = new Set();
  function rgb(value) {
    const match = value.match(/^rgba?\(\s*([\d.]+)[, ]+([\d.]+)[, ]+([\d.]+)(?:\s*[,/]\s*([\d.]+))?\s*\)$/);
    return match ? [Number(match[1]), Number(match[2]), Number(match[3]), match[4] === undefined ? 1 : Number(match[4])] : null;
  }
  function luminance(color) {
    return color.slice(0, 3).map(c => { c /= 255; return c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4; }).reduce((sum, c, i) => sum + c * [.2126, .7152, .0722][i], 0);
  }
  function family(color) {
    const [r, g, b] = color;
    if (Math.max(r, g, b) - Math.min(r, g, b) < 24) return null;
    if (r > g * 1.12 && r > b * 1.08) return 'danger';
    if (r > b * 1.15 && g > b * 1.12 && r >= g) return 'warning';
    if (g > r * 1.08 && g > b * 1.05) return 'success';
    if (b > r * 1.08) return 'info';
    return null;
  }
  function set(element, property, value) {
    let entry = originals.get(element);
    if (!entry) { entry = new Map(); originals.set(element, entry); }
    const current = element.style.getPropertyValue(property);
    let previous = entry.get(property);
    if (!previous || current !== previous.applied) {
      previous = {value: current, priority: element.style.getPropertyPriority(property), applied: value};
      entry.set(property, previous);
    }
    previous.applied = value;
    if (current !== value) element.style.setProperty(property, value);
  }
  function paint(element) {
    if (!(element instanceof HTMLElement) || element.closest(preserved) || ['STYLE','SCRIPT','LINK','META','OPTION'].includes(element.tagName)) return;
    const style = getComputedStyle(element);
    const background = rgb(style.backgroundColor);
    if (background && background[3] >= .8 && luminance(background) > .65 && style.backgroundImage === 'none') {
      const tone = family(background);
      set(element, 'background-color', tone ? `var(--portal-${tone}-bg)` : 'var(--portal-surface)');
    }
    const color = rgb(style.color);
    if (color && color[3] > 0 && luminance(color) < .35) {
      const tone = family(color);
      set(element, 'color', tone ? `var(--portal-${tone})` : 'var(--portal-muted)');
    }
    if (element.style.boxShadow.includes('--ink')) set(element, 'box-shadow', '0 8px 24px #0003');
  }
  function scan(node) {
    if (root.dataset.portalTheme !== 'dark' || !(node instanceof HTMLElement)) return;
    paint(node); node.querySelectorAll('*').forEach(paint);
  }
  function chartColors(chart) {
    const dark = root.dataset.portalTheme === 'dark';
    const palette = ['#87dfb5','#ffad99','#addfff','#dfff4f','#d2b7ff'];
    const options = chart.options;
    const text = dark ? '#b2c8bc' : '#4a5c56';
    if (options.plugins?.legend?.labels) options.plugins.legend.labels.color = text;
    if (options.plugins?.title) options.plugins.title.color = text;
    if (options.plugins?.tooltip) Object.assign(options.plugins.tooltip, {backgroundColor:dark ? '#24382f' : '#0d211d',titleColor:'#fffefa',bodyColor:'#fffefa',borderColor:dark ? '#496455' : '#0d211d',borderWidth:1});
    Object.values(options.scales || {}).forEach(axis => {
      if (axis.ticks) axis.ticks.color = text;
      if (axis.title) axis.title.color = text;
      if (axis.grid) axis.grid.color = dark ? '#3b5347' : '#d8e0da';
    });
    chart.data.datasets.forEach((dataset, index) => {
      if (!datasets.has(dataset)) datasets.set(dataset, {backgroundColor:dataset.backgroundColor,borderColor:dataset.borderColor});
      const previous = datasets.get(dataset);
      if (dark) {
        const color = palette[index % palette.length];
        dataset.borderColor = Array.isArray(previous.borderColor) ? previous.borderColor.map((_, i) => palette[i % palette.length]) : color;
        dataset.backgroundColor = Array.isArray(previous.backgroundColor) ? previous.backgroundColor.map((_, i) => palette[i % palette.length]) : color + (chart.config.type === 'line' ? '24' : '');
      } else Object.assign(dataset, previous);
    });
  }
  const api = window.PortalTheme = {
    apply(mode) {
      if (!['light','dark'].includes(mode)) return;
      root.dataset.portalTheme = mode;
      if (mode === 'light') {
        originals.forEach((entry, element) => entry.forEach((value, property) => {
          if (element.style.getPropertyValue(property) !== value.applied) return;
          if (value.value) element.style.setProperty(property, value.value, value.priority);
          else element.style.removeProperty(property);
        }));
        originals.clear();
      } else scan(root);
      observer?.takeRecords();
      Object.values(window.Chart?.instances || {}).forEach(chart => { chartColors(chart); chart.update('none'); });
      document.querySelectorAll('iframe[src*="product-receipt.php"]').forEach(frame => {
        try { frame.contentWindow.PortalTheme?.apply(mode); } catch (error) { /* Cross-origin content keeps its own theme. */ }
      });
      document.querySelectorAll('[name="theme_mode"]').forEach(input => { input.checked = input.value === mode; });
      window.dispatchEvent(new CustomEvent('portal-theme-change', {detail:{mode}}));
    }
  };
  observer = new MutationObserver(records => {
    if (root.dataset.portalTheme !== 'dark') return;
    records.forEach(record => {
      if (record.type === 'attributes') pending.add(record.target);
      else record.addedNodes.forEach(node => { if (node instanceof HTMLElement) pending.add(node); });
    });
    if (!pending.size || scheduled) return;
    scheduled = true;
    requestAnimationFrame(() => {
      pending.forEach(scan); pending.clear(); scheduled = false;
      // Discard our own style updates to avoid an observer loop.
      observer.takeRecords();
      originals.forEach((_, element) => { if (!element.isConnected) originals.delete(element); });
    });
  });
  observer.observe(root, {subtree:true,childList:true,attributes:true,attributeFilter:['style','class']});
  function start() {
    if (window.Chart?.register) window.Chart.register({id:'pikveroPortalTheme',beforeUpdate:chartColors});
    api.apply(root.dataset.portalTheme);
    document.addEventListener('change', event => {
      if (event.target.matches('[name="theme_mode"]')) api.apply(event.target.value);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true});
  else start();
  let beforePrint;
  window.addEventListener('beforeprint', () => { beforePrint = root.dataset.portalTheme; api.apply('light'); });
  window.addEventListener('afterprint', () => { if (beforePrint) api.apply(beforePrint); beforePrint = null; });
})();
