<?php
$pageTitle    = 'Donation Camp Manager';
$pageSubtitle = 'Create and manage blood donation camps and donor registrations';
require_once 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start">

  <!-- Camp Creation Form -->
  <div style="display:flex;flex-direction:column;gap:20px">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">🏕️ Create Donation Camp</h2>
      </div>
      <div class="card-body">
        <form id="camp-form" onsubmit="createCamp(event)">

          <div class="form-group">
            <label for="camp_name">Camp Name *</label>
            <input type="text" id="camp_name" name="name" placeholder="e.g. Spring Life Camp 2026" required>
          </div>

          <div class="form-group">
            <label for="location">Location / Venue *</label>
            <textarea id="location" name="location" placeholder="Community Hall, Sector 12, Delhi..." required style="min-height:70px"></textarea>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label for="camp_date">Camp Date *</label>
              <input type="date" id="camp_date" name="camp_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label for="capacity">Capacity</label>
              <input type="number" id="capacity" name="capacity" value="50" min="10" max="500">
            </div>
          </div>

          <div class="form-group">
            <label for="doctor_id">Assign Doctor</label>
            <select id="doctor_id" name="doctor_id">
              <option value="">— Optional —</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary btn-block" id="create-camp-btn">
            ✅ Create Camp
          </button>
        </form>
        <div id="camp-result" style="display:none;margin-top:12px"></div>
      </div>
    </div>

    <!-- Register Donor to Camp -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">👤 Register Donor for Camp</h2>
      </div>
      <div class="card-body">
        <form id="reg-form" onsubmit="registerDonor(event)">
          <div class="form-group">
            <label>Select Camp</label>
            <select id="reg_camp_id" required>
              <option value="">— Select a camp —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Select Eligible Donor</label>
            <select id="reg_donor_id" required>
              <option value="">— Select a donor —</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success btn-block">📋 Register Donor</button>
        </form>
        <div id="reg-result" style="display:none;margin-top:12px"></div>
      </div>
    </div>
  </div>

  <!-- Camps Table -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Donation Camps</h2>
      <button class="btn btn-outline btn-sm" onclick="loadCamps()">🔄 Refresh</button>
    </div>
    <div class="table-wrapper" id="camps-panel">
      <div style="padding:48px;text-align:center"><div class="spinner"></div></div>
    </div>
  </div>

</div>

<script>
async function initPage() {
  const res = await fetchAPI('/DBMS/api/camp.php');

  // Doctors dropdown
  const docSel = document.getElementById('doctor_id');
  (res.doctors || []).forEach(d => {
    const o = document.createElement('option');
    o.value = d.doctor_id;
    o.textContent = `${d.name} — ${d.specialization}`;
    docSel.appendChild(o);
  });

  // Donors dropdown
  const donSel = document.getElementById('reg_donor_id');
  (res.donors || []).forEach(d => {
    const o = document.createElement('option');
    o.value = d.donor_id;
    o.textContent = `${d.name} (${d.blood_type})`;
    donSel.appendChild(o);
  });

  renderCamps(res.camps || []);
}

function renderCamps(camps) {
  const campSel = document.getElementById('reg_camp_id');
  campSel.innerHTML = '<option value="">— Select a camp —</option>';
  camps.filter(c=>c.status==='UPCOMING').forEach(c => {
    const o = document.createElement('option');
    o.value = c.camp_id;
    o.textContent = `${c.name} (${formatDate(c.camp_date)})`;
    campSel.appendChild(o);
  });

  const panel = document.getElementById('camps-panel');
  if (!camps.length) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">🏕️</div><h3>No camps created yet</h3></div>';
    return;
  }

  const STATUS_C = {UPCOMING:'badge-blue',ONGOING:'badge-green',COMPLETED:'badge-gray',CANCELLED:'badge-red'};
  panel.innerHTML = `
    <table>
      <thead>
        <tr><th>#</th><th>Camp Name</th><th>Location</th><th>Date</th><th>Capacity</th><th>Registered</th><th>Doctor</th><th>Status</th></tr>
      </thead>
      <tbody>
        ${camps.map(c => {
          const pct = Math.min(100, Math.round((c.registrations / c.capacity) * 100));
          return `
          <tr>
            <td class="text-sm text-gray">#${c.camp_id}</td>
            <td><strong>${c.name}</strong></td>
            <td class="text-sm">${c.location.substring(0,40)}${c.location.length>40?'…':''}</td>
            <td class="text-sm">${formatDate(c.camp_date)}</td>
            <td>${c.capacity}</td>
            <td>
              ${c.registrations}/${c.capacity}
              <div class="progress-bar-wrap mt-4" style="width:90px">
                <div class="progress-bar ${pct >= 90? 'red': pct>=60?'yellow':'green'}" style="width:${pct}%"></div>
              </div>
            </td>
            <td>${c.doctor_name || '<span class="text-gray">—</span>'}</td>
            <td>${badge(c.status, STATUS_C[c.status])}</td>
          </tr>`;
        }).join('')}
      </tbody>
    </table>`;
}

async function loadCamps() {
  const res = await fetchAPI('/DBMS/api/camp.php');
  renderCamps(res.camps || []);
}

async function createCamp(e) {
  e.preventDefault();
  const btn = document.getElementById('create-camp-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
  const data = {
    action:    'create_camp',
    name:      document.getElementById('camp_name').value,
    location:  document.getElementById('location').value,
    camp_date: document.getElementById('camp_date').value,
    capacity:  document.getElementById('capacity').value,
    doctor_id: document.getElementById('doctor_id').value,
  };
  try {
    const res    = document.getElementById('camp-result');
    const result = await fetchAPI('/DBMS/api/camp.php', 'POST', data);
    res.style.display = 'block';
    if (result.success) {
      res.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✅</span> ${result.message}</div>`;
      showToast('Camp created!', 'success');
      document.getElementById('camp-form').reset();
      loadCamps();
    } else {
      res.innerHTML = `<div class="alert alert-danger"><span class="alert-icon">❌</span> ${result.message}</div>`;
    }
  } catch(err) { showToast('Error creating camp.', 'error'); }
  btn.disabled = false; btn.innerHTML = '✅ Create Camp';
}

async function registerDonor(e) {
  e.preventDefault();
  const data = {
    action:   'register_donor',
    camp_id:  document.getElementById('reg_camp_id').value,
    donor_id: document.getElementById('reg_donor_id').value,
  };
  const res    = document.getElementById('reg-result');
  const result = await fetchAPI('/DBMS/api/camp.php', 'POST', data);
  res.style.display = 'block';
  if (result.success) {
    res.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✅</span> ${result.message}</div>`;
    showToast('Donor registered for camp!', 'success');
    loadCamps();
  } else {
    res.innerHTML = `<div class="alert alert-danger"><span class="alert-icon">❌</span> ${result.message}</div>`;
  }
}

initPage();
</script>

<?php require_once 'includes/footer.php'; ?>
