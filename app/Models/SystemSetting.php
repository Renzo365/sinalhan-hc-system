<?php

namespace App\Models;

use App\Core\Model;

class SystemSetting extends Model {

    /**
     * Get all settings grouped by category.
     *
     * @return array
     */
    public function getAllGrouped(): array {
        $stmt = $this->db->query("
            SELECT setting_key, setting_value, category, updated_at, updated_by
            FROM system_settings
            ORDER BY category ASC, setting_key ASC
        ");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $cat = $row['category'] ?: 'general';
            $grouped[$cat][$row['setting_key']] = $row['setting_value'];
        }

        return $grouped;
    }

    /**
     * Get all settings as a flat key-value dictionary.
     *
     * @return array
     */
    public function getAllFlat(): array {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM system_settings");
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }

    /**
     * Get a setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    }

    /**
     * Set/update a single setting.
     *
     * @param string $key
     * @param string|null $value
     * @param string $category
     * @param int|null $userId
     * @return bool
     */
    public function set(string $key, ?string $value, string $category = 'general', ?int $userId = null): bool {
        $stmt = $this->db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, category, updated_by)
            VALUES (:key, :value, :category, :user_id)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                category = VALUES(category),
                updated_by = VALUES(updated_by)
        ");
        return $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':category' => $category,
            ':user_id' => $userId
        ]);
    }

    /**
     * Batch update settings for a category.
     *
     * @param array $settings Key-value array of settings to update
     * @param string $category Category name
     * @param int|null $userId User ID performing update
     * @return bool
     */
    public function updateBatch(array $settings, string $category, ?int $userId = null): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, category, updated_by)
                VALUES (:key, :value, :category, :user_id)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    category = VALUES(category),
                    updated_by = VALUES(updated_by)
            ");

            foreach ($settings as $key => $value) {
                $stmt->execute([
                    ':key' => (string)$key,
                    ':value' => ($value !== null) ? (string)$value : null,
                    ':category' => $category,
                    ':user_id' => $userId
                ]);
            }

            $this->db->commit();

            // Refresh in-memory static cache
            if (function_exists('setting')) {
                setting(null, null, true);
            }

            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("SystemSetting updateBatch failed: " . $e->getMessage());
            return false;
        }
    }
}
