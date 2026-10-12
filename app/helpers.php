<?php

if (!function_exists('h')) {
    function h($string) {
        if (is_array($string)) {
            $parts = [];
            foreach ($string as $k => $v) {
                if (is_array($v)) {
                    $parts[] = h($v);
                } elseif ($v !== null && $v !== '') {
                    $parts[] = (string)$v;
                }
            }
            return htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Verify CSRF token from input, POST payload, or request header against session.
     *
     * @param string|null $token
     * @return bool
     */
    function verify_csrf_token(?string $token = null): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/sinalhealth/public/index.php';
        $basePath = str_replace('/index.php', '', $scriptName);
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path = '') {
        $cleanPath = ltrim($path, '/');
        $fullPath = dirname(__DIR__) . '/public/assets/' . $cleanPath;
        $ver = file_exists($fullPath) ? '?v=' . filemtime($fullPath) : '';
        return url('assets/' . $cleanPath) . $ver;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'], true);
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin() {
        return ($_SESSION['user_role'] ?? '') === 'super_admin';
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null) {
        static $config = null;
        if ($config === null) {
            $file = dirname(__DIR__) . '/config/app.php';
            $config = file_exists($file) ? require $file : [];
        }
        if ($key === null) {
            return $config;
        }
        $parts = explode('.', $key);
        $value = $config;
        foreach ($parts as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}

if (!function_exists('setting')) {
    /**
     * Retrieve a system setting value from system_settings table with memory caching.
     * Falls back to config() if unset or table unavailable.
     *
     * @param string|null $key
     * @param mixed $default
     * @param bool $refresh
     * @return mixed
     */
    function setting(?string $key = null, $default = null, bool $refresh = false) {
        static $settings = null;
        if ($settings === null || $refresh) {
            try {
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
                $settings = $stmt ? $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) : [];
            } catch (\Throwable $e) {
                $settings = [];
            }
        }

        if ($key === null) {
            return $settings;
        }

        if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '') {
            return $settings[$key];
        }

        return config($key, $default);
    }
}

if (!function_exists('user_pref')) {
    /**
     * Retrieve personal accessibility/workflow preference for current logged in user.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function user_pref(?string $key = null, $default = null) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION['user_preferences']) && isset($_SESSION['user_id'])) {
            try {
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT preferences FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $raw = $stmt->fetchColumn();
                $_SESSION['user_preferences'] = !empty($raw) ? json_decode($raw, true) : [];
            } catch (\Throwable $e) {
                $_SESSION['user_preferences'] = [];
            }
        }

        $prefs = $_SESSION['user_preferences'] ?? [];
        if ($key === null) {
            return $prefs;
        }

        return $prefs[$key] ?? $default;
    }
}

if (!function_exists('classify_bmi')) {
    /**
     * Classify Body Mass Index (BMI) using Philippine DOH & WHO Asia-Pacific Guidelines.
     * Cutoffs:
     * - Underweight: < 18.5
     * - Normal: 18.5 - 22.9
     * - Overweight: 23.0 - 27.4
     * - Obese: >= 27.5
     *
     * @param float|int|string|null $bmi
     * @return array ['label' => string, 'class' => string, 'badge' => string, 'category' => string]
     */
    function classify_bmi($bmi): array {
        if ($bmi === null || $bmi === '' || (float)$bmi <= 0) {
            return [
                'label' => '—',
                'class' => 'text-muted',
                'badge' => 'bg-light text-muted border',
                'category' => 'Unknown'
            ];
        }

        $val = (float)$bmi;
        if ($val < 18.5) {
            return [
                'label' => 'Underweight',
                'class' => 'text-info',
                'badge' => 'bg-info-subtle text-info border border-info-subtle',
                'category' => 'Underweight'
            ];
        } elseif ($val <= 22.9) {
            return [
                'label' => 'Normal',
                'class' => 'text-success',
                'badge' => 'bg-success-subtle text-success border border-success-subtle',
                'category' => 'Normal'
            ];
        } elseif ($val <= 27.4) {
            return [
                'label' => 'Overweight',
                'class' => 'text-warning',
                'badge' => 'bg-warning-subtle text-dark border border-warning-subtle',
                'category' => 'Overweight'
            ];
        } else {
            return [
                'label' => 'Obese',
                'class' => 'text-danger',
                'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'category' => 'Obese'
            ];
        }
    }
}

if (!function_exists('format_temperature')) {
    /**
     * Format body temperature to standard clinical single decimal precision (e.g. 37.5).
     *
     * @param float|int|string|null $temp
     * @return string|null Formatted temperature string or null if empty
     */
    function format_temperature($temp): ?string {
        if ($temp === null || trim((string)$temp) === '' || (float)$temp <= 0) {
            return null;
        }
        return number_format((float)$temp, 1, '.', '');
    }
}

if (!function_exists('classify_temperature')) {
    /**
     * Classify body temperature based on clinical vital sign triage protocols.
     * Cutoffs:
     * - Hypothermia: < 35.5 °C
     * - Normal: 35.5 - 37.5 °C
     * - Low Fever: 37.6 - 37.9 °C
     * - Fever: >= 38.0 °C
     * - High Fever: >= 39.0 °C
     *
     * @param float|int|string|null $temp
     * @return array
     */
    function classify_temperature($temp): array {
        $val = format_temperature($temp);
        if ($val === null) {
            return [
                'value' => null,
                'formatted' => '—',
                'label' => '—',
                'badge' => 'bg-light text-muted border',
                'pill' => '',
                'is_abnormal' => false
            ];
        }

        $f = (float)$val;
        if ($f >= 39.0) {
            return [
                'value' => $val,
                'formatted' => "{$val} °C",
                'label' => 'High Fever',
                'badge' => 'bg-danger text-white border border-danger',
                'pill' => '<span class="badge bg-danger text-white px-1">High Fever</span>',
                'is_abnormal' => true
            ];
        } elseif ($f >= 38.0) {
            return [
                'value' => $val,
                'formatted' => "{$val} °C",
                'label' => 'Fever',
                'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'pill' => '<span class="badge bg-danger text-white px-1">Fever</span>',
                'is_abnormal' => true
            ];
        } elseif ($f >= 37.6) {
            return [
                'value' => $val,
                'formatted' => "{$val} °C",
                'label' => 'Low Fever',
                'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'pill' => '<span class="badge bg-warning text-dark px-1">Low Fever</span>',
                'is_abnormal' => true
            ];
        } elseif ($f < 35.5) {
            return [
                'value' => $val,
                'formatted' => "{$val} °C",
                'label' => 'Hypothermia',
                'badge' => 'bg-info-subtle text-info border border-info-subtle',
                'pill' => '<span class="badge bg-info text-white px-1">Hypothermia</span>',
                'is_abnormal' => true
            ];
        } else {
            return [
                'value' => $val,
                'formatted' => "{$val} °C",
                'label' => 'Normal',
                'badge' => 'bg-light text-dark border',
                'pill' => '',
                'is_abnormal' => false
            ];
        }
    }
}

if (!function_exists('classify_bp')) {
    /**
     * Classify Blood Pressure based on Philippine Society of Hypertension & JNC 8 / AHA guidelines.
     * Cutoffs:
     * - Hypertensive Crisis: Systolic >= 180 or Diastolic >= 120
     * - Hypertension: Systolic >= 140 or Diastolic >= 90
     * - Pre-HTN (Elevated): Systolic 120-139 or Diastolic 80-89
     * - Hypotension: Systolic < 90 or Diastolic < 60
     * - Normal: Systolic 90-119 and Diastolic 60-79
     *
     * @param int|float|string|null $sys
     * @param int|float|string|null $dia
     * @return array
     */
    function classify_bp($sys, $dia): array {
        $s = (int)($sys ?? 0);
        $d = (int)($dia ?? 0);

        if ($s <= 0 || $d <= 0) {
            return [
                'sys' => null,
                'dia' => null,
                'formatted' => '—',
                'label' => '—',
                'badge' => 'bg-light text-muted border',
                'pill' => '',
                'is_abnormal' => false,
                'is_high' => false
            ];
        }

        $formatted = "{$s}/{$d} mmHg";

        if ($s >= 180 || $d >= 120) {
            return [
                'sys' => $s,
                'dia' => $d,
                'formatted' => $formatted,
                'label' => 'Crisis',
                'badge' => 'bg-danger text-white border border-danger',
                'pill' => '<span class="badge bg-danger text-white px-1">Crisis</span>',
                'is_abnormal' => true,
                'is_high' => true
            ];
        } elseif ($s >= 140 || $d >= 90) {
            return [
                'sys' => $s,
                'dia' => $d,
                'formatted' => $formatted,
                'label' => 'Hypertension',
                'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'pill' => '<span class="badge bg-danger text-white px-1">Hypertension</span>',
                'is_abnormal' => true,
                'is_high' => true
            ];
        } elseif (($s >= 120 && $s <= 139) || ($d >= 80 && $d <= 89)) {
            return [
                'sys' => $s,
                'dia' => $d,
                'formatted' => $formatted,
                'label' => 'Pre-HTN',
                'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'pill' => '<span class="badge bg-warning text-dark px-1">Pre-HTN</span>',
                'is_abnormal' => true,
                'is_high' => false
            ];
        } elseif ($s < 90 || $d < 60) {
            return [
                'sys' => $s,
                'dia' => $d,
                'formatted' => $formatted,
                'label' => 'Hypotension',
                'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'pill' => '<span class="badge bg-warning text-dark px-1">Hypotension</span>',
                'is_abnormal' => true,
                'is_high' => false
            ];
        } else {
            return [
                'sys' => $s,
                'dia' => $d,
                'formatted' => $formatted,
                'label' => 'Normal',
                'badge' => 'bg-light text-dark border',
                'pill' => '',
                'is_abnormal' => false,
                'is_high' => false
            ];
        }
    }
}

if (!function_exists('classify_heart_rate')) {
    /**
     * Classify resting heart/pulse rate.
     * Normal: 60 - 100 bpm. Tachycardia: > 100 bpm. Bradycardia: < 60 bpm.
     *
     * @param int|float|string|null $hr
     * @return array
     */
    function classify_heart_rate($hr): array {
        $val = (int)($hr ?? 0);
        if ($val <= 0) {
            return ['value' => null, 'formatted' => '—', 'label' => '—', 'badge' => 'bg-light text-muted border', 'pill' => '', 'is_abnormal' => false];
        }
        $formatted = "{$val} bpm";
        if ($val > 100) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Tachycardia', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'pill' => '<span class="badge bg-warning text-dark px-1">Tachycardia</span>', 'is_abnormal' => true];
        } elseif ($val < 60) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Bradycardia', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'pill' => '<span class="badge bg-warning text-dark px-1">Bradycardia</span>', 'is_abnormal' => true];
        }
        return ['value' => $val, 'formatted' => $formatted, 'label' => 'Normal', 'badge' => 'bg-light text-dark border', 'pill' => '', 'is_abnormal' => false];
    }
}

