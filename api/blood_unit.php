<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $units = $pdo->query("
        SELECT bu.*, d.name as donor_name, d.blood_type as donor_blood
        FROM BloodUnit bu
        JOIN Donor d ON bu.donor_id = d.donor_id
        ORDER BY bu.created_at DESC
    ")->fetchAll();
    echo json_encode(['units' => $units]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'log';

    if ($action === 'log') {
        $donorId       = (int)($data['donor_id'] ?? 0);
        $bloodType     = trim($data['blood_type'] ?? '');
        $collectionDate= trim($data['collection_date'] ?? date('Y-m-d'));

        if (!$donorId) { echo json_encode(['success'=>false,'message'=>'Donor ID required.']); exit; }

        // Auto-calculate expiry: collection_date + 35 days
        $expiryDate = date('Y-m-d', strtotime($collectionDate . ' +35 days'));

        $stmt = $pdo->prepare("
            INSERT INTO BloodUnit (donor_id, blood_type, collection_date, expiry_date, status)
            VALUES (?, ?, ?, ?, 'COLLECTED')
        ");
        $stmt->execute([$donorId, $bloodType, $collectionDate, $expiryDate]);
        $unitId = $pdo->lastInsertId();

        // Update donor status to DONATED
        $pdo->prepare("UPDATE Donor SET status='DONATED', last_donation_date=? WHERE donor_id=?")
            ->execute([$collectionDate, $donorId]);

        echo json_encode([
            'success'        => true,
            'unit_id'        => $unitId,
            'expiry_date'    => $expiryDate,
            'message'        => "Blood unit logged. Expiry date set to $expiryDate (35 days from collection)."
        ]);
        exit;
    }

    if ($action === 'update_status') {
        $unitId    = (int)($data['unit_id'] ?? 0);
        $newStatus = trim($data['status'] ?? '');
        $valid     = ['COLLECTED','TESTED','AVAILABLE','RESERVED','USED','EXPIRED'];
        if (!in_array($newStatus, $valid)) { echo json_encode(['success'=>false,'message'=>'Invalid status.']); exit; }

        $pdo->prepare("UPDATE BloodUnit SET status=? WHERE unit_id=?")
            ->execute([$newStatus, $unitId]);
        echo json_encode(['success'=>true,'message'=>"Unit $unitId status updated to $newStatus."]);
        exit;
    }
}

echo json_encode(['error' => 'Method not allowed']);
