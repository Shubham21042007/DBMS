<?php
$pageTitle    = 'Hospital Request Panel';
$pageSubtitle = 'Submit and manage emergency blood requests from hospitals';
require_once 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:minmax(320px,420px) 1fr;gap:24px;align-items:start">

  <!-- Request Form -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">🏥 New Blood Request</h2>
    </div>
    <div class="card-body">
      <form id="request-form" onsubmit="submitRequest(event)">

        <div class="field">
          <select id="hospital_id" name="hospital_id" required>
            <option value="">— Select Hospital —</option>
          </select>
          <label for="hospital_id">Hospital *</label>
          <div class="field-error" id="err-hospital"></div>
        </div>

        <div class="form-grid">
          <div class="field">
            <select id="blood_type" name="blood_type" required>
              <option value="">Select type</option>
              <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
              <option value="<?= $bt ?>"><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
            <label for="blood_type">Blood Type Required *</label>
            <div class="field-error" id="err-type"></div>
          </div>
          <div class="field">
            <input type="number" id="units_needed" name="units_needed" min="1" max="20" value="1" placeholder=" " required>
            <label for="units_needed">Units Needed *</label>
            <div class="field-error" id="err-units"></div>
          </div>
        </div>

        <div class="field">
          <select id="urgency" name="urgency">
            <option value="NORMAL">Normal</option>
            <option value="URGENT">Urgent</option>
            <option value="CRITICAL">Critical</option>
          </select>
          <label for="urgency">Urgency Level</label>
        </div>

        <div class="field">
          <textarea id="notes" name="notes" placeholder=" "></textarea>
          <label for="notes">Additional Notes</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-req-btn">
          🔍 Submit & Match Request
        </button>
      </form>

      <!-- Match Result -->
      <div id="match-result" style="display:none;margin-top:16px"></div>
    </div>
  </div>

  <!-- Requests Table -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Blood Requests Log</h2>
      <button class="btn btn-outline btn-sm" onclick="loadRequests()">🔄 Refresh</button>
    </div>
    <div class="table-toolbar">
      <input type="search" id="request-search" class="search-input" placeholder="Search hospital, blood type, urgency...">
      <div class="table-actions">
        <select id="filter-status" class="search-input" style="width:160px" onchange="loadRequests()">
          <option value="">All Status</option>
          <option value="PENDING">PENDING</option>
          <option value="MATCHED">MATCHED</option>
          <option value="CONFIRMED">CONFIRMED</option>
          <option value="FULFILLED">FULFILLED</option>
        </select>
        <span class="text-sm text-gray" id="request-count">0 records</span>
      </div>
    </div>
    <div class="table-wrapper" id="requests-panel">
      <div style="padding:20px">
        <div class="skeleton-card">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Current Inventory Quick View -->
<div class="card mt-24">
  <div class="card-header">
    <h2 class="card-title">🩸 Current Available Inventory</h2>
    <span class="text-sm text-gray">Quick reference before submitting a request</span>
  </div>
  <div class="card-body">
    <div class="blood-grid" id="inventory-mini">
      <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
      <div class="blood-card" id="mini-<?= str_replace(['+','-'],['p','m'],$bt) ?>">
        <div class="blood-type-label"><?= $bt ?></div>
        <div class="blood-unit-count">—</div>
        <div class="blood-unit-label">available</div>
        <div><span class="blood-status-badge">—</span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
async function initPage() {
  // Load hospitals
  const res = await fetchAPI('/DBMS/api/request.php');
  const sel = document.getElementById('hospital_id');
  (res.hospitals || []).forEach(h => {
    const o = document.createElement('option');
    o.value = h.hospital_id; o.textContent = h.name;
    sel.appendChild(o);
  });

  // Load inventory mini
  const dash = await fetchAPI('/DBMS/api/dashboard.php');
  const types = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
  types.forEach(bt => {
    const id  = 'mini-' + bt.replace('+','p').replace('-','m');
    const cnt = dash.inventory[bt] ?? 0;
    const card = document.getElementById(id);
    if (!card) return;
    const cls   = cnt >= 10 ? 'status-ok' : cnt >= 5 ? 'status-low' : 'status-critical';
    const label = cnt >= 10 ? 'SUFFICIENT' : cnt >= 5 ? 'LOW' : 'CRITICAL';
    card.className = `blood-card ${cls}`;
    card.querySelector('.blood-unit-count').textContent = cnt;
    card.querySelector('.blood-status-badge').textContent = label;
  });
}

