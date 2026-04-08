<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth=1; $pageTitle='Employees';
$conn = getDBConnection();
$companyFilter = (int)($_GET['company'] ?? 0);
$where = $companyFilter ? "WHERE e.company_id=$companyFilter" : "";

$employees = $conn->query("
    SELECT e.*, c.name company_name
    FROM employees e LEFT JOIN companies c ON e.company_id=c.id
    $where ORDER BY e.id DESC
");
$companies = $conn->query("SELECT id, company_id, name FROM companies ORDER BY name");
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php $f=getFlash(); if($f): ?><script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script><?php endif; ?>

<div class="page-header">
    <div><h1>Employees</h1><p class="subtitle">Manage all employees</p></div>
    <button class="btn btn-primary" onclick="openModal('addEmpModal')"><i class="fa-solid fa-plus"></i> Add Employee</button>
</div>

<!-- Filter -->
<div style="margin-bottom:14px;display:flex;gap:8px;align-items:center">
    <span style="font-size:12.5px;color:#666">Filter by company:</span>
    <a href="?" class="btn btn-sm <?= !$companyFilter?'btn-primary':'btn-outline' ?>">All</a>
    <?php $companies->data_seek(0); while ($co=$companies->fetch_assoc()): ?>
    <a href="?company=<?= $co['id'] ?>" class="btn btn-sm <?= $companyFilter==$co['id']?'btn-primary':'btn-outline' ?>"><?= esc($co['name']) ?></a>
    <?php endwhile; ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Employees (<?= $employees->num_rows ?>)</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="empTable" data-paginate="10">
            <thead><tr>
                <th onclick="sortTable('empTable',0)">EMP ID <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',1)">NAME <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',2)">COMPANY <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',3)">DEPARTMENT <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',4)">POSITION <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',5)">SALARY <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',6)">START DATE <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('empTable',7)">STATUS <span class="sort-icon">⬍</span></th>
                <th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php while ($e = $employees->fetch_assoc()):
                $sc=['Active'=>'badge-active','Inactive'=>'badge-inactive','Terminated'=>'badge-danger'];
            ?>
            <tr>
                <td><?= esc($e['employee_id']) ?></td>
                <td><div class="company-name-cell">
                    <span class="letter-avatar <?= letterColor($e['first_name']) ?>" style="border-radius:50%"><?= strtoupper(substr($e['first_name'],0,1)) ?></span>
                    <div><a href="view.php?id=<?= $e['id'] ?>" style="color:#1a73e8;font-weight:500"><?= esc($e['first_name'].' '.$e['last_name']) ?></a>
                    <div style="font-size:11px;color:#aaa"><?= esc($e['email']) ?></div></div>
                </div></td>
                <td><?= esc($e['company_name'] ?? '-') ?></td>
                <td><?= esc($e['department'] ?? '-') ?></td>
                <td><?= esc($e['position'] ?? '-') ?></td>
                <td style="font-weight:600"><?= currency($e['salary']) ?></td>
                <td><?= fmtDate($e['start_date']) ?></td>
                <td><span class="badge <?= $sc[$e['status']]??'badge-secondary' ?>"><?= $e['status'] ?></span></td>
                <td>
                    <a href="view.php?id=<?= $e['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-eye"></i></a>
                    <button onclick="fillEditEmp(<?= htmlspecialchars(json_encode($e),ENT_QUOTES) ?>)" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></button>
                    <button onclick="confirmDelete('delete.php?id=<?= $e['id'] ?>','<?= addslashes($e['first_name'].' '.$e['last_name']) ?>')" class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination">
            <button class="btn-prev">Prev</button>
            <span class="page-info">Page 1 of 1</span>
            <button class="btn-next">Next</button>
            <select><option>10</option><option>25</option></select>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="addEmpModal">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header"><h5>Add Employee</h5><button class="modal-close" onclick="closeModal('addEmpModal')">&times;</button></div>
        <form method="POST" action="save.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="form-group"><label class="form-label">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">-- Select --</option>
                    <?php $companies->data_seek(0); while($co=$companies->fetch_assoc()): ?>
                    <option value="<?=$co['id']?>"><?=esc($co['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
            <div class="form-group"><label class="form-label">Position/Title</label><input type="text" name="position" class="form-control"></div>
            <div class="form-group"><label class="form-label">Salary (Annual)</label><input type="number" step="0.01" min="0" name="salary" class="form-control"></div>
            <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
            <div class="form-group"><label class="form-label">Employment Type</label>
                <select name="employment_type" class="form-select">
                    <option>Full-Time</option><option>Part-Time</option><option>Contract</option><option>Intern</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-select"><option>Active</option><option>Inactive</option></select>
            </div>
            <div class="form-group"><label class="form-label">Gender</label>
                <select name="gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select>
            </div>
        </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('addEmpModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Employee</button>
        </div>
        </form>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal-overlay" id="editEmpModal">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header"><h5>Edit Employee</h5><button class="modal-close" onclick="closeModal('editEmpModal')">&times;</button></div>
        <form method="POST" action="save.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="eId">
        <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="eFN" class="form-control"></div>
            <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="eLN" class="form-control"></div>
            <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="eEmail" class="form-control"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="ePhone" class="form-control"></div>
            <div class="form-group"><label class="form-label">Company</label>
                <select name="company_id" id="eCompany" class="form-select">
                    <option value="">-- Select --</option>
                    <?php $companies->data_seek(0); while($co=$companies->fetch_assoc()): ?>
                    <option value="<?=$co['id']?>"><?=esc($co['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" id="eDept" class="form-control"></div>
            <div class="form-group"><label class="form-label">Position</label><input type="text" name="position" id="ePos" class="form-control"></div>
            <div class="form-group"><label class="form-label">Salary</label><input type="number" step="0.01" name="salary" id="eSal" class="form-control"></div>
            <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" id="eStart" class="form-control"></div>
            <div class="form-group"><label class="form-label">Employment Type</label>
                <select name="employment_type" id="eEmpType" class="form-select">
                    <option>Full-Time</option><option>Part-Time</option><option>Contract</option><option>Intern</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" id="eStat" class="form-select">
                    <option>Active</option><option>Inactive</option><option>Terminated</option>
                </select>
            </div>
        </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('editEmpModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
        </form>
    </div>
</div>

<?php
$extraJs='<script>
function fillEditEmp(e){
    document.getElementById("eId").value=e.id;
    document.getElementById("eFN").value=e.first_name||"";
    document.getElementById("eLN").value=e.last_name||"";
    document.getElementById("eEmail").value=e.email||"";
    document.getElementById("ePhone").value=e.phone||"";
    document.getElementById("eCompany").value=e.company_id||"";
    document.getElementById("eDept").value=e.department||"";
    document.getElementById("ePos").value=e.position||"";
    document.getElementById("eSal").value=e.salary||"";
    document.getElementById("eStart").value=e.start_date||"";
    document.getElementById("eEmpType").value=e.employment_type||"Full-Time";
    document.getElementById("eStat").value=e.status||"Active";
    openModal("editEmpModal");
}
</script>';
include '../includes/footer.php'; ?>
