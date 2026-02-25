<?php
require_once __DIR__ . '/../core/config.php';
requireRole(['admin', 'security']);

include __DIR__ . '/../resources/layout/header.php';

$search = $_GET['vehicle'] ?? '';
$results = [];

if ($search != '') {

    $like = "%$search%";

    $stmt = $pdo->prepare("
        /* ================= VISITOR VEHICLES (ONLY INSIDE) ================= */
        SELECT 
            'Visitor' AS type,
            v.visitor_name AS name,
            v.mobile,
            v.vehicle_no AS vehicle,
            v.visit_type,
            v.purpose,
            f.block_number,
            f.flat_number,
            DATE_FORMAT(v.in_time,'%d-%m-%Y %h:%i %p') AS in_time,
            NULL AS out_time
        FROM visitor_entries v
        LEFT JOIN flats f ON v.flat_id = f.id
        WHERE v.vehicle_no LIKE ?
        AND v.out_time IS NULL

        UNION ALL

        /* ================= RESIDENT VEHICLE 1 ================= */
        SELECT 
            'Resident' AS type,
            u.name AS name,
            rp.mobile,
            rp.vehicle1 AS vehicle,
            'Resident Parking' AS visit_type,
            NULL AS purpose,
            f.block_number,
            f.flat_number,
            DATE_FORMAT(rp.created_at,'%d-%m-%Y %h:%i %p') AS in_time,
            NULL AS out_time
        FROM resident_parking rp
        JOIN flats f ON rp.flat_id = f.id
        LEFT JOIN users u ON rp.user_id = u.id
        WHERE rp.vehicle1 LIKE ?

        UNION ALL

        /* ================= RESIDENT VEHICLE 2 ================= */
        SELECT 
            'Resident' AS type,
            u.name AS name,
            rp.mobile,
            rp.vehicle2 AS vehicle,
            'Resident Parking' AS visit_type,
            NULL AS purpose,
            f.block_number,
            f.flat_number,
            DATE_FORMAT(rp.created_at,'%d-%m-%Y %h:%i %p') AS in_time,
            NULL AS out_time
        FROM resident_parking rp
        JOIN flats f ON rp.flat_id = f.id
        LEFT JOIN users u ON rp.user_id = u.id
        WHERE rp.vehicle2 LIKE ?
    ");

    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Finder</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/society_maintenance/assets/css/my_styles.css?v=1.1">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="text-center mb-5">
                <h1 class="fw-800">Vehicle Finder</h1>
                <p class="text-muted">Enter a vehicle number to find the owner's contact info.</p>
            </div>

            <div class="search-container">
                <div class="search-input-group">
                    <i class="fa-solid fa-magnifying-glass text-muted fs-4"></i>
                    <input type="text" placeholder="Enter Vehicle Number (e.g. CH01AX1234)..." id="vehicleSearch">
                    <button class="btn btn-brand px-4 py-2" style="border-radius:10px;">Search</button>
                </div>
            </div>

            <div class="results-list">
                <div class="result-card shadow-sm">
                    <div class="result-header">
                        <span class="vehicle-plate">CH-01-AX-1234</span>
                        <span class="badge-resident">Resident</span>
                    </div>
                    <div class="result-body">
                        <div class="d-flex align-items-center gap-4">
                            <div class="owner-avatar">RK</div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">Rajesh Kumar</h5>
                                <p class="text-muted m-0 small">Flat: <span class="fw-bold text-dark">A-402</span> | Slot: <span class="fw-bold text-dark">P-102</span></p>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block mb-1 fw-bold">CONTACT NUMBER</small>
                                <div class="phone-highlight">
                                    <i class="fa fa-phone-alt me-2"></i>+91 98765 43210
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="result-card shadow-sm">
                    <div class="result-header" style="background: #fffbeb;">
                        <span class="vehicle-plate">HR-26-Z-9988</span>
                        <span class="badge bg-warning text-dark fw-bold small px-3 py-1" style="border-radius:20px;">Visitor</span>
                    </div>
                    <div class="result-body">
                        <div class="d-flex align-items-center gap-4">
                            <div class="owner-avatar bg-warning text-dark">S</div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">Suresh (Plumber)</h5>
                                <p class="text-muted m-0 small">Visiting: <span class="fw-bold text-dark">B-105</span> | Entry: <span class="fw-bold text-dark">10:45 AM</span></p>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block mb-1 fw-bold">MOBILE</small>
                                <div class="phone-highlight" style="background:#fff7ed; color:#9a3412; border-color:#fed7aa;">
                                    <i class="fa fa-mobile-alt me-2"></i>+91 98111 00022
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>