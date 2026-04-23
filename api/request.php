<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $requests = $pdo->query("
        SELECT r.*, h.name as hospital_name
        FROM Request r
        JOIN Hospital h ON r.hospital_id = h.hospital_id
        ORDER BY r.request_date DESC
    ")->fetchAll();
    $hospitals = $pdo->query("SELECT hospital_id, name FROM Hospital ORDER BY name")->fetchAll();
    echo json_encode(['requests' => $requests, 'hospitals' => $hospitals]);
    exit;
}

if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $hospitalId = (int)($data['hospital_id'] ?? 0);
    $bloodType  = trim($data['blood_type'] ?? '');
    $units      = (int)($data['units_needed'] ?? 1);
    $urgency    = trim($data['urgency'] ?? 'NORMAL');
    $notes      = trim($data['notes'] ?? '');

    if (!$hospitalId || !$bloodType || $units < 1) {
        echo json_encode(['success'=>false,'message'=>'Hospital, blood type and units are required.']);
        exit;
    }

    // Search for available matching units
    $stmt = $pdo->prepare("
        SELECT unit_id FROM BloodUnit
        WHERE blood_type=? AND status='AVAILABLE' AND expiry_date >= CURDATE()
        ORDER BY expiry_date ASC
        LIMIT ?
    ");
    $stmt->execute([$bloodType, $units]);
    $matched = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $status = (count($matched) >= $units) ? 'MATCHED' : 'PENDING';

    // Insert request
    $ins = $pdo->prepare("
        INSERT INTO Request (hospital_id, blood_type, units_needed, urgency, status, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([$hospitalId, $bloodType, $units, $urgency, $status, $notes]);
    $requestId = $pdo->lastInsertId();

    if ($status === 'MATCHED') {
        // Reserve the matched units
        foreach (array_slice($matched, 0, $units) as $uid) {
            $pdo->prepare("UPDATE BloodUnit SET status='RESERVED' WHERE unit_id=?")
                ->execute([$uid]);
            $pdo->prepare("INSERT INTO RequestBloodUnit (request_id, unit_id) VALUES (?,?)")
                ->execute([$requestId, $uid]);
        }
    }

    echo json_encode([
        'success'      => true,
        'status'       => $status,
        'request_id'   => $requestId,
        'matched_units'=> count($matched),
        'units_needed' => $units,
        'message'      => $status === 'MATCHED'
            ? "✅ Request MATCHED! {$units} unit(s) of {$bloodType} blood reserved for the hospital."
            : "⚠️ NOT AVAILABLE. Only " . count($matched) . " unit(s) available. Request logged as PENDING.",
    ]);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
