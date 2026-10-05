<?php
require_once __DIR__ . '/../app/helpers.php';

$refDate = '2026-10-04';

// Case 1: 15 days old
$dob1 = '2026-09-19';
$age1 = calculate_pediatric_age($dob1, $refDate);
echo "DOB: $dob1 -> $age1\n";
assert(str_contains($age1, '15 days old'));

// Case 2: 3 months 12 days old
$dob2 = '2026-06-22';
$age2 = calculate_pediatric_age($dob2, $refDate);
echo "DOB: $dob2 -> $age2\n";
assert(str_contains($age2, '3 mos, 12 days'));

// Case 3: 3 years 2 months old
$dob3 = '2023-08-04';
$age3 = calculate_pediatric_age($dob3, $refDate);
echo "DOB: $dob3 -> $age3\n";
assert(str_contains($age3, '3 yrs, 2 mos (38 mos)'));

// Case 4: 1 year old
$dob4 = '2025-10-04';
$age4 = calculate_pediatric_age($dob4, $refDate);
echo "DOB: $dob4 -> $age4\n";
assert(str_contains($age4, '1 yr (12 mos)'));

// Case 5: calculate_age_in_months
$m1 = calculate_age_in_months($dob1, $refDate);
$m2 = calculate_age_in_months($dob2, $refDate);
$m3 = calculate_age_in_months($dob3, $refDate);
echo "Months: m1=$m1, m2=$m2, m3=$m3\n";
// Case 6: calculate_age (full years)
$y1 = calculate_age($dob1, $refDate);
$y3 = calculate_age($dob3, $refDate);
$yAdult = calculate_age('1996-05-10', $refDate);
echo "Years: y1=$y1, y3=$y3, yAdult=$yAdult\n";
assert($y1 === 0);
assert($y3 === 3);
assert($yAdult === 30);
assert(calculate_age('') === null);
assert(calculate_age('0000-00-00') === null);

echo "All pediatric helper tests passed successfully!\n";
