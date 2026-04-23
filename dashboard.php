<?php
$pageTitle    = 'Home Dashboard';
$pageSubtitle = 'Live Blood Inventory & System Overview';
require_once 'includes/header.php';
?>

<!-- Critical Alert Banner (shown when any blood type < 5) -->
<div id="critical-banner" style="display:none" class="urgent-banner mb-24">
  <span style="font-size:28px">🚨</span>
  <div>
    <h3>CRITICAL BLOOD SHORTAGE ALERT</h3>
    <p id="critical-banner-text">Some blood types are critically low. Immediate action required.</p>
  </div>
</div>

<!-- Active Emergency Events Banner -->
<div id="events-banner" style="display:none" class="alert alert-danger mb-24">
  <span class="alert-icon">⚠️</span>
  <div id="events-banner-text">Active emergency demand events require attention.</div>
</div>

<!-- Stats Row -->
<div class="stats-grid mb-24">
  <div class="stat-card">
    <div class="stat-icon red">🩸</div>
    <div class="stat-info">
      <h3 id="stat-available">—</h3>
      <p>Available Units</p>
      <span class="kpi-trend up" id="trend-available">↑ live</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">👤</div>
    <div class="stat-info">
      <h3 id="stat-donors">—</h3>
      <p>Registered Donors</p>
      <span class="kpi-trend up" id="trend-donors">↑ growth</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">🏥</div>
    <div class="stat-info">
      <h3 id="stat-requests">—</h3>
      <p>Pending Requests</p>
      <span class="kpi-trend down" id="trend-requests">↓ resolve fast</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">📅</div>
    <div class="stat-info">
      <h3 id="stat-month">—</h3>
      <p>Donations This Month</p>
      <span class="kpi-trend up" id="trend-month">↑ this month</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">🚨</div>
    <div class="stat-info">
      <h3 id="stat-events">—</h3>
      <p>Active Events</p>
      <span class="kpi-trend down" id="trend-events">↓ keep low</span>
    </div>
  </div>
</div>

<!-- Blood Type Inventory Grid -->
<div class="card mb-24">
  <div class="card-header">
    <h2 class="card-title">🩸 Live Blood Inventory</h2>
    <div>
      <span class="badge badge-green">● ≥10 Sufficient</span>&nbsp;
      <span class="badge badge-yellow">● 5–9 Low</span>&nbsp;
      <span class="badge badge-red">● &lt;5 Critical</span>
    </div>
  </div>
  <div class="card-body">
    <div class="blood-grid" id="blood-grid">
      <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
      <div class="blood-card" id="bc-<?= str_replace(['+','-'],['p','m'],$bt) ?>">
        <div class="blood-type-label"><?= $bt ?></div>
        <div class="blood-unit-count">—</div>
        <div class="blood-unit-label">units available</div>
        <div class="progress-bar-wrap"><div class="progress-bar" style="width:0%"></div></div>
        <div><span class="blood-status-badge">LOADING</span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Bottom Two-Column Layout -->
<div class="dashboard-grid">

  <!-- Recent Activity -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Recent Activity</h2>
      <span class="text-sm text-gray">Last 10 entries</span>
    </div>
    <div class="card-body" style="padding:0 24px">
      <ul class="activity-feed" id="activity-feed">
        <li style="padding:24px 0;text-align:center;color:var(--gray-400)">Loading...</li>
      </ul>
    </div>
  </div>

  <!-- Active Emergency Requests -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">🚑 Active Emergency Events</h2>
    </div>
    <div class="card-body" id="events-list" style="padding:0 24px">
      <p style="padding:24px 0;text-align:center;color:var(--gray-400)">Loading...</p>
    </div>
  </div>

</div>

<script>
const BLOOD_TYPES = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
const ID_MAP = t => t.replace('+','p').replace('-','m');

