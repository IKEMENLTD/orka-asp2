<?php
/**
 * CSV Class - CSV file handling
 */
class CSV {
    public static function read($filename) {
        if (!file_exists($filename)) {
            return [];
        }
        $data = [];
        $handle = fopen($filename, 'r');
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        fclose($handle);
        return $data;
    }

    public static function write($filename, $data) {
        $handle = fopen($filename, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}
?>
