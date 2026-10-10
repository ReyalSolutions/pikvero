<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\FacilityRepository;

Auth::requirePermission('products.view');
$canAdd = Auth::can('products.add');
$canEdit = Auth::can('products.edit');
$canDelete = Auth::can('products.delete');
$canInventory = Auth::can('products.inventory');
$canSell = Auth::can('products.sell');
$canRevenue = Auth::can('products.revenue');

$facilityRepo = new FacilityRepository();
$role = Auth::role();
$facilities = [];
if ($role === 'super_admin' || $role === 'platform_admin') {
    $facilities = $facilityRepo->getAllActive();
} else {
    $orgId = Auth::organizationId();
    if ($orgId) {
        $facilities = $facilityRepo->findByOrganizationId($orgId);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Products &amp; Equipment Management</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    .stat-card-mini {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 16px;
      box-shadow: 4px 4px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .dataTables_wrapper {
      font-family: inherit;
      font-size: 0.85rem;
      color: var(--ink);
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      padding: 6px 10px !important;
      font-weight: 700 !important;
      background: var(--white);
      outline: none;
    }
    .dataTables_wrapper .dataTables_filter input { margin-left: 8px; }
    .dataTables_wrapper .dataTables_info {
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      color: #4a5c56;
      padding-top: 14px;
    }
    .dataTables_wrapper .dataTables_paginate { padding-top: 10px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      background: var(--sand) !important;
      color: var(--ink) !important;
      font-weight: 800 !important;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem !important;
      padding: 5px 12px !important;
      margin: 0 3px !important;
      transition: all 0.15s ease;
      cursor: pointer !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: var(--lime) !important;
      color: var(--ink) !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
      background: var(--coral) !important;
      color: var(--white) !important;
      border: 2px solid var(--ink) !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      opacity: 0.4 !important;
      background: var(--sand) !important;
      color: var(--ink) !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
    }
  </style>
<?php require_once __DIR__ . '/../../includes/subscription-gate.php'; ?>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">EQUIPMENT &amp; MERCHANDISE</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">PRODUCTS &amp; RENTALS</h1>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <?php if ($canAdd): ?>
            <button type="button" onclick="openAddProductModal()" class="button coral" style="padding:9px 18px; font-size:0.84rem;">
              <i class="bi bi-plus-circle-fill"></i> Add New Product
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- METRIC CARDS -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="stat-card-mini" style="background:#e0f2fe;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#0369a1;">TOTAL PRODUCTS</span>
            <i class="bi bi-box-seam-fill" style="font-size:1.4rem; color:#0284c7;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-total-products">0</div>
        </div>

        <div class="stat-card-mini" style="background:#fef9c3;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#a16207;">LOW STOCK ALERTS</span>
            <i class="bi bi-exclamation-triangle-fill" style="font-size:1.4rem; color:#ca8a04;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-low-stock">0</div>
        </div>

        <div class="stat-card-mini" style="background:#dcfce7;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#15803d;">ITEMS SOLD / RENTED</span>
            <i class="bi bi-bag-check-fill" style="font-size:1.4rem; color:#16a34a;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-sold-items">0</div>
        </div>

        <?php if ($canRevenue): ?>
          <div class="stat-card-mini" style="background:#ffedd5;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span class="mono" style="font-size:0.75rem; font-weight:800; color:#c2410c;">SALES REVENUE</span>
              <i class="bi bi-currency-dollar" style="font-size:1.4rem; color:#ea580c;"></i>
            </div>
            <div style="font-size:1.8rem; font-weight:900; margin-top:8px; color:var(--coral);" id="stat-sales-revenue">₱0.00</div>
          </div>
        <?php endif; ?>
      </div>

      <!-- TAB SWITCHER BAR -->
      <div style="display:flex; gap:10px; margin-bottom:16px;">
        <button type="button" id="tab-btn-inventory" onclick="switchProductTab('inventory')" class="button lime" style="padding:8px 16px; font-size:0.82rem;">
          <i class="bi bi-box-seam-fill"></i> Product Inventory Catalog
        </button>
        <button type="button" id="tab-btn-sales" onclick="switchProductTab('sales')" class="button sand" style="padding:8px 16px; font-size:0.82rem;">
          <i class="bi bi-receipt"></i> Sales &amp; Transaction Receipts
        </button>
      </div>

      <!-- INVENTORY DATATABLES CARD -->
      <div class="card-streetside" id="tab-inventory-card" style="padding:20px; background:var(--white);">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="products-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead class="desktop-table-header">
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">PRODUCT &amp; CATEGORY</th>
                <th style="padding:10px;">FACILITY</th>
                <th style="padding:10px;">PRICE</th>
                <th style="padding:10px;">STOCK LEVEL</th>
                <th style="padding:10px;">SALES REVENUE</th>
                <th style="padding:10px;">STATUS</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loaded via DataTables -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- SALES HISTORY DATATABLES CARD -->
      <div class="card-streetside" id="tab-sales-card" style="padding:20px; background:var(--white); display:none;">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="sales-history-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead class="desktop-table-header">
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">RECEIPT &amp; DATE</th>
                <th style="padding:10px;">CUSTOMER NAME</th>
                <th style="padding:10px;">ITEM PURCHASED</th>
                <th style="padding:10px;">QTY &amp; TOTAL</th>
                <th style="padding:10px;">PAYMENT METHOD</th>
                <th style="padding:10px;">FACILITY</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loaded via DataTables -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- ADD / EDIT PRODUCT MODAL -->
  <div class="modal-overlay" id="product-modal" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(500px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;" id="product-modal-title"><i class="bi bi-box-seam"></i> ADD NEW PRODUCT</h3>
        <button onclick="closeModal('product-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="product-form" onsubmit="submitProductForm(event)">
        <input type="hidden" id="prod-id">

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">ASSIGNED FACILITY (OPTIONAL)</label>
          <select id="prod-facility-id" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
            <option value="">All Facilities (Global)</option>
            <?php foreach ($facilities as $fac): ?>
              <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['city'] ?? 'Bohol') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PRODUCT NAME *</label>
          <input type="text" id="prod-name" required placeholder="e.g. Carbon Fiber Paddle, Tour Balls..." style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CATEGORY *</label>
            <select id="prod-category" required style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
              <option value="Equipment">General Equipment</option>
              <option value="Paddle">Paddle</option>
              <option value="Ball">Ball</option>
              <option value="Grip">Grip / Tape</option>
              <option value="Apparel">Apparel / Shoes</option>
              <option value="Bag">Bag / Carrier</option>
              <option value="Accessories">Accessories / First Aid</option>
            </select>
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">TYPE *</label>
            <select id="prod-type" required style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace;">
              <option value="sale">FOR SALE</option>
              <option value="rental">FOR RENTAL</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">UNIT PRICE (₱) *</label>
            <input type="number" step="0.01" id="prod-price" required placeholder="1450.00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">STOCK QUANTITY *</label>
            <input type="number" id="prod-stock" required value="10" min="0" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
          </div>
        </div>

        <div style="margin-bottom:18px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">DESCRIPTION</label>
          <textarea id="prod-desc" rows="3" placeholder="Product details, specs, sizes..." style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('product-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-save-fill"></i> Save Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- PROCESS POS SALE / RENTAL MODAL -->
  <div class="modal-overlay" id="sell-modal" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(460px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:var(--green);"><i class="bi bi-bag-check-fill"></i> PROCESS SALE / RENTAL</h3>
        <button onclick="closeModal('sell-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="sell-form" onsubmit="submitProcessSale(event)">
        <input type="hidden" id="sell-prod-id">

        <div style="background:var(--cream); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:16px;">
          <div style="font-weight:800; font-size:1.05rem;" id="sell-item-name">Product Name</div>
          <div style="font-size:0.8rem; color:#4a5c56; display:flex; justify-content:space-between; margin-top:4px;">
            <span>Price: <strong id="sell-item-price" class="mono" style="color:var(--green);">₱0.00</strong></span>
            <span>Available Stock: <strong id="sell-item-stock" class="mono">0</strong></span>
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CUSTOMER NAME</label>
          <input type="text" id="sell-customer-name" value="Walk-in Customer" required style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">QUANTITY *</label>
            <input type="number" id="sell-quantity" required value="1" min="1" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;" oninput="updateSellTotal()">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PAYMENT METHOD</label>
            <select id="sell-payment-method" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace;">
              <option value="cash">CASH</option>
              <option value="gcash">GCASH</option>
              <option value="card">CARD / MAYA</option>
            </select>
          </div>
        </div>

        <div style="background:#f0fdf4; border:2px solid var(--ink); border-radius:10px; padding:12px 16px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center;">
          <span class="mono" style="font-weight:800; font-size:0.85rem;">TOTAL DUE:</span>
          <span class="mono" style="font-weight:900; font-size:1.3rem; color:var(--green);" id="sell-total-amount">₱0.00</span>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('sell-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-cash"></i> Complete Sale</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let productsTable = null;
    let salesHistoryTable = null;
    let selectedSellProduct = null;
    const canEdit = <?= json_encode($canEdit) ?>;
    const canSell = <?= json_encode($canSell) ?>;
    const canDelete = <?= json_encode($canDelete) ?>;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('products', 'admin');
      FooterComponent.render('#footer-container', true);

      loadMetrics();

      productsTable = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '/pikvero/api/admin/products.php',
          type: 'GET'
        },
        order: [[0, 'desc']],
        columns: [
          {
            data: 'name',
            render: function (data, type, row) {
              const isRental = (row.type === 'rental');
              return `
                <div>
                  <div style="font-weight:800; font-size:0.95rem; text-transform:uppercase;">${data}</div>
                  <div style="display:flex; gap:6px; margin-top:4px;">
                    <span class="badge-streetside ${isRental ? 'sky' : 'lime'}" style="font-size:0.65rem;">${isRental ? 'RENTAL' : 'FOR SALE'}</span>
                    <span class="badge-streetside sand" style="font-size:0.65rem;">${row.category || 'Equipment'}</span>
                  </div>
                </div>
              `;
            }
          },
          {
            data: 'facility_name',
            render: function (data) {
              return `<span style="font-size:0.82rem; font-weight:700; color:#4a5c56;">${data || 'Global (All Facilities)'}</span>`;
            }
          },
          {
            data: 'price',
            render: function (data) {
              return `<strong class="mono" style="font-size:0.95rem; color:var(--green);">₱${parseFloat(data||0).toFixed(2)}</strong>`;
            }
          },
          {
            data: 'stock_quantity',
            render: function (data, type, row) {
              const qty = parseInt(data || 0, 10);
              let badgeCls = 'lime';
              if (qty <= 0) badgeCls = 'coral';
              else if (qty <= 3) badgeCls = 'sand';
              return `<span class="badge-streetside ${badgeCls}">${qty} IN STOCK</span>`;
            }
          },
          {
            data: 'total_revenue',
            render: function (data, type, row) {
              const totalSold = parseInt(row.total_sold || 0, 10);
              const rev = parseFloat(data || 0).toFixed(2);
              return `
                <div>
                  <span class="mono" style="font-weight:800; color:var(--green);">₱${rev}</span>
                  <div style="font-size:0.72rem; color:#4a5c56;">${totalSold} items sold</div>
                </div>
              `;
            }
          },
          {
            data: 'status',
            render: function (data) {
              const st = (data || 'active').toLowerCase();
              let badgeCls = 'lime';
              if (st === 'out_of_stock') badgeCls = 'coral';
              else if (st === 'archived') badgeCls = 'sand';
              return `<span class="badge-streetside ${badgeCls}">${st.toUpperCase()}</span>`;
            }
          },
          {
            data: null,
            orderable: false,
            render: function (data, type, row) {
              return `
                <div style="display:flex; gap:6px; justify-content:flex-end;">
                  ${canSell ? `
                    <button type="button" onclick="openSellModal(${row.id})" class="button lime" style="padding:5px 10px; font-size:0.75rem;" title="Process Sale / Rental">
                      <i class="bi bi-cash"></i> Sell
                    </button>
                  ` : ''}
                  ${canEdit ? `
                    <button type="button" onclick="openEditProductModal(${row.id})" class="button sand" style="padding:5px 10px; font-size:0.75rem;" title="Edit Product">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                  ` : ''}
                  ${canDelete ? `
                    <button type="button" onclick="deleteProductAct(${row.id}, '${escapeJsStr(row.name)}')" class="button coral" style="padding:5px 10px; font-size:0.75rem;" title="Archive Product">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  ` : ''}
                </div>
              `;
            }
          }
        ]
      });
    });

    function switchProductTab(tab) {
      const invCard = document.getElementById('tab-inventory-card');
      const salesCard = document.getElementById('tab-sales-card');
      const btnInv = document.getElementById('tab-btn-inventory');
      const btnSales = document.getElementById('tab-btn-sales');

      if (tab === 'inventory') {
        invCard.style.display = 'block';
        salesCard.style.display = 'none';
        btnInv.className = 'button lime';
        btnSales.className = 'button sand';
      } else {
        invCard.style.display = 'none';
        salesCard.style.display = 'block';
        btnInv.className = 'button sand';
        btnSales.className = 'button lime';

        if (!salesHistoryTable) {
          salesHistoryTable = $('#sales-history-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
              url: '/pikvero/api/admin/products.php?action=sales_history',
              type: 'GET'
            },
            order: [[0, 'desc']],
            columns: [
              {
                data: 'id',
                render: function (data, type, row) {
                  const dt = row.sale_date ? row.sale_date.substring(0,16) : '';
                  return `
                    <div>
                      <span class="mono" style="font-weight:800; font-size:0.75rem; color:var(--green);">#SALE-${data}</span>
                      <div style="font-size:0.75rem; color:#4a5c56;"><i class="bi bi-clock"></i> ${dt}</div>
                    </div>
                  `;
                }
              },
              {
                data: 'customer_name',
                render: function (data) {
                  return `<strong style="font-size:0.88rem;">${data || 'Walk-in Customer'}</strong>`;
                }
              },
              {
                data: 'product_name',
                render: function (data, type, row) {
                  return `
                    <div>
                      <div style="font-weight:800; font-size:0.88rem;">${data}</div>
                      <span class="badge-streetside sand" style="font-size:0.65rem;">${row.category || 'Equipment'}</span>
                    </div>
                  `;
                }
              },
              {
                data: 'total_amount',
                render: function (data, type, row) {
                  return `
                    <div>
                      <strong class="mono" style="color:var(--green); font-size:0.92rem;">₱${parseFloat(data||0).toFixed(2)}</strong>
                      <div style="font-size:0.72rem; color:#4a5c56;">Qty: ${row.quantity} @ ₱${parseFloat(row.unit_price||0).toFixed(2)}</div>
                    </div>
                  `;
                }
              },
              {
                data: 'payment_method',
                render: function (data) {
                  const pm = (data || 'cash').toUpperCase();
                  let badgeCls = 'lime';
                  if (pm === 'GCASH') badgeCls = 'sky';
                  else if (pm === 'CARD') badgeCls = 'coral';
                  return `<span class="badge-streetside ${badgeCls}">${pm}</span>`;
                }
              },
              {
                data: 'facility_name',
                render: function (data) {
                  return `<span style="font-size:0.8rem; font-weight:700; color:#4a5c56;">${data || 'Global'}</span>`;
                }
              },
              {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                  return `
                    <div style="display:flex; justify-content:flex-end;">
                      <a href="/pikvero/public/product-receipt.php?sale_id=${row.id}" target="_blank" class="button lime" style="padding:5px 10px; font-size:0.75rem;" title="Print Official Receipt">
                        <i class="bi bi-printer-fill"></i> Print Receipt
                      </a>
                    </div>
                  `;
                }
              }
            ]
          });
        } else {
          salesHistoryTable.ajax.reload(null, false);
        }
      }
    }

    function escapeJsStr(str) {
      return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    async function loadMetrics() {
      try {
        const res = await Api.get('/pikvero/api/admin/products.php', { action: 'metrics' });
        if (res.success && res.data) {
          $('#stat-total-products').text(res.data.total_products || 0);
          $('#stat-low-stock').text(res.data.low_stock_count || 0);
          $('#stat-sold-items').text(res.data.total_sold_items || 0);
          if ($('#stat-sales-revenue').length) {
            $('#stat-sales-revenue').text('₱' + parseFloat(res.data.total_sales_revenue || 0).toFixed(2));
          }
        }
      } catch (e) { console.error(e); }
    }

    function openModal(id) {
      const el = document.getElementById(id);
      if (el) {
        el.classList.add('active');
        el.style.display = 'flex';
      }
    }

    function closeModal(id) {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('active');
        el.style.display = 'none';
      }
    }

    function openAddProductModal() {
      document.getElementById('product-modal-title').innerHTML = `<i class="bi bi-box-seam"></i> ADD NEW PRODUCT`;
      document.getElementById('prod-id').value = '';
      document.getElementById('prod-facility-id').value = '';
      document.getElementById('prod-name').value = '';
      document.getElementById('prod-category').value = 'Paddle';
      document.getElementById('prod-type').value = 'sale';
      document.getElementById('prod-price').value = '1450.00';
      document.getElementById('prod-stock').value = '10';
      document.getElementById('prod-desc').value = '';
      openModal('product-modal');
    }

    async function openEditProductModal(id) {
      try {
        const res = await Api.get('/pikvero/api/admin/products.php', { action: 'details', id: id });
        if (res.success && res.data) {
          const p = res.data;
          document.getElementById('product-modal-title').innerHTML = `<i class="bi bi-pencil-square"></i> EDIT PRODUCT`;
          document.getElementById('prod-id').value = p.id;
          document.getElementById('prod-facility-id').value = p.facility_id || '';
          document.getElementById('prod-name').value = p.name;

          const catSelect = document.getElementById('prod-category');
          const catVal = p.category || 'Equipment';
          catSelect.value = catVal;
          if (!catSelect.value && catVal) {
            const opt = new Option(catVal, catVal, true, true);
            catSelect.add(opt);
          }

          document.getElementById('prod-type').value = p.type || 'sale';
          document.getElementById('prod-price').value = p.price;
          document.getElementById('prod-stock').value = p.stock_quantity;
          document.getElementById('prod-desc').value = p.description || '';
          openModal('product-modal');
        }
      } catch (err) { console.error(err); }
    }

    async function submitProductForm(e) {
      e.preventDefault();
      const id = document.getElementById('prod-id').value;
      const action = id ? 'update' : 'create';

      const payload = {
        id: id,
        facility_id: document.getElementById('prod-facility-id').value,
        name: document.getElementById('prod-name').value.trim(),
        category: document.getElementById('prod-category').value,
        type: document.getElementById('prod-type').value,
        price: document.getElementById('prod-price').value,
        stock_quantity: document.getElementById('prod-stock').value,
        description: document.getElementById('prod-desc').value.trim()
      };

      try {
        const res = await Api.post('/pikvero/api/admin/products.php?action=' + action, payload);
        if (res.success) {
          Toast.success('Saved', res.message || 'Product item saved.');
          closeModal('product-modal');
          productsTable.ajax.reload(null, false);
          loadMetrics();
        }
      } catch (err) { console.error(err); }
    }

    async function deleteProductAct(id, name) {
      if (!confirm(`Are you sure you want to archive product "${name}"?`)) return;
      try {
        const res = await Api.post('/pikvero/api/admin/products.php?action=delete', { id: id });
        if (res.success) {
          Toast.success('Archived', 'Product archived successfully.');
          productsTable.ajax.reload(null, false);
          loadMetrics();
        }
      } catch (err) { console.error(err); }
    }

    async function openSellModal(id) {
      try {
        const res = await Api.get('/pikvero/api/admin/products.php', { action: 'details', id: id });
        if (res.success && res.data) {
          selectedSellProduct = res.data;
          document.getElementById('sell-prod-id').value = selectedSellProduct.id;
          document.getElementById('sell-item-name').innerText = selectedSellProduct.name;
          document.getElementById('sell-item-price').innerText = '₱' + parseFloat(selectedSellProduct.price).toFixed(2);
          document.getElementById('sell-item-stock').innerText = selectedSellProduct.stock_quantity;
          document.getElementById('sell-customer-name').value = 'Walk-in Customer';
          document.getElementById('sell-quantity').value = 1;
          updateSellTotal();
          openModal('sell-modal');
        }
      } catch (err) { console.error(err); }
    }

    function updateSellTotal() {
      if (!selectedSellProduct) return;
      const qty = parseInt(document.getElementById('sell-quantity').value || '1', 10);
      const total = qty * parseFloat(selectedSellProduct.price || 0);
      document.getElementById('sell-total-amount').innerText = '₱' + total.toFixed(2);
    }

    async function submitProcessSale(e) {
      e.preventDefault();
      const payload = {
        product_id: document.getElementById('sell-prod-id').value,
        customer_name: document.getElementById('sell-customer-name').value.trim(),
        quantity: document.getElementById('sell-quantity').value,
        payment_method: document.getElementById('sell-payment-method').value
      };

      try {
        const res = await Api.post('/pikvero/api/admin/products.php?action=sell', payload);
        if (res.success) {
          Toast.success('Sale Processed', 'Product sale completed successfully.');
          closeModal('sell-modal');
          productsTable.ajax.reload(null, false);
          loadMetrics();

          if (res.data && res.data.sale_id) {
            window.open(`/pikvero/public/product-receipt.php?sale_id=${res.data.sale_id}`, '_blank');
          }
        }
      } catch (err) { console.error(err); }
    }
  </script>
</body>
</html>
