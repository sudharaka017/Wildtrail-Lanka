<?php
// admin/vehicles.php - Vehicle Management (CRUD)
require_once '../db.php';

// Security check - only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';

// ==================== DELETE VEHICLE ====================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Check if vehicle is not booked
        $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE vehicle_id = ? AND status IN ('pending', 'confirmed')");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $message = 'Cannot delete: Vehicle is currently booked!';
            $messageType = 'warning';
        } else {
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Vehicle deleted successfully!';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting vehicle!';
        $messageType = 'danger';
    }
}

// ==================== ADD / EDIT VEHICLE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = $_POST['vehicle_id'] ?? '';
    $vehicleName = trim($_POST['vehicle_name'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? 'Jeep';
    $plateNumber = trim($_POST['plate_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $driverId = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
    $status = $_POST['status'] ?? 'available';

    // Validation
    if (empty($vehicleName) || empty($plateNumber) || $capacity < 1) {
        $message = 'Please fill all required fields correctly!';
        $messageType = 'danger';
    } else {
        try {
            if (!empty($vehicleId)) {
                // UPDATE existing vehicle
                $stmt = $pdo->prepare("
                    UPDATE vehicles 
                    SET vehicle_name = ?, vehicle_type = ?, plate_number = ?, capacity = ?, driver_id = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$vehicleName, $vehicleType, $plateNumber, $capacity, $driverId, $status, $vehicleId]);
                $message = 'Vehicle updated successfully!';
                $messageType = 'success';
            } else {
                // INSERT new vehicle
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles (vehicle_name, vehicle_type, plate_number, capacity, driver_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$vehicleName, $vehicleType, $plateNumber, $capacity, $driverId, $status]);
                $message = 'Vehicle added successfully!';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Plate number already exists!';
                $messageType = 'warning';
            } else {
                $message = 'Error saving vehicle!';
                $messageType = 'danger';
            }
        }
    }
}

// ==================== GET VEHICLE FOR EDIT ====================
$editVehicle = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$editId]);
    $editVehicle = $stmt->fetch();
}

