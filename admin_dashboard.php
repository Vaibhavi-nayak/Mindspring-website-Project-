<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/Database.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($_POST['action'] === 'update_status') {
        $id = (int)$_POST['appointment_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        
        header('Location: admin_dashboard.php?updated=1');
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['appointment_id'];
        
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        header('Location: admin_dashboard.php?deleted=1');
        exit;
    }
}

// Fetch appointments
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT * FROM appointments WHERE 1=1";
    
    if ($filter !== 'all') {
        $sql .= " AND status = '" . $conn->real_escape_string($filter) . "'";
    }
    
    if (!empty($search)) {
        $search_term = $conn->real_escape_string($search);
        $sql .= " AND (patient_name LIKE '%$search_term%' OR patient_email LIKE '%$search_term%' OR doctor_name LIKE '%$search_term%')";
    }
    
    $sql .= " ORDER BY appointment_date DESC, appointment_time DESC";
    
    $result = $conn->query($sql);
    $appointments = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    
    // Get statistics
    $stats = [
        'total' => $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'],
        'pending' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='pending'")->fetch_assoc()['count'],
        'confirmed' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='confirmed'")->fetch_assoc()['count'],
        'completed' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='completed'")->fetch_assoc()['count'],
        'cancelled' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='cancelled'")->fetch_assoc()['count']
    ];
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MindSpring Clinic</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌱</text></svg>">
    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>
    <!-- Hamburger Menu Button -->
    <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🌱 MindSpring</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#table-wrapper" class="menu-item active">
                    📊 Dashboard
                </a>
                
                
            </nav>
            
            <form method="POST" action="admin_logout.php">
                <button type="submit" class="logout-btn">🚪 Logout</button>
            </form>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <div class="admin-info">
                    <div class="admin-avatar">A</div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                        <div style="font-size: 0.85rem; color: #718096;">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    ✅ Appointment updated successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    ✅ Appointment deleted successfully!
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3>Total Appointments</h3>
                    <div class="number"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <div class="number"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="stat-card confirmed">
                    <h3>Confirmed</h3>
                    <div class="number"><?php echo $stats['confirmed']; ?></div>
                </div>
                <div class="stat-card completed">
                    <h3>Completed</h3>
                    <div class="number"><?php echo $stats['completed']; ?></div>
                </div>
                <div class="stat-card cancelled">
                    <h3>Cancelled</h3>
                    <div class="number"><?php echo $stats['cancelled']; ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label>Status Filter</label>
                            <select name="filter">
                                <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo ($filter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo ($filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" placeholder="Search by name, email, doctor..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                </form>
            </div>
            
            <div class="appointments-section">
    <div class="section-header">
        <h2>Appointments</h2>
        <div style="color: #718096; font-size: 0.9rem;">
            Showing <?php echo count($appointments); ?> appointments
        </div>
    </div>
    
    <?php if (empty($appointments)): ?>
        <div class="no-appointments">
            <div class="no-appointments-icon">📋</div>
            <h3>No appointments found</h3>
            <p>Try adjusting your filters</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Email</th>
                        <th>Doctor</th>
                        <th>Therapy Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Booked On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td>#<?php echo $apt['id']; ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_email']); ?></td>
                            <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                            <td>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 0.3rem 1rem; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($apt['therapy_type'] ?? 'General Consultation'); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $apt['status']; ?>">
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($apt['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-edit" onclick="openEditModal(<?php echo $apt['id']; ?>, '<?php echo $apt['status']; ?>')">
                                        Edit
                                    </button>
                                    <button class="btn btn-delete" onclick="confirmDelete(<?php echo $apt['id']; ?>)">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Appointment Status</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" id="edit_appointment_id">
                
                <div class="filter-group">
                    <label>New Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" onclick="closeEditModal()" style="background: #e2e8f0; color: #2d3748;">
                        Cancel
                    </button>
                    <button type="submit" style="background: linear-gradient(135deg, #BADFDB, #FFA4A4); color: white;">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
            </div>
            <p style="color: #718096; margin-bottom: 1.5rem;">
                Are you sure you want to delete this appointment? This action cannot be undone.
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="appointment_id" id="delete_appointment_id">
                
                <div class="modal-buttons">
                    <button type="button" onclick="closeDeleteModal()" style="background: #e2e8f0; color: #2d3748;">
                        Cancel
                    </button>
                    <button type="submit" style="background: #ef4444; color: white;">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
            document.getElementById('hamburgerBtn').classList.toggle('active');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.getElementById('hamburgerBtn').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Window Resize Handler
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        // Modal Functions
        function openEditModal(appointmentId, currentStatus) {
            document.getElementById('editModal').classList.add('active');
            document.getElementById('edit_appointment_id').value = appointmentId;
            document.getElementById('edit_status').value = currentStatus;
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function openDeleteModal(appointmentId) {
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('delete_appointment_id').value = appointmentId;
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Alert Auto-dismiss
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            });
        });

        // Confirm Delete Function
        function confirmDelete(appointmentId) {
            openDeleteModal(appointmentId);
        }
    </script>
</body>
</html>