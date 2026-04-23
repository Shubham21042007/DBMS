<?php
// Shared sidebar include — used on every page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Blood Bank System') ?> — Smart Blood Bank</title>
  <meta name="description" content="Smart Blood Bank Management System — managing blood inventory, donors, and hospital requests.">
  <link rel="stylesheet" href="/DBMS/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">🩸</div>
    <div class="brand-text">
      <h2>Smart Blood Bank</h2>
      <span>Management System</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="/DBMS/dashboard.php" class="nav-item <?= $currentPage==='dashboard.php'?'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
      <span class="nav-badge" id="nav-critical-count" style="display:none"></span>
    </a>

    <div class="nav-section-label">Donors</div>
    <a href="/DBMS/donor_register.php" class="nav-item <?= $currentPage==='donor_register.php'?'active':'' ?>">
      <span class="nav-icon">👤</span> Donor Registration
    </a>
    <a href="/DBMS/blood_unit_logger.php" class="nav-item <?= $currentPage==='blood_unit_logger.php'?'active':'' ?>">
      <span class="nav-icon">🏷️</span> Blood Unit Logger
    </a>
    <a href="/DBMS/donation_camp.php" class="nav-item <?= $currentPage==='donation_camp.php'?'active':'' ?>">
      <span class="nav-icon">🏕️</span> Donation Camps
    </a>

    <div class="nav-section-label">Requests & Events</div>
    <a href="/DBMS/hospital_request.php" class="nav-item <?= $currentPage==='hospital_request.php'?'active':'' ?>">
      <span class="nav-icon">🏥</span> Hospital Requests
    </a>
    <a href="/DBMS/blood_demand_event.php" class="nav-item <?= $currentPage==='blood_demand_event.php'?'active':'' ?>">
      <span class="nav-icon">🚨</span> Demand Events
    </a>

    <div class="nav-section-label">Monitoring</div>
    <a href="/DBMS/expiry_tracker.php" class="nav-item <?= $currentPage==='expiry_tracker.php'?'active':'' ?>">
      <span class="nav-icon">⏳</span> Expiry Tracker
    </a>
    <a href="/DBMS/analytics.php" class="nav-item <?= $currentPage==='analytics.php'?'active':'' ?>">
      <span class="nav-icon">📈</span> Analytics
    </a>
  </nav>

  <div class="sidebar-footer">
    🩸 Smart Blood Bank v1.0<br>
    <span id="live-clock" style="font-size:12px;color:rgba(255,255,255,.4)"></span>
  </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">☰</button>
      <div>
        <div class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
        <div class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Smart Blood Bank Management System') ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <span class="topbar-time" id="live-clock-top"></span>
      <span class="topbar-badge" id="topbar-emergency" style="display:none">Emergency Active</span>
    </div>
  </header>

  <div class="page-body">
