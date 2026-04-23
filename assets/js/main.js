// ============================================================
// Smart Blood Bank Management System — Shared JS Utilities
// ============================================================

const APP_UI = {
  chartInstances: {},
  previousValues: new Map(),
};

// ===== API Fetch Helper =====
async function fetchAPI(endpoint, method = 'GET', data = null) {
  const options = { method, headers: { 'Content-Type': 'application/json' } };
  if (data) options.body = JSON.stringify(data);
  const res = await fetch(endpoint, options);
  return res.json();
}

// ===== Toast Notifications =====
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: '✓', error: '!', warning: '!', info: 'i' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || '•'}</span>
    <span class="toast-message">${message}</span>
    <button class="toast-close" aria-label="Close notification">×</button>
  `;
  container.appendChild(toast);

  const close = () => {
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 240);
  };

  toast.querySelector('.toast-close')?.addEventListener('click', close);
  setTimeout(close, 3200);
}

// ===== Sidebar Toggle =====
function initSidebar() {
  const toggle = document.getElementById('menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  let overlay = document.querySelector('.sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
  }
  if (toggle) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
      document.body.classList.toggle('no-scroll', sidebar.classList.contains('open'));
    });
  }
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.classList.remove('no-scroll');
  });
}

// ===== Live Clock =====
function initClock() {
  const el = document.getElementById('live-clock');
  if (!el) return;
  function update() {
    el.textContent = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
  update();
  setInterval(update, 1000);
}

// ===== Blood Type Color Coding =====
function getBloodCardClass(units) {
  if (units >= 10) return 'status-ok';
  if (units >= 5)  return 'status-low';
  return 'status-critical';
}

function getBloodStatusLabel(units) {
  if (units >= 10) return 'SUFFICIENT';
  if (units >= 5)  return 'LOW';
  return 'CRITICAL';
}

// ===== Date Helpers =====
function addDays(dateStr, days) {
  const d = new Date(dateStr);
  d.setDate(d.getDate() + days);
  return d.toISOString().split('T')[0];
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function daysUntil(dateStr) {
  const diff = new Date(dateStr) - new Date();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

// ===== Status Badge Helper =====
const STATUS_BADGES = {
  ELIGIBLE:       'badge-green',
  DONATED:        'badge-blue',
  COOLING_PERIOD: 'badge-yellow',
  COLLECTED:      'badge-gray',
  TESTED:         'badge-blue',
  AVAILABLE:      'badge-green',
  RESERVED:       'badge-yellow',
  USED:           'badge-purple',
  EXPIRED:        'badge-red',
  PENDING:        'badge-yellow',
  MATCHED:        'badge-blue',
  CONFIRMED:      'badge-orange',
  FULFILLED:      'badge-green',
  UPCOMING:       'badge-blue',
  ONGOING:        'badge-green',
  COMPLETED:      'badge-gray',
  CANCELLED:      'badge-red',
  ACTIVE:         'badge-red',
  RESOLVED:       'badge-green',
  CLOSED:         'badge-gray',
  LOW:            'badge-yellow',
  MEDIUM:         'badge-orange',
  HIGH:           'badge-red',
  CRITICAL:       'badge-red',
};

function badge(text, cls) {
  const c = cls || STATUS_BADGES[text] || 'badge-gray';
  return `<span class="badge ${c}">${text}</span>`;
}

// ===== Active Nav Link =====
function setActiveNav() {
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(a => {
    const href = a.getAttribute('href').split('/').pop();
    if (href === path) a.classList.add('active');
  });
}

// ===== Form Serialize =====
function serializeForm(formId) {
  const form = document.getElementById(formId);
  const data = {};
  new FormData(form).forEach((v, k) => { data[k] = v; });
  return data;
}

// ===== Number Formatting =====
function fmt(n) { return Number(n).toLocaleString('en-IN'); }

// ===== Number Animation =====
function animateValue(elOrId, nextValue, options = {}) {
  const el = typeof elOrId === 'string' ? document.getElementById(elOrId) : elOrId;
  if (!el) return;

  const key = el.id || options.key || String(Math.random());
  const previousRaw = APP_UI.previousValues.get(key);
  const previous = Number(previousRaw ?? 0);
  const target = Number(nextValue ?? 0);

  if (!Number.isFinite(target)) {
    el.textContent = nextValue;
    return;
  }

  if (!Number.isFinite(previousRaw)) {
    APP_UI.previousValues.set(key, target);
    el.textContent = fmt(target);
    return;
  }

  const duration = options.duration ?? 450;
  const start = performance.now();
  const delta = target - previous;

  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const value = Math.round(previous + delta * eased);
    el.textContent = options.raw ? String(value) : fmt(value);
    if (progress < 1) {
      requestAnimationFrame(tick);
      return;
    }
    APP_UI.previousValues.set(key, target);
  };

  requestAnimationFrame(tick);
}

// ===== Skeleton Helpers =====
function renderSkeletonRows(rows = 5, cols = 5) {
  return `
    <table class="table-skeleton">
      <thead>
        <tr>${new Array(cols).fill('<th><span class="skeleton skeleton-line"></span></th>').join('')}</tr>
      </thead>
      <tbody>
        ${new Array(rows).fill(`<tr>${new Array(cols).fill('<td><span class="skeleton skeleton-line"></span></td>').join('')}</tr>`).join('')}
      </tbody>
    </table>
  `;
}

function showLoadingSkeleton(target, variant = 'card') {
  const el = typeof target === 'string' ? document.getElementById(target) : target;
  if (!el) return;
  if (variant === 'table') {
    el.innerHTML = renderSkeletonRows(6, 7);
    return;
  }
  el.innerHTML = '<div class="skeleton-card"><span class="skeleton skeleton-line w-60"></span><span class="skeleton skeleton-line w-90"></span><span class="skeleton skeleton-line w-75"></span></div>';
}

// ===== Confirmation Modal =====
function ensureConfirmModal() {
  let modal = document.getElementById('confirm-modal');
  if (modal) return modal;

  modal = document.createElement('div');
  modal.id = 'confirm-modal';
  modal.className = 'modal-backdrop';
  modal.innerHTML = `
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
      <h3 id="confirm-title">Please confirm</h3>
      <p id="confirm-message">Are you sure you want to continue?</p>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-confirm-cancel>Cancel</button>
        <button type="button" class="btn btn-primary" data-confirm-ok>Confirm</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  return modal;
}