if (!function_exists('classify_respiratory_rate')) {
    /**
     * Classify respiratory rate.
     * Normal: 12 - 20 cpm. Tachypnea: > 20 cpm. Bradypnea: < 12 cpm.
     *
     * @param int|float|string|null $rr
     * @return array
     */
    function classify_respiratory_rate($rr): array {
        $val = (int)($rr ?? 0);
        if ($val <= 0) {
            return ['value' => null, 'formatted' => '—', 'label' => '—', 'badge' => 'bg-light text-muted border', 'pill' => '', 'is_abnormal' => false];
        }
        $formatted = "{$val} cpm";
        if ($val > 20) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Tachypnea', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'pill' => '<span class="badge bg-warning text-dark px-1">Tachypnea</span>', 'is_abnormal' => true];
        } elseif ($val < 12) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Bradypnea', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'pill' => '<span class="badge bg-warning text-dark px-1">Bradypnea</span>', 'is_abnormal' => true];
        }
        return ['value' => $val, 'formatted' => $formatted, 'label' => 'Normal', 'badge' => 'bg-light text-dark border', 'pill' => '', 'is_abnormal' => false];
    }
}

if (!function_exists('classify_spo2')) {
    /**
     * Classify pulse oximetry oxygen saturation.
     * Normal: >= 95%. Hypoxia risk: < 95%.
     *
     * @param int|float|string|null $spo2
     * @return array
     */
    function classify_spo2($spo2): array {
        $val = (int)($spo2 ?? 0);
        if ($val <= 0) {
            return ['value' => null, 'formatted' => '—', 'label' => '—', 'badge' => 'bg-light text-muted border', 'pill' => '', 'is_abnormal' => false];
        }
        $formatted = "{$val}%";
        if ($val < 95) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Hypoxia Risk', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle', 'pill' => '<span class="badge bg-danger text-white px-1">Hypoxia</span>', 'is_abnormal' => true];
        }
        return ['value' => $val, 'formatted' => $formatted, 'label' => 'Normal', 'badge' => 'bg-light text-dark border', 'pill' => '', 'is_abnormal' => false];
    }
}

