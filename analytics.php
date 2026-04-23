<?php
$pageTitle    = 'Analytics Dashboard';
$pageSubtitle = 'Blood bank performance metrics, charts and historical trends';
require_once 'includes/header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- KPI Stats -->
<div class="stats-grid mb-24" id="analytics-stats">
  <div class="stat-card">
    <div class="stat-icon red">🩸</div>
    <div class="stat-info"><h3 id="kpi-month">—</h3><p>Donations This Month</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">🏆</div>
    <div class="stat-info"><h3 id="kpi-top">—</h3><p>Most Donated Type</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">🏥</div>
    <div class="stat-info"><h3 id="kpi-hospitals">—</h3><p>Hospitals Served</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">📉</div>
    <div class="stat-info"><h3 id="kpi-waste">—%</h3><p>Expiry Waste Rate</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">👤</div>
    <div class="stat-info"><h3 id="kpi-donors">—</h3><p>Total Donors</p></div>
  </div>
</div>

<!-- Peak Shortage Alert -->
<div id="peak-banner" style="display:none" class="alert alert-warning mb-24">
  <span class="alert-icon">⚠️</span>
  <div id="peak-text"></div>
</div>

<!-- Charts Row 1 -->
<div class="analytics-grid-2 mb-24">

  <!-- Monthly Donations Chart -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📅 Monthly Donations (Last 6 Months)</h2>
    </div>
    <div class="card-body">
      <div class="chart-shell" id="shell-monthly">
        <div class="chart-skeleton">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
        <canvas id="chart-monthly"></canvas>
      </div>
    </div>
  </div>

  <!-- Donations by Blood Type Doughnut -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">🩸 Donations by Blood Type</h2>
    </div>
    <div class="card-body">
      <div class="chart-shell" id="shell-by-type">
        <div class="chart-skeleton">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
        <canvas id="chart-by-type"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- Charts Row 2 -->
<div class="analytics-grid-3 mb-24">

  <!-- Request Status Doughnut -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Request Status</h2>
    </div>
    <div class="card-body">
      <div class="chart-shell" id="shell-request-status" style="min-height:200px">
        <div class="chart-skeleton">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
        <canvas id="chart-request-status"></canvas>
      </div>
    </div>
  </div>

  <!-- Donor Status -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">👤 Donor Status</h2>
    </div>
    <div class="card-body">
      <div class="chart-shell" id="shell-donor-status" style="min-height:200px">
        <div class="chart-skeleton">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
        <canvas id="chart-donor-status"></canvas>
      </div>
    </div>
  </div>

  <!-- Available Units by Blood Type -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📦 Available Now</h2>
    </div>
    <div class="card-body">
      <div class="chart-shell" id="shell-available" style="min-height:200px">
        <div class="chart-skeleton">
          <span class="skeleton skeleton-line w-90"></span>
          <span class="skeleton skeleton-line w-75"></span>
          <span class="skeleton skeleton-line w-60"></span>
        </div>
        <canvas id="chart-available"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- Detailed Stats Table -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">📊 Blood Type Breakdown</h2>
  </div>
  <div class="table-wrapper" id="breakdown-table">
    <div class="table-toolbar">
      <input type="search" class="search-input" id="breakdown-search" placeholder="Search blood type...">
      <div class="table-actions">
        <button class="btn btn-outline btn-sm" onclick="loadAnalytics()">Refresh</button>
      </div>
    </div>
    <div id="breakdown-table-content"></div>
  </div>
</div>

<script>
Chart.defaults.plugins.legend.position = 'bottom';

const COLORS_8 = ['#f38ea1','#e05b6b','#fb7185','#f97316','#22c55e','#3b82f6','#8b5cf6','#14b8a6'];
const RED_PALETTE = ['#ffe3e8','#ffccd5','#f8a7b3','#f38194','#e05b6b','#d9465a','#be3144','#9f1239'];

function makeChart(id, type, labels, data, colors, options={}) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  const chart = new Chart(ctx, {
    type,
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderColor: type === 'bar' ? colors : '#fff',
        borderWidth: type === 'bar' ? 0 : 2,
        borderRadius: type === 'bar' ? 6 : 0,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: type !== 'bar' } },
      scales: type === 'bar' ? {
        y: { grid: { color:'#F3F4F6' }, ticks: { stepSize: 1 } },
        x: { grid: { display: false } }
      } : {},
      ...options
    }
  });
  const shell = document.getElementById(`shell-${id.replace('chart-', '')}`);
  shell?.classList.add('is-ready');
  return registerChart(id, chart);
}