async function submitRequest(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-req-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Matching...';

  const data = {
    hospital_id:  document.getElementById('hospital_id').value,
    blood_type:   document.getElementById('blood_type').value,
    units_needed: document.getElementById('units_needed').value,
    urgency:      document.getElementById('urgency').value,
    notes:        document.getElementById('notes').value,
  };

  document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });
  let invalid = false;
  if (!data.hospital_id) { document.getElementById('err-hospital').textContent = 'Select a hospital.'; invalid = true; }
  if (!data.blood_type) { document.getElementById('err-type').textContent = 'Select required blood type.'; invalid = true; }
  if (Number(data.units_needed) < 1 || Number(data.units_needed) > 20) {
    document.getElementById('err-units').textContent = 'Units must be between 1 and 20.';
    invalid = true;
  }

  if (invalid) {
    btn.disabled = false;
    btn.innerHTML = '🔍 Submit & Match Request';
    showToast('Please fix the highlighted fields.', 'warning');
    return;
  }

  try {
    const res   = await fetchAPI('/DBMS/api/request.php', 'POST', data);
    const panel = document.getElementById('match-result');
    panel.style.display = 'block';

    if (res.status === 'MATCHED') {
      panel.innerHTML = `
        <div class="result-box show eligible">
          <div class="result-icon">✅</div>
          <div class="result-title">MATCHED!</div>
          <div class="result-reason">${res.message}</div>
          <div style="margin-top:8px;font-size:12px;color:var(--gray-400)">Request ID: #${res.request_id}</div>
        </div>`;
      showToast('Blood request matched successfully!', 'success');
    } else {
      panel.innerHTML = `
        <div class="result-box show rejected">
          <div class="result-icon">⚠️</div>
          <div class="result-title">NOT AVAILABLE</div>
          <div class="result-reason">${res.message}</div>
          <div style="margin-top:8px;font-size:12px;color:var(--gray-400)">Request ID: #${res.request_id} — logged as PENDING</div>
        </div>`;
      showToast('Insufficient stock. Request logged as PENDING.', 'warning');
    }
    document.getElementById('request-form').reset();
    loadRequests();
  } catch(e) {
    showToast('Error submitting request.', 'error');
  }

  btn.disabled = false; btn.innerHTML = '🔍 Submit & Match Request';
}

async function loadRequests() {
  const panel  = document.getElementById('requests-panel');
  const filter = document.getElementById('filter-status').value;
  showLoadingSkeleton(panel, 'table');

  try {
    const res = await fetchAPI('/DBMS/api/request.php');
    let reqs = res.requests || [];
    if (filter) reqs = reqs.filter(r => r.status === filter);

    if (reqs.length === 0) {
      panel.innerHTML = '<div class="empty-state"><div class="empty-icon">🏥</div><h3>No requests found</h3></div>';
      return;
    }

    const URGENCY_CLASS = { NORMAL:'badge-gray', URGENT:'badge-yellow', CRITICAL:'badge-red' };
    panel.innerHTML = `
      <table>
        <thead>
          <tr><th>#</th><th>Hospital</th><th>Blood</th><th>Units</th><th>Urgency</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
          ${reqs.map(r => `
            <tr class="${r.urgency==='CRITICAL'?'urgency-critical':r.urgency==='URGENT'?'urgency-high':''}">
              <td class="text-sm text-gray">#${r.request_id}</td>
              <td><strong>${r.hospital_name}</strong></td>
              <td><span class="badge badge-red">${r.blood_type}</span></td>
              <td>${r.units_needed}</td>
              <td>${badge(r.urgency, URGENCY_CLASS[r.urgency])}</td>
              <td>${badge(r.status)}</td>
              <td class="text-sm">${formatDate(r.request_date)}</td>
            </tr>`).join('')}
        </tbody>
      </table>`;

    const rows = panel.querySelectorAll('tbody tr');
    document.getElementById('request-count').textContent = `${rows.length} records`;
    const search = document.getElementById('request-search');
    if (search) {
      search.oninput = () => {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach((row) => {
          const match = row.textContent.toLowerCase().includes(q);
          row.style.display = match ? '' : 'none';
          if (match) visible += 1;
        });
        document.getElementById('request-count').textContent = `${visible} records`;
      };
    }
  } catch(e) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Failed to load requests</h3></div>';
  }
}

initPage(); loadRequests();
</script>

<?php require_once 'includes/footer.php'; ?>