function confirmAction(message, onConfirm, title = 'Please confirm') {
  const modal = ensureConfirmModal();
  const titleEl = modal.querySelector('#confirm-title');
  const msgEl = modal.querySelector('#confirm-message');
  const ok = modal.querySelector('[data-confirm-ok]');
  const cancel = modal.querySelector('[data-confirm-cancel]');

  titleEl.textContent = title;
  msgEl.textContent = message;
  modal.classList.add('show');
  document.body.classList.add('no-scroll');

  const close = () => {
    modal.classList.remove('show');
    document.body.classList.remove('no-scroll');
    ok.removeEventListener('click', handleOk);
    cancel.removeEventListener('click', close);
    modal.removeEventListener('click', backdropClose);
  };

  const handleOk = () => {
    close();
    if (typeof onConfirm === 'function') onConfirm();
  };

  const backdropClose = (e) => {
    if (e.target === modal) close();
  };

  ok.addEventListener('click', handleOk);
  cancel.addEventListener('click', close);
  modal.addEventListener('click', backdropClose);
}

// ===== Ripple Effect for Buttons =====
function initButtonRipples() {
  document.querySelectorAll('.btn').forEach((btn) => {
    if (btn.dataset.rippleBound) return;
    btn.dataset.rippleBound = '1';
    btn.classList.add('ripple-host');

    btn.addEventListener('click', (e) => {
      const rect = btn.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.className = 'ripple';
      ripple.style.left = `${e.clientX - rect.left}px`;
      ripple.style.top = `${e.clientY - rect.top}px`;
      btn.appendChild(ripple);
      setTimeout(() => ripple.remove(), 450);
    });
  });
}

// ===== Floating Labels =====
function initFloatingFields() {
  const controls = document.querySelectorAll('.field input, .field select, .field textarea');
  controls.forEach((control) => {
    const sync = () => {
      const hasValue = !!String(control.value ?? '').trim();
      control.parentElement?.classList.toggle('is-filled', hasValue);
    };
    sync();
    control.addEventListener('input', sync);
    control.addEventListener('change', sync);
    control.addEventListener('focus', () => control.parentElement?.classList.add('is-focused'));
    control.addEventListener('blur', () => control.parentElement?.classList.remove('is-focused'));
  });
}

// ===== Chart.js Defaults =====
function initChartDefaults() {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = '"Plus Jakarta Sans", "Segoe UI", sans-serif';
  Chart.defaults.color = '#64748b';
  Chart.defaults.animation.duration = 700;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
  Chart.defaults.plugins.tooltip.padding = 12;
  Chart.defaults.plugins.tooltip.cornerRadius = 10;
}

function registerChart(key, chart) {
  if (APP_UI.chartInstances[key]) {
    APP_UI.chartInstances[key].destroy();
  }
  APP_UI.chartInstances[key] = chart;
  return chart;
}

// ===== Init on DOM ready =====
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initClock();
  setActiveNav();
  initButtonRipples();
  initFloatingFields();
  initChartDefaults();
});
