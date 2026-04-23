<?php
$pageTitle    = 'Blood Unit Logger';
$pageSubtitle = 'Log collected blood units and track status from collection to use';
require_once 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:420px 1fr;gap:24px;align-items:start">

  <!-- Log Form -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">🏷️ Log Blood Unit</h2>
    </div>
    <div class="card-body">
      <form id="unit-form" onsubmit="submitUnit(event)">

        <div class="form-group">
          <label for="donor_id">Select Donor *</label>
          <select id="donor_id" name="donor_id" required onchange="fillBloodType()">
            <option value="">— Loading donors... —</option>
          </select>
        </div>

        <div class="form-group">
          <label for="blood_type">Blood Type *</label>
          <select id="blood_type" name="blood_type" required>
            <option value="">— Auto-filled from donor —</option>
            <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
            <option value="<?= $bt ?>"><?= $bt ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="collection_date">Collection Date *</label>
          <input type="date" id="collection_date" name="collection_date"
                 value="<?= date('Y-m-d') ?>"
                 max="<?= date('Y-m-d') ?>"
                 oninput="updateExpiry()" required>
        </div>

        <!-- Auto Expiry Preview -->
        <div class="alert alert-info">
          <span class="alert-icon">📅</span>
          <div>
            Expiry Date (auto): <strong id="expiry-preview">—</strong>
            <div class="text-sm mt-4">Collection date + 35 days</div>
          </div>
        </div>

        <div class="alert alert-warning mt-8">
          <span class="alert-icon">⚠️</span>
          <div>Unit status begins as <strong>COLLECTED</strong>.
            Manually advance to TESTED → AVAILABLE after lab screening.
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submit-unit-btn" style="margin-top:16px">
          🩸 Log Blood Unit
        </button>
      </form>

      <!-- Success/Error Result -->
      <div id="log-result" style="display:none;margin-top:16px"></div>
    </div>
  </div>

  <!-- Blood Units Table -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Blood Unit Records</h2>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="filter-status" class="btn btn-outline btn-sm" style="padding:5px 12px;font-size:12px" onchange="loadUnits()">
          <option value="">All Status</option>
          <option value="COLLECTED">COLLECTED</option>
          <option value="TESTED">TESTED</option>
          <option value="AVAILABLE">AVAILABLE</option>
          <option value="RESERVED">RESERVED</option>
          <option value="USED">USED</option>
          <option value="EXPIRED">EXPIRED</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="loadUnits()">🔄 Refresh</button>
      </div>
    </div>
    <div class="table-wrapper" id="units-panel">
      <div style="padding:48px;text-align:center"><div class="spinner"></div></div>
    </div>
  </div>
</div>

<!-- Status Update Modal -->
<div id="status-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center">
  <div class="card" style="width:380px;margin:0">
    <div class="card-header">
      <h2 class="card-title">Update Unit Status</h2>
      <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--gray-400)">✕</button>
    </div>
    <div class="card-body">
      <p style="margin-bottom:16px;font-size:13px;color:var(--gray-500)">Unit ID: <strong id="modal-unit-id"></strong></p>
      <div class="form-group">
        <label>New Status</label>
        <select id="new-status">
          <option value="COLLECTED">COLLECTED</option>
          <option value="TESTED">TESTED</option>
          <option value="AVAILABLE">AVAILABLE</option>
          <option value="RESERVED">RESERVED</option>
          <option value="USED">USED</option>
          <option value="EXPIRED">EXPIRED</option>
        </select>
      </div>
      <button class="btn btn-primary btn-block" onclick="saveStatus()">Save Status</button>
    </div>
  </div>
</div>

<script>
let currentUnitId = null;

// Load donors via JS API
async function loadDonors() {
  try {
    const res = await fetchAPI('/DBMS/api/donor.php');
    const sel = document.getElementById('donor_id');
    sel.innerHTML = '<option value="">— Choose a donor —</option>';
    (res.donors || []).forEach(d => {
      const o = document.createElement('option');
      o.value = d.donor_id;
      o.dataset.bt = d.blood_type;
      o.textContent = `${d.name} (${d.blood_type})`;
      sel.appendChild(o);
    });
  } catch(e) {
    console.error('Failed to load donors', e);
  }
}

