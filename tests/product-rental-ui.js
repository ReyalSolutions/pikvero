const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const page = fs.readFileSync('public/admin/products.php', 'utf8');
const script = [...page.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)].filter(match => !match[1].includes('src='))[0][2]
  .replace(/<\?= json_encode\(\$\w+\) \?>/g, 'true');
const nodes = {};
const tables = {};
const calls = [];
let ready;
const context = vm.createContext({
  console,
  document: {documentElement: {dataset: {portalTheme:'light'}}, addEventListener: (name, handler) => { if (name === 'DOMContentLoaded') ready = handler; }, getElementById: id => nodes[id] ||= {
    style: {}, disabled: false, open: false,
    addEventListener() {}, showModal() { this.open = true; }, close() { this.open = false; },
    removeAttribute(name) { delete this[name]; }, contentDocument: {addEventListener() {}}, contentWindow: {PortalTheme: {apply() {}}}
  }},
  NavbarComponent: {render: async () => {}}, SidebarComponent: {render() {}}, FooterComponent: {render() {}},
  Api: {get: async () => ({success: true, data: {}}), post: async (url, payload) => { calls.push({url, payload}); return {success: true, message: 'Rental returned'}; }},
  Toast: {success: (title, message) => calls.push({title, message})}, confirm: () => true,
  $: selector => ({length: 1, text() {}, DataTable: options => {
    tables[selector] = {options, reloads: 0};
    return {ajax: {reload: () => tables[selector].reloads++}};
  }})
});
vm.runInContext(script, context);
(async () => {
  await ready();
  vm.runInContext("switchProductTab('sales')", context);
  const history = tables['#sales-history-table'].options;
  const status = history.columns.find(column => column.data === 'rental_status');
  assert(status.render('rented', 'display', {type: 'rental', rental_stock_deducted: 1}).includes('Rented out'));
  assert(status.render('returned', 'display', {type: 'rental', returned_at: '2026-10-15 12:00:00'}).includes('Returned'));
  const actions = history.columns.at(-1).render;
  assert(actions(null, 'display', {id: 10, type: 'rental', rental_status: 'rented'}).includes('Mark returned'));
  assert(!actions(null, 'display', {id: 10, type: 'rental', rental_status: 'returned'}).includes('Mark returned'));
  assert(!actions(null, 'display', {id: 10, type: 'sale'}).includes('Mark returned'));
  assert(actions(null, 'display', {id: 10, type: 'sale'}).includes('openReceiptModal(10)'));
  assert(!actions(null, 'display', {id: 10, type: 'sale'}).includes('target="_blank"'));
  const inventoryActions = tables['#products-table'].options.columns.at(-1).render;
  assert(inventoryActions(null, 'display', {id: 1, type: 'rental', name: 'Paddle'}).includes('Rent'));
  await vm.runInContext('markRentalReturned(10, {disabled:false,isConnected:true})', context);
  assert.equal(calls.length, 0, 'Opening confirmation must not return stock yet.');
  assert.equal(nodes['rental-return-dialog'].open, true);
  vm.runInContext('closeRentalReturnModal()', context);
  await vm.runInContext('confirmRentalReturn()', context);
  assert.equal(calls.length, 0, 'Cancel must leave stock unchanged.');
  vm.runInContext('markRentalReturned(10, {disabled:false,isConnected:true})', context);
  await vm.runInContext('confirmRentalReturn()', context);
  assert.equal(calls[0].url, '/pikvero/api/admin/products.php?action=return_rental');
  assert.equal(calls[0].payload.sale_id, 10);
  assert.equal(tables['#sales-history-table'].reloads, 1);
  assert.equal(tables['#products-table'].reloads, 1);
  assert.equal(calls[1].title, 'Rental returned');
  assert.equal(nodes['rental-return-dialog'].open, false);
  vm.runInContext('openReceiptModal(10)', context);
  assert.equal(nodes['product-receipt-dialog'].open, true);
  assert.equal(nodes['product-receipt-frame'].src, '/pikvero/public/product-receipt.php?sale_id=10&embedded=1');
  vm.runInContext('receiptModalLoaded()', context);
  assert.equal(nodes['product-receipt-frame'].hidden, false);
  assert.equal(nodes['product-receipt-loading'].hidden, true);
  vm.runInContext('closeReceiptModal()', context);
  assert.equal(nodes['product-receipt-dialog'].open, false);
  context.Api.post = async () => ({success: true, data: {sale_id: 12}});
  vm.runInContext("selectedSellProduct = {type:'rental'}; document.getElementById('sell-prod-id').value='1'; document.getElementById('sell-customer-name').value='Walk-in'; document.getElementById('sell-quantity').value='1'; document.getElementById('sell-payment-method').value='cash';", context);
  nodes['sell-modal'] = {style: {}, classList: {remove() {}}};
  await vm.runInContext('submitProcessSale({preventDefault(){}})', context);
  assert.equal(nodes['product-receipt-dialog'].open, true, 'Successful transactions must show their receipt in the modal.');
  assert.equal(nodes['product-receipt-frame'].src, '/pikvero/public/product-receipt.php?sale_id=12&embedded=1');
  console.log('PASS: rental status, confirmation/cancel, return API, receipt modal, toast and table refresh.');
})().catch(error => { console.error(error); process.exitCode = 1; });
