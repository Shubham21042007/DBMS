<?php
require_once '../db.php';
header('Content-Type: application/json');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $events = $pdo->query("
        SELECT bde.*, 
               (SELECT COUNT(*) FROM BloodDemand bd WHERE bd.event_id=bde.event_id) as demands_count
        FROM BloodDemandEvent bde ORDER BY bde.created_at DESC
    ")->fetchAll();

    // For each event, get demands with current stock
    foreach ($events as &$ev) {
        $dStmt = $pdo->prepare("SELECT * FROM BloodDemand WHERE event_id=?");
        $dStmt->execute([$ev['event_id']]);
        $demands = $dStmt->fetchAll();
        foreach ($demands as &$d) {
            $avail = $pdo->prepare("SELECT COUNT(*) FROM BloodUnit WHERE blood_type=? AND status='AVAILABLE' AND expiry_date>=CURDATE()");
            $avail->execute([$d['blood_type']]);
            $d['current_stock'] = (int)$avail->fetchColumn();
            $d['shortage']      = max(0, $d['units_required'] - $d['current_stock']);
        }
        $ev['demands'] = $demands;
    }
    echo json_encode(['events' => $events]);
    exit;
}

if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'create_event';

    if ($action === 'create_event') {
        $title    = trim($data['title'] ?? '');
        $desc     = trim($data['description'] ?? '');
        $type     = trim($data['event_type'] ?? 'SHORTAGE');
        $location = trim($data['location'] ?? '');
        $date     = trim($data['event_date'] ?? date('Y-m-d'));
        $demands  = $data['demands'] ?? [];   // [{blood_type, units_required, urgency_level}]

        if (!$title) { echo json_encode(['success'=>false,'message'=>'Title is required.']); exit; }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("
                INSERT INTO BloodDemandEvent (title, description, event_type, location, event_date, status)
                VALUES (?, ?, ?, ?, ?, 'ACTIVE')
            ");
            $ins->execute([$title, $desc, $type, $location, $date]);
            $eventId = $pdo->lastInsertId();

            $stockAlerts = [];
            foreach ($demands as $d) {
                $bt  = $d['blood_type'];
                $req = (int)$d['units_required'];
                $urg = $d['urgency_level'] ?? 'HIGH';

                // Current available stock
                $avail = $pdo->prepare("SELECT COUNT(*) FROM BloodUnit WHERE blood_type=? AND status='AVAILABLE' AND expiry_date>=CURDATE()");
                $avail->execute([$bt]);
                $available = (int)$avail->fetchColumn();

                $pdo->prepare("
                    INSERT INTO BloodDemand (event_id, blood_type, units_required, units_available, urgency_level)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$eventId, $bt, $req, $available, $urg]);

                if ($available < 5) {
                    $stockAlerts[] = ['blood_type'=>$bt,'available'=>$available,'required'=>$req,'shortfall'=>($req-$available)];
                }
            }

            // Flag eligible donors of the demanded blood types
            $bloodTypes = array_column($demands, 'blood_type');
            $flagged = [];
            if (!empty($bloodTypes)) {
                $placeholders = implode(',', array_fill(0, count($bloodTypes), '?'));
                $donorStmt = $pdo->prepare("
                    SELECT donor_id, name, blood_type, phone, email FROM Donor
                    WHERE blood_type IN ($placeholders) AND status='ELIGIBLE'
                ");
                $donorStmt->execute($bloodTypes);
                $flagged = $donorStmt->fetchAll();
            }

            $pdo->commit();
            echo json_encode([
                'success'       => true,
                'event_id'      => $eventId,
                'stock_alerts'  => $stockAlerts,
                'flagged_donors'=> $flagged,
                'urgent_appeal' => !empty($stockAlerts),
                'message'       => "Blood demand event created. " . count($flagged) . " eligible donor(s) flagged."
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'resolve_event') {
        $eventId = (int)($data['event_id'] ?? 0);
        $pdo->prepare("UPDATE BloodDemandEvent SET status='RESOLVED' WHERE event_id=?")->execute([$eventId]);
        echo json_encode(['success'=>true,'message'=>'Event marked as resolved.']);
        exit;
    }
}

echo json_encode(['error' => 'Method not allowed']);
