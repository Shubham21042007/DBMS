<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-expire units past expiry date
$expired = $pdo->exec("UPDATE BloodUnit SET status='EXPIRED' WHERE expiry_date < CURDATE() AND status NOT IN ('USED','EXPIRED')");

if ($method === 'GET') {
    // Units expiring in next 7 days
    $expiring = $pdo->query("
        SELECT bu.*, d.name as donor_name
        FROM BloodUnit bu
        JOIN Donor d ON bu.donor_id = d.donor_id
        WHERE bu.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND bu.status NOT IN ('USED','EXPIRED')
        ORDER BY bu.expiry_date ASC
    ")->fetchAll();

    // All expired units (waste report)
    $expiredUnits = $pdo->query("
        SELECT bu.*, d.name as donor_name
        FROM BloodUnit bu
        JOIN Donor d ON bu.donor_id = d.donor_id
        WHERE bu.status='EXPIRED'
        ORDER BY bu.expiry_date DESC
    ")->fetchAll();

    // Waste stats
    $totalUnits   = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit")->fetchColumn();
    $wasteCount   = (int)$pdo->query("SELECT COUNT(*) FROM BloodUnit WHERE status='EXPIRED'")->fetchColumn();
    $wastePercent = $totalUnits > 0 ? round(($wasteCount / $totalUnits) * 100, 1) : 0;

    // Waste by blood type
    $wasteByType = $pdo->query("
        SELECT blood_type, COUNT(*) as expired_count
        FROM BloodUnit WHERE status='EXPIRED'
        GROUP BY blood_type ORDER BY expired_count DESC
    ")->fetchAll();

    echo json_encode([
        'expiring_soon'    => $expiring,
        'expired_units'    => $expiredUnits,
        'auto_expired'     => $expired,
        'total_units'      => $totalUnits,
        'waste_count'      => $wasteCount,
        'waste_percent'    => $wastePercent,
        'waste_by_type'    => $wasteByType,
    ]);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
