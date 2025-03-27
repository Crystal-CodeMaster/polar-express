<?php
// Database file
$databaseFile = 'admin_users.db';
// Backup file name (adds timestamp to avoid overwriting)
$backupFile = 'backup/admin_users_backup_' . date('Y-m-d_H-i-s') . '.db';

// Ensure backup directory exists
if (!is_dir('backup')) {
    mkdir('backup', 0777, true);
}

// Perform backup using SQLite3
if (copy($databaseFile, $backupFile)) {

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backupFile));
    
    // Clear output buffer and read the file
    ob_clean();
    flush();
    readfile($backupFile);
    
    // After download, clear specific table data
    $db = new SQLite3($databaseFile);
    $tables = ['rides', 'shift_availability_cache', 'volunteer_slots', 'volunteer_signups'];

    foreach ($tables as $table) {
        $query = "DELETE FROM $table;";
        $db->exec($query);
    }

    $db->close();
    exit;
} else {
    echo "Backup failed!";
}
