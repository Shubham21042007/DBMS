<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $donors = $pdo->query("SELECT * FROM Donor ORDER BY created_at DESC")->fetchAll();
    echo json_encode(['donors' => $donors]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // ---- Validation ----
    $name     = trim($data['name'] ?? '');
    $age      = (int)($data['age'] ?? 0);
    $weight   = (float)($data['weight'] ?? 0);
    $blood    = trim($data['blood_type'] ?? '');
    $phone    = trim($data['phone'] ?? '');
    $email    = trim($data['email'] ?? '');
    $lastDon  = trim($data['last_donation_date'] ?? '');

    $validTypes = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];

    if (empty($name)) { echo json_encode(['eligible'=>false,'reason'=>'Name is required.']); exit; }
    if (!in_array($blood, $validTypes)) { echo json_encode(['eligible'=>false,'reason'=>'Invalid blood type selected.']); exit; }

    // ---- Eligibility Check ----
    if ($age < 18) {
        echo json_encode(['eligible'=>false,'reason'=>"Donor age is $age years. Minimum age requirement is 18 years."]);
        exit;
    }

    if ($weight < 50) {
        echo json_encode(['eligible'=>false,'reason'=>"Donor weight is {$weight}kg. Minimum weight requirement is 50 kg."]);
        exit;
    }

    if (!empty($lastDon)) {
        $daysSince = (int)((time() - strtotime($lastDon)) / 86400);
        if ($daysSince < 56) {
            $remaining = 56 - $daysSince;
            echo json_encode(['eligible'=>false,'reason'=>"Last donation was only $daysSince days ago. Donor must wait at least 56 days between donations. {$remaining} more days required."]);
            exit;
        }
    }

    // ---- Email duplicate check ----
    if (!empty($email)) {
        $check = $pdo->prepare("SELECT donor_id FROM Donor WHERE email=?");
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            echo json_encode(['eligible'=>false,'reason'=>'A donor with this email already exists in the system.']);
            exit;
        }
    }

    // ---- Insert Donor ----
    $stmt = $pdo->prepare("
        INSERT INTO Donor (name, age, weight, blood_type, phone, email, status, last_donation_date)
        VALUES (?, ?, ?, ?, ?, ?, 'ELIGIBLE', ?)
    ");
    $stmt->execute([$name, $age, $weight, $blood, $phone, $email, $lastDon ?: null]);
    $donorId = $pdo->lastInsertId();

    echo json_encode([
        'eligible' => true,
        'reason'   => 'Donor meets all eligibility criteria and has been registered successfully.',
        'donor_id' => $donorId,
        'name'     => $name,
        'blood_type'=> $blood,
    ]);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
