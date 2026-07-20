<?php
// admin/parks.php - Parks & Animals Management WITH IMAGE UPLOAD
require_once '../db.php';

// Security: Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';

// Allowed image types
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Delete park
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get image path to delete file
        $imgStmt = $pdo->prepare("SELECT image FROM parks WHERE id = ?");
        $imgStmt->execute([$id]);
        $oldImage = $imgStmt->fetchColumn();
        
        if ($oldImage && file_exists('../images/' . $oldImage)) {
            unlink('../images/' . $oldImage);
        }
        
        $stmt = $pdo->prepare("DELETE FROM parks WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Park deleted successfully!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Cannot delete: Park may have existing bookings!';
        $messageType = 'danger';
    }
}

// Add/Edit park
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parkId = $_POST['park_id'] ?? '';
    $parkName = trim($_POST['park_name'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $entryFee = (float)($_POST['entry_fee'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $imageName = '';

    // Handle image upload
    if (!empty($_FILES['park_image']['name'])) {
        $file = $_FILES['park_image'];
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Error uploading image!';
            $messageType = 'danger';
        } elseif (!in_array($file['type'], $allowedTypes)) {
            $message = 'Only JPG, PNG, or WEBP images allowed!';
            $messageType = 'danger';
        } elseif ($file['size'] > $maxSize) {
            $message = 'Image must be smaller than 2MB!';
            $messageType = 'danger';
        } else {
            // Create unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $imageName = 'park_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $uploadPath = '../images/' . $imageName;
            
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $message = 'Failed to save image!';
                $messageType = 'danger';
                $imageName = '';
            }
        }
    }

    if ($messageType === 'danger') {
        // Error already set, do nothing
    } elseif (empty($parkName) || empty($province)) {
        $message = 'Park name and province are required!';
        $messageType = 'danger';
    } else {
        try {
            if (!empty($parkId)) {
                // UPDATE - keep old image if no new upload
                if (empty($imageName)) {
                    $stmt = $pdo->prepare("SELECT image FROM parks WHERE id = ?");
                    $stmt->execute([$parkId]);
                    $imageName = $stmt->fetchColumn() ?? '';
                } else {
                    // Delete old image
                    $oldStmt = $pdo->prepare("SELECT image FROM parks WHERE id = ?");
                    $oldStmt->execute([$parkId]);
                    $oldImage = $oldStmt->fetchColumn();
                    if ($oldImage && file_exists('../images/' . $oldImage)) {
                        unlink('../images/' . $oldImage);
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE parks SET park_name = ?, province = ?, description = ?, entry_fee = ?, image = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$parkName, $province, $description, $entryFee, $imageName, $status, $parkId]);
                $message = 'Park updated successfully!';
                $messageType = 'success';
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO parks (park_name, province, description, entry_fee, image, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$parkName, $province, $description, $entryFee, $imageName, $status]);
                $message = 'Park added successfully!';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error saving park!';
            $messageType = 'danger';
        }
    }
}

// Get park for edit
$editPark = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM parks WHERE id = ?");
    $stmt->execute([$editId]);
    $editPark = $stmt->fetch();
}