if (!function_exists('classify_fht')) {
    /**
     * Classify Fetal Heart Tone (FHT) based on WHO & Philippine clinical standards.
     * Normal baseline: 110 - 160 bpm.
     * Fetal Tachycardia: > 160 bpm.
     * Fetal Bradycardia: < 110 bpm.
     *
     * @param int|float|string|null $fht
     * @return array
     */
    function classify_fht($fht): array {
        $val = (int)($fht ?? 0);
        if ($val <= 0) {
            return ['value' => null, 'formatted' => '—', 'label' => '—', 'badge' => 'badge bg-light text-dark border', 'pill' => '', 'is_abnormal' => false];
        }
        $formatted = "{$val} bpm";
        if ($val > 160) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Fetal Tachycardia', 'badge' => 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold', 'pill' => '<span class="badge bg-danger text-white px-1">Fetal Tachycardia</span>', 'is_abnormal' => true];
        } elseif ($val < 110) {
            return ['value' => $val, 'formatted' => $formatted, 'label' => 'Fetal Bradycardia', 'badge' => 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold', 'pill' => '<span class="badge bg-danger text-white px-1">Fetal Bradycardia</span>', 'is_abnormal' => true];
        }
        return ['value' => $val, 'formatted' => $formatted, 'label' => 'Normal FHT', 'badge' => 'badge bg-success-subtle text-success border border-success-subtle', 'pill' => '', 'is_abnormal' => false];
    }
}

