<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth=1; $pageTitle='Tax Settings';
$conn = getDBConnection();
$taxes = $conn->query("SELECT * FROM tax_settings ORDER BY tax_type, tax_name");

// Handle save
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action']??'';
    if ($action==='add') {
        $name  = $conn->real_escape_string(trim($_POST['tax_name']??''));
        $type  = $conn->real_escape_string(trim($_POST['tax_type']??'Federal'));
        $rate  = (float)($_POST['rate']??0);
        $desc  = $conn->real_escape_string(trim($_POST['description']??''));
        $appTo = $conn->real_escape_string(trim($_POST['applies_to']??'All Employees'));
        $conn->query("INSERT INTO tax_settings (tax_name,tax_type,rate,description,applies_to) VALUES ('$name','$type',$rate,'$desc','$appTo')");
        setFlash($conn->affected_rows>0?'success':'error','Tax rule '.($conn->affected_rows>0?'added!':'failed.'));
    } elseif ($action==='edit') {
        $id    = (int)($_POST['id']??0);
        $name  = $conn->real_escape_string(trim($_POST['tax_name']??''));
        $type  = $conn->real_escape_string(trim($_POST['tax_type']??'Federal'));
        $rate  = (float)($_POST['rate']??0);
        $desc  = $conn->real_escape_string(trim($_POST['description']??''));
        $appTo = $conn->real_escape_string(trim($_POST['applies_to']??'All Employees'));
        $stat  = (int)($_POST['status']??1);
        $conn->query("UPDATE tax_settings SET tax_name='$name',tax_type='$type',rate=$rate,description='$desc',applies_to='$appTo',status=$stat WHERE id=$id");
        setFlash('success','Tax rule updated!');
    } elseif ($action==='toggle') {
        $id   = (int)($_POST['id']??0);
        $stat = (int)($_POST['status']??1);
        $conn->query("UPDATE tax_settings SET status=$stat WHERE id=$id");
        setFlash('success','Status updated.');
    } elseif ($action==='delete') {
        $id = (int)($_POST['id']??0);
        $conn->query("DELETE FROM tax_settings WHERE id=$id");
        setFlash($conn->affected_rows>0?'success':'error','Deleted.');
    }
    redirect('index.php');
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php $f=getFlash(); if($f): ?><script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script><?php endif; ?>

<div class="page-header">
    <div><h1>Tax Settings</h1><p class="subtitle">Configure federal, state, and local tax rates</p></div>
    <button class="btn btn-primary" onclick="openModal('addTaxModal')"><i class="fa-solid fa-plus"></i> Add Tax Rule</button>
</div>

<!-- Summary Cards -->
<?php
$taxes->data_seek(0);
$byType=[];
while ($t=$taxes->fetch_assoc()) $byType[$t['tax_type']][]=$t;
$taxes->data_seek(0);
?>
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:16px">
    <?php
    $types=[['Federal','fa-flag-usa'],['FICA','fa-shield-halved'],['Medicare','fa-hospital'],['State','fa-map'],['Local','fa-city']];
    foreach ($types as [$type,$icon]):
        $rate = array_sum(array_column($byType[$type]??[], 'rate'));
    ?>
    <div class="stat-card">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <i class="fa-solid <?=$icon?>" style="color:#2e7d32;font-size:14px"></i>
            <div class="stat-label" style="margin:0"><?=$type?></div>
        </div>
        <div class="stat-value" style="font-size:22px"><?=number_format($rate,2)?>%</div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Tax Rules (<?= $taxes->num_rows ?>)</span></div>
    <div class="table-wrapper">
        <table class="data-table" id="taxTable" data-paginate="10">
            <thead><tr>
                <th>TAX NAME</th><th>TYPE</th><th>RATE</th><th>APPLIES TO</th><th>DESCRIPTION</th><th>STATUS</th><th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php $taxes->data_seek(0); while ($t=$taxes->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:500"><?=esc($t['tax_name'])?></td>
                <td><span class="badge badge-generated"><?=esc($t['tax_type'])?></span></td>
                <td style="font-weight:700;color:#2e7d32"><?=number_format($t['rate'],3)?>%</td>
                <td><?=esc($t['applies_to'])?></td>
                <td style="color:#666;font-size:12px"><?=esc($t['description']??'-')?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?=$t['id']?>">
                        <input type="hidden" name="status" value="<?=$t['status']?1:0?>">
                        <button type="submit" class="badge <?=$t['status']?'badge-active':'badge-inactive'?>" style="cursor:pointer;border:none">
                            <?=$t['status']?'Active':'Inactive'?>
                        </button>
                    </form>
                </td>
                <td>
                    <button onclick="fillEditTax(<?=htmlspecialchars(json_encode($t),ENT_QUOTES)?>)" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this tax rule?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=$t['id']?>">
                        <button type="submit" class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination"><button class="btn-prev">Prev</button><span class="page-info">Page 1 of 1</span><button class="btn-next">Next</button></div>
    </div>
</div>

<!-- Add Tax Modal -->
<div class="modal-overlay" id="addTaxModal">
    <div class="modal-box">
        <div class="modal-header"><h5>Add Tax Rule</h5><button class="modal-close" onclick="closeModal('addTaxModal')">&times;</button></div>
        <form method="POST"><input type="hidden" name="action" value="add">
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Tax Name <span class="required">*</span></label><input type="text" name="tax_name" class="form-control" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label">Tax Type</label>
                    <select name="tax_type" class="form-select"><option>Federal</option><option>State</option><option>Local</option><option>FICA</option><option>Medicare</option></select>
                </div>
                <div class="form-group"><label class="form-label">Rate (%)</label><input type="number" step="0.001" name="rate" class="form-control" placeholder="e.g. 22.000"></div>
            </div>
            <div class="form-group"><label class="form-label">Applies To</label>
                <select name="applies_to" class="form-select"><option>All Employees</option><option>Employer</option><option>Employee</option></select>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('addTaxModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div></form>
    </div>
</div>

<!-- Edit Tax Modal -->
<div class="modal-overlay" id="editTaxModal">
    <div class="modal-box">
        <div class="modal-header"><h5>Edit Tax Rule</h5><button class="modal-close" onclick="closeModal('editTaxModal')">&times;</button></div>
        <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="tId">
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Tax Name</label><input type="text" name="tax_name" id="tName" class="form-control"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label">Tax Type</label>
                    <select name="tax_type" id="tType" class="form-select"><option>Federal</option><option>State</option><option>Local</option><option>FICA</option><option>Medicare</option></select>
                </div>
                <div class="form-group"><label class="form-label">Rate (%)</label><input type="number" step="0.001" name="rate" id="tRate" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">Applies To</label>
                <select name="applies_to" id="tAppTo" class="form-select"><option>All Employees</option><option>Employer</option><option>Employee</option></select>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="tDesc" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" id="tStat" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('editTaxModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div></form>
    </div>
</div>

<?php $extraJs='<script>
function fillEditTax(t){
    document.getElementById("tId").value=t.id;
    document.getElementById("tName").value=t.tax_name||"";
    document.getElementById("tType").value=t.tax_type||"Federal";
    document.getElementById("tRate").value=t.rate||"";
    document.getElementById("tAppTo").value=t.applies_to||"All Employees";
    document.getElementById("tDesc").value=t.description||"";
    document.getElementById("tStat").value=t.status;
    openModal("editTaxModal");
}
</script>';
include '../includes/footer.php'; ?>
