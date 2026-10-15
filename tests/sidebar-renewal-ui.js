const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');

const source = fs.readFileSync('assets/js/components/sidebar.js', 'utf8');
function sidebar(role, permissions, portal = 'admin') {
  const element = {innerHTML: '', className: '', classList: {contains: () => false}};
  const context = vm.createContext({
    window: {location: {pathname: '/pikvero/public/admin/calendar.php'}, innerWidth: 1280},
    document: {body: {classList: {add() {}}}, querySelector: () => element, getElementById: () => null, addEventListener() {}},
    AuthHelper: {currentUser: role ? {role, user: {role_name: role}, permissions} : null}
  });
  vm.runInContext(source, context);
  vm.runInContext(`SidebarComponent.render('calendar', '${portal}')`, context);
  return element.innerHTML;
}
const owner = sidebar('court_owner', ['facility.view', 'court.view', 'booking.view', 'calendar.view', 'subscription.view', 'organization.update', 'users.view']);
assert(owner.includes('Facilities & courts') && owner.includes('Account & team'));
assert(owner.includes('/owner/my-plan.php'), 'Owner on an admin page must link to their own plan.');
assert(owner.includes('aria-current="page"'));
assert(!owner.includes('toggleSubmenu') && !owner.includes('submenu-') && !owner.includes('bi-chevron-down'));
const staff = sidebar('receptionist', ['calendar.view', 'booking.view']);
assert(!staff.includes('My Plan') && !staff.includes('User Directory'));
assert(!staff.includes('Facilities & courts'), 'Do not display empty groups.');
const admin = sidebar('super_admin', []);
assert(admin.includes('Platform administration') && admin.includes('Security Monitor'));
assert(sidebar(null, [], 'guest').includes('About & Support'));

const invoiceNodes = Object.fromEntries(['sam-plans-container', 'sam-inv-subtotal', 'sam-inv-total', 'sam-inv-fees', 'sam-inv-plan'].map(id => [id, {textContent: '', innerHTML: ''}]));
const invoiceContext = vm.createContext({document: {getElementById: id => invoiceNodes[id]}});
vm.runInContext(fs.readFileSync('assets/js/components/subscription-alert-modal.js', 'utf8'), invoiceContext);
vm.runInContext(`SubscriptionAlertModal.currentStatusData = {plans:[{id:9, name:'Paid plan', monthly_price:1000, trial_eligible:false}]}; SubscriptionAlertModal.selectedPlanId=999; SubscriptionAlertModal.renderPlans();`, invoiceContext);
assert.equal(invoiceNodes['sam-inv-fees'].textContent, '₱45.00');
assert.equal(invoiceNodes['sam-inv-total'].textContent, '₱1,045.00');

// Compile the actual PHP-rendered inline scripts, including the renewal handlers.
const html = execFileSync('C:\\xampp\\php\\php.exe', ['-d', 'session.save_path=' + require('node:os').tmpdir(), 'public/owner/my-plan.php'], {encoding: 'utf8'});
for (const match of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
  if (!match[1].includes('src=') && match[2].trim()) new vm.Script(match[2]);
}
const start = html.indexOf('    async function confirmPayNow()');
const end = html.indexOf('    function openUpgradePlanSelectorModal()', start);
assert(start >= 0 && end > start, 'Renewal handler not found.');
const calls = [];
const button = {disabled: false, innerHTML: ''};
const renewContext = vm.createContext({
  currentSubscriptionData: {subscription: {plan_id: 7, current_billing_cycle: 'monthly'}},
  document: {getElementById: () => button},
  window: {location: {origin: 'http://localhost', assign: url => calls.push(url)}},
  Api: {post: async (url, payload) => { calls.push({url, payload}); return {success: true, data: {checkout_url: 'https://checkout.example/renew'}}; }},
  Toast: {error: () => assert.fail('Unexpected renewal error')}, console
});
vm.runInContext(html.slice(start, end), renewContext);
(async () => {
  await vm.runInContext('confirmPayNow()', renewContext);
  assert.equal(calls[0].url, '/pikvero/api/payments/paymongo-checkout.php');
  assert.equal(calls[0].payload.plan_id, 7);
  assert.equal(calls[0].payload.billing_cycle, 'monthly');
  assert.equal(calls[0].payload.redirect_url, 'http://localhost/pikvero/public/owner/my-plan.php');
  assert.equal(calls[1], 'https://checkout.example/renew');
  console.log('PASS: grouped navigation, owner links, permission filtering, inline script syntax and renewal checkout redirect.');
})().catch(error => { console.error(error); process.exitCode = 1; });