if (!function_exists('calculate_age')) {
    /**
     * Calculate age in full completed years from date of birth.
     *
     * @param string|\DateTimeInterface|null $dob
     * @param string|\DateTimeInterface|null $referenceDate
     * @return int|null
     */
    function calculate_age($dob, $referenceDate = null): ?int {
        if (empty($dob) || $dob === '0000-00-00') {
            return null;
        }

        try {
            $dobObj = ($dob instanceof \DateTimeInterface) ? clone $dob : new \DateTime($dob);
            $refObj = $referenceDate 
                ? (($referenceDate instanceof \DateTimeInterface) ? clone $referenceDate : new \DateTime($referenceDate)) 
                : new \DateTime();

            $dobObj->setTime(0, 0, 0);
            $refObj->setTime(0, 0, 0);

            if ($dobObj > $refObj) {
                return 0;
            }

            return (int)$refObj->diff($dobObj)->y;
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('calculate_pediatric_age')) {
    /**
     * Calculate readable pediatric age with days/months/years for clinical records.
     * Guidelines:
     * - Newborn / < 1 month: e.g. "15 days old" or "Newborn (0 days)"
     * - < 1 year: e.g. "3 mos, 12 days (3.4 mos)" or "3 mos (3.0 mos)"
     * - >= 1 year: e.g. "3 yrs, 2 mos (38 mos)" or "1 yr (12 mos)"
     *
     * @param string|\DateTimeInterface|null $dob
     * @param string|\DateTimeInterface|null $referenceDate
     * @return string
     */
    function calculate_pediatric_age($dob, $referenceDate = null): string {
        if (empty($dob) || $dob === '0000-00-00') {
            return 'N/A';
        }

        try {
            $dobObj = ($dob instanceof \DateTimeInterface) ? clone $dob : new \DateTime($dob);
            $refObj = $referenceDate 
                ? (($referenceDate instanceof \DateTimeInterface) ? clone $referenceDate : new \DateTime($referenceDate)) 
                : new \DateTime();

            $dobObj->setTime(0, 0, 0);
            $refObj->setTime(0, 0, 0);

            if ($dobObj > $refObj) {
                return '0 days old';
            }

            $diff = $refObj->diff($dobObj);

            // < 1 month: show days
            if ($diff->y === 0 && $diff->m === 0) {
                $days = $diff->d;
                if ($days === 0) {
                    return 'Newborn (0 days)';
                }
                return $days === 1 ? '1 day old' : "{$days} days old";
            }

            // < 1 year: show months and days + decimal total months
            if ($diff->y === 0) {
                $months = $diff->m;
                $days = $diff->d;
                $totalMonths = round($months + ($days / 30.4375), 1);
                $moUnit = ($months === 1) ? 'mo' : 'mos';
                $dayUnit = ($days === 1) ? 'day' : 'days';
                if ($days > 0) {
                    return "{$months} {$moUnit}, {$days} {$dayUnit} ({$totalMonths} mos)";
                }
                return "{$months} {$moUnit} ({$totalMonths} mos)";
            }

            // >= 1 year: show years, months + total months in parentheses
            $years = $diff->y;
            $months = $diff->m;
            $totalMonths = ($years * 12) + $months;
            $yrLabel = $years === 1 ? '1 yr' : "{$years} yrs";
            $moLabel = $months > 0 ? (', ' . $months . ($months === 1 ? ' mo' : ' mos')) : '';

            return "{$yrLabel}{$moLabel} ({$totalMonths} mos)";
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

if (!function_exists('calculate_age_in_months')) {
    /**
     * Calculate exact pediatric age in months rounded to 1 decimal place.
     *
     * @param string|\DateTimeInterface|null $dob
     * @param string|\DateTimeInterface|null $referenceDate
     * @return float
     */
    function calculate_age_in_months($dob, $referenceDate = null): float {
        if (empty($dob) || $dob === '0000-00-00') {
            return 0.0;
        }

        try {
            $dobObj = ($dob instanceof \DateTimeInterface) ? clone $dob : new \DateTime($dob);
            $refObj = $referenceDate 
                ? (($referenceDate instanceof \DateTimeInterface) ? clone $referenceDate : new \DateTime($referenceDate)) 
                : new \DateTime();

            $dobObj->setTime(0, 0, 0);
            $refObj->setTime(0, 0, 0);

            if ($dobObj > $refObj) {
                return 0.0;
            }

            $diff = $refObj->diff($dobObj);
            $totalMonths = ($diff->y * 12) + $diff->m + ($diff->d / 30.4375);
            return (float)round($totalMonths, 1);
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}

if (!function_exists('format_patient_name')) {
    /**
     * Standard patient name formatter across SinalHealth.
     * Default format ($lastFirst = true): "Lastname, Firstname M. Suffix"
     * Alternate format ($lastFirst = false): "Firstname M. Lastname Suffix"
     * Handles missing names and joined appointment query aliases gracefully.
     *
     * @param array|object|null $patient
     * @param bool $lastFirst
     * @return string
     */
    function format_patient_name($patient, bool $lastFirst = true): string {
        if (empty($patient)) {
            return 'Unnamed Patient';
        }

        if (is_object($patient)) {
            $patient = (array)$patient;
        }

        if (!is_array($patient)) {
            return 'Unnamed Patient';
        }

        $first = trim($patient['first_name'] ?? $patient['patient_first'] ?? '');
        $last = trim($patient['last_name'] ?? $patient['patient_last'] ?? '');
        $middle = trim($patient['middle_name'] ?? $patient['patient_middle'] ?? '');
        $suffix = trim($patient['suffix'] ?? $patient['patient_suffix'] ?? '');

        $middleInitial = !empty($middle) ? mb_substr($middle, 0, 1) . '.' : '';

        if ($lastFirst) {
            if (!empty($last) && !empty($first)) {
                $name = $last . ', ' . $first;
                if (!empty($middleInitial)) {
                    $name .= ' ' . $middleInitial;
                }
                if (!empty($suffix)) {
                    $name .= ' ' . $suffix;
                }
                return $name;
            } elseif (!empty($last)) {
                return $last . (!empty($suffix) ? ' ' . $suffix : '');
            } elseif (!empty($first)) {
                return $first . (!empty($suffix) ? ' ' . $suffix : '');
            }
            return 'Unnamed Patient';
        } else {
            $parts = [];
            if (!empty($first)) $parts[] = $first;
            if (!empty($middleInitial)) $parts[] = $middleInitial;
            if (!empty($last)) $parts[] = $last;
            if (!empty($suffix)) $parts[] = $suffix;
            return !empty($parts) ? implode(' ', $parts) : 'Unnamed Patient';
        }
    }
}


