<?php
$pageTitle    = 'Expiry Tracker';
$pageSubtitle = 'Monitor expiring units and manage waste reporting';
require_once 'includes/header.php';
?>

<!-- Auto-Expire Notice -->
<div class="alert alert-info mb-24">
  <span class="alert-icon">ℹ️</span>
  <div>Blood units past their expiry date are <strong>automatically marked EXPIRED</strong> when this page loads.
       Units expiring within the next 7 days are highlighted in yellow.</div>
</div>

<!-- Stats Row -->
<div class="stats-grid mb-24" id="expiry-stats">
  <div class="stat-card">
    <div class="stat-icon yellow">⏳</div>
    <div class="stat-info"><h3 id="stat-expiring">—</h3><p>Expiring in 7 Days</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">🗑️</div>
    <div class="stat-info"><h3 id="stat-expired">—</h3><p>Total Expired Units</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">🩸</div>
    <div class="stat-info"><h3 id="stat-total">—</h3><p>Total Units Logged</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">📉</div>
    <div class="stat-info"><h3 id="stat-waste">—%</h3><p>Waste Percentage</p></div>
  </div>
</div>

<!-- Expiring Soon Table -->
<div class="card mb-24">
  <div class="card-header">
    <h2 class="card-title">⚠️ Units Expiring in Next 7 Days</h2>
    <button class="btn btn-outline btn-sm" onclick="loadExpiry()">🔄 Refresh & Auto-Mark</button>
  </div>
  <div class="table-wrapper" id="expiring-soon-panel">
    <div style="padding:48px;text-align:center"><div class="spinner"></div></div>
  </div>
</div>

<!-- Waste Report -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px">
  <!-- Expired Units Table -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">🗑️ Expired Units (Waste Report)</h2>
    </div>
    <div class="table-wrapper" id="expired-panel">
      <div style="padding:48px;text-align:center"><div class="spinner"></div></div>
    </div>
  </div>

  <!-- Waste By Blood Type -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📊 Waste by Type</h2>
    </div>
    <div class="card-body" id="waste-by-type-panel">
      <div style="padding:24px;text-align:center"><div class="spinner"></div></div>
    </div>
  </div>
</div>

<script>
async function loadExpiry() {
  try {
    const res = await fetchAPI('/DBMS/api/expiry.php');

    // Stats
    document.getElementById('stat-expiring').textContent = res.expiring_soon?.length || 0;
    document.getElementById('stat-expired').textContent  = res.waste_count || 0;
    document.getElementById('stat-total').textContent    = res.total_units || 0;
    document.getElementById('stat-waste').textContent    = (res.waste_percent || 0) + '%';

    if (res.auto_expired > 0) {
      showToast(`Auto-marked ${res.auto_expired} unit(s) as EXPIRED.`, 'warning');
    }

    // Expiring Soon Table
    const expPanel = document.getElementById('expiring-soon-panel');
    const expiring = res.expiring_soon || [];
    if (!expiring.length) {
      expPanel.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><h3>No units expiring in the next 7 days</h3><p>All inventory is within safe date range.</p></div>';
    } else {
      expPanel.innerHTML = `
        <table>
          <thead>
            <tr><th>Unit #</th><th>Blood Type</th><th>Donor</th><th>Collection Date</th><th>Expiry Date</th><th>Days Left</th><th>Status</th></tr>
          </thead>
          <tbody>
            ${expiring.map(u => {
              const days = daysUntil(u.expiry_date);
              return `<tr class="expiring-soon">
                <td class="text-sm text-gray">#${u.unit_id}</td>
                <td><span class="badge badge-red">${u.blood_type}</span></td>
                <td><strong>${u.donor_name}</strong></td>
                <td class="text-sm">${formatDate(u.collection_date)}</td>
                <td class="text-sm text-red fw-600">${formatDate(u.expiry_date)}</td>
                <td>
                  <span class="badge ${days<=2?'badge-red':'badge-yellow'}">${days} day${days!==1?'s':''} left</span>
                </td>
                <td>${badge(u.status)}</td>
              </tr>`;
            }).join('')}
          </tbody>
        </table>`;
    }

    // Expired (Waste) Table
    const wPanel  = document.getElementById('expired-panel');
    const expired = res.expired_units || [];
    if (!expired.length) {
      wPanel.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><h3>No expired units</h3></div>';
    } else {
      wPanel.innerHTML = `
        <table>
          <thead>
            <tr><th>Unit #</th><th>Blood</th><th>Donor</th><th>Expired On</th></tr>
          </thead>
          <tbody>
            ${expired.map(u => `
              <tr class="expired-row">
                <td class="text-sm">#${u.unit_id}</td>
                <td><span class="badge badge-gray">${u.blood_type}</span></td>
                <td>${u.donor_name}</td>
                <td class="text-sm">${formatDate(u.expiry_date)}</td>
              </tr>`).join('')}
          </tbody>
        </table>`;
    }

    // Waste by blood type
    const wtPanel = document.getElementById('waste-by-type-panel');
    const wbt     = res.waste_by_type || [];
    const maxCount = wbt.length > 0 ? Math.max(...wbt.map(w => w.expired_count)) : 1;
    if (!wbt.length) {
      wtPanel.innerHTML = '<div style="text-align:center;color:var(--gray-400);padding:24px">No waste recorded</div>';
    } else {
      wtPanel.innerHTML = wbt.map(w => {
        const pct = Math.round((w.expired_count / maxCount) * 100);
        return `
          <div style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
              <span class="fw-600">${w.blood_type}</span>
              <span class="text-sm text-gray">${w.expired_count} units</span>
            </div>
            <div class="progress-bar-wrap">
              <div class="progress-bar red" style="width:${pct}%"></div>
            </div>
          </div>`;
      }).join('');
    }

  } catch(e) {
    showToast('Failed to load expiry data.', 'error');
  }
}

loadExpiry();
</script>

<?php require_once 'includes/footer.php'; ?>
