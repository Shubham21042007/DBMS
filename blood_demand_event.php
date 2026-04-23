<?php
$pageTitle    = 'Blood Demand Events';
$pageSubtitle = 'Log tragedy or shortage events and trigger urgent appeals';
require_once 'includes/header.php';
?>

<!-- Create Event Form -->
<div class="card mb-24">
  <div class="card-header">
    <h2 class="card-title">🚨 Log New Demand Event</h2>
  </div>
  <div class="card-body">
    <form id="event-form" onsubmit="createEvent(event)">
      <div class="form-grid">
        <div class="form-group">
          <label for="title">Event Title *</label>
          <input type="text" id="title" name="title" placeholder="e.g. Highway Accident — NH-8" required>
        </div>
        <div class="form-group">
          <label for="event_type">Event Type</label>
          <select id="event_type" name="event_type">
            <option value="TRAGEDY">🚑 Tragedy / Accident</option>
            <option value="SHORTAGE">⚠️ Blood Shortage</option>
            <option value="DISASTER">🌊 Natural Disaster</option>
            <option value="OTHER">📋 Other</option>
          </select>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="City, State / National">
        </div>
        <div class="form-group">
          <label for="event_date">Event Date *</label>
          <input type="date" id="event_date" name="event_date" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Describe the event, affected persons, urgency..."></textarea>
      </div>

      <!-- Blood Type Demands Table -->
      <div class="form-group">
        <label>Blood Types Required <span class="text-sm text-gray">(fill in the types needed)</span></label>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Blood Type</th><th>Units Required</th><th>Urgency Level</th><th>Include?</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bt): ?>
              <tr>
                <td><span class="badge badge-red"><?= $bt ?></span></td>
                <td><input type="number" name="req_<?= str_replace(['+','-'],['p','m'],$bt) ?>" min="1" max="100" value="5" style="width:80px;padding:6px;" class="demand-input"></td>
                <td>
                  <select name="urg_<?= str_replace(['+','-'],['p','m'],$bt) ?>" style="padding:6px;font-size:12px">
                    <option value="CRITICAL">CRITICAL</option>
                    <option value="HIGH" selected>HIGH</option>
                    <option value="MEDIUM">MEDIUM</option>
                    <option value="LOW">LOW</option>
                  </select>
                </td>
                <td>
                  <input type="checkbox" name="chk_<?= str_replace(['+','-'],['p','m'],$bt) ?>" value="1" style="width:18px;height:18px;cursor:pointer">
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" id="create-event-btn">
        🚨 Create Emergency Event
      </button>
    </form>
  </div>
</div>

<!-- Event Result Panel -->
<div id="event-result" style="display:none"></div>

<!-- Active Events List -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">📋 All Demand Events</h2>
    <button class="btn btn-outline btn-sm" onclick="loadEvents()">🔄 Refresh</button>
  </div>
  <div id="events-list-panel">
    <div style="padding:48px;text-align:center"><div class="spinner"></div></div>
  </div>
</div>

<script>
const BT_LIST = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];
const BT_ID   = bt => bt.replace('+','p').replace('-','m');