async function loadDashboard() {
  try {
    const d = await fetchAPI('/DBMS/api/dashboard.php');

    const setTrend = (id, value, favorableLow = false) => {
      const el = document.getElementById(id);
      if (!el) return;
      const isDown = favorableLow ? value <= 0 : value < 0;
      el.classList.remove('up', 'down');
      el.classList.add(isDown ? 'down' : 'up');
      const arrow = isDown ? '↓' : '↑';
      const label = Number.isFinite(value) ? `${arrow} ${Math.abs(value)} vs last update` : `${arrow} live`;
      el.textContent = label;
    };

    const prevAvailable = Number(document.getElementById('stat-available').textContent.replace(/,/g, '')) || 0;
    const prevDonors = Number(document.getElementById('stat-donors').textContent.replace(/,/g, '')) || 0;
    const prevReq = Number(document.getElementById('stat-requests').textContent.replace(/,/g, '')) || 0;
    const prevMonth = Number(document.getElementById('stat-month').textContent.replace(/,/g, '')) || 0;
    const prevEvents = Number(document.getElementById('stat-events').textContent.replace(/,/g, '')) || 0;

    // Stats
    animateValue('stat-available', d.available_units || 0);
    animateValue('stat-donors', d.donor_count || 0);
    animateValue('stat-requests', d.active_requests || 0);
    animateValue('stat-month', d.month_donations || 0);
    animateValue('stat-events', d.active_events || 0);

    setTrend('trend-available', (d.available_units || 0) - prevAvailable);
    setTrend('trend-donors', (d.donor_count || 0) - prevDonors);
    setTrend('trend-requests', (d.active_requests || 0) - prevReq, true);
    setTrend('trend-month', (d.month_donations || 0) - prevMonth);
    setTrend('trend-events', (d.active_events || 0) - prevEvents, true);

    // Critical banner
    if (d.critical_types && d.critical_types.length > 0) {
      const names = d.critical_types.map(c => `${c.blood_type} (${c.units} units)`).join(', ');
      document.getElementById('critical-banner').style.display = 'flex';
      document.getElementById('critical-banner-text').textContent = `Critical shortage: ${names}`;

      // Nav badge
      const nb = document.getElementById('nav-critical-count');
      if (nb) { nb.textContent = d.critical_types.length; nb.style.display = ''; }
    }

    // Active events banner
    if (d.active_events > 0) {
      document.getElementById('events-banner').style.display = 'flex';
      document.getElementById('events-banner-text').textContent =
        `⚠️ ${d.active_events} active emergency demand event(s) require immediate attention. Check the Demand Events page.`;
      document.getElementById('topbar-emergency').style.display = 'flex';
    }

    // Blood inventory cards
    BLOOD_TYPES.forEach(bt => {
      const cnt   = d.inventory[bt] ?? 0;
      const card  = document.getElementById('bc-' + ID_MAP(bt));
      if (!card) return;
      const cls   = cnt >= 10 ? 'status-ok' : cnt >= 5 ? 'status-low' : 'status-critical';
      const label = cnt >= 10 ? 'SUFFICIENT' : cnt >= 5 ? 'LOW' : 'CRITICAL';
      card.className = `blood-card ${cls}`;
      animateValue(card.querySelector('.blood-unit-count'), cnt || 0);
      card.querySelector('.blood-status-badge').textContent = label;
      const pct = Math.min((cnt / 20) * 100, 100);
      const bar = card.querySelector('.progress-bar');
      if (bar) {
        bar.style.width = `${pct}%`;
        bar.className = `progress-bar ${cnt >= 10 ? 'green' : cnt >= 5 ? 'yellow' : 'red'}`;
      }
    });

    // Activity feed
    const feed = document.getElementById('activity-feed');
    if (d.recent_activity && d.recent_activity.length > 0) {
      feed.innerHTML = d.recent_activity.map(a => {
        const dotClass = a.status === 'AVAILABLE' ? 'donated' : a.status === 'EXPIRED' ? 'expired' : 'donated';
        return `<li class="activity-item">
          <div class="activity-dot ${dotClass}"></div>
          <div>
            <div class="activity-text"><strong>${a.donor_name}</strong> donated <strong>${a.blood_type}</strong> blood — Unit #${a.unit_id}</div>
            <div class="activity-time">${formatDate(a.collection_date)} · Status: ${a.status}</div>
          </div>
        </li>`;
      }).join('');
    } else {
      feed.innerHTML = '<li style="padding:24px 0;text-align:center;color:var(--gray-400)">No recent activity</li>';
    }

    // Events list
    const evList = document.getElementById('events-list');
    if (d.events_detail && d.events_detail.length > 0) {
      evList.innerHTML = d.events_detail.map(ev => `
        <div class="activity-item" style="padding:14px 0">
          <div class="activity-dot request"></div>
          <div>
            <div class="activity-text fw-600">${ev.title}</div>
            <div class="activity-time">${ev.event_type} · ${formatDate(ev.event_date)} · ${ev.location||'—'}</div>
          </div>
        </div>`).join('');
    } else {
      evList.innerHTML = '<div style="padding:24px 0;text-align:center;color:var(--gray-400)">✅ No active emergency events</div>';
    }

  } catch(e) {
    showToast('Failed to load dashboard data', 'error');
  }
}

loadDashboard();
// Auto-refresh every 30s
setInterval(loadDashboard, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>
