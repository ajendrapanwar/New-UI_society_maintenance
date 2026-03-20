<?php
require_once __DIR__ . '/../core/config.php';
requireRole(['admin', 'security']);

$search = $_GET['vehicle'] ?? $_POST['vehicle'] ?? '';
$results = [];

if ($search != '') {

    $like = "%$search%";

    $stmt = $pdo->prepare("
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


        UNION ALL

        SELECT 
            'Tenant' AS type,
            t.tenant_name AS name,
            t.mobile_no AS mobile,
            t.vehicle_no AS vehicle,
            'Tenant Parking' AS visit_type,
            NULL AS purpose,
            f.block_number,
            f.flat_number,
            DATE_FORMAT(t.move_in,'%d-%m-%Y %h:%i %p') AS in_time,
            NULL AS out_time
        FROM tenants t
        JOIN flats f ON t.flat_id = f.id
        WHERE t.vehicle_no LIKE ?
        AND t.status = 'active'

    ");

    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll();
}

/* ================= AJAX RESPONSE ================= */

if (isset($_POST['vehicle'])) {

    if (!empty($results)) {

        foreach ($results as $r):

            if ($r['type'] === 'Visitor') {
                $badgeClass = 'bg-warning text-dark';
            } elseif ($r['type'] === 'Tenant') {
                $badgeClass = 'bg-info text-dark';
            } else {
                $badgeClass = 'badge-resident';
            }
?>

            <div class="result-card shadow-sm mb-3">

                <div class="result-header <?= ($r['type'] === 'Visitor') ? 'bg-warning-subtle' : '' ?>">
                    <span class="vehicle-plate"><?= htmlspecialchars($r['vehicle']) ?></span>

                    <span class="badge <?= $badgeClass ?> fw-bold small px-3 py-1" style="border-radius:20px;">
                        <?= $r['type'] ?>
                    </span>
                </div>

                <div class="result-body">
                    <div class="d-flex align-items-center gap-4 flex-wrap">

                        <div class="flex-grow-1">

                            <h5 class="fw-bold mb-1">
                                <?= htmlspecialchars($r['name'] ?? 'Unknown') ?>
                            </h5>

                            <p class="text-muted m-0 small">

                                <?php if ($r['type'] === 'Visitor'): ?>

                                    Visit Type:
                                    <span class="fw-bold text-primary">
                                        <?= htmlspecialchars($r['visit_type'] ?? '-') ?>
                                    </span>

                                    | Visiting:
                                    <span class="fw-bold text-dark">
                                        Block <?= $r['block_number'] ?> - Flat <?= $r['flat_number'] ?>
                                    </span>

                                    | Entry:
                                    <span class="fw-bold text-dark">
                                        <?= $r['in_time'] ?>
                                    </span>

                                <?php else: ?>

                                    Flat:
                                    <span class="fw-bold text-dark">
                                        Block <?= $r['block_number'] ?> - Flat <?= $r['flat_number'] ?>
                                    </span>

                                    | Parking Type:
                                    <span class="fw-bold text-dark">
                                        <?= $r['visit_type'] ?>
                                    </span>

                                <?php endif; ?>

                            </p>

                        </div>

                        <div class="text-end">
                            <small class="text-muted d-block mb-1 fw-bold">
                                <?= ($r['type'] === 'Visitor') ? 'MOBILE' : 'CONTACT' ?>
                            </small>

                            <div class="phone-highlight">
                                <i class="fa fa-phone-alt me-2"></i>
                                <?= htmlspecialchars($r['mobile'] ?? '-') ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

<?php
        endforeach;
    } else {
        echo '<div class="alert alert-danger text-center fw-bold">❌ No Vehicle Found</div>';
    }

    exit;
}

include __DIR__ . '/../resources/layout/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Finder</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/society_maintenance/assets/css/my_styles.css?v=1.1">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">

            <!-- TITLE -->
            <div class="text-center mb-5">
                <h1 class="fw-800">Vehicle Finder</h1>
                <p class="text-muted">Enter a vehicle number to find the owner's contact info.</p>
            </div>

            <!-- SEARCH BOX -->
            <form onsubmit="return false;">
                <div class="search-container mb-4" style="padding:15px;">
                    <div class="search-input-group d-flex align-items-center gap-2" style="padding:6px 12px;">

                        <i class="fa-solid fa-magnifying-glass text-muted"></i>

                        <input
                            type="text"
                            id="vehicleSearch"
                            placeholder="Enter Vehicle Number..."
                            style="font-weight:600; height:36px; border:none; outline:none; flex:1;"
                            autocomplete="off">

                        <button id="clearBtn"
                            class="btn btn-outline-dark btn-sm px-3"
                            style="display:none; border-radius:8px;">
                            Clear
                        </button>

                    </div>
                </div>
            </form>

            <div id="resultsContainer"></div>

        </main>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {

            $("#vehicleSearch").keyup(function() {

                let vehicle = $(this).val();

                if (vehicle.length === 0) {
                    $("#resultsContainer").html('');
                    $("#clearBtn").hide();
                    return;
                }

                $("#clearBtn").show();

                $.ajax({
                    url: "vehicle_finder.php",
                    method: "POST",
                    data: {
                        vehicle: vehicle
                    },
                    success: function(data) {
                        $("#resultsContainer").html(data);
                    }
                });

            });

            $("#clearBtn").click(function() {
                $("#vehicleSearch").val('');
                $("#resultsContainer").html('');
                $(this).hide();
            });

        });
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>