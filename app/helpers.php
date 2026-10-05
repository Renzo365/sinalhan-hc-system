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


