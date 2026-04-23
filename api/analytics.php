<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo = getDB();

// ---- Total donations this month ----
$monthDonations = (int)$pdo->query("
    SELECT COUNT(*) FROM BloodUnit
    WHERE MONTH(collection_date)=MONTH(CURDATE())
      AND YEAR(collection_date)=YEAR(CURDATE())
")->fetchColumn();

// ---- Most donated blood type ----
$topBlood = $pdo->query("
    SELECT blood_type, COUNT(*) as cnt FROM BloodUnit
    GROUP BY blood_type ORDER BY cnt DESC LIMIT 1
")->fetch();

// ---- Hospitals served (fulfilled requests) ----
$hospitalsServed = (int)$pdo->query("
    SELECT COUNT(DISTINCT hospital_id) FROM Request WHERE status='FULFILLED'
")->fetchColumn();

// ---- Expiry waste percentage ----
$totalUnits = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit")->fetchColumn();
$wasteCount = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit WHERE status='EXPIRED'")->fetchColumn();
$wastePercent = $totalUnits > 0 ? round(($wasteCount/$totalUnits)*100,1) : 0;

// ---- Donations by blood type (for chart) ----
$byType = $pdo->query("
    SELECT blood_type, COUNT(*) as total FROM BloodUnit
    GROUP BY blood_type ORDER BY blood_type
")->fetchAll();

// ---- Monthly donations (last 6 months) ----
$monthly = $pdo->query("
    SELECT DATE_FORMAT(collection_date,'%b %Y') as month_label,
           DATE_FORMAT(collection_date,'%Y-%m') as ym,
           COUNT(*) as total
    FROM BloodUnit
    WHERE collection_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym ASC
")->fetchAll();

// ---- Historical shortage by month (most requests filed) ----
$shortagePeak = $pdo->query("
    SELECT DATE_FORMAT(request_date,'%b %Y') as month_label,
           DATE_FORMAT(request_date,'%Y-%m') as ym,
           COUNT(*) as requests
    FROM Request
    WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym ORDER BY requests DESC
    LIMIT 1
")->fetch();

// ---- Request status breakdown ----
$requestStatus = $pdo->query("
    SELECT status, COUNT(*) as cnt FROM Request GROUP BY status
")->fetchAll();

// ---- Total donors ----
$totalDonors = (int)$pdo->query("SELECT COUNT(*) FROM Donor")->fetchColumn();

// ---- Donor status breakdown ----
$donorStatus = $pdo->query("
    SELECT status, COUNT(*) as cnt FROM Donor GROUP BY status
")->fetchAll();

// ---- Available by blood type ----
$availByType = $pdo->query("
    SELECT blood_type, COUNT(*) as available FROM BloodUnit
    WHERE status='AVAILABLE'
    GROUP BY blood_type ORDER BY blood_type
")->fetchAll();

echo json_encode([
    'month_donations'  => $monthDonations,
    'top_blood'        => $topBlood,
    'hospitals_served' => $hospitalsServed,
    'waste_percent'    => $wastePercent,
    'waste_count'      => $wasteCount,
    'total_units'      => $totalUnits,
    'by_type'          => $byType,
    'monthly'          => $monthly,
    'shortage_peak'    => $shortagePeak,
    'request_status'   => $requestStatus,
    'total_donors'     => $totalDonors,
    'donor_status'     => $donorStatus,
    'avail_by_type'    => $availByType,
]);
