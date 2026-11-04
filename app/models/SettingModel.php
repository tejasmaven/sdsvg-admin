<?php
/**
 * SettingModel — pdf_settings key-value store
 */
class SettingModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieve a single setting value.
     */
    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare(
            "SELECT setting_value FROM pdf_settings WHERE setting_key = :key"
        );
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();
        return ($value !== false) ? (string) $value : $default;
    }

    /**
     * Upsert a setting value.
     */
    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pdf_settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = :value2"
        );
        $stmt->execute([
            ':key'    => $key,
            ':value'  => $value,
            ':value2' => $value,
        ]);
    }

    /**
     * Return all settings as an associative array keyed by setting_key.
     */
    public function getAll(): array
    {
        $rows   = $this->db->query("SELECT setting_key, setting_value FROM pdf_settings")->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
