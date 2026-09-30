<?php
/**
 * Synthetic Test Data Seeder for Barangay Sinalhan Health Center
 *
 * Preserves:
 *   - users (all user accounts, roles, credentials, permissions)
 *   - settings (all system configurations)
 *   - audit_logs (security audit trails preserved)
 *
 * Resets & Populates:
 *   - patients (300+ realistic synthetic patients)
 *   - wellbaby_records & child_growth_logs
 *   - prenatal_records, prenatal_visits & past_obstetric_histories
 *   - patient_medical_histories, patient_conditions, patient_surgeries, patient_external_immunizations
 *   - vital_signs
 *   - consultations & prescriptions
 *   - immunizations
 *   - appointments
 *   - queue_entries & queue_daily_counters
 *   - pcb_obligated_services & pcb_service_logs
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run via CLI.\n");
}

$dbConfig = require dirname(__DIR__) . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
    $dbConfig['username'],
    $dbConfig['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

echo "====================================================================\n";
echo " BARANGAY SINALHAN HEALTH CENTER - SYNTHETIC DATA GENERATOR\n";
echo "====================================================================\n\n";

// ------------------------------------------------------------------
// 1. SAFETY INSPECTION & BACKUP OF CRITICAL SYSTEM DATA
// ------------------------------------------------------------------
echo "Phase 1: Inspecting & Preserving Critical System Accounts & Settings...\n";

$savedUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
$savedSettings = $pdo->query("SELECT * FROM settings ORDER BY setting_key ASC")->fetchAll();

echo "  -> Found " . count($savedUsers) . " existing user accounts:\n";
foreach ($savedUsers as $u) {
    echo "     * [ID: {$u['id']}] {$u['username']} ({$u['role']}) - {$u['first_name']} {$u['last_name']}\n";
}
echo "  -> Found " . count($savedSettings) . " configuration settings preserved.\n\n";

if (count($savedUsers) === 0) {
    die("ERROR: No user accounts found in database! Aborting to prevent accidental blank state.\n");
}

// Valid user IDs for attribution
$adminUserIds = [1, 22, 29];
$staffUserIds = [2, 3];
$midwifeUserId = 3;
$allUserIds = array_column($savedUsers, 'id');

// ------------------------------------------------------------------
// 2. CLEARING PATIENT & OPERATIONAL DATA ONLY
// ------------------------------------------------------------------
echo "Phase 2: Clearing patient and operational records...\n";

$tablesToClear = [
    'pcb_service_logs',
    'pcb_obligated_services',
    'child_growth_logs',
    'wellbaby_records',
    'prenatal_visits',
    'prenatal_records',
    'past_obstetric_histories',
    'patient_conditions',
    'patient_surgeries',
    'patient_external_immunizations',
    'patient_medical_histories',
    'prescriptions',
    'consultations',
    'vital_signs',
    'appointments',
    'queue_entries',
    'queue_daily_counters',
    'immunizations',
    'patients'
];

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
foreach ($tablesToClear as $table) {
    $pdo->exec("TRUNCATE TABLE `{$table}`;");
    echo "  -> Cleared table: `{$table}`\n";
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "  -> Patient and operational tables cleared successfully.\n";

// Verify users & settings remain untouched
$postCheckUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$postCheckSettings = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

if ($postCheckUsers != count($savedUsers)) {
    die("FATAL: Users count changed unexpectedly during reset! Aborting.\n");
}
echo "  -> Verified: All {$postCheckUsers} user accounts and {$postCheckSettings} settings remain intact.\n\n";

// ------------------------------------------------------------------
// 3. SYNTHETIC POOLS DEFINITION (Barangay Sinalhan Context)
// ------------------------------------------------------------------
echo "Phase 3: Generating Synthetic Patient Cohorts & Family Clusters...\n";

$surnames = [
    'Santos', 'Dela Cruz', 'Bautista', 'Reyes', 'Ramos', 'Mendoza', 'Garcia', 'Flores',
    'Gonzales', 'Aquino', 'Castillo', 'Villanueva', 'Fernandez', 'Cruz', 'Mercado', 'Soriano',
    'Navarro', 'Tolentino', 'Valenzuela', 'Alcantara', 'Pascual', 'Salazar', 'De Leon', 'Rivera',
    'Corpuz', 'Santiago', 'Cortez', 'Maghirang', 'Tatlonghari', 'Alvarez', 'Manalo', 'Panganiban',
    'Vergara', 'Dimaculangan', 'Ilagan', 'Catacutan', 'Guinto', 'Macaraig', 'Evangelista', 'Dizon',
    'Ocampo', 'Paredes', 'Custodio', 'Espiritu', 'Sarmiento', 'Del Rosario', 'Agoncillo', 'Ponce',
    'Marasigan', 'Peña', 'Beltran', 'Domingo', 'Perez', 'Guerrero', 'Enriquez', 'Velasco',
    'Bermudez', 'Rosales', 'Bernardo', 'Quizon', 'Salas', 'Montenegro', 'Austria', 'Lagman',
    'Palma', 'Bello', 'San Jose', 'Carandang', 'Atienza', 'Morales', 'Legaspi', 'Ignacio',
    'David', 'Miranda', 'Suarez', 'Campos', 'Padilla', 'Lim', 'Tan', 'Chua', 'Sy',
    'Gomez', 'Hernandez', 'Lopez', 'Moran', 'Valdez', 'Roxas', 'Villar'
];

$firstNamesMale = [
    'Juan', 'Jose', 'Mark', 'John Mark', 'Angelo', 'Christian', 'Joshua', 'Gabriel', 'Daniel',
    'Nathaniel', 'Carl', 'Michael', 'Paolo', 'Rafael', 'Jerome', 'Arnel', 'Danilo', 'Rogelio',
    'Eduardo', 'Fernando', 'Renato', 'Ernesto', 'Ramon', 'Antonio', 'Vicente', 'Manuel',
    'Rodrigo', 'Francis', 'Alden', 'Emilio', 'Lito', 'Noel', 'Jaime', 'Arturo', 'Cesar',
    'Rolando', 'Arman', 'Reynaldo', 'Efren', 'Darwin', 'Edison', 'Bryan', 'Kenneth', 'Kevin',
    'Justin', 'Jayson', 'Tristan', 'Kyle', 'Ethan', 'Liam', 'Mateo', 'Joaquin', 'Sebastian',
    'Lucas', 'Dominic', 'Julian', 'Marco', 'Miguel', 'Elijah', 'Aaron', 'Dexter', 'Rodel'
];

$firstNamesFemale = [
    'Maria', 'Mary Joy', 'Angelica', 'Jasmine', 'Princess', 'Kristine', 'Patricia', 'Camille',
    'Kathryn', 'Bea', 'Nicole', 'Alyssa', 'Rhea', 'Elena', 'Lourdes', 'Carmela', 'Rosario',
    'Teresa', 'Corazon', 'Imelda', 'Luzviminda', 'Gloria', 'Florencia', 'Esperanza', 'Remedios',
    'Consuelo', 'Milagros', 'Josefina', 'Rowena', 'Maricel', 'Divina', 'Gemma', 'Marilou',
    'Jocelyn', 'Charito', 'Cherry', 'Hazel', 'Aileen', 'Rachelle', 'Giselle', 'Althea',
    'Chloe', 'Samantha', 'Sofia', 'Andrea', 'Hannah', 'Brianna', 'Danielle', 'Eunice',
    'Stephanie', 'Janice', 'Fatima', 'Clarisse', 'Bernadette', 'Vanessa', 'Marjorie', 'Christine'
];

$middleNames = [
    'Santos', 'Dela Cruz', 'Reyes', 'Bautista', 'Aquino', 'Garcia', 'Mendoza', 'Flores',
    'Castillo', 'Villanueva', 'Fernandez', 'Cruz', 'Mercado', 'Ramos', 'Navarro', 'Alvarez',
    'Manalo', 'Santiago', 'Rivera', 'Perez', 'Del Rosario', 'Salazar', 'Guinto', 'Ocampo'
];

$sinalhanAddresses = [
    'Purok 1, Sitio Aplaya',
    'Purok 1, Seaside Compound',
    'Purok 2, Coastal Road',
    'Purok 2, Fishermen Village',
    'Purok 3, Rizal Extension',
    'Purok 3, M.L. Quezon Street',
    'Purok 4, Ilaya Street',
    'Purok 4, Bonifacio Interior',
    'Purok 5, Wawa Street',
    'Purok 5, Riverside',
    'Purok 6, Lakeside Road',
    'Purok 6, San Antonio Area',
    'Sitio Sampaguita, Sinalhan',
    'Seaside Subdivision, Sinalhan',
    'San Antonio Compound, Sinalhan'
];

$occupations = [
    'Fisherman', 'Tricycle Driver', 'Construction Worker', 'Vendor', 'Barangay Tanod',
    'Factory Worker', 'Housewife', 'Security Guard', 'Tailor / Dressmaker', 'Teacher',
    'Store Owner / Tindero', 'Caregiver', 'Electrician', 'Auto Mechanic', 'Carpenter',
    'Call Center Agent', 'Delivery Rider', 'Office Clerk', 'Fish Vendor', 'Retired'
];

$bloodTypes = ['O+', 'O+', 'O+', 'A+', 'A+', 'B+', 'B+', 'AB+', 'O-', 'A-', 'Unknown'];
$civilStatuses = ['Single', 'Married', 'Married', 'Married', 'Widow/Widower', 'Separated'];
$religions = ['Roman Catholic', 'Roman Catholic', 'Roman Catholic', 'Iglesia ni Cristo', 'Born Again Christian', 'Seventh-day Adventist', 'None'];
$educationLevels = ['High School', 'High School', 'College degree, post graduate', 'Elementary', 'Vocational', 'No Schooling'];

// Helper to pick random element
function pick($arr) {
    return $arr[array_rand($arr)];
}

// Helper to format PhilHealth number: XX-XXXXXXXXX-X
function makePhilHealthNo($index) {
    $p1 = str_pad(($index % 89) + 10, 2, '0', STR_PAD_LEFT);
    $p2 = str_pad(100000000 + ($index * 137) % 899999999, 9, '0', STR_PAD_LEFT);
    $p3 = ($index * 3) % 10;
    return "{$p1}-{$p2}-{$p3}";
}

// Helper to format PH mobile: 09XXXXXXXXX
function makeMobileNo($index) {
    $prefixes = ['0917', '0918', '0920', '0922', '0927', '0939', '0945', '0998', '0908', '0966'];
    $pref = $prefixes[$index % count($prefixes)];
    $suffix = str_pad(($index * 7391) % 10000000, 7, '0', STR_PAD_LEFT);
    return $pref . $suffix;
}

// ------------------------------------------------------------------
// 4. STRUCTURE 110 FAMILY HOUSEHOLDS WITH 330 PATIENTS
// ------------------------------------------------------------------
$patients = [];
$patientIndex = 0;
$totalTargetPatients = 330;

// Age Categories target:
// 1. Infants/Toddlers (0-5 yrs): ~50
// 2. Children/Teens (6-19 yrs): ~45
// 3. Reproductive Females (20-45 yrs): ~75 (including ~28 maternal/prenatal candidates)
// 4. Adult Males (20-59 yrs): ~80
// 5. Older Adults/Seniors (60-88 yrs): ~80

$familyClusters = [];
$numFamilies = 110;

for ($f = 1; $f <= $numFamilies; $f++) {
    $familySurname = $surnames[($f - 1) % count($surnames)];
    $familyNo = "FAM-2026-" . str_pad($f, 4, '0', STR_PAD_LEFT);
    $envelopeNo = "ENV-" . str_pad($f, 4, '0', STR_PAD_LEFT);
    
    // ~85% Sinalhan, 15% neighboring Santa Rosa barangays
    if ($f % 7 == 0) {
        $barangay = pick(['Aplaya', 'Caingin', 'Tagapo', 'Market Area', 'Ibaba']);
        $address = "Purok " . (($f % 5) + 1) . ", Barangay " . $barangay . ", Santa Rosa City";
    } else {
        $barangay = 'Sinalhan';
        $address = $sinalhanAddresses[($f - 1) % count($sinalhanAddresses)] . ", Barangay Sinalhan, Santa Rosa City";
    }

    $familyClusters[$f] = [
        'family_no' => $familyNo,
        'envelope_no' => $envelopeNo,
        'surname' => $familySurname,
        'barangay' => $barangay,
        'address' => $address,
        'members' => []
    ];
}

echo "  -> Created 110 realistic family units with envelope & family clusters.\n";

// Distribute patients across families:
// Archetype A: Young Family (Mother + Father + Infant/Toddler) -> Families 1 to 35
// Archetype B: Growing Family (Mother + Father + School Child/Teen) -> Families 36 to 65
// Archetype C: Multi-gen Family (Senior Grandparent + Adult Child + Grandchild) -> Families 66 to 90
// Archetype D: Senior Couple or Elderly Living Alone -> Families 91 to 105
// Archetype E: Single Adults / Couples -> Families 106 to 110

$today = new DateTime('2026-09-24');

// Generator helper for DOB
function dateOfBirth($yearsMin, $yearsMax, $monthsMin = 0, $monthsMax = 11) {
    global $today;
    $years = rand($yearsMin, $yearsMax);
    $months = rand($monthsMin, $monthsMax);
    $days = rand(1, 28);
    $interval = new DateInterval("P{$years}Y{$months}M{$days}D");
    $dob = (clone $today)->sub($interval);
    return $dob->format('Y-m-d');
}

$patientList = [];
$pIdCounter = 1;

// 1. Infants & Toddlers (50 patients)
$infantList = [];
for ($i = 1; $i <= 50; $i++) {
    $fId = (($i - 1) % 45) + 1; // associate with families 1-45
    $fam = &$familyClusters[$fId];
    $sex = ($i % 2 == 0) ? 'Male' : 'Female';
    $fn = ($sex === 'Male') ? pick($firstNamesMale) : pick($firstNamesFemale);
    $mn = pick($middleNames);
    $ln = $fam['surname'];
    
    // Ages 0 to 5 years
    if ($i <= 25) {
        // Infants 0 to 11 months
        $months = rand(1, 11);
        $days = rand(1, 28);
        $dob = (clone $today)->sub(new DateInterval("P0Y{$months}M{$days}D"))->format('Y-m-d');
        $isInfant = true;
    } else {
        // Toddlers 1 to 5 years
        $dob = dateOfBirth(1, 5);
        $isInfant = false;
    }

    $pat = [
        'id' => $pIdCounter++,
        'patient_no' => 'P-2026-' . str_pad($pIdCounter - 1, 5, '0', STR_PAD_LEFT),
        'envelope_no' => $fam['envelope_no'],
        'family_no' => $fam['family_no'],
        'first_name' => $fn,
        'middle_name' => $mn,
        'last_name' => $ln,
        'suffix' => ($sex === 'Male' && $i % 7 == 0) ? 'Jr.' : null,
        'dob' => $dob,
        'sex' => $sex,
        'civil_status' => 'Single',
        'civil_status_other' => null,
        'blood_type' => pick($bloodTypes),
        'religion' => pick($religions),
        'occupation' => 'None / Dependent Minor',
        'education_attainment' => 'No Schooling',
        'contact_no' => null, // minors use parent's contact
        'barangay' => $fam['barangay'],
        'address' => $fam['address'],
        'phic_status' => 'Dependent',
        'phic_type' => 'Dependent of PhilHealth Member',
        'philhealth_no' => null,
        'father_name' => "Jose {$ln}",
        'father_dob' => dateOfBirth(25, 42),
        'mother_name' => "Maria {$mn} {$ln}",
        'mother_dob' => dateOfBirth(22, 38),
        'spouse_name' => null,
        'spouse_dob' => null,
        'emergency_name' => "Maria {$ln}",
        'emergency_relationship' => 'Mother',
        'emergency_no' => makeMobileNo($fId),
        'created_by' => pick($allUserIds),
        'type_category' => 'infant_toddler',
        'is_infant' => $isInfant,
        'family_id' => $fId
    ];

    $patientList[] = $pat;
    $infantList[] = $pat;
    $fam['members'][] = $pat['id'];
}

// 2. Adult Females of Childbearing Age (75 patients: ages 20-45)
// Contains maternal candidates, mothers of the infants above, and general adult females
$femaleAdultList = [];
$motherMapping = []; // map family_id to mother patient_id

for ($i = 1; $i <= 75; $i++) {
    $fId = (($i - 1) % 70) + 1;
    $fam = &$familyClusters[$fId];
    $fn = pick($firstNamesFemale);
    $mn = pick($middleNames);
    $ln = $fam['surname'];
    $dob = dateOfBirth(20, 42);
    $cStat = ($i % 8 == 0) ? 'Single' : 'Married';
    $spouse = ($cStat === 'Married') ? pick($firstNamesMale) . " " . $ln : null;

    $pat = [
        'id' => $pIdCounter++,
        'patient_no' => 'P-2026-' . str_pad($pIdCounter - 1, 5, '0', STR_PAD_LEFT),
        'envelope_no' => $fam['envelope_no'],
        'family_no' => $fam['family_no'],
        'first_name' => $fn,
        'middle_name' => $mn,
        'last_name' => $ln,
        'suffix' => null,
        'dob' => $dob,
        'sex' => 'Female',
        'civil_status' => $cStat,
        'civil_status_other' => null,
        'blood_type' => pick($bloodTypes),
        'religion' => pick($religions),
        'occupation' => ($i % 3 == 0) ? 'Housewife' : pick($occupations),
        'education_attainment' => pick($educationLevels),
        'contact_no' => makeMobileNo($pIdCounter),
        'barangay' => $fam['barangay'],
        'address' => $fam['address'],
        'phic_status' => ($i % 4 == 0) ? 'Dependent' : 'Member',
        'phic_type' => ($i % 4 == 0) ? 'Dependent of Employed Member' : 'Direct Contributor - Employed',
        'philhealth_no' => ($i % 4 != 0) ? makePhilHealthNo($pIdCounter) : null,
        'father_name' => pick($firstNamesMale) . " " . $mn,
        'father_dob' => dateOfBirth(50, 70),
        'mother_name' => pick($firstNamesFemale) . " " . pick($surnames),
        'mother_dob' => dateOfBirth(48, 68),
        'spouse_name' => $spouse,
        'spouse_dob' => $spouse ? dateOfBirth(22, 45) : null,
        'emergency_name' => $spouse ?: "Elena {$ln}",
        'emergency_relationship' => $spouse ? 'Spouse' : 'Parent',
        'emergency_no' => makeMobileNo($pIdCounter + 500),
        'created_by' => pick($allUserIds),
        'type_category' => 'adult_female',
        'family_id' => $fId
    ];

    $patientList[] = $pat;
    $femaleAdultList[] = $pat;
    $fam['members'][] = $pat['id'];

    if (!isset($motherMapping[$fId])) {
        $motherMapping[$fId] = $pat['id'];
    }
}

// 3. Children and Teens (45 patients: ages 6-19)
for ($i = 1; $i <= 45; $i++) {
    $fId = (($i - 1) % 65) + 1;
    $fam = &$familyClusters[$fId];
    $sex = ($i % 2 == 0) ? 'Male' : 'Female';
    $fn = ($sex === 'Male') ? pick($firstNamesMale) : pick($firstNamesFemale);
    $mn = pick($middleNames);
    $ln = $fam['surname'];
    $dob = dateOfBirth(6, 19);

    $pat = [
        'id' => $pIdCounter++,
        'patient_no' => 'P-2026-' . str_pad($pIdCounter - 1, 5, '0', STR_PAD_LEFT),
        'envelope_no' => $fam['envelope_no'],
        'family_no' => $fam['family_no'],
        'first_name' => $fn,
        'middle_name' => $mn,
        'last_name' => $ln,
        'suffix' => null,
        'dob' => $dob,
        'sex' => $sex,
        'civil_status' => 'Single',
        'civil_status_other' => null,
        'blood_type' => pick($bloodTypes),
        'religion' => pick($religions),
        'occupation' => 'Student',
        'education_attainment' => (intval(substr($dob, 0, 4)) > 2014) ? 'Elementary' : 'High School',
        'contact_no' => ($i > 25) ? makeMobileNo($pIdCounter) : null,
        'barangay' => $fam['barangay'],
        'address' => $fam['address'],
        'phic_status' => 'Dependent',
        'phic_type' => 'Dependent of PhilHealth Member',
        'philhealth_no' => null,
        'father_name' => pick($firstNamesMale) . " " . $ln,
        'father_dob' => dateOfBirth(35, 55),
        'mother_name' => pick($firstNamesFemale) . " " . $mn . " " . $ln,
        'mother_dob' => dateOfBirth(32, 52),
        'spouse_name' => null,
        'spouse_dob' => null,
        'emergency_name' => "Parent / Guardian",
        'emergency_relationship' => 'Parent',
        'emergency_no' => makeMobileNo($fId),
        'created_by' => pick($allUserIds),
        'type_category' => 'child_teen',
        'family_id' => $fId
    ];

    $patientList[] = $pat;
    $fam['members'][] = $pat['id'];
}

// 4. Adult Males (80 patients: ages 20-59)
$maleAdultList = [];
for ($i = 1; $i <= 80; $i++) {
    $fId = (($i - 1) % 85) + 1;
    $fam = &$familyClusters[$fId];
    $fn = pick($firstNamesMale);
    $mn = pick($middleNames);
    $ln = $fam['surname'];
    $dob = dateOfBirth(20, 59);
    $cStat = ($i % 6 == 0) ? 'Single' : 'Married';
    $spouse = ($cStat === 'Married') ? pick($firstNamesFemale) . " " . $ln : null;

    $pat = [
        'id' => $pIdCounter++,
        'patient_no' => 'P-2026-' . str_pad($pIdCounter - 1, 5, '0', STR_PAD_LEFT),
        'envelope_no' => $fam['envelope_no'],
        'family_no' => $fam['family_no'],
        'first_name' => $fn,
        'middle_name' => $mn,
        'last_name' => $ln,
        'suffix' => ($i % 9 == 0) ? 'Jr.' : (($i % 17 == 0) ? 'III' : null),
        'dob' => $dob,
        'sex' => 'Male',
        'civil_status' => $cStat,
        'civil_status_other' => null,
        'blood_type' => pick($bloodTypes),
        'religion' => pick($religions),
        'occupation' => pick($occupations),
        'education_attainment' => pick($educationLevels),
        'contact_no' => makeMobileNo($pIdCounter),
        'barangay' => $fam['barangay'],
        'address' => $fam['address'],
        'phic_status' => ($i % 3 == 0) ? 'Non-Member' : 'Member',
        'phic_type' => ($i % 3 == 0) ? null : (($i % 2 == 0) ? 'Direct Contributor - Employed' : 'Indirect Contributor - Sponsored / Indigent'),
        'philhealth_no' => ($i % 3 != 0) ? makePhilHealthNo($pIdCounter) : null,
        'father_name' => pick($firstNamesMale) . " " . $ln,
        'father_dob' => dateOfBirth(55, 80),
        'mother_name' => pick($firstNamesFemale) . " " . pick($surnames),
        'mother_dob' => dateOfBirth(52, 78),
        'spouse_name' => $spouse,
        'spouse_dob' => $spouse ? dateOfBirth(22, 58) : null,
        'emergency_name' => $spouse ?: "Relative",
        'emergency_relationship' => $spouse ? 'Spouse' : 'Sibling',
        'emergency_no' => makeMobileNo($pIdCounter + 600),
        'created_by' => pick($allUserIds),
        'type_category' => 'adult_male',
        'family_id' => $fId
    ];

    $patientList[] = $pat;
    $maleAdultList[] = $pat;
    $fam['members'][] = $pat['id'];
}

// 5. Older Adults & Seniors (80 patients: ages 60-88)
$seniorList = [];
for ($i = 1; $i <= 80; $i++) {
    $fId = (($i - 1) % 110) + 1;
    $fam = &$familyClusters[$fId];
    $sex = ($i % 2 == 0) ? 'Female' : 'Male';
    $fn = ($sex === 'Male') ? pick($firstNamesMale) : pick($firstNamesFemale);
    $mn = pick($middleNames);
    $ln = $fam['surname'];
    $dob = dateOfBirth(60, 88);
    $cStat = ($i % 3 == 0) ? 'Widow/Widower' : 'Married';
    $spouse = ($cStat === 'Married') ? (($sex === 'Male') ? pick($firstNamesFemale) : pick($firstNamesMale)) . " " . $ln : null;

    $pat = [
        'id' => $pIdCounter++,
        'patient_no' => 'P-2026-' . str_pad($pIdCounter - 1, 5, '0', STR_PAD_LEFT),
        'envelope_no' => $fam['envelope_no'],
        'family_no' => $fam['family_no'],
        'first_name' => $fn,
        'middle_name' => $mn,
        'last_name' => $ln,
        'suffix' => ($sex === 'Male' && $i % 8 == 0) ? 'Sr.' : null,
        'dob' => $dob,
        'sex' => $sex,
        'civil_status' => $cStat,
        'civil_status_other' => null,
        'blood_type' => pick($bloodTypes),
        'religion' => pick($religions),
        'occupation' => ($i % 4 == 0) ? 'Pensioner' : 'Retired',
        'education_attainment' => pick(['Elementary', 'High School', 'College degree, post graduate']),
        'contact_no' => makeMobileNo($pIdCounter),
        'barangay' => $fam['barangay'],
        'address' => $fam['address'],
        'phic_status' => 'Member',
        'phic_type' => 'Senior Citizen (RA 10645)',
        'philhealth_no' => makePhilHealthNo($pIdCounter),
        'father_name' => pick($firstNamesMale) . " " . $ln,
        'father_dob' => null,
        'mother_name' => pick($firstNamesFemale) . " " . pick($surnames),
        'mother_dob' => null,
        'spouse_name' => $spouse,
        'spouse_dob' => $spouse ? dateOfBirth(60, 85) : null,
        'emergency_name' => pick($firstNamesMale) . " {$ln}",
        'emergency_relationship' => 'Child / Son',
        'emergency_no' => makeMobileNo($pIdCounter + 800),
        'created_by' => pick($allUserIds),
        'type_category' => 'senior',
        'family_id' => $fId
    ];

    $patientList[] = $pat;
    $seniorList[] = $pat;
    $fam['members'][] = $pat['id'];
}

// Include ~8 archived patients (for testing /archive hub)
$archivedReasons = [
    'Transferred residence to another province',
    'Transferred to private hospital',
    'Patient deceased',
    'Duplicate chart merged into primary record'
];
for ($a = 0; $a < 8; $a++) {
    $idx = rand(10, count($patientList) - 1);
    $patientList[$idx]['deleted_at'] = '2026-08-' . str_pad($a + 5, 2, '0', STR_PAD_LEFT) . ' 10:30:00';
    $patientList[$idx]['deleted_by'] = pick($adminUserIds);
    $patientList[$idx]['archive_reason'] = $archivedReasons[$a % count($archivedReasons)];
}

echo "  -> Assembled " . count($patientList) . " synthetic patients (target 300+ achieved).\n";

// Insert patients into database
$stmtPatient = $pdo->prepare("
    INSERT INTO patients (
        id, patient_no, envelope_no, family_no, first_name, middle_name, last_name, suffix, dob, sex,
        civil_status, civil_status_other, blood_type, religion, occupation, education_attainment,
        contact_no, barangay, address, phic_status, phic_type, philhealth_no,
        father_name, father_dob, mother_name, mother_dob, spouse_name, spouse_dob,
        emergency_name, emergency_relationship, emergency_no, deleted_at, deleted_by, archive_reason,
        created_by, created_at, updated_at
    ) VALUES (
        :id, :patient_no, :envelope_no, :family_no, :first_name, :middle_name, :last_name, :suffix, :dob, :sex,
        :civil_status, :civil_status_other, :blood_type, :religion, :occupation, :education_attainment,
        :contact_no, :barangay, :address, :phic_status, :phic_type, :philhealth_no,
        :father_name, :father_dob, :mother_name, :mother_dob, :spouse_name, :spouse_dob,
        :emergency_name, :emergency_relationship, :emergency_no, :deleted_at, :deleted_by, :archive_reason,
        :created_by, NOW(), NOW()
    )
");

$pdo->beginTransaction();
foreach ($patientList as $p) {
    $stmtPatient->execute([
        ':id' => $p['id'],
        ':patient_no' => $p['patient_no'],
        ':envelope_no' => $p['envelope_no'],
        ':family_no' => $p['family_no'],
        ':first_name' => $p['first_name'],
        ':middle_name' => $p['middle_name'],
        ':last_name' => $p['last_name'],
        ':suffix' => $p['suffix'],
        ':dob' => $p['dob'],
        ':sex' => $p['sex'],
        ':civil_status' => $p['civil_status'],
        ':civil_status_other' => $p['civil_status_other'],
        ':blood_type' => $p['blood_type'],
        ':religion' => $p['religion'],
        ':occupation' => $p['occupation'],
        ':education_attainment' => $p['education_attainment'],
        ':contact_no' => $p['contact_no'],
        ':barangay' => $p['barangay'],
        ':address' => $p['address'],
        ':phic_status' => $p['phic_status'],
        ':phic_type' => $p['phic_type'],
        ':philhealth_no' => $p['philhealth_no'],
        ':father_name' => $p['father_name'],
        ':father_dob' => $p['father_dob'],
        ':mother_name' => $p['mother_name'],
        ':mother_dob' => $p['mother_dob'],
        ':spouse_name' => $p['spouse_name'],
        ':spouse_dob' => $p['spouse_dob'],
        ':emergency_name' => $p['emergency_name'],
        ':emergency_relationship' => $p['emergency_relationship'],
        ':emergency_no' => $p['emergency_no'],
        ':deleted_at' => $p['deleted_at'] ?? null,
        ':deleted_by' => $p['deleted_by'] ?? null,
        ':archive_reason' => $p['archive_reason'] ?? null,
        ':created_by' => $p['created_by']
    ]);
}
$pdo->commit();
echo "  -> Inserted " . count($patientList) . " patient records successfully.\n\n";

// ------------------------------------------------------------------
// 5. WELL-BABY & PEDIATRIC GROWTH LOGS (app/Models/WellbabyRecord, ChildGrowthLog)
// ------------------------------------------------------------------
echo "Phase 4: Generating Well-Baby & Child Growth Monitoring Records...\n";

$stmtWb = $pdo->prepare("
    INSERT INTO wellbaby_records (
        patient_id, mother_patient_id, birth_time, birth_weight_kg, birth_length_cm,
        place_of_delivery, delivery_type, attended_by, newborn_screening_done,
        newborn_screening_date, newborn_screening_result, mother_cpab_tt,
        feeding_method, created_by, created_at
    ) VALUES (
        :patient_id, :mother_patient_id, :birth_time, :birth_weight_kg, :birth_length_cm,
        :place_of_delivery, :delivery_type, :attended_by, :newborn_screening_done,
        :newborn_screening_date, :newborn_screening_result, :mother_cpab_tt,
        :feeding_method, :created_by, :created_at
    )
");

$stmtCgl = $pdo->prepare("
    INSERT INTO child_growth_logs (
        wellbaby_id, log_date, age_months, weight_kg, height_cm, head_circumference_cm,
        chest_circumference_cm, temperature, feeding_method, vaccines_administered,
        vitamin_a_dose, deworming_dose, tcb_notes, recorded_by, created_at
    ) VALUES (
        :wellbaby_id, :log_date, :age_months, :weight_kg, :height_cm, :head_circumference_cm,
        :chest_circumference_cm, :temperature, :feeding_method, :vaccines_administered,
        :vitamin_a_dose, :deworming_dose, :tcb_notes, :recorded_by, :created_at
    )
");

$pdo->beginTransaction();
$wellbabyCount = 0;
$cglCount = 0;

foreach ($infantList as $inf) {
    // Find synthetic mother in the same family or female adult
    $motherId = $motherMapping[$inf['family_id']] ?? null;
    $bWeight = round(rand(250, 390) / 100, 2);
    $bLength = round(rand(470, 520) / 10, 1);
    $nbsDone = rand(0, 10) > 1 ? 1 : 0;
    $nbsDate = $nbsDone ? (new DateTime($inf['dob']))->add(new DateInterval('P3D'))->format('Y-m-d') : null;
    
    $stmtWb->execute([
        ':patient_id' => $inf['id'],
        ':mother_patient_id' => $motherId,
        ':birth_time' => sprintf('%02d:%02d:00', rand(0, 23), rand(0, 59)),
        ':birth_weight_kg' => $bWeight,
        ':birth_length_cm' => $bLength,
        ':place_of_delivery' => pick(['Lying-in', 'Hospital', 'Home']),
        ':delivery_type' => pick(['Normal Spontaneous Delivery (NSD)', 'Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)']),
        ':attended_by' => pick(['Midwife', 'Midwife', 'Doctor', 'Nurse']),
        ':newborn_screening_done' => $nbsDone,
        ':newborn_screening_date' => $nbsDate,
        ':newborn_screening_result' => $nbsDone ? 'Normal / Negative' : null,
        ':mother_cpab_tt' => 'CPAB - Protected at Birth (TT2+)',
        ':feeding_method' => pick(['LAM / Exclusive Breastfeeding', 'LAM / Exclusive Breastfeeding', 'Mixed', 'Bottle Feed']),
        ':created_by' => $midwifeUserId,
        ':created_at' => $inf['dob'] . ' 10:00:00'
    ]);
    
    $wbId = $pdo->lastInsertId();
    $wellbabyCount++;

    // Calculate age in months
    $bDate = new DateTime($inf['dob']);
    $diff = $bDate->diff($today);
    $totalAgeMonths = ($diff->y * 12) + $diff->m;
    $numLogs = min(max(1, $totalAgeMonths), 6);

    $curWeight = $bWeight;
    $curHeight = $bLength;

    for ($m = 1; $m <= $numLogs; $m++) {
        $curWeight += round(rand(40, 80) / 100, 2); // gains ~0.4-0.8 kg/month
        $curHeight += round(rand(15, 30) / 10, 1);  // gains ~1.5-3.0 cm/month
        $logDate = (clone $bDate)->add(new DateInterval("P{$m}M"))->format('Y-m-d');
        if ($logDate > '2026-09-24') continue;

        $stmtCgl->execute([
            ':wellbaby_id' => $wbId,
            ':log_date' => $logDate,
            ':age_months' => $m,
            ':weight_kg' => $curWeight,
            ':height_cm' => $curHeight,
            ':head_circumference_cm' => round(34.0 + ($m * 0.7), 1),
            ':chest_circumference_cm' => round(33.0 + ($m * 0.7), 1),
            ':temperature' => round(rand(364, 372) / 10, 1),
            ':feeding_method' => ($m <= 6) ? 'LAM / Exclusive Breastfeeding' : 'Mixed',
            ':vaccines_administered' => ($m == 1) ? 'HepB, BCG' : (($m == 2) ? 'Penta 1, OPV 1, PCV 1' : 'Routine follow-up'),
            ':vitamin_a_dose' => ($m >= 6 && $m % 6 == 0) ? 1 : 0,
            ':deworming_dose' => ($m >= 12 && $m % 6 == 0) ? 1 : 0,
            ':tcb_notes' => 'Child thriving, milestones on schedule.',
            ':recorded_by' => $midwifeUserId,
            ':created_at' => $logDate . ' 09:30:00'
        ]);
        $cglCount++;
    }
}
$pdo->commit();
echo "  -> Created {$wellbabyCount} Well-Baby records and {$cglCount} Child Growth Logs.\n\n";

// ------------------------------------------------------------------
// 6. MATERNAL & PRENATAL CARE RECORDS (app/Models/PrenatalRecord, PrenatalVisit)
// ------------------------------------------------------------------
echo "Phase 5: Generating Maternal Episodes, Serial Prenatal Visits & Obstetric History...\n";

$stmtPr = $pdo->prepare("
    INSERT INTO prenatal_records (
        patient_id, husband_name, gravida, para, term_births, preterm_births,
        abortions, living_children, lmp, edc, is_active, delivery_date, delivery_outcome,
        notes, created_by, created_at
    ) VALUES (
        :patient_id, :husband_name, :gravida, :para, :term_births, :preterm_births,
        :abortions, :living_children, :lmp, :edc, :is_active, :delivery_date, :delivery_outcome,
        :notes, :created_by, :created_at
    )
");

$stmtPv = $pdo->prepare("
    INSERT INTO prenatal_visits (
        prenatal_id, visit_date, chief_complaint, aog_weeks, bp_systolic, bp_diastolic,
        weight_kg, height_cm, fetal_heart_tone, fundal_height_cm, fetal_presentation,
        tcb, remarks, attended_by, created_at
    ) VALUES (
        :prenatal_id, :visit_date, :chief_complaint, :aog_weeks, :bp_systolic, :bp_diastolic,
        :weight_kg, :height_cm, :fetal_heart_tone, :fundal_height_cm, :fetal_presentation,
        :tcb, :remarks, :attended_by, :created_at
    )
");

$stmtPoh = $pdo->prepare("
    INSERT INTO past_obstetric_histories (
        patient_id, gravida_no, delivery_type, infant_sex, place_of_delivery,
        year_delivered, attended_by, status, birth_date, tt_status, created_at
    ) VALUES (
        :patient_id, :gravida_no, :delivery_type, :infant_sex, :place_of_delivery,
        :year_delivered, :attended_by, :status, :birth_date, :tt_status, NOW()
    )
");

$pdo->beginTransaction();
$prenatalRecordCount = 0;
$prenatalVisitCount = 0;
$pohCount = 0;

// Pick 26 adult females for prenatal tracking
$prenatalCandidates = array_slice($femaleAdultList, 0, 26);

foreach ($prenatalCandidates as $idx => $mom) {
    $isActive = ($idx < 16) ? 1 : 0; // 16 active pregnancies, 10 completed
    $gravida = rand(1, 4);
    $para = $gravida - 1;
    $termBirths = $para;
    $pretermBirths = 0;
    $abortions = 0;
    $living = $termBirths;

    if ($isActive) {
        // Active pregnancy: LMP between 8 and 35 weeks ago
        $weeksAgo = rand(8, 35);
        $lmpObj = (clone $today)->sub(new DateInterval("P{$weeksAgo}W"));
        $lmp = $lmpObj->format('Y-m-d');
        // Naegele's rule: +1 year - 3 months + 7 days (~280 days)
        $edc = (clone $lmpObj)->add(new DateInterval('P280D'))->format('Y-m-d');
        $deliveryDate = null;
        $deliveryOutcome = null;
        $notes = "Active prenatal monitoring. Gravida {$gravida} Para {$para}.";
    } else {
        // Completed past pregnancy
        $delDateObj = (clone $today)->sub(new DateInterval('P' . rand(60, 240) . 'D'));
        $deliveryDate = $delDateObj->format('Y-m-d');
        $lmp = (clone $delDateObj)->sub(new DateInterval('P280D'))->format('Y-m-d');
        $edc = $delDateObj->format('Y-m-d');
        $deliveryOutcome = 'Live Birth';
        $notes = "Completed episode. Delivered healthy infant via spontaneous delivery.";
    }

    $stmtPr->execute([
        ':patient_id' => $mom['id'],
        ':husband_name' => $mom['spouse_name'] ?: "Jose {$mom['last_name']}",
        ':gravida' => $gravida,
        ':para' => $para,
        ':term_births' => $termBirths,
        ':preterm_births' => $pretermBirths,
        ':abortions' => $abortions,
        ':living_children' => $living,
        ':lmp' => $lmp,
        ':edc' => $edc,
        ':is_active' => $isActive,
        ':delivery_date' => $deliveryDate,
        ':delivery_outcome' => $deliveryOutcome,
        ':notes' => $notes,
        ':created_by' => $midwifeUserId,
        ':created_at' => $lmp . ' 08:00:00'
    ]);
    $prId = $pdo->lastInsertId();
    $prenatalRecordCount++;

    // Generate past obstetric histories if Gravida > 1
    if ($gravida > 1) {
        for ($g = 1; $g < $gravida; $g++) {
            $yearDel = 2026 - ($gravida - $g) * 2;
            $stmtPoh->execute([
                ':patient_id' => $mom['id'],
                ':gravida_no' => $g,
                ':delivery_type' => ($g % 3 == 0) ? 'CS' : 'NSD',
                ':infant_sex' => ($g % 2 == 0) ? 'Male' : 'Female',
                ':place_of_delivery' => 'Sinalhan Lying-in Clinic',
                ':year_delivered' => $yearDel,
                ':attended_by' => 'Juana Dela Cruz (Midwife)',
                ':status' => 'Alive',
                ':birth_date' => "{$yearDel}-05-15",
                ':tt_status' => "TT{$g} given"
            ]);
            $pohCount++;
        }
    }

    // Generate 2 to 5 serial visits for active pregnancies
    if ($isActive) {
        $numVisits = rand(2, 5);
        $lmpTime = new DateTime($lmp);
        for ($v = 1; $v <= $numVisits; $v++) {
            $aog = round(10 + ($v * 5) + rand(0, 10) / 10, 1);
            $vDate = (clone $lmpTime)->add(new DateInterval('P' . round($aog * 7) . 'D'))->format('Y-m-d');
            if ($vDate > '2026-09-24') continue;

            $fht = ($aog >= 16) ? rand(135, 155) : null;
            $fundal = ($aog >= 14) ? round($aog - 1 + (rand(0, 20) / 10), 1) : null;
            $pres = ($aog >= 32) ? 'Cephalic' : (($aog >= 24) ? pick(['Cephalic', 'Breech']) : 'Undetermined');

            $stmtPv->execute([
                ':prenatal_id' => $prId,
                ':visit_date' => $vDate,
                ':chief_complaint' => ($v == 1) ? 'First prenatal checkup' : 'Routine prenatal follow-up',
                ':aog_weeks' => $aog,
                ':bp_systolic' => rand(105, 125),
                ':bp_diastolic' => rand(68, 82),
                ':weight_kg' => round(52.0 + ($v * 1.5), 1),
                ':height_cm' => 152.0,
                ':fetal_heart_tone' => $fht,
                ':fundal_height_cm' => $fundal,
                ':fetal_presentation' => $pres,
                ':tcb' => "Td Dose {$v} received",
                ':remarks' => 'Maternal vitals stable. Prescribed prenatal vitamins (Ferrous sulfate + folic acid).',
                ':attended_by' => $midwifeUserId,
                ':created_at' => $vDate . ' 10:15:00'
            ]);
            $prenatalVisitCount++;
        }
    }
}
$pdo->commit();
echo "  -> Created {$prenatalRecordCount} Prenatal episodes, {$prenatalVisitCount} Serial visits & {$pohCount} Obstetric records.\n\n";

// ------------------------------------------------------------------
// 7. PATIENT MEDICAL HISTORIES, CONDITIONS & SURGERIES
// ------------------------------------------------------------------
echo "Phase 6: Generating Annex A1 IHP Medical Histories, Conditions & Surgeries...\n";

$stmtPmh = $pdo->prepare("
    INSERT INTO patient_medical_histories (
        patient_id, smoking_status, smoking_pack_years, alcohol_status,
        alcohol_bottles_per_day, illicit_drugs, menarche_age, sexual_onset_age,
        lmp, period_duration_days, cycle_interval_days, pads_per_day,
        is_menopausal, menopause_age, birth_control_method, baseline_bp_systolic,
        baseline_bp_diastolic, baseline_heart_rate, baseline_respiratory_rate,
        baseline_height, baseline_weight, baseline_waist_circumference,
        gravida, para, delivery_type, term_births, preterm_births, abortions,
        living_children, pre_eclampsia, fp_counselling, updated_by
    ) VALUES (
        :patient_id, :smoking_status, :smoking_pack_years, :alcohol_status,
        :alcohol_bottles_per_day, :illicit_drugs, :menarche_age, :sexual_onset_age,
        :lmp, :period_duration_days, :cycle_interval_days, :pads_per_day,
        :is_menopausal, :menopause_age, :birth_control_method, :baseline_bp_systolic,
        :baseline_bp_diastolic, :baseline_heart_rate, :baseline_respiratory_rate,
        :baseline_height, :baseline_weight, :baseline_waist_circumference,
        :gravida, :para, :delivery_type, :term_births, :preterm_births, :abortions,
        :living_children, :pre_eclampsia, :fp_counselling, :updated_by
    )
");

$stmtCond = $pdo->prepare("
    INSERT INTO patient_conditions (
        patient_id, condition_type, condition_name, remarks
    ) VALUES (
        :patient_id, :condition_type, :condition_name, :remarks
    )
");

$stmtSurg = $pdo->prepare("
    INSERT INTO patient_surgeries (
        patient_id, procedure_name, surgery_date, hospital
    ) VALUES (
        :patient_id, :procedure_name, :surgery_date, :hospital
    )
");

$pdo->beginTransaction();
$pmhCount = 0;
$condCount = 0;
$surgCount = 0;

// Populate PMH for adults and seniors (~160 patients)
$eligiblePmh = array_merge($femaleAdultList, $maleAdultList, $seniorList);
shuffle($eligiblePmh);
$selectedPmh = array_slice($eligiblePmh, 0, 140);

foreach ($selectedPmh as $p) {
    $isFemale = ($p['sex'] === 'Female');
    $age = intval(substr($today->format('Y-m-d'), 0, 4)) - intval(substr($p['dob'], 0, 4));
    
    $smoking = ($p['sex'] === 'Male' && rand(0, 10) > 4) ? 'Yes' : (($age > 50 && rand(0, 10) > 6) ? 'Quit' : 'Never');
    $packYears = ($smoking === 'Yes') ? rand(5, 25) : (($smoking === 'Quit') ? rand(10, 20) : null);
    
    $alcohol = ($p['sex'] === 'Male' && rand(0, 10) > 5) ? 'Yes' : 'Never';
    $bottles = ($alcohol === 'Yes') ? rand(1, 3) : null;

    $isMeno = ($isFemale && $age >= 50) ? 1 : 0;
    
    $stmtPmh->execute([
        ':patient_id' => $p['id'],
        ':smoking_status' => $smoking,
        ':smoking_pack_years' => $packYears,
        ':alcohol_status' => $alcohol,
        ':alcohol_bottles_per_day' => $bottles,
        ':illicit_drugs' => 0,
        ':menarche_age' => $isFemale ? rand(11, 14) : null,
        ':sexual_onset_age' => $isFemale ? rand(18, 24) : null,
        ':lmp' => ($isFemale && !$isMeno) ? '2026-08-28' : null,
        ':period_duration_days' => $isFemale ? rand(3, 5) : null,
        ':cycle_interval_days' => $isFemale ? 28 : null,
        ':pads_per_day' => $isFemale ? rand(2, 4) : null,
        ':is_menopausal' => $isMeno,
        ':menopause_age' => $isMeno ? rand(48, 52) : null,
        ':birth_control_method' => $isFemale ? pick(['DMPA Injectable', 'Pills', 'BTL', 'Implant', 'None']) : null,
        ':baseline_bp_systolic' => ($age >= 60) ? rand(130, 160) : rand(110, 128),
        ':baseline_bp_diastolic' => ($age >= 60) ? rand(80, 95) : rand(70, 84),
        ':baseline_heart_rate' => rand(68, 88),
        ':baseline_respiratory_rate' => rand(16, 20),
        ':baseline_height' => $isFemale ? round(rand(148, 162), 2) : round(rand(160, 178), 2),
        ':baseline_weight' => round(rand(50, 80), 2),
        ':baseline_waist_circumference' => round(rand(70, 95), 2),
        ':gravida' => $isFemale ? rand(1, 3) : null,
        ':para' => $isFemale ? rand(1, 3) : null,
        ':delivery_type' => $isFemale ? 'NSD' : null,
        ':term_births' => $isFemale ? rand(1, 3) : null,
        ':preterm_births' => 0,
        ':abortions' => 0,
        ':living_children' => $isFemale ? rand(1, 3) : null,
        ':pre_eclampsia' => 0,
        ':fp_counselling' => $isFemale ? 1 : 0,
        ':updated_by' => pick($allUserIds)
    ]);
    $pmhCount++;

    // Add Past or Family Conditions for seniors & some adults
    if ($age >= 45) {
        $commonConditions = ['Essential Hypertension', 'Type 2 Diabetes Mellitus', 'Bronchial Asthma', 'Hypercholesterolemia', 'Osteoarthritis'];
        $selectedCond = pick($commonConditions);
        
        $stmtCond->execute([
            ':patient_id' => $p['id'],
            ':condition_type' => 'Past',
            ':condition_name' => $selectedCond,
            ':remarks' => 'Maintenance medications managed at health center.'
        ]);
        $condCount++;

        // Family condition
        $stmtCond->execute([
            ':patient_id' => $p['id'],
            ':condition_type' => 'Family',
            ':condition_name' => pick(['Hypertension', 'Diabetes Mellitus', 'Cerebrovascular Disease (Stroke)']),
            ':remarks' => 'Positive hereditary family history.'
        ]);
        $condCount++;
    }

    // Add surgeries for some patients
    if ($age >= 50 && rand(0, 10) > 6) {
        $surgeries = [
            'Appendectomy' => 'Santa Rosa Community Hospital',
            'Caesarean Section' => 'Laguna Provincial Hospital',
            'Cholecystectomy' => 'Ospital ng Santa Rosa',
            'Bilateral Tubal Ligation' => 'Sinalhan Lying-in'
        ];
        $sName = array_rand($surgeries);
        $sHosp = $surgeries[$sName];

        $stmtSurg->execute([
            ':patient_id' => $p['id'],
            ':procedure_name' => $sName,
            ':surgery_date' => (2026 - rand(3, 15)) . '-' . sprintf('%02d', rand(1, 12)) . '-10',
            ':hospital' => $sHosp
        ]);
        $surgCount++;
    }
}
$pdo->commit();
echo "  -> Created {$pmhCount} Medical Histories, {$condCount} Conditions & {$surgCount} Surgeries.\n\n";

// ------------------------------------------------------------------
// 8. VITAL SIGNS, CLINICAL CONSULTATIONS & PRESCRIPTIONS
// ------------------------------------------------------------------
echo "Phase 7: Generating Vital Signs, SOAP Consultations & Prescriptions...\n";

$stmtVitals = $pdo->prepare("
    INSERT INTO vital_signs (
        id, patient_id, bp_systolic, bp_diastolic, heart_rate, respiratory_rate,
        temperature, weight, height, bmi, waist_circumference, oxygen_saturation,
        notes, recorded_by, recorded_at
    ) VALUES (
        :id, :patient_id, :bp_systolic, :bp_diastolic, :heart_rate, :respiratory_rate,
        :temperature, :weight, :height, :bmi, :waist_circumference, :oxygen_saturation,
        :notes, :recorded_by, :recorded_at
    )
");

$stmtConsult = $pdo->prepare("
    INSERT INTO consultations (
        id, patient_id, vital_signs_id, subjective, objective, assessment,
        plan, status, consulted_by, consulted_at, created_by, created_at
    ) VALUES (
        :id, :patient_id, :vital_signs_id, :subjective, :objective, :assessment,
        :plan, :status, :consulted_by, :consulted_at, :created_by, :created_at
    )
");

$stmtRx = $pdo->prepare("
    INSERT INTO prescriptions (
        consultation_id, patient_id, medicine_name, dosage, frequency,
        duration, instructions, prescribed_by, prescribed_at
    ) VALUES (
        :consultation_id, :patient_id, :medicine_name, :dosage, :frequency,
        :duration, :instructions, :prescribed_by, :prescribed_at
    )
");

$clinicalCases = [
    [
        'subjective' => 'Patient complains of dry cough, low-grade fever, nasal congestion, and mild sore throat for 3 days. No dyspnea.',
        'objective' => 'Throat: slightly hyperemic posterior pharyngeal wall. Lungs: clear breath sounds, equal chest expansion, no wheezes or rales.',
        'assessment' => 'Acute Upper Respiratory Tract Infection (URTI) / Nasopharyngitis',
        'plan' => 'Hydration, oral fluids, rest. Prescribed paracetamol for fever and cetirizine for rhinorrhea. Advised to return if persistent fever >3 days.',
        'meds' => [
            ['Paracetamol', '500mg tablet', '1 tablet every 4 hours PRN', '3 days', 'Take after meals for fever or headache.'],
            ['Cetirizine', '10mg tablet', '1 tablet once daily at bedtime', '5 days', 'Take at night; may cause mild drowsiness.']
        ]
    ],
    [
        'subjective' => 'Patient came in for routine hypertension follow-up and monthly maintenance refill. Occasional morning occipital heaviness.',
        'objective' => 'CVS: Adynamic precordium, normal rate and regular rhythm. No ankle edema. JVP not distended.',
        'assessment' => 'Essential Hypertension - Stage 2 (Uncontrolled)',
        'plan' => 'Reinforced low-salt, low-fat diet. Continued Amlodipine, added Losartan 50mg. Advised daily home BP logging.',
        'meds' => [
            ['Amlodipine Besylate', '5mg tablet', '1 tablet once daily in the morning', '30 days', 'Take consistently every morning with or without food.'],
            ['Losartan Potassium', '50mg tablet', '1 tablet once daily in the evening', '30 days', 'Take once daily before sleep.']
        ]
    ],
    [
        'subjective' => 'Follow-up for Type 2 Diabetes Mellitus. Patient reports fasting blood sugar range 120-145 mg/dL. No dizziness or hypoglycemia.',
        'objective' => 'Conscious, coherent. Feet: intact sensation, no foot ulcers or calluses. Peripheral pulses palpable.',
        'assessment' => 'Type 2 Diabetes Mellitus - Controlled on Oral Hypoglycemic Agents',
        'plan' => 'Maintain diabetic diet, regular 30-min walking. Continue Metformin. Scheduled for quarterly HbA1c screening next month.',
        'meds' => [
            ['Metformin Hydrochloride', '500mg tablet', '1 tablet twice daily with meals', '30 days', 'Take immediately after breakfast and dinner.']
        ]
    ],
    [
        'subjective' => '3 episodes of watery, non-bloody bowel movements since early morning accompanied by crampy periumbilical abdominal pain and mild nausea.',
        'objective' => 'Abdomen: soft, normoactive bowel sounds, mild generalized tenderness without peritoneal signs. Good skin turgor.',
        'assessment' => 'Acute Gastroenteritis (AGE) with Mild Dehydration',
        'plan' => 'Oral Rehydration Solution (ORS) replacement therapy after every stool. Prescribed zinc supplement and antispasmodic.',
        'meds' => [
            ['Oral Rehydration Salts (ORS)', '20.5g sachet', '1 sachet dissolved in 1L clean water', '3 days', 'Drink 200mL after each loose watery stool.'],
            ['Hyoscine N-butylbromide', '10mg tablet', '1 tablet 3 times a day PRN', '3 days', 'Take for severe abdominal cramps.']
        ]
    ],
    [
        'subjective' => 'Wheezing and shortness of breath triggered by cold weather and dust exposure. History of bronchial asthma since childhood.',
        'objective' => 'Chest: symmetric expansion, prolonged expiratory phase, bilateral scattered musical expiratory wheezes.',
        'assessment' => 'Bronchial Asthma in Mild Acute Exacerbation',
        'plan' => 'Administered Salbutamol nebulization in clinic with good relief. Prescribed oral bronchodilator and instructed on asthma trigger avoidance.',
        'meds' => [
            ['Salbutamol Sulfate', '2mg/5mL syrup', '1 teaspoon 3 times a day', '5 days', 'Take for coughing or tightness of chest.']
        ]
    ],
    [
        'subjective' => 'Painful, itchy red bumps and rash on bilateral lower legs for 4 days after wading in floodwater/canal.',
        'objective' => 'Skin: multiple erythematous papules and pustules with honey-colored crusting on anterior tibia. No systemic fever.',
        'assessment' => 'Bacterial Folliculitis / Impetigo Contagiosa',
        'plan' => 'Gentle warm soap wash. Prescribed systemic antibiotic and topical mupirocin ointment.',
        'meds' => [
            ['Cloxacillin', '500mg capsule', '1 capsule 4 times a day', '7 days', 'Take 1 hour before meals on an empty stomach.'],
            ['Mupirocin 2% Ointment', '5g tube', 'Apply thin layer twice daily', '7 days', 'Apply to affected skin lesions after washing with soap.']
        ]
    ],
    [
        'subjective' => 'Dysuria (pain on urination), increased urinary frequency, and lower abdominal heaviness for 2 days. No flank pain or fever.',
        'objective' => 'Abdomen: soft, tenderness on suprapubic palpation. No costovertebral angle (CVA) tenderness.',
        'assessment' => 'Acute Uncomplicated Urinary Tract Infection (Cystitis)',
        'plan' => 'Advised increased water intake (2-3 liters/day). Prescribed oral Co-amoxiclav. Urinalysis ordered for monitoring.',
        'meds' => [
            ['Co-amoxiclav (Amoxicillin + Clavulanate)', '625mg tablet', '1 tablet twice daily', '7 days', 'Take with meals every 12 hours. Complete full 7-day course.']
        ]
    ]
];

$pdo->beginTransaction();
$vitalsCount = 0;
$consultCount = 0;
$rxCount = 0;

$vitalIdCounter = 1;
$consultIdCounter = 1;

// Generate 260 Vital Signs and 180 Consultations
for ($c = 1; $c <= 200; $c++) {
    // Pick patient (excluding infant minors for standard adult consults, or pick specifically)
    $patient = $patientList[array_rand($patientList)];
    $pId = $patient['id'];
    $age = intval(substr($today->format('Y-m-d'), 0, 4)) - intval(substr($patient['dob'], 0, 4));

    // Date of encounter between 60 days ago and today
    $daysAgo = rand(0, 60);
    $encDate = (clone $today)->sub(new DateInterval("P{$daysAgo}D"))->format('Y-m-d');
    $encTime = sprintf('%02d:%02d:00', rand(8, 15), rand(0, 59));
    $encTimestamp = "{$encDate} {$encTime}";

    // Generate Vitals
    $isHypertensive = ($age >= 50 && rand(0, 10) > 4);
    $isFever = (rand(0, 10) > 8);
    $sbp = $isHypertensive ? rand(140, 175) : rand(105, 128);
    $dbp = $isHypertensive ? rand(90, 105) : rand(65, 82);
    $temp = $isFever ? round(rand(378, 391) / 10, 1) : round(rand(362, 372) / 10, 1);
    $hr = $isFever ? rand(95, 115) : rand(68, 86);
    $rr = rand(16, 22);
    $ht = round(rand(148, 175), 1);
    $wt = round(rand(45, 85), 1);
    $bmi = round($wt / (($ht / 100) * ($ht / 100)), 2);

    $vId = $vitalIdCounter++;
    $stmtVitals->execute([
        ':id' => $vId,
        ':patient_id' => $pId,
        ':bp_systolic' => $sbp,
        ':bp_diastolic' => $dbp,
        ':heart_rate' => $hr,
        ':respiratory_rate' => $rr,
        ':temperature' => $temp,
        ':weight' => $wt,
        ':height' => $ht,
        ':bmi' => $bmi,
        ':waist_circumference' => round(rand(70, 95), 1),
        ':oxygen_saturation' => rand(96, 99),
        ':notes' => $isHypertensive ? 'Elevated blood pressure flagged during triage.' : 'Normal baseline vitals.',
        ':recorded_by' => 2, // Maria Santos (BHW)
        ':recorded_at' => $encTimestamp
    ]);
    $vitalsCount++;

    // Generate Consultation (for ~180 cases)
    if ($c <= 180) {
        $case = $clinicalCases[($c - 1) % count($clinicalCases)];
        $cId = $consultIdCounter++;
        $doctorUserId = pick([1, 22, 29]); // IT / Admin physicians

        $stmtConsult->execute([
            ':id' => $cId,
            ':patient_id' => $pId,
            ':vital_signs_id' => $vId,
            ':subjective' => $case['subjective'],
            ':objective' => "BP {$sbp}/{$dbp} mmHg, PR {$hr} bpm, RR {$rr} cpm, Temp {$temp}°C. " . $case['objective'],
            ':assessment' => $case['assessment'],
            ':plan' => $case['plan'],
            ':status' => 'Completed',
            ':consulted_by' => $doctorUserId,
            ':consulted_at' => $encTimestamp,
            ':created_by' => $doctorUserId,
            ':created_at' => $encTimestamp
        ]);
        $consultCount++;

        // Add Prescriptions
        foreach ($case['meds'] as $m) {
            $stmtRx->execute([
                ':consultation_id' => $cId,
                ':patient_id' => $pId,
                ':medicine_name' => $m[0],
                ':dosage' => $m[1],
                ':frequency' => $m[2],
                ':duration' => $m[3],
                ':instructions' => $m[4],
                ':prescribed_by' => $doctorUserId,
                ':prescribed_at' => $encTimestamp
            ]);
            $rxCount++;
        }
    }
}
$pdo->commit();
echo "  -> Created {$vitalsCount} Vital Signs logs, {$consultCount} SOAP Consultations & {$rxCount} Prescriptions.\n\n";

// ------------------------------------------------------------------
// 9. IMMUNIZATIONS (Childhood EPI, Maternal Td, Senior Flu/Pneumo)
// ------------------------------------------------------------------
echo "Phase 8: Generating Childhood EPI, Maternal & Senior Immunizations...\n";

$stmtImm = $pdo->prepare("
    INSERT INTO immunizations (
        patient_id, vaccine_name, dose_number, administered_date,
        source, documentation_status, remarks, administered_by, created_at
    ) VALUES (
        :patient_id, :vaccine_name, :dose_number, :administered_date,
        :source, :documentation_status, :remarks, :administered_by, :created_at
    )
");

$pdo->beginTransaction();
$immCount = 0;

// Childhood EPI Schedule
$childVaccines = [
    ['BCG', 1, 0],
    ['Hepatitis B (Birth Dose)', 1, 0],
    ['Pentavalent (DTP-HepB-Hib)', 1, 45],
    ['Oral Polio Vaccine (OPV)', 1, 45],
    ['Pneumococcal Conjugate Vaccine (PCV)', 1, 45],
    ['Pentavalent (DTP-HepB-Hib)', 2, 75],
    ['Oral Polio Vaccine (OPV)', 2, 75],
    ['Pneumococcal Conjugate Vaccine (PCV)', 2, 75],
    ['Pentavalent (DTP-HepB-Hib)', 3, 105],
    ['Oral Polio Vaccine (OPV)', 3, 105],
    ['Inactivated Polio Vaccine (IPV)', 1, 105],
    ['Measles-Rubella (MCV1)', 1, 270],
    ['Measles-Mumps-Rubella (MCV2)', 2, 365]
];

foreach ($infantList as $inf) {
    $dobObj = new DateTime($inf['dob']);
    foreach ($childVaccines as $v) {
        $vDate = (clone $dobObj)->add(new DateInterval("P{$v[2]}D"))->format('Y-m-d');
        if ($vDate > '2026-09-24') continue;

        $stmtImm->execute([
            ':patient_id' => $inf['id'],
            ':vaccine_name' => $v[0],
            ':dose_number' => $v[1],
            ':administered_date' => $vDate,
            ':source' => 'Health Center',
            ':documentation_status' => 'Administered',
            ':remarks' => 'DOH National Expanded Program on Immunization (EPI).',
            ':administered_by' => $midwifeUserId,
            ':created_at' => $vDate . ' 10:00:00'
        ]);
        $immCount++;
    }
}

// Senior Flu and Pneumococcal Vaccines
foreach ($seniorList as $sen) {
    $stmtImm->execute([
        ':patient_id' => $sen['id'],
        ':vaccine_name' => 'Influenza (Trivalent/Quadrivalent Flu Vaccine)',
        ':dose_number' => 1,
        ':administered_date' => '2026-06-12',
        ':source' => 'Health Center',
        ':documentation_status' => 'Administered',
        ':remarks' => 'Annual DOH Senior Citizen Free Flu Immunization Program.',
        ':administered_by' => 2,
        ':created_at' => '2026-06-12 11:00:00'
    ]);
    $immCount++;

    if (rand(0, 10) > 4) {
        $stmtImm->execute([
            ':patient_id' => $sen['id'],
            ':vaccine_name' => 'Pneumococcal Polysaccharide Vaccine (PPV23)',
            ':dose_number' => 1,
            ':administered_date' => '2025-10-18',
            ':source' => 'Health Center',
            ':documentation_status' => 'Administered',
            ':remarks' => 'DOH Senior Pneumococcal Protection.',
            ':administered_by' => 2,
            ':created_at' => '2025-10-18 10:30:00'
        ]);
        $immCount++;
    }
}
$pdo->commit();
echo "  -> Created {$immCount} official Immunization records.\n\n";

// ------------------------------------------------------------------
// 10. APPOINTMENTS & DAILY QUEUE FLOW (app/Models/Appointment, QueueEntry)
// ------------------------------------------------------------------
echo "Phase 9: Generating Appointments & Real-Time Daily Queue Counters...\n";

$stmtAppt = $pdo->prepare("
    INSERT INTO appointments (
        patient_id, program_type, appointment_date, appointment_time,
        purpose, status, notes, created_by, created_at
    ) VALUES (
        :patient_id, :program_type, :appointment_date, :appointment_time,
        :purpose, :status, :notes, :created_by, :created_at
    )
");

$stmtQueue = $pdo->prepare("
    INSERT INTO queue_entries (
        patient_id, service_type, queue_date, queue_no, status,
        time_in, time_called, time_completed, created_by, created_at
    ) VALUES (
        :patient_id, :service_type, :queue_date, :queue_no, :status,
        :time_in, :time_called, :time_completed, :created_by, :created_at
    )
");

$stmtCounter = $pdo->prepare("
    INSERT INTO queue_daily_counters (queue_date, last_queue_no)
    VALUES (:queue_date, :last_queue_no)
    ON DUPLICATE KEY UPDATE last_queue_no = :last_queue_no_update
");

$programs = [
    'General OPD' => 'Doctor consultation and medical checkup',
    'Prenatal Care' => 'Monthly maternal checkup and fetal monitoring',
    'Well Baby Immunization' => 'Childhood growth check and EPI vaccine dose',
    'Senior Care' => 'Senior citizen hypertension and maintenance monitoring',
    'Family Planning' => 'Contraceptive counseling and supply refill',
    'Dental Care' => 'Dental prophylaxis and oral examination',
    'NCD / Hypertension' => 'Cardiovascular risk evaluation and blood sugar testing'
];

$pdo->beginTransaction();
$apptCount = 0;

// 1. Appointments (Past, Today, Upcoming)
$statuses = ['Scheduled', 'Scheduled', 'Completed', 'Completed', 'Cancelled', 'Missed'];

for ($a = 1; $a <= 85; $a++) {
    $patient = $patientList[array_rand($patientList)];
    $prog = array_rand($programs);
    $purpose = $programs[$prog];
    
    // Spread appointments between 20 days ago and 20 days ahead
    $dayOffset = rand(-20, 20);
    $apptDate = (clone $today)->modify("{$dayOffset} days")->format('Y-m-d');
    $apptTime = sprintf('%02d:%02d:00', rand(8, 14), pick([0, 15, 30, 45]));

    if ($dayOffset < 0) {
        $status = pick(['Completed', 'Completed', 'Missed', 'Cancelled']);
    } else {
        $status = 'Scheduled';
    }

    $stmtAppt->execute([
        ':patient_id' => $patient['id'],
        ':program_type' => $prog,
        ':appointment_date' => $apptDate,
        ':appointment_time' => $apptTime,
        ':purpose' => $purpose,
        ':status' => $status,
        ':notes' => ($status === 'Missed') ? 'Patient did not arrive during scheduled morning window.' : 'Standard clinic booking.',
        ':created_by' => 2,
        ':created_at' => $today->format('Y-m-d H:i:s')
    ]);
    $apptCount++;
}

// 2. Queue for Recent Days + Today (2026-09-24)
$queueCount = 0;
$recentDays = [-2, -1, 0];

foreach ($recentDays as $offset) {
    $qDate = (clone $today)->modify("{$offset} days")->format('Y-m-d');
    $numQueueToday = ($offset == 0) ? 18 : rand(15, 22);

    for ($q = 1; $q <= $numQueueToday; $q++) {
        $pat = $patientList[array_rand($patientList)];
        $service = array_rand($programs);
        $timeIn = sprintf('%02d:%02d:00', rand(8, 11), rand(0, 59));

        if ($offset < 0) {
            // Historical past days: all Completed or Cancelled
            $qStatus = ($q % 10 == 0) ? 'Cancelled' : 'Completed';
            $timeCalled = sprintf('%02d:%02d:00', rand(9, 12), rand(0, 59));
            $timeComp = sprintf('%02d:%02d:00', rand(10, 14), rand(0, 59));
        } else {
            // Today (2026-09-24): Active dynamic queue
            if ($q <= 7) {
                $qStatus = 'Completed';
                $timeCalled = sprintf('%02d:%02d:00', 8 + intval($q / 3), rand(0, 59));
                $timeComp = sprintf('%02d:%02d:00', 9 + intval($q / 3), rand(0, 59));
            } elseif ($q <= 10) {
                $qStatus = 'Serving';
                $timeCalled = '10:45:00';
                $timeComp = null;
            } elseif ($q <= 12) {
                $qStatus = 'Called';
                $timeCalled = '10:55:00';
                $timeComp = null;
            } else {
                $qStatus = 'Waiting';
                $timeCalled = null;
                $timeComp = null;
            }
        }

        $stmtQueue->execute([
            ':patient_id' => $pat['id'],
            ':service_type' => $service,
            ':queue_date' => $qDate,
            ':queue_no' => $q,
            ':status' => $qStatus,
            ':time_in' => $timeIn,
            ':time_called' => $timeCalled,
            ':time_completed' => $timeComp,
            ':created_by' => 2,
            ':created_at' => "{$qDate} {$timeIn}"
        ]);
        $queueCount++;
    }

    $stmtCounter->execute([
        ':queue_date' => $qDate,
        ':last_queue_no' => $numQueueToday,
        ':last_queue_no_update' => $numQueueToday
    ]);
}
$pdo->commit();
echo "  -> Created {$apptCount} Appointments, {$queueCount} Queue Entries and updated daily counters.\n\n";

// ------------------------------------------------------------------
// 11. PHILHEALTH PCB OBLIGATED SERVICES & ENCOUNTER LOGS
// ------------------------------------------------------------------
echo "Phase 10: Generating PhilHealth PCB Annual Surveillance & Encounter Logs...\n";

$stmtPcbObl = $pdo->prepare("
    INSERT INTO pcb_obligated_services (
        patient_id, service_year, is_hypertensive, bp_q1, bp_q2, bp_q3, bp_q4,
        cbe_q1, cbe_q2, cbe_q3, cbe_q4, via_q1, via_q2, via_q3, via_q4,
        remarks, updated_by
    ) VALUES (
        :patient_id, :service_year, :is_hypertensive, :bp_q1, :bp_q2, :bp_q3, :bp_q4,
        :cbe_q1, :cbe_q2, :cbe_q3, :cbe_q4, :via_q1, :via_q2, :via_q3, :via_q4,
        :remarks, :updated_by
    )
");

$stmtPcbLog = $pdo->prepare("
    INSERT INTO pcb_service_logs (
        patient_id, service_category, service_date, diagnosis, service_type,
        status_given, status_referred, referred_to, remarks, recorded_by
    ) VALUES (
        :patient_id, :service_category, :service_date, :diagnosis, :service_type,
        :status_given, :status_referred, :referred_to, :remarks, :recorded_by
    )
");

$pdo->beginTransaction();
$pcbOblCount = 0;
$pcbLogCount = 0;

$pcbCandidates = array_slice($seniorList, 0, 45);
foreach ($pcbCandidates as $sen) {
    $stmtPcbObl->execute([
        ':patient_id' => $sen['id'],
        ':service_year' => 2026,
        ':is_hypertensive' => 1,
        ':bp_q1' => '2026-02-14',
        ':bp_q2' => '2026-05-18',
        ':bp_q3' => '2026-08-20',
        ':bp_q4' => null,
        ':cbe_q1' => ($sen['sex'] === 'Female') ? '2026-02-14' : null,
        ':cbe_q2' => null,
        ':cbe_q3' => null,
        ':cbe_q4' => null,
        ':via_q1' => null,
        ':via_q2' => null,
        ':via_q3' => null,
        ':via_q4' => null,
        ':remarks' => 'Compliant with monthly health center BP monitoring.',
        ':updated_by' => 2
    ]);
    $pcbOblCount++;

    // Add PCB Encounter log (Diagnostic)
    $stmtPcbLog->execute([
        ':patient_id' => $sen['id'],
        ':service_category' => 'Diagnostic',
        ':service_date' => '2026-05-18',
        ':diagnosis' => 'Essential Hypertension / Diabetes Mellitus',
        ':service_type' => 'Fasting Blood Sugar (FBS)',
        ':status_given' => 1,
        ':status_referred' => 0,
        ':referred_to' => null,
        ':remarks' => 'FBS: 114 mg/dL. Well controlled.',
        ':recorded_by' => 2
    ]);
    $pcbLogCount++;
}
$pdo->commit();
echo "  -> Created {$pcbOblCount} PCB Obligated Annual Trackers & {$pcbLogCount} PCB Service Encounter Logs.\n\n";

// ------------------------------------------------------------------
// 12. LOG SYSTEM AUDIT EVENT
// ------------------------------------------------------------------
$stmtAudit = $pdo->prepare("
    INSERT INTO audit_logs (user_id, username, action, module, ip_address, user_agent, details, created_at)
    VALUES (:user_id, :username, :action, :module, :ip_address, :user_agent, :details, NOW())
");
$stmtAudit->execute([
    ':user_id' => 1,
    ':username' => 'admin',
    ':action' => 'DATABASE_RESET_AND_SYNTHETIC_SEED',
    ':module' => 'System',
    ':ip_address' => '127.0.0.1',
    ':user_agent' => 'CLI Database Seeder Script',
    ':details' => 'Reset database operational tables and populated 330 synthetic patients with full clinical history, consultations, prescriptions, maternal, wellbaby, and queue records while preserving all existing user accounts.'
]);

echo "====================================================================\n";
echo " SYNTHETIC DATA POPULATION COMPLETED SUCCESSFULLY!\n";
echo "====================================================================\n";
