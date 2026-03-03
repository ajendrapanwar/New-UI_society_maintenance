<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

/* ================= ACCESS CONTROL ================= */
requireRole(['admin', 'user']);

$errors = [
    'flat_no' => '',
    'tenant_name' => '',
    'mobile_no' => '',
    'move_in' => '',
    'agreement' => '',
    'police_files' => '',
];
$old = [
    'flat_no' => '',
    'tenant_name' => '',
    'mobile_no' => '',
    'vehicle_no' => '',
    'move_in' => '',
];


/* ================= ADD TENANT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tenant'])) {

    $old['flat_id'] = trim($_POST['flat_id'] ?? '');
    $old['tenant_name'] = trim($_POST['tenant_name'] ?? '');
    $old['mobile_no'] = trim($_POST['mobile_no'] ?? '');
    $old['vehicle_no'] = trim($_POST['vehicle_no'] ?? '');
    $old['move_in'] = $_POST['move_in'] ?? '';

    $flat_id = $old['flat_no'];
    $tenant_name = $old['tenant_name'];
    $mobile_no = $old['mobile_no'];
    $vehicle_no = $old['vehicle_no'];
    $move_in = $old['move_in'];

    $MAX_SIZE = 2 * 1024 * 1024; // 2MB

    /* ===== BASIC VALIDATION ===== */
    if (empty($flat_no)) {
        $errors['flat_no'] = "Please select a flat.";
    }

    if (empty($tenant_name)) {
        $errors['tenant_name'] = "Tenant name is required.";
    } elseif (strlen($tenant_name) < 3) {
        $errors['tenant_name'] = "Name must be at least 3 characters.";
    }

    /* ===== MOBILE VALIDATION ===== */
    if (empty($mobile_no)) {
        $errors['mobile_no'] = "Mobile number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile_no)) {
        $errors['mobile_no'] = "Enter valid 10 digit mobile number.";
    }

    if (empty($move_in)) {
        $errors['move_in'] = "Move-in date is required.";
    }

    /* ===== AGREEMENT VALIDATION ===== */
    if (empty($_FILES['agreement']['name'])) {
        $errors['agreement'] = "Rent Agreement file is required.";
    } else {

        if ($_FILES['agreement']['type'] !== 'application/pdf') {
            $errors['agreement'] = "Agreement must be PDF.";
        }

        if ($_FILES['agreement']['size'] > $MAX_SIZE) {
            $errors['agreement'] = "Agreement must be less than 2MB.";
        }
    }

    /* ===== POLICE FILES VALIDATION ===== */
    if (empty($_FILES['police_files']['name'][0])) {
        $errors['police_files'] = "Police verification files are required.";
    } else {

        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (count($_FILES['police_files']['name']) > 5) {
            $errors['police_files'] = "Maximum 5 files allowed.";
        }

        foreach ($_FILES['police_files']['name'] as $key => $name) {

            $type = $_FILES['police_files']['type'][$key];
            $size = $_FILES['police_files']['size'][$key];

            if (!in_array($type, $allowedTypes)) {
                $errors['police_files'] = "Only PDF, JPG, PNG allowed.";
                break;
            }

            if ($size > $MAX_SIZE) {
                $errors['police_files'] = "Each file must be less than 2MB.";
                break;
            }
        }
    }

    /* ===== CHECK DUPLICATE ACTIVE TENANT ===== */
    if (empty($errors['flat_no'])) {
        $checkTenant = $pdo->prepare("SELECT id FROM tenants WHERE flat_id = ? AND status = 'active'");
        $checkTenant->execute([$flat_id]);

        if ($checkTenant->fetch()) {
            $errors['flat_no'] = "This flat already has an active tenant.";
        }
    }

    /* ===== IF NO ERRORS THEN PROCESS UPLOAD + INSERT ===== */
    if (!array_filter($errors)) {

        /* ===== ABSOLUTE UPLOAD PATHS (VERY IMPORTANT) ===== */
        $agreementDir = __DIR__ . '/uploads/agreements/';
        $policeDir    = __DIR__ . '/uploads/police/';

        // Create folders safely
        if (!is_dir($agreementDir)) {
            mkdir($agreementDir, 0777, true);
        }
        if (!is_dir($policeDir)) {
            mkdir($policeDir, 0777, true);
        }

        /* ================= UPLOAD AGREEMENT ================= */
        $agreementName = '';

        if (isset($_FILES['agreement']) && $_FILES['agreement']['error'] === 0) {

            $ext = strtolower(pathinfo($_FILES['agreement']['name'], PATHINFO_EXTENSION));
            $agreementName = time() . "_agreement." . $ext;

            $agreementPath = $agreementDir . $agreementName;

            if (!is_uploaded_file($_FILES['agreement']['tmp_name'])) {
                $errors['agreement'] = "Invalid agreement upload.";
            } elseif (!move_uploaded_file($_FILES['agreement']['tmp_name'], $agreementPath)) {
                $errors['agreement'] = "Failed to save agreement file to folder.";
            }
        } else {
            $errors['agreement'] = "Agreement upload error. Code: " . ($_FILES['agreement']['error'] ?? 'unknown');
        }

        /* ================= UPLOAD POLICE FILES ================= */
        $policeFilesArray = [];

        if (isset($_FILES['police_files']) && !empty($_FILES['police_files']['name'][0])) {

            foreach ($_FILES['police_files']['tmp_name'] as $key => $tmpName) {

                if ($_FILES['police_files']['error'][$key] === 0 && is_uploaded_file($tmpName)) {

                    $ext = strtolower(pathinfo($_FILES['police_files']['name'][$key], PATHINFO_EXTENSION));
                    $newName = time() . "_police_" . $key . "." . $ext;

                    $targetPath = $policeDir . $newName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $policeFilesArray[] = $newName;
                    } else {
                        $errors['police_files'] = "Failed to save police files.";
                        break;
                    }
                } else {
                    $errors['police_files'] = "Police file upload error.";
                    break;
                }
            }
        }

        /* ===== FINAL ERROR CHECK AFTER UPLOAD (CRITICAL FIX) ===== */
        if (!array_filter($errors)) {

            $policeFiles = implode(",", $policeFilesArray);

            $stmt = $pdo->prepare("INSERT INTO tenants 
            (flat_id, tenant_name, mobile_no, vehicle_no, move_in, agreement_file, police_files)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $flat_id,
                $tenant_name,
                $mobile_no,
                $vehicle_no,
                $move_in,
                $agreementName,
                $policeFiles
            ]);

            $_SESSION['success'] = "Tenant registered successfully!";
            header("Location: tenants.php");
            exit;
        }
    }
}


