<?php
/**
 * Column Builder Utility
 * 
 * Handles building column definitions for table operations
 */

class ColumnBuilder {
    /**
     * Build column definition from form data
     * 
     * @param array $data Column configuration data
     * @return string Column definition SQL
     */
    public static function buildDefinition($data) {
        $definition = $data['type'];
        
        // Add NOT NULL if not allowing null
        if (!$data['null']) {
            $definition .= ' NOT NULL';
        }
        
        // Add DEFAULT value
        if ($data['default'] !== null && $data['default'] !== '') {
            $definition .= ' DEFAULT ' . $data['default'];
        }
        
        // Add AUTO_INCREMENT
        if ($data['auto_increment']) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        // Add UNIQUE
        if ($data['unique']) {
            $definition .= ' UNIQUE';
        }
        
        // Add PRIMARY KEY
        if ($data['primary']) {
            $definition .= ' PRIMARY KEY';
        }
        
        // Add extra attributes
        if (!empty($data['extra'])) {
            $definition .= ' ' . $data['extra'];
        }
        
        return $definition;
    }
}
?>