// Get all parks
try {
    $parks = $pdo->query("SELECT * FROM parks ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $parks = [];
}

// Province list
$provinces = [
    'Central Province', 'Eastern Province', 'North Central Province',
    'Northern Province', 'North Western Province', 'Sabaragamuwa Province',
    'Southern Province', 'Uva Province', 'Western Province'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parks - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --primary-green: #1a5f2a; --accent-gold: #d4a017; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: var(--sidebar-bg); min-height: 100vh; position: fixed; left: 0; top: 0; width: 260px; padding-top: 20px; z-index: 1000; }
        .sidebar-brand { color: white; font-size: 22px; font-weight: bold; text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .nav-link { color: rgba(255,255,255,0.7) !important; padding: 12px 25px; margin: 2px 10px; border-radius: 8px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--sidebar-hover); color: white !important; }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { margin-left: 260px; padding: 20px; }
        .topbar { background: white; border-radius: 12px; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 24px; font-weight: bold; color: #333; margin: 0; }
        .user-badge { background: var(--primary-green); color: white; padding: 8px 20px; border-radius: 25px; font-weight: 500; }
        .form-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .form-card h4 { color: var(--primary-green); margin-bottom: 25px; font-weight: bold; }
        .form-label { font-weight: 600; color: #555; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.25); }
        .btn-submit { background: var(--primary-green); color: white; padding: 10px 30px; border: none; border-radius: 8px; font-weight: bold; }
        .btn-submit:hover { background: var(--sidebar-hover); }
        .btn-cancel { background: #6c757d; color: white; padding: 10px 30px; border: none; border-radius: 8px; font-weight: bold; margin-left: 10px; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { background: var(--primary-green); color: white; padding: 18px 25px; font-size: 18px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .table { margin-bottom: 0; }
        .table thead th { background: #f8f9fa; border: none; color: #666; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        .table tbody td { padding: 15px; vertical-align: middle; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .btn-action { padding: 5px 12px; border-radius: 6px; font-size: 13px; margin: 2px; }
        .park-img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }
        .park-img-placeholder { width: 80px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 24px; }
        .image-preview { max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 10px; }
        .footer { text-align: center; color: #888; padding: 30px; font-size: 13px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="bi bi-shield-lock"></i> Admin Panel</div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="parks.php"><i class="bi bi-tree"></i> Parks & Animals</a></li>
            <li class="nav-item"><a class="nav-link" href="bookings.php"><i class="bi bi-calendar-check"></i> Bookings</a></li>
            <li class="nav-item"><a class="nav-link" href="vehicles.php"><i class="bi bi-truck"></i> Vehicles</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-globe"></i> View Website</a></li>
            <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-tree"></i> Parks & Animals</h1>
            <div class="user-badge"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?></div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h4><i class="bi bi-<?php echo $editPark ? 'pencil-square' : 'plus-circle'; ?>"></i> 
                <?php echo $editPark ? 'Edit Park' : 'Add New Park'; ?>
            </h4>
            <form method="POST" action="parks.php" enctype="multipart/form-data">
                <?php if ($editPark): ?>
                    <input type="hidden" name="park_id" value="<?php echo $editPark['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Park Name *</label>
                        <input type="text" class="form-control" name="park_name" 
                               value="<?php echo $editPark ? htmlspecialchars($editPark['park_name']) : ''; ?>" 
                               placeholder="e.g., Yala National Park" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Province *</label>
                        <select class="form-select" name="province" required>
                            <option value="">-- Select Province --</option>
                            <?php foreach ($provinces as $prov): ?>
                            <option value="<?php echo $prov; ?>" <?php echo ($editPark && $editPark['province'] == $prov) ? 'selected' : ''; ?>><?php echo $prov; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Describe the park..."><?php echo $editPark ? htmlspecialchars($editPark['description']) : ''; ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Entry Fee (Rs.) *</label>
                        <input type="number" class="form-control" name="entry_fee" step="0.01" min="0"
                               value="<?php echo $editPark ? $editPark['entry_fee'] : '0.00'; ?>" required>
                        <small class="text-muted">Price per person per day</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Park Image</label>
                        <input type="file" class="form-control" name="park_image" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">Max 2MB. JPG, PNG, or WEBP.</small>
                        <?php if ($editPark && !empty($editPark['image'])): ?>
                        <div class="mt-2">
                            <img src="../images/<?php echo htmlspecialchars($editPark['image']); ?>" class="image-preview" alt="Current image">
                            <p class="small text-muted mb-0">Current image</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo ($editPark && $editPark['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editPark && $editPark['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn-submit"><i class="bi bi-<?php echo $editPark ? 'check-circle' : 'plus-lg'; ?>"></i> <?php echo $editPark ? 'Update Park' : 'Add Park'; ?></button>
                    <?php if ($editPark): ?><a href="parks.php" class="btn-cancel">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Parks Table -->
        <div class="table-card">
            <div class="table-header">
                <span><i class="bi bi-list"></i> All Parks</span>
                <span class="badge bg-white text-dark"><?php echo count($parks); ?> Total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Park Name</th>
                            <th>Province</th>
                            <th>Entry Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parks)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-tree" style="font-size: 48px;"></i><p class="mt-3">No parks added yet.</p></td></tr>
                        <?php else: ?>
                        <?php foreach ($parks as $park): ?>
                        <tr>
                            <td>
                                <?php if (!empty($park['image']) && file_exists('../images/' . $park['image'])): ?>
                                <img src="../images/<?php echo htmlspecialchars($park['image']); ?>" class="park-img" alt="<?php echo htmlspecialchars($park['park_name']); ?>">
                                <?php else: ?>
                                <div class="park-img-placeholder"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($park['park_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($park['province']); ?></td>
                            <td class="fw-bold text-success">Rs. <?php echo number_format($park['entry_fee'], 2); ?></td>
                            <td><span class="status-badge status-<?php echo $park['status']; ?>"><?php echo ucfirst($park['status']); ?></span></td>
                            <td>
                                <a href="parks.php?edit=<?php echo $park['id']; ?>" class="btn btn-sm btn-primary btn-action"><i class="bi bi-pencil"></i> Edit</a>
                                <a href="parks.php?delete=<?php echo $park['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this park?');"><i class="bi bi-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer"><i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Admin Panel &copy; 2026</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>