/* ================= FETCH FLATS ALLOTTED TO USERS (FOR TENANT) ================= */
$availableFlats = $pdo->query("
    SELECT f.id, f.flat_number, f.block_number
    FROM flats f
    INNER JOIN allotments a ON f.id = a.flat_id
    LEFT JOIN tenants t 
        ON t.flat_id = f.id 
        AND t.status = 'active'
    WHERE t.id IS NULL
    ORDER BY f.block_number, f.flat_number ASC
")->fetchAll(PDO::FETCH_ASSOC);


/* ================= VACATE TENANT ================= */
if (isset($_GET['vacate']) && ctype_digit($_GET['vacate'])) {

    $tenantId = $_GET['vacate'];

    $stmt = $pdo->prepare("UPDATE tenants 
                           SET status='vacated', 
                               move_out = CURDATE() 
                           WHERE id=?");

    $stmt->execute([$tenantId]);

    $_SESSION['success'] = "Tenant moved to archive.";
    header("Location: tenants.php");
    exit;
}

// $stmt = $pdo->query("
//     SELECT t.*, f.flat_number, f.block_number
//     FROM tenants t
//     JOIN flats f ON t.flat_id = f.id
//     ORDER BY t.move_in DESC
// ");
// $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$role   = $_SESSION['user_role'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if ($role === 'admin') {

    // Admin sees all tenants
    $stmt = $pdo->query("
        SELECT t.*, f.flat_number, f.block_number
        FROM tenants t
        JOIN flats f ON t.flat_id = f.id
        ORDER BY t.move_in DESC
    ");
} else {

    // User sees only their allotted flat tenants
    $stmt = $pdo->prepare("
        SELECT t.*, f.flat_number, f.block_number
        FROM tenants t
        JOIN flats f ON t.flat_id = f.id
        JOIN allotments a ON f.id = a.flat_id
        WHERE a.user_id = ?
        ORDER BY t.move_in DESC
    ");

    $stmt->execute([$userId]);
}

$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);



/* ================= DELETE TENANT (PERMANENT) ================= */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {

    $tenantId = $_GET['delete'];

    // Optional: Fetch files to delete from server
    $stmt = $pdo->prepare("SELECT agreement_file, police_files FROM tenants WHERE id = ?");
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tenant) {

        // Delete agreement file
        $agreementPath = __DIR__ . '/uploads/agreements/' . $tenant['agreement_file'];

        if (!empty($tenant['agreement_file']) && file_exists($agreementPath)) {
            unlink($agreementPath);
        }

        // Delete police files
        if (!empty($tenant['police_files'])) {
            $files = explode(",", $tenant['police_files']);
            foreach ($files as $file) {
                $path = __DIR__ . '/uploads/police/' . $file;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        // Delete from database
        $del = $pdo->prepare("DELETE FROM tenants WHERE id = ?");
        $del->execute([$tenantId]);

        flash_set('success', 'Tenant record deleted permanently');
        header('Location: ' . BASE_URL . 'tenants.php');
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
    <title>Tenant Records & Archives</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        .doc-link {
            font-size: 0.82rem;
            color: var(--brand-color);
            text-decoration: none;
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
        }

        .doc-link:hover {
            text-decoration: underline;
        }

        .vehicle-plate {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            font-size: 0.75rem;
        }

        /* Highlighting vacated rows while keeping content legible */
        .vacated-row {
            background-color: #fff7ed !important;
            border-left: 4px solid #f97316;
        }

        .vacated-row td {
            color: #64748b;
        }

        .police-doc-list {
            max-height: 80px;
            overflow-y: auto;
            padding: 5px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 6px;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-vacated {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="main-wrapper">

        <main id="main-content">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">

                <div>
                    <h1 class="fw-800 m-0">Tenant & Archive Ledger</h1>
                    <p class="text-muted small">View active and historical tenant documents anytime.</p>
                </div>


                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <div class="sm-w-100 mt-3 mt-md-0">
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <button type="button" class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                                <i class="fa-solid fa-user-plus me-2"></i> New Registration
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Resident & Unit</th>
                                <th>Mobile No</th>
                                <th>Rent Period</th>
                                <th>Agreement</th>
                                <th>Verification Files</th>
                                <th>Status</th>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <th class="text-end">Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tenants as $t): ?>
                                <tr class="<?= $t['status'] === 'vacated' ? 'vacated-row' : '' ?>">
                                    <td>
                                        <span class="fw-bold d-block">
                                            <?= htmlspecialchars($t['tenant_name']) ?>
                                            <?= $t['status'] === 'vacated' ? '(Past)' : '' ?>
                                        </span>
                                        <small>
                                            Flat: <?= htmlspecialchars($t['block_number']) ?> -
                                            <?= htmlspecialchars($t['flat_number']) ?> |
                                            <?php if ($t['vehicle_no']): ?>
                                                <span class="vehicle-plate"><?= htmlspecialchars($t['vehicle_no']) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($t['mobile_no']) ?>
                                    </td>

                                    <td>
                                        <div class="small fw-bold text-success">
                                            IN: <?= date("d M Y", strtotime($t['move_in'])) ?>
                                        </div>
                                        <div class="small <?= $t['move_out'] ? 'fw-bold text-danger' : 'text-muted' ?>">
                                            OUT: <?= $t['move_out'] ? date("d M Y", strtotime($t['move_out'])) : '--' ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($t['agreement_file']): ?>
                                            <a href="uploads/agreements/<?= htmlspecialchars($t['agreement_file']) ?>"
                                                class="doc-link text-danger"
                                                target="_blank">
                                                <i class="fa fa-file-pdf"></i> View Agreement
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="police-doc-list">
                                            <?php
                                            if ($t['police_files']) {
                                                $files = explode(",", $t['police_files']);
                                                foreach ($files as $file) {
                                                    echo '<a href="uploads/police/' . htmlspecialchars($file) . '" 
                                                                class="doc-link text-success" 
                                                                target="_blank">
                                                                <i class="fa fa-file-shield"></i> View File
                                                            </a>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($t['status'] === 'active'): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-vacated">Vacated</span>
                                        <?php endif; ?>
                                    </td>

                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                        <td class="text-end">
                                            <?php if ($t['status'] === 'active'): ?>
                                                <a href="?vacate=<?= $t['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Confirm move-out?')">
                                                    Vacate
                                                </a>
                                            <?php else: ?>
                                                <a href="?delete=<?= $t['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to permanently delete this tenant record? This action cannot be undone.')">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Tenant Modal -->
    <div class="modal fade" id="addTenantModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800">Tenant Onboarding</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <form method="POST" enctype="multipart/form-data" class="row g-3">

                        <!-- REQUIRED FLAG -->
                        <input type="hidden" name="add_tenant" value="1">

                        <!-- FLAT SELECT (ONLY NOT ALLOTTED) -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                SELECT FLAT *
                            </label>

                            <select name="flat_id" class="form-select bg-light border-0 <?= $errors['flat_no'] ? 'is-invalid' : '' ?>">
                                <option value="">Select Available Flat</option>

                                <?php foreach ($availableFlats as $flat): ?>
                                    <option
                                        value="<?= htmlspecialchars($flat['id']) ?>"
                                        <?= ($old['flat_no'] == $flat['id']) ? 'selected' : '' ?>>
                                        Block <?= htmlspecialchars($flat['block_number']) ?> -
                                        <?= htmlspecialchars($flat['flat_number']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($errors['flat_no']): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['flat_no'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- TENANT NAME -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                TENANT NAME *
                            </label>
                            <input type="text"
                                name="tenant_name"
                                value="<?= htmlspecialchars($old['tenant_name']) ?>"
                                class="form-control bg-light border-0 <?= $errors['tenant_name'] ? 'is-invalid' : '' ?>"
                                placeholder="Enter tenant name">

                            <?php if ($errors['tenant_name']): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['tenant_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- VEHICLE NUMBER -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                VEHICLE NUMBER
                            </label>
                            <input type="text"
                                name="vehicle_no"
                                class="form-control bg-light border-0"
                                placeholder="Optional (MH12AB1234)">
                        </div>

                        <!-- MOBILE NUMBER -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                MOBILE NUMBER *
                            </label>
                            <input type="text"
                                name="mobile_no"
                                value="<?= htmlspecialchars($old['mobile_no']) ?>"
                                class="form-control bg-light border-0 <?= $errors['mobile_no'] ? 'is-invalid' : '' ?>"
                                placeholder="Enter 10 digit mobile number">

                            <?php if ($errors['mobile_no']): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['mobile_no'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- MOVE IN DATE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                MOVE-IN DATE *
                            </label>
                            <input type="date"
                                name="move_in"
                                value="<?= htmlspecialchars($old['move_in']) ?>"
                                class="form-control bg-light border-0 <?= $errors['move_in'] ? 'is-invalid' : '' ?>">

                            <?php if ($errors['move_in']): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['move_in'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- AGREEMENT FILE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                RENT AGREEMENT (PDF)
                            </label>
                            <input type="file"
                                name="agreement"
                                id="agreementFile"
                                accept="application/pdf"
                                required
                                class="form-control bg-light border-0">
                            <small class="text-muted">Only PDF allowed (Max: 2MB)</small>
                        </div>

                        <!-- POLICE VERIFICATION -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                POLICE VERIFICATION (Multiple Files)
                            </label>
                            <input type="file"
                                name="police_files[]"
                                id="policeFiles"
                                multiple
                                required
                                class="form-control bg-light border-0">
                            <small class="text-muted">
                                Allowed: PDF, JPG, PNG (Max 5 files, 2MB each)
                            </small>

                            <div id="preview"
                                class="mt-2 d-none p-2 bg-light border rounded small">
                            </div>
                        </div>

                        <!-- SUBMIT -->
                        <div class="col-12 mt-4">
                            <button type="submit"
                                class="btn btn-brand w-100 py-3 fw-bold">
                                <i class="fa fa-user-plus me-2"></i>
                                Confirm Registration
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.getElementById('policeFiles').addEventListener('change', function() {
            const p = document.getElementById('preview');
            p.classList.remove('d-none');
            p.innerHTML = `Selected: ${this.files.length} files`;
        });

        function confirmVacate(name) {
            confirm(`Proceed with move-out for ${name}? All documents will be archived for future reference.`);
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const agreementInput = document.getElementById("agreementFile");
            const policeInput = document.getElementById("policeFiles");
            const previewBox = document.getElementById("preview");

            const MAX_SIZE = 2 * 1024 * 1024; // 2MB
            const MAX_FILES = 5;

            // ===== AGREEMENT VALIDATION (PDF ONLY) =====
            agreementInput.addEventListener("change", function() {
                const file = this.files[0];

                if (!file) return;

                if (file.type !== "application/pdf") {
                    alert("Rent Agreement must be a PDF file.");
                    this.value = "";
                    return;
                }

                if (file.size > MAX_SIZE) {
                    alert("Agreement file size must be less than 2MB.");
                    this.value = "";
                    return;
                }
            });

            // ===== POLICE FILES VALIDATION =====
            policeInput.addEventListener("change", function() {
                const files = this.files;
                previewBox.innerHTML = "";
                previewBox.classList.add("d-none");

                if (files.length > MAX_FILES) {
                    alert("You can upload maximum 5 police verification files.");
                    this.value = "";
                    return;
                }

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // Allowed types
                    const allowedTypes = [
                        "application/pdf",
                        "image/jpeg",
                        "image/png",
                        "image/jpg"
                    ];

                    if (!allowedTypes.includes(file.type)) {
                        alert("Only PDF, JPG, PNG files are allowed.");
                        this.value = "";
                        return;
                    }

                    if (file.size > MAX_SIZE) {
                        alert("Each file must be less than 2MB.");
                        this.value = "";
                        return;
                    }

                    // Preview file names
                    const div = document.createElement("div");
                    div.innerHTML = "📄 " + file.name;
                    previewBox.appendChild(div);
                }

                if (files.length > 0) {
                    previewBox.classList.remove("d-none");
                }
            });
        });
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>