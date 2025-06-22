<?php
require_once '../config.php';
session_start();

// Check admin authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Verify user is admin
try {
    $stmt = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['user_type'] !== 'admin') {
        header("Location: ../index.php");
        exit;
    }
} catch(PDOException $e) {
    die("Error checking user permissions: " . $e->getMessage());
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessId = intval($_POST['business_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if ($businessId <= 0 || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error'] = "Invalid request";
        header("Location: approve_businesses.php");
        exit;
    }

    if ($action === 'reject' && empty($rejectionReason)) {
        $_SESSION['error'] = "Please provide a reason for rejection";
        header("Location: approve_businesses.php?id=$businessId");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $adminId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("UPDATE businesses SET status = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE business_id = ?");
        $stmt->execute([$status, $adminId, $action === 'reject' ? $rejectionReason : null, $businessId]);

        // Get business owner info
        $stmt = $pdo->prepare("SELECT u.user_id, u.email, b.name FROM businesses b 
                              JOIN users u ON b.owner_id = u.user_id 
                              WHERE b.business_id = ?");
        $stmt->execute([$businessId]);
        $business = $stmt->fetch();

        if ($business) {
            // Send notification to business owner
            $message = $action === 'approve' 
                ? "Your business '{$business['name']}' has been approved!" 
                : "Your business registration has been rejected. Reason: $rejectionReason";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $stmt->execute([$business['user_id'], "Business Registration Update", $message]);

            // Send email notification (implementation depends on your email setup)
            // $subject = "Business Registration Update";
            // $body = $message;
            // mail($business['email'], $subject, $body);
        }

        $pdo->commit();
        $_SESSION['success'] = "Business {$status} successfully";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }

    header("Location: approve_businesses.php");
    exit;
}

// Get pending businesses
$businesses = [];
try {
    $stmt = $pdo->query("SELECT b.business_id, b.name, b.description, b.created_at, 
                         bt.name AS business_type, u.username AS owner_username
                         FROM businesses b
                         JOIN business_types bt ON b.business_type_id = bt.type_id
                         JOIN users u ON b.owner_id = u.user_id
                         WHERE b.status = 'pending'
                         ORDER BY b.created_at ASC");
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching businesses: " . $e->getMessage());
}

// If viewing a specific business
$businessDetails = null;
$businessDocuments = null;
if (isset($_GET['id'])) {
    $businessId = intval($_GET['id']);
    
    try {
        // Get business details
        $stmt = $pdo->prepare("SELECT b.*, bt.name AS business_type, 
                              u.username, u.email, up.first_name, up.last_name, up.phone,
                              ba.address_line1, ba.address_line2, ba.city, ba.state, ba.postal_code, ba.country
                              FROM businesses b
                              JOIN business_types bt ON b.business_type_id = bt.type_id
                              JOIN users u ON b.owner_id = u.user_id
                              JOIN user_profiles up ON u.user_id = up.user_id
                              JOIN business_addresses ba ON b.business_id = ba.business_id AND ba.is_primary = 1
                              WHERE b.business_id = ?");
        $stmt->execute([$businessId]);
        $businessDetails = $stmt->fetch();

        // Get business documents
        $stmt = $pdo->prepare("SELECT * FROM business_documents WHERE business_id = ?");
        $stmt->execute([$businessId]);
        $businessDocuments = $stmt->fetchAll();
    } catch(PDOException $e) {
        die("Error fetching business details: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Businesses | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-<?= $businessDetails ? '8' : '12' ?>">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h4">Pending Business Registrations</h2>
                        <span class="badge bg-primary"><?= count($businesses) ?></span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($businesses)): ?>
                            <div class="alert alert-info">No pending business registrations.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Business Name</th>
                                            <th>Type</th>
                                            <th>Owner</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($businesses as $business): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($business['name']) ?></td>
                                                <td><?= htmlspecialchars($business['business_type']) ?></td>
                                                <td><?= htmlspecialchars($business['owner_username']) ?></td>
                                                <td><?= date('M j, Y', strtotime($business['created_at'])) ?></td>
                                                <td>
                                                    <a href="approve_businesses.php?id=<?= $business['business_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Review
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($businessDetails): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5">Review Business</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="h6">Business Information</h4>
                        <p><strong>Name:</strong> <?= htmlspecialchars($businessDetails['name']) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($businessDetails['business_type']) ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($businessDetails['description']) ?></p>
                        <p><strong>Registered:</strong> <?= date('M j, Y', strtotime($businessDetails['created_at'])) ?></p>
                        
                        <h4 class="h6 mt-4">Owner Information</h4>
                        <p><strong>Name:</strong> <?= htmlspecialchars($businessDetails['first_name'] . ' ' . $businessDetails['last_name']) ?></p>
                        <p><strong>Username:</strong> <?= htmlspecialchars($businessDetails['username']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($businessDetails['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($businessDetails['phone']) ?></p>
                        
                        <h4 class="h6 mt-4">Business Address</h4>
                        <address>
                            <?= htmlspecialchars($businessDetails['address_line1']) ?><br>
                            <?php if ($businessDetails['address_line2']): ?>
                                <?= htmlspecialchars($businessDetails['address_line2']) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($businessDetails['city']) ?>, <?= htmlspecialchars($businessDetails['state']) ?> <?= htmlspecialchars($businessDetails['postal_code']) ?><br>
                            <?= htmlspecialchars($businessDetails['country']) ?>
                        </address>
                        
                        <?php if (!empty($businessDocuments)): ?>
                        <h4 class="h6 mt-4">Submitted Documents</h4>
                        <div class="list-group">
                            <?php foreach ($businessDocuments as $doc): ?>
                                <a href="../<?= htmlspecialchars($doc['document_url']) ?>" target="_blank" class="list-group-item list-group-item-action">
                                    <i class="bi bi-file-earmark"></i> 
                                    <?= ucfirst($doc['document_type']) ?> 
                                    <small class="text-muted">(<?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>)</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <form method="POST">
                            <input type="hidden" name="business_id" value="<?= $businessDetails['business_id'] ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                
                                <div class="mt-3">
                                    <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" name="action" value="reject" class="btn btn-danger mt-2">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>