// ==================== GET ALL VEHICLES ====================
try {
    $vehicles = $pdo->query("
        SELECT v.*, u.full_name as driver_name 
        FROM vehicles v 
        LEFT JOIN users u ON v.driver_id = u.id 
        ORDER BY v.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $vehicles = [];
}

// ==================== GET DRIVERS FOR DROPDOWN ====================
try {
    $drivers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'driver' AND status = 'active' ORDER BY full_name")->fetchAll();
} catch (PDOException $e) {
    $drivers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-bg: #1a1a2e;
            --sidebar-hover: #16213e;
            --primary-green: #1a5f2a;
            --accent-gold: #d4a017;
        }
        
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            padding-top: 20px;
            z-index: 1000;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.7) !important;
            padding: 12px 25px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--sidebar-hover);
            color: white !important;
        }
        
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        
        .topbar {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .user-badge {
            background: var(--primary-green);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .form-card h4 {
            color: var(--primary-green);
            margin-bottom: 25px;
            font-weight: bold;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.25);
        }
        
        .btn-submit {
            background: var(--primary-green);
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .btn-submit:hover {
            background: var(--sidebar-hover);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-green);
            color: white;
            padding: 18px 25px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table { margin-bottom: 0; }
        .table thead th {
            background: #f8f9fa;
            border: none;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        .table tbody td { padding: 15px; vertical-align: middle; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-available { background: #d4edda; color: #155724; }
        .status-booked { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin: 2px;
        }
        
        .vehicle-icon {
            font-size: 24px;
            margin-right: 8px;
        }
        
        .footer {
            text-align: center;
            color: #888;
            padding: 30px;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-lock"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="parks.php">
                    <i class="bi bi-tree"></i> Parks & Animals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="bookings.php">
                    <i class="bi bi-calendar-check"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="vehicles.php">
                    <i class="bi bi-truck"></i> Vehicles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-globe"></i> View Website
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Top Bar -->
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-truck"></i> Vehicle Management</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h4><i class="bi bi-<?php echo $editVehicle ? 'pencil-square' : 'plus-circle'; ?>"></i> 
                <?php echo $editVehicle ? 'Edit Vehicle' : 'Add New Vehicle'; ?>
            </h4>
            <form method="POST" action="vehicles.php">
                <?php if ($editVehicle): ?>
                    <input type="hidden" name="vehicle_id" value="<?php echo $editVehicle['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vehicle Name *</label>
                        <input type="text" class="form-control" name="vehicle_name" 
                               value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['vehicle_name']) : ''; ?>" 
                               placeholder="e.g., Safari Jeep 01" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vehicle Type *</label>
                        <select class="form-select" name="vehicle_type" required>
                            <option value="Jeep" <?php echo ($editVehicle && $editVehicle['vehicle_type'] == 'Jeep') ? 'selected' : ''; ?>>🚙 Jeep</option>
                            <option value="Van" <?php echo ($editVehicle && $editVehicle['vehicle_type'] == 'Van') ? 'selected' : ''; ?>>🚐 Van</option>
                            <option value="Bus" <?php echo ($editVehicle && $editVehicle['vehicle_type'] == 'Bus') ? 'selected' : ''; ?>>🚌 Bus</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Plate Number *</label>
                        <input type="text" class="form-control" name="plate_number" 
                               value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['plate_number']) : ''; ?>" 
                               placeholder="e.g., WP-ABC-1234" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Capacity (people) *</label>
                        <input type="number" class="form-control" name="capacity" min="1" 
                               value="<?php echo $editVehicle ? $editVehicle['capacity'] : ''; ?>" 
                               placeholder="e.g., 6" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Assigned Driver</label>
                        <select class="form-select" name="driver_id">
                            <option value="">-- Select Driver --</option>
                            <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>" 
                                <?php echo ($editVehicle && $editVehicle['driver_id'] == $driver['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($driver['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="available" <?php echo ($editVehicle && $editVehicle['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="booked" <?php echo ($editVehicle && $editVehicle['status'] == 'booked') ? 'selected' : ''; ?>>Booked</option>
                            <option value="maintenance" <?php echo ($editVehicle && $editVehicle['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-<?php echo $editVehicle ? 'check-circle' : 'plus-lg'; ?>"></i> 
                        <?php echo $editVehicle ? 'Update Vehicle' : 'Add Vehicle'; ?>
                    </button>
                    <?php if ($editVehicle): ?>
                        <a href="vehicles.php" class="btn-cancel">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Vehicles Table -->
        <div class="table-card">
            <div class="table-header">
                <span><i class="bi bi-list"></i> All Vehicles</span>
                <span class="badge bg-white text-dark"><?php echo count($vehicles); ?> Total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Plate Number</th>
                            <th>Capacity</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-truck" style="font-size: 48px;"></i>
                                <p class="mt-3">No vehicles added yet. Add your first vehicle above!</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td>#<?php echo $vehicle['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></strong>
                            </td>
                            <td>
                                <?php 
                                $icon = $vehicle['vehicle_type'] == 'Jeep' ? '🚙' : ($vehicle['vehicle_type'] == 'Van' ? '🚐' : '🚌');
                                echo $icon . ' ' . $vehicle['vehicle_type']; 
                                ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($vehicle['plate_number']); ?></code></td>
                            <td><?php echo $vehicle['capacity']; ?> people</td>
                            <td>
                                <?php echo $vehicle['driver_name'] ? htmlspecialchars($vehicle['driver_name']) : '<span class="text-muted">Not assigned</span>'; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $vehicle['status']; ?>">
                                    <?php echo ucfirst($vehicle['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="vehicles.php?edit=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="vehicles.php?delete=<?php echo $vehicle['id']; ?>" 
                                   class="btn btn-sm btn-danger btn-action"
                                   onclick="return confirm('Are you sure you want to delete this vehicle?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Admin Panel &copy; 2026
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>