function fillBloodType() {
  const sel = document.getElementById('donor_id');
  const opt = sel.options[sel.selectedIndex];
  if (opt.dataset.bt) {
    document.getElementById('blood_type').value = opt.dataset.bt;
  }
}

function updateExpiry() {
  const cd = document.getElementById('collection_date').value;
  if (cd) {
    const exp = addDays(cd, 35);
    document.getElementById('expiry-preview').textContent = formatDate(exp);
  }
}

updateExpiry();

async function submitUnit(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-unit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Logging...';

  const data = {
    action:          'log',
    donor_id:        document.getElementById('donor_id').value,
    blood_type:      document.getElementById('blood_type').value,
    collection_date: document.getElementById('collection_date').value,
  };

  try {
    const res = await fetchAPI('/DBMS/api/blood_unit.php', 'POST', data);
    const result = document.getElementById('log-result');
    if (res.success) {
      result.style.display = 'block';
      result.innerHTML = `
        <div class="alert alert-success">
          <span class="alert-icon">✅</span>
          <div><strong>Blood unit logged!</strong><br>Unit ID: #${res.unit_id}<br>Expiry: ${formatDate(res.expiry_date)}</div>
        </div>`;
      showToast('Blood unit logged successfully!', 'success');
      loadUnits();
    } else {
      result.style.display = 'block';
      result.innerHTML = `<div class="alert alert-danger"><span class="alert-icon">❌</span> ${res.message}</div>`;
    }
  } catch(e) {
    showToast('Error logging blood unit.', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '🩸 Log Blood Unit';
}

async function loadUnits() {
  const panel = document.getElementById('units-panel');
  const filter = document.getElementById('filter-status').value;
  panel.innerHTML = '<div style="padding:40px;text-align:center"><div class="spinner"></div></div>';

  try {
    const res = await fetchAPI('/DBMS/api/blood_unit.php');
    let units = res.units || [];
    if (filter) units = units.filter(u => u.status === filter);

    if (units.length === 0) {
      panel.innerHTML = '<div class="empty-state"><div class="empty-icon">🩸</div><h3>No blood units found</h3></div>';
      return;
    }

    panel.innerHTML = `
      <table>
        <thead>
          <tr><th>Unit #</th><th>Blood</th><th>Donor</th><th>Collection</th><th>Expiry</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          ${units.map(u => {
            const days = daysUntil(u.expiry_date);
            let rowClass = '';
            if (u.status === 'EXPIRED') rowClass = 'expired-row';
            else if (days <= 7 && days >= 0) rowClass = 'expiring-soon';
            return `<tr class="${rowClass}">
              <td class="text-sm text-gray">#${u.unit_id}</td>
              <td><span class="badge badge-red">${u.blood_type}</span></td>
              <td><strong>${u.donor_name}</strong></td>
              <td class="text-sm">${formatDate(u.collection_date)}</td>
              <td class="text-sm ${days<=7?'text-red':''}">${formatDate(u.expiry_date)} ${days>=0&&days<=7?`<span class="badge badge-yellow">${days}d left</span>`:''}</td>
              <td>${badge(u.status)}</td>
              <td><button class="btn btn-outline btn-sm" onclick="openModal(${u.unit_id},'${u.status}')">✏️</button></td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>`;
  } catch(e) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Failed to load units</h3></div>';
  }
}

function openModal(unitId, currentStatus) {
  currentUnitId = unitId;
  document.getElementById('modal-unit-id').textContent = unitId;
  document.getElementById('new-status').value = currentStatus;
  document.getElementById('status-modal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('status-modal').style.display = 'none';
}

async function saveStatus() {
  const status = document.getElementById('new-status').value;
  const res = await fetchAPI('/DBMS/api/blood_unit.php', 'POST', { action:'update_status', unit_id: currentUnitId, status });
  if (res.success) {
    showToast(res.message, 'success');
    closeModal();
    loadUnits();
  } else {
    showToast(res.message, 'error');
  }
}

// Init page
loadDonors();
loadUnits();
</script>

<?php require_once 'includes/footer.php'; ?>
