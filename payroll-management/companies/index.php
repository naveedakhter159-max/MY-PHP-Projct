<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth = 1; $pageTitle = 'Companies';
$conn = getDBConnection();

$companies = $conn->query("
    SELECT c.*, COUNT(e.id) total_emp, COALESCE(SUM(e.salary),0) total_salaries
    FROM companies c LEFT JOIN employees e ON e.company_id=c.id
    GROUP BY c.id ORDER BY c.id DESC
");
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php $f=getFlash(); if($f): ?><script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script><?php endif; ?>

<div class="page-header">
    <div><h1>Companies</h1><p class="subtitle">Manage client companies</p></div>
    <button class="btn btn-primary" onclick="openModal('addCompanyModal')"><i class="fa-solid fa-plus"></i> Add Company</button>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Companies (<?= $companies->num_rows ?>)</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="compTable" data-paginate="10">
            <thead>
                <tr>
                    <th onclick="sortTable('compTable',0)">COMP ID <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',1)">COMPANY NAME <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',2)">INDUSTRY <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',3)">TOTAL EMP <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',4)">TOTAL SALARIES <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',5)">EMAIL <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('compTable',6)">STATUS <span class="sort-icon">⬍</span></th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($c = $companies->fetch_assoc()):
                $sc=['Active'=>'badge-active','Suspended'=>'badge-suspended','Pending'=>'badge-pending','Inactive'=>'badge-inactive'];
            ?>
            <tr>
                <td><?= esc($c['company_id']) ?></td>
                <td><div class="company-name-cell">
                    <span class="letter-avatar <?= letterColor($c['name']) ?>"><?= strtoupper(substr($c['name'],0,1)) ?></span>
                    <a href="view.php?id=<?= $c['id'] ?>" style="color:#1a73e8;font-weight:500"><?= esc($c['name']) ?></a>
                </div></td>
                <td><?= esc($c['industry'] ?? '-') ?></td>
                <td><?= $c['total_emp'] ?></td>
                <td><?= currency($c['total_salaries']) ?></td>
                <td><?= esc($c['email'] ?? '-') ?></td>
                <td><span class="badge <?= $sc[$c['status']]??'badge-secondary' ?>"><?= $c['status'] ?></span></td>
                <td>
                    <a href="view.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-eye"></i></a>
                    <button onclick="fillEditModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></button>
                    <button onclick="confirmDelete('delete.php?id=<?= $c['id'] ?>','<?= addslashes($c['name']) ?>')" class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb"><i class="fa fa-trash"></i></button>
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
            <select><option>10</option><option>25</option><option>50</option></select>
        </div>
    </div>
</div>

<!-- Add Company Modal -->
<div class="modal-overlay" id="addCompanyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h5>Add Company</h5>
            <button class="modal-close" onclick="closeModal('addCompanyModal')">&times;</button>
        </div>
        <form method="POST" action="save.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Company Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Industry</label>
                        <input type="text" name="industry" class="form-control" placeholder="e.g. Technology">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option>Active</option><option>Pending</option><option>Inactive</option><option>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCompanyModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Company</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Company Modal -->
<div class="modal-overlay" id="editCompanyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h5>Edit Company</h5>
            <button class="modal-close" onclick="closeModal('editCompanyModal')">&times;</button>
        </div>
        <form method="POST" action="save.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Company Name <span class="required">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Industry</label>
                        <input type="text" name="industry" id="editIndustry" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="editPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="editCity" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" id="editState" class="form-control">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="editAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" id="editWebsite" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select">
                            <option>Active</option><option>Pending</option><option>Inactive</option><option>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCompanyModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Company</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '<script>
function fillEditModal(c){
    document.getElementById("editId").value=c.id;
    document.getElementById("editName").value=c.name||"";
    document.getElementById("editIndustry").value=c.industry||"";
    document.getElementById("editEmail").value=c.email||"";
    document.getElementById("editPhone").value=c.phone||"";
    document.getElementById("editCity").value=c.city||"";
    document.getElementById("editState").value=c.state||"";
    document.getElementById("editAddress").value=c.address||"";
    document.getElementById("editWebsite").value=c.website||"";
    document.getElementById("editStatus").value=c.status||"Active";
    openModal("editCompanyModal");
}
</script>';
include '../includes/footer.php'; ?>
