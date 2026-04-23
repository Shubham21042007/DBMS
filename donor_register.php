<?php
$pageTitle    = 'Donor Registration';
$pageSubtitle = 'Register new blood donors with automatic eligibility check';
require_once 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

  <!-- Registration Form -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">👤 Register New Donor</h2>
    </div>
    <div class="card-body">
      <form id="donor-form" onsubmit="submitDonor(event)">

        <div class="form-grid">
          <div class="field">
            <input type="text" id="name" name="name" placeholder=" " required>
            <label for="name">Full Name *</label>
            <div class="field-error" id="err-name"></div>
          </div>
          <div class="field">
            <input type="number" id="age" name="age" min="1" max="80" placeholder=" " required>
            <label for="age">Age (years) *</label>
            <div class="field-error" id="err-age"></div>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <input type="number" id="weight" name="weight" step="0.1" min="1" placeholder=" " required>
            <label for="weight">Weight (kg) *</label>
            <div class="field-error" id="err-weight"></div>
          </div>
          <div class="field">
            <select id="blood_type" name="blood_type" required>
              <option value="">Select blood type</option>
              <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
              <option value="<?= $bt ?>"><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
            <label for="blood_type">Blood Type *</label>
            <div class="field-error" id="err-blood"></div>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <input type="tel" id="phone" name="phone" placeholder=" ">
            <label for="phone">Phone Number</label>
          </div>
          <div class="field">
            <input type="email" id="email" name="email" placeholder=" ">
            <label for="email">Email Address</label>
            <div class="field-error" id="err-email"></div>
          </div>
        </div>

        <div class="field">
          <input type="date" id="last_donation_date" name="last_donation_date" max="<?= date('Y-m-d') ?>" placeholder=" ">
          <label for="last_donation_date">Last Donation Date (if any)</label>
          <div class="input-help">
            Leave blank if this is their first donation.
          </div>
        </div>

        <!-- Eligibility Rules Box -->
        <div class="alert alert-info mb-16">
          <span class="alert-icon">ℹ️</span>
          <div>
            <strong>Eligibility Criteria:</strong><br>
            • Age ≥ 18 years &nbsp;•&nbsp; Weight ≥ 50 kg<br>
            • At least 56 days since last donation
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
          ✅ Check Eligibility & Register
        </button>
      </form>

      <!-- Result Box -->
      <div class="result-box" id="result-box">
        <div class="result-icon" id="result-icon"></div>
        <div class="result-title" id="result-title"></div>
        <div class="result-reason" id="result-reason"></div>
        <div id="result-id" style="margin-top:10px;font-size:12px;color:var(--gray-400)"></div>
      </div>
    </div>
  </div>

  <!-- Right Panel: Recently Registered Donors -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">📋 Registered Donors</h2>
      <button class="btn btn-outline btn-sm" onclick="loadDonors()">🔄 Refresh</button>
    </div>
    <div class="table-toolbar">
      <input type="search" id="donor-search" class="search-input" placeholder="Search donor by name, blood type, status...">
      <div class="table-actions">
        <span class="text-sm text-gray" id="donor-count">0 records</span>
      </div>
    </div>
    <div class="table-wrapper" id="donors-panel">
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

<script>
async function submitDonor(e) {
  e.preventDefault();
  const btn    = document.getElementById('submit-btn');
  const form   = document.getElementById('donor-form');
  const result = document.getElementById('result-box');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Checking...';
  result.className = 'result-box';

  const data = {
    name:               form.name.value,
    age:                form.age.value,
    weight:             form.weight.value,
    blood_type:         form.blood_type.value,
    phone:              form.phone.value,
    email:              form.email.value,
    last_donation_date: form.last_donation_date.value,
  };

  document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });
  let invalid = false;
  if (!data.name.trim()) { document.getElementById('err-name').textContent = 'Name is required.'; invalid = true; }
  if (Number(data.age) < 18) { document.getElementById('err-age').textContent = 'Minimum age is 18 years.'; invalid = true; }
  if (Number(data.weight) < 50) { document.getElementById('err-weight').textContent = 'Weight should be at least 50 kg.'; invalid = true; }
  if (data.email && !/^\S+@\S+\.\S+$/.test(data.email)) { document.getElementById('err-email').textContent = 'Enter a valid email.'; invalid = true; }
  if (!data.blood_type) { document.getElementById('err-blood').textContent = 'Please select a blood type.'; invalid = true; }

  if (invalid) {
    btn.disabled = false;
    btn.innerHTML = '✅ Check Eligibility & Register';
    showToast('Please fix highlighted form fields.', 'warning');
    return;
  }

  try {
    const res = await fetchAPI('/DBMS/api/donor.php', 'POST', data);
    const box   = document.getElementById('result-box');
    const icon  = document.getElementById('result-icon');
    const title = document.getElementById('result-title');
    const reason= document.getElementById('result-reason');
    const rid   = document.getElementById('result-id');

    if (res.eligible) {
      box.className   = 'result-box show eligible';
      icon.textContent  = '✅';
      title.textContent = 'ELIGIBLE — REGISTERED!';
      reason.textContent= res.reason;
      rid.textContent   = `Donor ID: #${res.donor_id} · Blood Type: ${res.blood_type}`;
      showToast('Donor registered successfully!', 'success');
      form.reset();
      loadDonors();
    } else {
      box.className   = 'result-box show rejected';
      icon.textContent  = '❌';
      title.textContent = 'REJECTED — NOT ELIGIBLE';
      reason.textContent= res.reason;
      rid.textContent   = '';
      showToast('Donor does not meet eligibility criteria.', 'warning');
    }
  } catch(err) {
    showToast('Error submitting form. Check server connection.', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '✅ Check Eligibility & Register';
}

const STATUS_COLOR = {ELIGIBLE:'badge-green',DONATED:'badge-blue',COOLING_PERIOD:'badge-yellow'};

async function loadDonors() {
  const panel = document.getElementById('donors-panel');
  showLoadingSkeleton(panel, 'table');
  try {
    const res = await fetchAPI('/DBMS/api/donor.php');
    if (!res.donors || res.donors.length === 0) {
      panel.innerHTML = '<div class="empty-state"><div class="empty-icon">👤</div><h3>No donors registered yet</h3></div>';
      return;
    }
    panel.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Blood</th>
            <th>Age</th><th>Weight</th><th>Status</th><th>Last Donated</th>
          </tr>
        </thead>
        <tbody>
          ${res.donors.map(d => `
            <tr>
              <td class="text-gray text-sm">${d.donor_id}</td>
              <td><strong>${d.name}</strong><div class="text-sm text-gray">${d.email||'—'}</div></td>
              <td><span class="badge badge-red">${d.blood_type}</span></td>
              <td>${d.age}</td>
              <td>${d.weight} kg</td>
              <td>${badge(d.status)}</td>
              <td class="text-sm">${formatDate(d.last_donation_date)}</td>
            </tr>`).join('')}
        </tbody>
      </table>`;

    const rows = panel.querySelectorAll('tbody tr');
    document.getElementById('donor-count').textContent = `${rows.length} records`;

    const search = document.getElementById('donor-search');
    if (search) {
      search.oninput = () => {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach((row) => {
          const match = row.textContent.toLowerCase().includes(q);
          row.style.display = match ? '' : 'none';
          if (match) visible += 1;
        });
        document.getElementById('donor-count').textContent = `${visible} records`;
      };
    }
  } catch(e) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Failed to load donors</h3></div>';
  }
}

loadDonors();
</script>

<?php require_once 'includes/footer.php'; ?>
