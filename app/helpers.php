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
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/sinalhan-hc-system/public/index.php';
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


