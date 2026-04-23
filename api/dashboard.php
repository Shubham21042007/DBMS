<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo = getDB();

// Auto-expire blood units past expiry date
$pdo->exec("UPDATE BloodUnit SET status='EXPIRED' WHERE expiry_date < CURDATE() AND status NOT IN ('USED','EXPIRED')");

// ---- Blood Inventory by Type ----
$inventory = [];
$bloodTypes = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
foreach ($bloodTypes as $bt) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM BloodUnit WHERE blood_type=? AND status='AVAILABLE'");
    $stmt->execute([$bt]);
    $inventory[$bt] = (int)$stmt->fetchColumn();
}

// ---- Recent Activity (last 10 blood unit logs) ----
$activityStmt = $pdo->query("
    SELECT bu.unit_id, bu.blood_type, bu.collection_date, bu.status,
           d.name as donor_name
    FROM BloodUnit bu
    JOIN Donor d ON bu.donor_id = d.donor_id
    ORDER BY bu.created_at DESC
    LIMIT 10
");
$recentActivity = $activityStmt->fetchAll();

// ---- Active Emergency Requests ----
$reqStmt = $pdo->query("SELECT COUNT(*) as cnt FROM Request WHERE status IN ('PENDING','MATCHED')");
$activeRequests = (int)$reqStmt->fetchColumn();

// ---- Active Demand Events ----
$evStmt = $pdo->query("SELECT COUNT(*) as cnt FROM BloodDemandEvent WHERE status='ACTIVE'");
$activeEvents = (int)$evStmt->fetchColumn();

// ---- Total Donors ----
$donorCount = (int)$pdo->query("SELECT COUNT(*) FROM Donor")->fetchColumn();

// ---- Total Units ----
$unitCount   = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit WHERE status='AVAILABLE'")->fetchColumn();

// ---- This Month Donations ----
$monthDonations = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit WHERE MONTH(collection_date)=MONTH(CURDATE()) AND YEAR(collection_date)=YEAR(CURDATE())")->fetchColumn();

// ---- Critical Blood Types (< 5 units) ----
$critical = [];
foreach ($inventory as $bt => $cnt) {
    if ($cnt < 5) $critical[] = ['blood_type' => $bt, 'units' => $cnt];
}

// ---- Active Events Detail ----
$eventsDetail = $pdo->query("SELECT * FROM BloodDemandEvent WHERE status='ACTIVE' ORDER BY created_at DESC LIMIT 5")->fetchAll();

echo json_encode([
    'inventory'        => $inventory,
    'recent_activity'  => $recentActivity,
    'active_requests'  => $activeRequests,
    'active_events'    => $activeEvents,
    'donor_count'      => $donorCount,
    'available_units'  => $unitCount,
    'month_donations'  => $monthDonations,
    'critical_types'   => $critical,
    'events_detail'    => $eventsDetail,
]);
