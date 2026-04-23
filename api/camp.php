<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $camps   = $pdo->query("
        SELECT dc.*, doc.name as doctor_name,
               (SELECT COUNT(*) FROM CampDonorRegistration cdr WHERE cdr.camp_id=dc.camp_id) as registrations
        FROM DonationCamp dc
        LEFT JOIN Doctor doc ON dc.doctor_id = doc.doctor_id
        ORDER BY dc.camp_date DESC
    ")->fetchAll();
    $doctors = $pdo->query("SELECT doctor_id, name, specialization FROM Doctor ORDER BY name")->fetchAll();
    $donors  = $pdo->query("SELECT donor_id, name, blood_type FROM Donor WHERE status='ELIGIBLE' ORDER BY name")->fetchAll();
    echo json_encode(['camps' => $camps, 'doctors' => $doctors, 'donors' => $donors]);
    exit;
}

if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'create_camp';

    if ($action === 'create_camp') {
        $name     = trim($data['name'] ?? '');
        $location = trim($data['location'] ?? '');
        $date     = trim($data['camp_date'] ?? '');
        $capacity = (int)($data['capacity'] ?? 50);
        $doctorId = !empty($data['doctor_id']) ? (int)$data['doctor_id'] : null;

        if (!$name || !$location || !$date) {
            echo json_encode(['success'=>false,'message'=>'Name, location and date are required.']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO DonationCamp (name, location, camp_date, capacity, doctor_id, status)
            VALUES (?, ?, ?, ?, ?, 'UPCOMING')
        ");
        $stmt->execute([$name, $location, $date, $capacity, $doctorId]);
        echo json_encode(['success'=>true,'camp_id'=>$pdo->lastInsertId(),'message'=>"Donation camp '{$name}' created successfully."]);
        exit;
    }

    if ($action === 'register_donor') {
        $campId  = (int)($data['camp_id'] ?? 0);
        $donorId = (int)($data['donor_id'] ?? 0);
        if (!$campId || !$donorId) { echo json_encode(['success'=>false,'message'=>'Camp and donor required.']); exit; }

        // Check camp capacity
        $camp = $pdo->prepare("SELECT capacity FROM DonationCamp WHERE camp_id=?");
        $camp->execute([$campId]);
        $campRow  = $camp->fetch();
        $regCount = (int)$pdo->prepare("SELECT COUNT(*) FROM CampDonorRegistration WHERE camp_id=?")->execute([$campId]);
        $regStmt  = $pdo->prepare("SELECT COUNT(*) FROM CampDonorRegistration WHERE camp_id=?");
        $regStmt->execute([$campId]);
        $regCount = (int)$regStmt->fetchColumn();

        if ($regCount >= $campRow['capacity']) {
            echo json_encode(['success'=>false,'message'=>'Camp is at full capacity.']);
            exit;
        }

        // Check duplicate
        $dup = $pdo->prepare("SELECT reg_id FROM CampDonorRegistration WHERE camp_id=? AND donor_id=?");
        $dup->execute([$campId, $donorId]);
        if ($dup->fetch()) {
            echo json_encode(['success'=>false,'message'=>'Donor already registered for this camp.']);
            exit;
        }

        $pdo->prepare("INSERT INTO CampDonorRegistration (camp_id, donor_id) VALUES (?,?)")
            ->execute([$campId, $donorId]);
        echo json_encode(['success'=>true,'message'=>'Donor registered for the camp successfully.']);
        exit;
    }
}

echo json_encode(['error' => 'Method not allowed']);