async function createEvent(e) {
  e.preventDefault();
  const btn = document.getElementById('create-event-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Creating...';

  const demands = [];
  BT_LIST.forEach(bt => {
    const id  = BT_ID(bt);
    const chk = document.querySelector(`input[name="chk_${id}"]`);
    if (chk && chk.checked) {
      demands.push({
        blood_type:     bt,
        units_required: parseInt(document.querySelector(`input[name="req_${id}"]`).value) || 5,
        urgency_level:  document.querySelector(`select[name="urg_${id}"]`).value,
      });
    }
  });

  if (!demands.length) {
    showToast('Please check at least one blood type needed.', 'warning');
    btn.disabled = false; btn.innerHTML = '🚨 Create Emergency Event';
    return;
  }

  const data = {
    action:      'create_event',
    title:       document.getElementById('title').value,
    description: document.getElementById('description').value,
    event_type:  document.getElementById('event_type').value,
    location:    document.getElementById('location').value,
    event_date:  document.getElementById('event_date').value,
    demands,
  };

  try {
    const res    = await fetchAPI('/DBMS/api/demand_event.php', 'POST', data);
    const panel  = document.getElementById('event-result');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior:'smooth', block:'start' });

    let html = '';
    if (res.urgent_appeal) {
      const alerts = res.stock_alerts.map(a =>
        `<strong>${a.blood_type}</strong>: only ${a.available} units available, ${a.required} required (shortfall: ${a.shortfall})`
      ).join('<br>');
      html += `<div class="urgent-banner mb-16">
        <span style="font-size:28px">🆘</span>
        <div><h3>URGENT APPEAL ACTIVATED</h3><p>${alerts}</p></div>
      </div>`;
    }

    if (res.flagged_donors && res.flagged_donors.length > 0) {
      html += `<div class="card mb-16">
        <div class="card-header"><h2 class="card-title">🏃 Flagged Eligible Donors (${res.flagged_donors.length})</h2></div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Name</th><th>Blood Type</th><th>Phone</th><th>Email</th></tr></thead>
            <tbody>
              ${res.flagged_donors.map(d => `
                <tr>
                  <td><strong>${d.name}</strong></td>
                  <td><span class="badge badge-red">${d.blood_type}</span></td>
                  <td>${d.phone||'—'}</td>
                  <td>${d.email||'—'}</td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </div>`;
    } else {
      html += `<div class="alert alert-warning"><span>⚠️</span> No eligible donors found for the demanded blood types currently.</div>`;
    }

    panel.innerHTML = html;
    showToast(res.message, 'success');
    document.getElementById('event-form').reset();
    loadEvents();
  } catch(err) {
    showToast('Error creating event.', 'error');
  }
  btn.disabled = false; btn.innerHTML = '🚨 Create Emergency Event';
}

async function resolveEvent(eventId) {
  const res = await fetchAPI('/DBMS/api/demand_event.php', 'POST', {action:'resolve_event', event_id: eventId});
  if (res.success) { showToast('Event resolved.', 'success'); loadEvents(); }
}

async function loadEvents() {
  const panel = document.getElementById('events-list-panel');
  panel.innerHTML = '<div style="padding:40px;text-align:center"><div class="spinner"></div></div>';

  try {
    const res = await fetchAPI('/DBMS/api/demand_event.php');
    const events = res.events || [];

    if (!events.length) {
      panel.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><h3>No demand events logged</h3></div>';
      return;
    }

    const TYPE_ICONS = { TRAGEDY:'🚑', SHORTAGE:'⚠️', DISASTER:'🌊', OTHER:'📋' };
    const ST_CLS = { ACTIVE:'badge-red', RESOLVED:'badge-green', CLOSED:'badge-gray' };
    const UG_CLS = { CRITICAL:'badge-red', HIGH:'badge-orange', MEDIUM:'badge-yellow', LOW:'badge-gray' };

    panel.innerHTML = events.map(ev => {
      const demandRows = (ev.demands||[]).map(d => `
        <tr>
          <td><span class="badge badge-red">${d.blood_type}</span></td>
          <td>${badge(d.urgency_level, UG_CLS[d.urgency_level])}</td>
          <td>${d.units_required}</td>
          <td>${d.current_stock}</td>
          <td>${d.shortage > 0 ? `<span class="text-red fw-600">-${d.shortage}</span>` : `<span class="text-green fw-600">+${d.current_stock - d.units_required}</span>`}</td>
        </tr>`).join('');

      return `
      <div class="card mb-16" style="border-left:4px solid ${ev.status==='ACTIVE'?'#DC2626':'#6B7280'}">
        <div class="card-header">
          <div>
            <span style="font-size:20px">${TYPE_ICONS[ev.event_type]||'📋'}</span>
            <strong style="margin-left:8px">${ev.title}</strong>
            <div class="text-sm text-gray mt-4">${ev.location||'—'} · ${formatDate(ev.event_date)}</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center">
            ${badge(ev.status, ST_CLS[ev.status])}
            ${ev.status==='ACTIVE'?`<button class="btn btn-outline btn-sm" onclick="resolveEvent(${ev.event_id})">✅ Resolve</button>`:''}
          </div>
        </div>
        ${ev.demands && ev.demands.length>0 ? `
        <div class="card-body" style="padding-top:0">
          <table>
            <thead><tr><th>Blood Type</th><th>Urgency</th><th>Required</th><th>In Stock</th><th>Shortfall</th></tr></thead>
            <tbody>${demandRows}</tbody>
          </table>
        </div>` : ''}
      </div>`;
    }).join('');
  } catch(e) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Failed to load events</h3></div>';
  }
}

loadEvents();
</script>

<?php require_once 'includes/footer.php'; ?>