async function loadAnalytics() {
  try {
    ['monthly','by-type','request-status','donor-status','available'].forEach(key => {
      document.getElementById(`shell-${key}`)?.classList.remove('is-ready');
    });

    const d = await fetchAPI('/DBMS/api/analytics.php');

    // KPIs
    animateValue('kpi-month', d.month_donations || 0);
    document.getElementById('kpi-top').textContent      = d.top_blood?.blood_type || '—';
    animateValue('kpi-hospitals', d.hospitals_served || 0);
    document.getElementById('kpi-waste').textContent    = (d.waste_percent || 0) + '%';
    animateValue('kpi-donors', d.total_donors || 0);

    // Peak shortage banner
    if (d.shortage_peak) {
      document.getElementById('peak-banner').style.display = 'flex';
      document.getElementById('peak-text').innerHTML =
        `📆 Historical shortage peak: <strong>${d.shortage_peak.month_label}</strong> had the highest request volume (${d.shortage_peak.requests} requests). Plan donation drives ahead of this period.`;
    }

    // Monthly chart
    const months = (d.monthly||[]).map(m => m.month_label);
    const mCounts = (d.monthly||[]).map(m => m.total);
    makeChart('chart-monthly', 'bar', months, mCounts, COLORS_8.map(_=>'#e05b6b'));

    // By type doughnut
    const btLabels = (d.by_type||[]).map(b => b.blood_type);
    const btCounts = (d.by_type||[]).map(b => b.total);
    makeChart('chart-by-type', 'doughnut', btLabels, btCounts, RED_PALETTE);

    // Request status doughnut
    const reqLabels = (d.request_status||[]).map(r => r.status);
    const reqCounts = (d.request_status||[]).map(r => r.cnt);
    const REQ_COLORS = { PENDING:'#FCD34D', MATCHED:'#60A5FA', CONFIRMED:'#F97316', FULFILLED:'#34D399' };
    makeChart('chart-request-status', 'doughnut', reqLabels, reqCounts, reqLabels.map(l=>REQ_COLORS[l]||'#E5E7EB'));

    // Donor status doughnut
    const donLabels = (d.donor_status||[]).map(ds => ds.status.replace('_',' '));
    const donCounts = (d.donor_status||[]).map(ds => ds.cnt);
    const DON_COLORS = ['#34D399','#60A5FA','#FCD34D'];
    makeChart('chart-donor-status', 'doughnut', donLabels, donCounts, DON_COLORS);

    // Available bar chart
    const avLabels = (d.avail_by_type||[]).map(a => a.blood_type);
    const avCounts = (d.avail_by_type||[]).map(a => a.available);
    makeChart('chart-available', 'bar', avLabels, avCounts,
      avCounts.map(c => c < 5 ? '#DC2626' : c < 10 ? '#D97706' : '#16A34A'));

    // Breakdown table
    const breakMap = {};
    (d.by_type||[]).forEach(b => { breakMap[b.blood_type] = { total: b.total, available: 0, waste: 0 }; });
    (d.avail_by_type||[]).forEach(a => { if(breakMap[a.blood_type]) breakMap[a.blood_type].available = a.available; });

    const panel = document.getElementById('breakdown-table');
    const tableContent = document.getElementById('breakdown-table-content');
    tableContent.innerHTML = `
      <table>
        <thead>
          <tr><th>Blood Type</th><th>Total Collected</th><th>Currently Available</th><th>Wasted (Expired)</th><th>Utilization</th></tr>
        </thead>
        <tbody>
          ${Object.entries(breakMap).map(([bt, data]) => {
            const pct = data.total > 0 ? Math.round((data.available / data.total) * 100) : 0;
            return `
              <tr>
                <td><span class="badge badge-red">${bt}</span></td>
                <td>${data.total}</td>
                <td>${data.available}</td>
                <td>${d.waste_count > 0 ? '—' : 0}</td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress-bar-wrap" style="width:100px;flex-shrink:0">
                      <div class="progress-bar ${pct>=60?'green':pct>=30?'yellow':'red'}" style="width:${pct}%"></div>
                    </div>
                    <span class="text-sm">${pct}%</span>
                  </div>
                </td>
              </tr>`;
          }).join('')}
        </tbody>
      </table>`;

    const search = document.getElementById('breakdown-search');
    if (search) {
      search.oninput = () => {
        const q = search.value.trim().toUpperCase();
        tableContent.querySelectorAll('tbody tr').forEach((row) => {
          row.style.display = row.textContent.toUpperCase().includes(q) ? '' : 'none';
        });
      };
    }

  } catch(e) {
    showToast('Failed to load analytics.', 'error');
  }
}

loadAnalytics();
</script>

<?php require_once 'includes/footer.php'; ?>
