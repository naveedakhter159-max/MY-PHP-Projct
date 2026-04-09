<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
requirePerm('Tax', 'view', 1);
$canEditTax = canDo('Tax', 'edit');
$depth = 1; $pageTitle = 'Tax Management';
$conn = getDBConnection();

$employees = $conn->query("
    SELECT e.id, e.employee_id, e.first_name, e.last_name, c.name company_name
    FROM employees e
    LEFT JOIN companies c ON e.company_id = c.id
    WHERE e.status = 'Active'
    ORDER BY c.name, e.first_name
");

// US States list
$usStates = ['AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
             'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
             'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
             'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
             'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri',
             'MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey',
             'NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio',
             'OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
             'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
             'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming'];

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<style>
/* ── Tax Management Specific Styles ────────────────────── */
.tm-employee-bar {
    background: #fff; border-radius: 8px; padding: 14px 20px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.tm-employee-bar label {
    font-size: 13px; font-weight: 600; color: #374151; white-space: nowrap;
}
.tm-employee-bar select {
    flex: 0 0 300px; padding: 8px 12px; border: 1.5px solid #e5e7eb;
    border-radius: 6px; font-size: 13px; color: #374151;
    background: #fff; cursor: pointer;
}
.tm-employee-bar select:focus { border-color: #2e7d32; outline: none; }

.tm-layout { display: flex; gap: 16px; align-items: flex-start; }

/* Left Tab Nav */
.tm-tabs {
    flex: 0 0 200px; background: #fff; border-radius: 8px; padding: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.tm-tab {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 14px; border-radius: 6px; cursor: pointer;
    font-size: 13px; color: #555; transition: all .15s; margin-bottom: 2px;
    border: none; background: none; width: 100%; text-align: left;
}
.tm-tab:hover { background: #f9fafb; color: #2e7d32; }
.tm-tab.active { background: #e8f5e9; color: #2e7d32; font-weight: 600; }
.tm-tab i { font-size: 13px; color: inherit; }

/* Right Content */
.tm-content {
    flex: 1; background: #fff; border-radius: 8px; padding: 28px 32px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06); min-height: 400px;
}
.tm-section { display: none; }
.tm-section.active { display: block; }

.tm-section-title {
    font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;
    padding-bottom: 14px; border-bottom: 1px solid #f0f0f0;
}

/* Form grid inside tax sections */
.tm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
.tm-grid-1 { margin-bottom: 18px; }
.tm-form-group label {
    display: block; font-size: 12px; color: #666; margin-bottom: 6px;
}
.tm-form-group input, .tm-form-group select {
    width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb;
    border-radius: 6px; font-size: 13px; color: #374151; background: #fff;
    box-sizing: border-box; transition: border-color .15s;
}
.tm-form-group input:focus, .tm-form-group select:focus {
    border-color: #2e7d32; outline: none;
}
.tm-form-group input[readonly] { background: #f9fafb; color: #888; }

/* Toggle switch */
.toggle-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.toggle-wrap .toggle-label { font-size: 13px; color: #555; }
.ts-switch { position: relative; display: inline-block; width: 38px; height: 20px; }
.ts-switch input { opacity: 0; width: 0; height: 0; }
.ts-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #d1d5db; border-radius: 20px; transition: .25s;
}
.ts-slider:before {
    content: ''; position: absolute; width: 14px; height: 14px;
    left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .25s;
}
input:checked + .ts-slider { background: #2e7d32; }
input:checked + .ts-slider:before { transform: translateX(18px); }

/* Calculated read-only boxes */
.calc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
.calc-box {
    background: #f8f9fa; border-radius: 6px; padding: 13px 16px;
    display: flex; justify-content: space-between; align-items: center;
}
.calc-box .calc-label { font-size: 13px; color: #555; }
.calc-box .calc-val { font-size: 14px; font-weight: 700; color: #1a1a2e; }

.tm-section-subtitle {
    font-size: 13px; font-weight: 700; color: #374151;
    margin: 20px 0 12px; text-transform: uppercase; letter-spacing: .4px;
}

/* Save button row */
.tm-save-row {
    display: flex; justify-content: flex-end; gap: 10px;
    margin-top: 24px; padding-top: 16px; border-top: 1px solid #f0f0f0;
}

/* No employee placeholder */
.tm-placeholder {
    text-align: center; padding: 60px 20px; color: #aaa;
}
.tm-placeholder i { font-size: 40px; margin-bottom: 12px; display: block; }
.tm-placeholder p { font-size: 14px; }
</style>

<div class="page-header">
    <div>
        <h1>Tax Management</h1>
        <p class="subtitle" style="font-size:12px;color:#888;margin-top:2px">
            Payroll Management System &nbsp;›&nbsp; Tax
        </p>
    </div>
</div>

<!-- Employee Selector -->
<div class="tm-employee-bar">
    <label>Select Employee for Tax Setup:</label>
    <select id="employeeSelect" onchange="loadEmployee(this.value)">
        <option value="">-- Select Employee --</option>
        <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()): ?>
        <option value="<?= $e['id'] ?>">
            <?= esc($e['first_name'].' '.$e['last_name']) ?> (<?= esc($e['employee_id']) ?>) — <?= esc($e['company_name'] ?? '') ?>
        </option>
        <?php endwhile; ?>
    </select>
    <div id="empBadge" style="font-size:12px;color:#2e7d32;display:none">
        <i class="fa fa-circle-check"></i> <span id="empBadgeText"></span>
    </div>
</div>

<!-- Two-column layout -->
<div class="tm-layout">

    <!-- Left: Tab Nav -->
    <div class="tm-tabs">
        <button class="tm-tab active" onclick="switchTab('federal',this)">
            <span><i class="fa fa-landmark"></i>&nbsp; Federal Tax</span>
            <i class="fa fa-landmark" style="opacity:.3"></i>
        </button>
        <button class="tm-tab" onclick="switchTab('state',this)">
            <span><i class="fa fa-location-dot"></i>&nbsp; State Tax</span>
            <i class="fa fa-location-dot" style="opacity:.3"></i>
        </button>
        <button class="tm-tab" onclick="switchTab('local',this)">
            <span><i class="fa fa-city"></i>&nbsp; Local / City Tax</span>
            <i class="fa fa-city" style="opacity:.3"></i>
        </button>
        <button class="tm-tab" onclick="switchTab('employer',this)">
            <span><i class="fa fa-circle-dollar-to-slot"></i>&nbsp; Employer Taxes</span>
            <i class="fa fa-circle" style="opacity:.3"></i>
        </button>
        <button class="tm-tab" onclick="switchTab('deductions',this)">
            <span><i class="fa fa-clock-rotate-left"></i>&nbsp; Deductions</span>
            <i class="fa fa-clock-rotate-left" style="opacity:.3"></i>
        </button>
    </div>

    <!-- Right: Content Sections -->
    <div class="tm-content">

        <!-- No employee selected placeholder -->
        <div id="noEmpPlaceholder" class="tm-placeholder">
            <i class="fa fa-user-tie"></i>
            <p>Select an employee above to configure their tax settings.</p>
        </div>

        <div id="taxForm" style="display:none">

            <!-- ── SECTION: Federal Tax ──────────────────────────── -->
            <div class="tm-section active" id="sec-federal">
                <div class="tm-section-title">Section 1: Federal Tax Information</div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>SSN (Masked)</label>
                        <input type="text" id="ssnMasked" value="XXX-XX-XXXX" readonly placeholder="XXX-XX-XXXX">
                    </div>
                    <div class="tm-form-group">
                        <label>Filing Status</label>
                        <select id="filingStatus" onchange="recalcAll()">
                            <option value="Single">Single</option>
                            <option value="Married Filing Jointly">Married Filing Jointly</option>
                            <option value="Married Filing Separately">Married Filing Separately</option>
                            <option value="Head of Household">Head of Household</option>
                        </select>
                    </div>
                </div>
                <div class="tm-grid-2">
                    <div>
                        <div class="toggle-wrap">
                            <label class="ts-switch">
                                <input type="checkbox" id="multipleJobs" onchange="recalcAll()">
                                <span class="ts-slider"></span>
                            </label>
                            <span class="toggle-label">Multiple Jobs?&nbsp; <strong id="multipleJobsLabel">No</strong></span>
                        </div>
                    </div>
                    <div class="tm-form-group">
                        <label>Dependents Count</label>
                        <input type="number" id="dependentsCount" value="0" min="0" oninput="recalcAll()">
                    </div>
                </div>
                <div class="tm-grid-2">
                    <div>
                        <div class="toggle-wrap">
                            <label class="ts-switch">
                                <input type="checkbox" id="taxExempt" onchange="recalcAll()">
                                <span class="ts-slider"></span>
                            </label>
                            <span class="toggle-label">Tax Exempt?&nbsp; <strong id="taxExemptLabel">No</strong></span>
                        </div>
                    </div>
                    <div class="tm-form-group">
                        <label>Extra Withholding ($)</label>
                        <input type="number" id="extraWithholding" value="0" min="0" step="0.01" oninput="recalcAll()">
                    </div>
                </div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>Other Income ($)</label>
                        <input type="number" id="otherIncome" value="0" min="0" step="0.01" oninput="recalcAll()">
                    </div>
                    <div class="tm-form-group">
                        <label>Deductions ($)</label>
                        <input type="number" id="fedDeductions" value="0" min="0" step="0.01" oninput="recalcAll()">
                    </div>
                </div>

                <div class="tm-section-title" style="font-size:14px;margin-top:24px">Section 2: Auto Calculated (Read-Only)</div>
                <div class="calc-grid">
                    <div class="calc-box">
                        <span class="calc-label">Federal Income Tax</span>
                        <span class="calc-val" id="calcFedTax">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">Social Security (6.2%)</span>
                        <span class="calc-val" id="calcSS">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">Medicare (1.45%)</span>
                        <span class="calc-val" id="calcMedicare">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">Addt'l Medicare</span>
                        <span class="calc-val" id="calcAddMedicare">$0.00</span>
                    </div>
                </div>
                <div class="tm-save-row">
                    <button class="btn btn-outline" type="button" onclick="switchTab('state', document.querySelectorAll('.tm-tab')[1])">
                        Next: State Tax <i class="fa fa-arrow-right"></i>
                    </button>
                    <?php if ($canEditTax): ?><button class="btn btn-primary" type="button" onclick="saveTaxSettings()">
                        <i class="fa fa-save"></i> Save Tax Settings
                    </button><?php endif; ?>
                </div>
            </div>

            <!-- ── SECTION: State Tax ──────────────────────────── -->
            <div class="tm-section" id="sec-state">
                <div class="tm-section-title">Section 3: State Tax Information</div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>State</label>
                        <select id="stateCode" onchange="recalcAll()">
                            <option value="">Select State</option>
                            <?php foreach ($usStates as $code => $name): ?>
                            <option value="<?= $code ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tm-form-group">
                        <label>Filing Status</label>
                        <select id="stateFilingStatus">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                        </select>
                    </div>
                </div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>Allowances</label>
                        <input type="number" id="stateAllowances" value="0" min="0" oninput="recalcAll()">
                    </div>
                    <div class="tm-form-group">
                        <label>Extra Withholding ($)</label>
                        <input type="number" id="stateExtraWithholding" value="0" min="0" step="0.01">
                    </div>
                </div>
                <div class="toggle-wrap">
                    <label class="ts-switch">
                        <input type="checkbox" id="disabilityInsurance">
                        <span class="ts-slider"></span>
                    </label>
                    <span class="toggle-label">Disability Insurance&nbsp; <strong id="disabilityLabel">Disabled</strong></span>
                </div>
                <div class="tm-save-row">
                    <?php if ($canEditTax): ?><button class="btn btn-primary" type="button" onclick="saveTaxSettings()">
                        <i class="fa fa-save"></i> Save Tax Settings
                    </button><?php endif; ?>
                </div>
            </div>

            <!-- ── SECTION: Local / City Tax ──────────────────────── -->
            <div class="tm-section" id="sec-local">
                <div class="tm-section-title">Section 4: Local / City Tax</div>
                <div class="toggle-wrap" style="margin-bottom:20px">
                    <label class="ts-switch">
                        <input type="checkbox" id="localTaxEnabled" onchange="toggleLocalFields()">
                        <span class="ts-slider"></span>
                    </label>
                    <span class="toggle-label">Enable Local Tax?&nbsp; <strong id="localTaxLabel">No</strong></span>
                </div>
                <div id="localFields" style="display:none">
                    <div class="tm-grid-2">
                        <div class="tm-form-group">
                            <label>City / County Name</label>
                            <input type="text" id="localCity" placeholder="e.g. New York City">
                        </div>
                        <div class="tm-form-group">
                            <label>Local Tax Rate (%)</label>
                            <input type="number" id="localTaxRate" value="0" min="0" step="0.001" max="15" oninput="recalcAll()">
                        </div>
                    </div>
                    <div class="calc-grid" style="grid-template-columns:1fr 1fr">
                        <div class="calc-box">
                            <span class="calc-label">Estimated Local Tax (Monthly)</span>
                            <span class="calc-val" id="calcLocalTax">$0.00</span>
                        </div>
                    </div>
                </div>
                <div class="tm-save-row">
                    <?php if ($canEditTax): ?><button class="btn btn-primary" type="button" onclick="saveTaxSettings()">
                        <i class="fa fa-save"></i> Save Tax Settings
                    </button><?php endif; ?>
                </div>
            </div>

            <!-- ── SECTION: Employer Taxes ──────────────────────── -->
            <div class="tm-section" id="sec-employer">
                <div class="tm-section-title">Section 5: Employer Taxes (Admin Only)</div>
                <div class="calc-grid">
                    <div class="calc-box">
                        <span class="calc-label">Employer SS (6.2%)</span>
                        <span class="calc-val" id="calcEmpSS">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">Employer Medicare (1.45%)</span>
                        <span class="calc-val" id="calcEmpMedicare">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">FUTA <span style="font-size:11px;font-weight:400;color:#888">(0.6% on first $7,000)</span></span>
                        <span class="calc-val" id="calcFUTA">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">SUTA <span style="font-size:11px;font-weight:400;color:#888">(2.7% on first $7,000)</span></span>
                        <span class="calc-val" id="calcSUTA">$0.00</span>
                    </div>
                </div>
                <p style="font-size:11px;color:#aaa;margin-top:16px">
                    * FUTA and SUTA totals reflect the annual employer liability per employee.
                    Actual monthly amounts depend on year-to-date wages.
                </p>
            </div>

            <!-- ── SECTION: Deductions ──────────────────────────── -->
            <div class="tm-section" id="sec-deductions">
                <div class="tm-section-title">Section 6: Deductions</div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>401k Contribution (%)</label>
                        <input type="number" id="contrib401k" value="0" min="0" max="100" step="0.1" oninput="recalcAll()">
                    </div>
                    <div class="tm-form-group">
                        <label>Health Insurance ($)</label>
                        <input type="number" id="healthInsurance" value="0" min="0" step="0.01" oninput="recalcAll()">
                    </div>
                </div>

                <div class="tm-section-subtitle">Other Deductions</div>
                <div class="tm-grid-2">
                    <div class="tm-form-group">
                        <label>Deduction Name</label>
                        <input type="text" id="otherDedName" placeholder="e.g. Union Dues">
                    </div>
                    <div class="tm-form-group">
                        <label>Amount ($)</label>
                        <input type="number" id="otherDedAmount" value="0" min="0" step="0.01" oninput="recalcAll()">
                    </div>
                </div>

                <div class="tm-section-subtitle">Deduction Summary (Monthly)</div>
                <div class="calc-grid">
                    <div class="calc-box">
                        <span class="calc-label">401k Contribution</span>
                        <span class="calc-val" id="calc401k">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label">Health Insurance</span>
                        <span class="calc-val" id="calcHealth">$0.00</span>
                    </div>
                    <div class="calc-box">
                        <span class="calc-label" id="otherDedLabel">Other Deduction</span>
                        <span class="calc-val" id="calcOtherDed">$0.00</span>
                    </div>
                    <div class="calc-box" style="background:#e8f5e9">
                        <span class="calc-label" style="font-weight:700;color:#2e7d32">Total Deductions</span>
                        <span class="calc-val" style="color:#2e7d32" id="calcTotalDed">$0.00</span>
                    </div>
                </div>
                <div class="tm-save-row">
                    <button class="btn btn-primary" type="button" onclick="saveTaxSettings()">
                        <i class="fa fa-save"></i> Save All Tax Settings
                    </button>
                </div>
            </div>

        </div><!-- end #taxForm -->
    </div>
</div>

<?php $f = getFlash(); if ($f): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script>
<?php endif; ?>

<script>
// ── State data ────────────────────────────────────────────
// Approximate 2024 state income tax rates (flat/effective % for estimation)
const STATE_RATES = {
    AL:5.0,AK:0,AZ:2.5,AR:4.4,CA:9.3,CO:4.4,CT:5.0,DE:6.6,FL:0,GA:5.49,
    HI:8.25,ID:5.8,IL:4.95,IN:3.05,IA:4.4,KS:5.7,KY:4.0,LA:3.5,ME:7.15,MD:5.75,
    MA:5.0,MI:4.25,MN:7.85,MS:5.0,MO:4.8,MT:6.75,NE:5.84,NV:0,NH:0,NJ:5.525,
    NM:4.9,NY:6.85,NC:4.75,ND:2.5,OH:3.99,OK:4.75,OR:9.9,PA:3.07,RI:5.99,SC:7.0,
    SD:0,TN:0,TX:0,UT:4.85,VT:8.75,VA:5.75,WA:0,WV:6.5,WI:5.3,WY:0
};

// 2024 Federal tax brackets
const BRACKETS = {
    'Single': [[11600,0.10],[47150,0.12],[100525,0.22],[191950,0.24],[243725,0.32],[609350,0.35],[Infinity,0.37]],
    'Married Filing Jointly': [[23200,0.10],[94300,0.12],[201050,0.22],[383900,0.24],[487450,0.32],[731200,0.35],[Infinity,0.37]],
    'Married Filing Separately': [[11600,0.10],[47150,0.12],[100525,0.22],[191950,0.24],[243725,0.32],[609350,0.35],[Infinity,0.37]],
    'Head of Household': [[16550,0.10],[63100,0.12],[100500,0.22],[191950,0.24],[243700,0.32],[609350,0.35],[Infinity,0.37]]
};
const STD_DEDUCTION = {'Single':14600,'Married Filing Jointly':29200,'Married Filing Separately':14600,'Head of Household':21900};

let currentMonthlyGross = 0;
let currentAnnualGross  = 0;
let currentEmployeeId   = 0;

function fmt(n) { return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

function calcFederalTax(annualGross, filingStatus, otherIncome, fedDeductions, dependents, exempt, multipleJobs, extraWithhold) {
    if (exempt) return 0;
    const totalIncome = annualGross + parseFloat(otherIncome || 0);
    const stdDed  = STD_DEDUCTION[filingStatus] || 14600;
    const taxable = Math.max(0, totalIncome - stdDed - parseFloat(fedDeductions || 0));
    const bracks  = BRACKETS[filingStatus] || BRACKETS['Single'];

    let tax = 0, prev = 0;
    for (const [limit, rate] of bracks) {
        if (taxable <= prev) break;
        const chunk = Math.min(taxable, limit) - prev;
        tax += chunk * rate;
        prev = limit;
        if (limit === Infinity) break;
    }
    // Child tax credit per dependent ($2,000 each), phase-out above $200k/$400k
    const phaseout = (filingStatus === 'Married Filing Jointly') ? 400000 : 200000;
    const excess   = Math.max(0, totalIncome - phaseout);
    const creditRed = Math.floor(excess / 1000) * 50;
    const credit   = Math.max(0, dependents * 2000 - creditRed);
    tax = Math.max(0, tax - credit);

    // Multiple jobs adjustment: add half of standard deduction back (simplified)
    if (multipleJobs) tax *= 1.10; // rough approximation

    // Monthly
    const monthlyTax = tax / 12;
    return Math.max(0, monthlyTax + parseFloat(extraWithhold || 0));
}

function recalcAll() {
    if (!currentMonthlyGross) return;

    const filingStatus  = document.getElementById('filingStatus').value;
    const multipleJobs  = document.getElementById('multipleJobs').checked;
    const taxExempt     = document.getElementById('taxExempt').checked;
    const dependents    = parseInt(document.getElementById('dependentsCount').value) || 0;
    const extraWith     = parseFloat(document.getElementById('extraWithholding').value) || 0;
    const otherIncome   = parseFloat(document.getElementById('otherIncome').value) || 0;
    const fedDeductions = parseFloat(document.getElementById('fedDeductions').value) || 0;
    const stateCode     = document.getElementById('stateCode').value;
    const stateAllow    = parseInt(document.getElementById('stateAllowances').value) || 0;
    const localEnabled  = document.getElementById('localTaxEnabled').checked;
    const localRate     = parseFloat(document.getElementById('localTaxRate').value) || 0;
    const contrib401k   = parseFloat(document.getElementById('contrib401k').value) || 0;
    const healthIns     = parseFloat(document.getElementById('healthInsurance').value) || 0;
    const otherDedAmt   = parseFloat(document.getElementById('otherDedAmount').value) || 0;
    const otherDedName  = document.getElementById('otherDedName').value;

    const annualGross = currentAnnualGross;

    // Social Security: 6.2% on first $168,600
    const ssWageBase = 168600;
    const ssMonthly  = Math.min(currentMonthlyGross, ssWageBase / 12) * 0.062;

    // Medicare: 1.45%, Additional 0.9% on annual over $200k (single) / $250k (MFJ)
    const medicareMon = currentMonthlyGross * 0.0145;
    const addMedThresh = (filingStatus === 'Married Filing Jointly') ? 250000 : 200000;
    const addMedAnnual = Math.max(0, annualGross - addMedThresh) * 0.009;
    const addMedicareMon = addMedAnnual / 12;

    // Federal Income Tax
    const fedTaxMon = calcFederalTax(annualGross, filingStatus, otherIncome, fedDeductions, dependents, taxExempt, multipleJobs, extraWith);

    // State Tax (simplified percentage based)
    const stateRate = STATE_RATES[stateCode] || 0;
    const stateAllowDeduction = stateAllow * 5000 / 12; // ~$5k per allowance annual
    const stateTaxable = Math.max(0, currentMonthlyGross - stateAllowDeduction);
    const stateTax = stateTaxable * (stateRate / 100);

    // Local Tax
    const localTax = localEnabled ? currentMonthlyGross * (localRate / 100) : 0;

    // Employer
    const empSS      = Math.min(currentMonthlyGross, ssWageBase / 12) * 0.062;
    const empMedicare = currentMonthlyGross * 0.0145;
    const futaAnnual = Math.min(annualGross, 7000) * 0.006; // 0.6% on first $7,000
    const sutaAnnual = Math.min(annualGross, 7000) * 0.027; // 2.7% on first $7,000

    // Deductions
    const d401k      = currentMonthlyGross * (contrib401k / 100);
    const dedTotal   = d401k + healthIns + otherDedAmt;

    // Update Federal section
    document.getElementById('calcFedTax').textContent      = fmt(fedTaxMon);
    document.getElementById('calcSS').textContent          = fmt(ssMonthly);
    document.getElementById('calcMedicare').textContent    = fmt(medicareMon);
    document.getElementById('calcAddMedicare').textContent = fmt(addMedicareMon);

    // Update Employer section
    document.getElementById('calcEmpSS').textContent      = fmt(empSS);
    document.getElementById('calcEmpMedicare').textContent = fmt(empMedicare);
    document.getElementById('calcFUTA').textContent        = fmt(futaAnnual);
    document.getElementById('calcSUTA').textContent        = fmt(sutaAnnual);

    // Local Tax
    document.getElementById('calcLocalTax').textContent = fmt(localTax);

    // Deductions
    document.getElementById('calc401k').textContent    = fmt(d401k);
    document.getElementById('calcHealth').textContent  = fmt(healthIns);
    document.getElementById('calcOtherDed').textContent = fmt(otherDedAmt);
    document.getElementById('calcTotalDed').textContent = fmt(dedTotal);
    if (otherDedName) document.getElementById('otherDedLabel').textContent = otherDedName;

    // Toggle labels
    document.getElementById('multipleJobsLabel').textContent = document.getElementById('multipleJobs').checked ? 'Yes' : 'No';
    document.getElementById('taxExemptLabel').textContent    = document.getElementById('taxExempt').checked ? 'Yes' : 'No';
    document.getElementById('disabilityLabel').textContent   = document.getElementById('disabilityInsurance').checked ? 'Enabled' : 'Disabled';
}

// ── Tab switching ──────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tm-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tm-section').forEach(s => s.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('sec-' + name).classList.add('active');
}

// ── Local tax toggle ──────────────────────────────────
function toggleLocalFields() {
    const on = document.getElementById('localTaxEnabled').checked;
    document.getElementById('localTaxLabel').textContent = on ? 'Yes' : 'No';
    document.getElementById('localFields').style.display = on ? 'block' : 'none';
    recalcAll();
}

// ── Load employee data via AJAX ───────────────────────
function loadEmployee(id) {
    if (!id) {
        document.getElementById('noEmpPlaceholder').style.display = 'block';
        document.getElementById('taxForm').style.display = 'none';
        document.getElementById('empBadge').style.display = 'none';
        currentMonthlyGross = 0; currentAnnualGross = 0; currentEmployeeId = 0;
        return;
    }
    fetch('get_employee_tax.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            const emp = data.employee;
            const ts  = data.settings;
            currentMonthlyGross = emp.monthly;
            currentAnnualGross  = emp.salary;
            currentEmployeeId   = emp.id;

            // SSN masked
            document.getElementById('ssnMasked').value = emp.ssn_last4
                ? 'XXX-XX-' + emp.ssn_last4 : 'XXX-XX-XXXX';

            // Populate saved settings or defaults
            if (ts) {
                document.getElementById('filingStatus').value      = ts.filing_status;
                document.getElementById('multipleJobs').checked    = ts.multiple_jobs == 1;
                document.getElementById('taxExempt').checked       = ts.tax_exempt == 1;
                document.getElementById('dependentsCount').value   = ts.dependents_count;
                document.getElementById('extraWithholding').value  = ts.extra_withholding;
                document.getElementById('otherIncome').value       = ts.other_income;
                document.getElementById('fedDeductions').value     = ts.fed_deductions;
                document.getElementById('stateCode').value         = ts.state_code;
                document.getElementById('stateFilingStatus').value = ts.state_filing_status;
                document.getElementById('stateAllowances').value   = ts.state_allowances;
                document.getElementById('stateExtraWithholding').value = ts.state_extra_withholding;
                document.getElementById('disabilityInsurance').checked = ts.disability_insurance == 1;
                document.getElementById('localTaxEnabled').checked = ts.local_tax_enabled == 1;
                document.getElementById('localCity').value         = ts.local_city;
                document.getElementById('localTaxRate').value      = ts.local_tax_rate;
                document.getElementById('contrib401k').value       = ts.contrib_401k_pct;
                document.getElementById('healthInsurance').value   = ts.health_insurance;
                document.getElementById('otherDedName').value      = ts.other_deduction_name;
                document.getElementById('otherDedAmount').value    = ts.other_deduction_amount;
            } else {
                // Reset to defaults, pre-fill state from company state
                document.getElementById('filingStatus').value     = 'Single';
                document.getElementById('multipleJobs').checked   = false;
                document.getElementById('taxExempt').checked      = false;
                document.getElementById('dependentsCount').value  = 0;
                document.getElementById('extraWithholding').value = 0;
                document.getElementById('otherIncome').value      = 0;
                document.getElementById('fedDeductions').value    = 0;
                document.getElementById('stateCode').value        = emp.state || '';
                document.getElementById('stateFilingStatus').value= 'Single';
                document.getElementById('stateAllowances').value  = 0;
                document.getElementById('stateExtraWithholding').value = 0;
                document.getElementById('disabilityInsurance').checked = false;
                document.getElementById('localTaxEnabled').checked = false;
                document.getElementById('localCity').value        = '';
                document.getElementById('localTaxRate').value     = 0;
                document.getElementById('contrib401k').value      = 0;
                document.getElementById('healthInsurance').value  = 0;
                document.getElementById('otherDedName').value     = '';
                document.getElementById('otherDedAmount').value   = 0;
            }

            // Toggle local fields visibility
            document.getElementById('localFields').style.display =
                document.getElementById('localTaxEnabled').checked ? 'block' : 'none';

            document.getElementById('noEmpPlaceholder').style.display = 'none';
            document.getElementById('taxForm').style.display = 'block';
            document.getElementById('empBadge').style.display = 'inline-flex';
            document.getElementById('empBadgeText').textContent =
                emp.name + ' — ' + emp.department + ' | Monthly: $' + emp.monthly.toFixed(2);

            recalcAll();
        })
        .catch(e => console.error(e));
}

// ── Save tax settings ──────────────────────────────────
const TAX_CAN_EDIT = <?= $canEditTax ? 'true' : 'false' ?>;
function saveTaxSettings() {
    if (!TAX_CAN_EDIT) { showToast('error','You do not have permission to edit tax settings.'); return; }
    if (!currentEmployeeId) {
        showToast('warning', 'Please select an employee first.');
        return;
    }
    const fd = new FormData();
    fd.append('employee_id',       currentEmployeeId);
    fd.append('filing_status',     document.getElementById('filingStatus').value);
    fd.append('multiple_jobs',     document.getElementById('multipleJobs').checked ? 1 : 0);
    fd.append('tax_exempt',        document.getElementById('taxExempt').checked ? 1 : 0);
    fd.append('dependents_count',  document.getElementById('dependentsCount').value);
    fd.append('extra_withholding', document.getElementById('extraWithholding').value);
    fd.append('other_income',      document.getElementById('otherIncome').value);
    fd.append('fed_deductions',    document.getElementById('fedDeductions').value);
    fd.append('state_code',        document.getElementById('stateCode').value);
    fd.append('state_filing_status', document.getElementById('stateFilingStatus').value);
    fd.append('state_allowances',  document.getElementById('stateAllowances').value);
    fd.append('state_extra_withholding', document.getElementById('stateExtraWithholding').value);
    fd.append('disability_insurance', document.getElementById('disabilityInsurance').checked ? 1 : 0);
    fd.append('local_tax_enabled', document.getElementById('localTaxEnabled').checked ? 1 : 0);
    fd.append('local_city',        document.getElementById('localCity').value);
    fd.append('local_tax_rate',    document.getElementById('localTaxRate').value);
    fd.append('contrib_401k_pct',  document.getElementById('contrib401k').value);
    fd.append('health_insurance',  document.getElementById('healthInsurance').value);
    fd.append('other_deduction_name',   document.getElementById('otherDedName').value);
    fd.append('other_deduction_amount', document.getElementById('otherDedAmount').value);

    fetch('save_tax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showToast(data.success ? 'success' : 'error', data.message);
        })
        .catch(() => showToast('error', 'Network error saving tax settings.'));
}
</script>

<?php include '../includes/footer.php'; ?>
