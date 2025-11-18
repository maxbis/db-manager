/**
 * Column Operations Module
 * Handles saving, deleting, and SQL generation for columns
 */

const ColumnOperations = {
    /**
     * Save column
     */
    save: function() {
        const formData = {
            name: $('#fieldName').val(),
            type: $('#fieldType').val(),
            default: $('#fieldDefault').val() || null,
            null: $('#attrNull').is(':checked'),
            primary: $('#attrPrimary').is(':checked'),
            unique: $('#attrUnique').is(':checked'),
            index: $('#attrIndex').is(':checked'),
            auto_increment: $('#attrAutoIncrement').is(':checked'),
            extra: $('#fieldExtra').val()
        };
        
        // Add foreign key data if checkbox is checked
        if ($('#hasForeignKey').is(':checked')) {
            formData.foreignKey = {
                referenced_table: $('#fkReferencedTable').val(),
                referenced_column: $('#fkReferencedColumn').val(),
                update_rule: $('#fkUpdateRule').val(),
                delete_rule: $('#fkDeleteRule').val()
            };
            
            // Validate foreign key fields
            if (!formData.foreignKey.referenced_table || !formData.foreignKey.referenced_column) {
                alert('Please select both a referenced table and column for the foreign key');
                return;
            }
        }
        
        // Add position for new columns
        if (!window.State.currentEditColumn) {
            formData.position = $('#fieldPosition').val();
        }
        
        if (!formData.name) {
            alert('Please enter a column name');
            return;
        }
        
        // Generate SQL query instead of executing
        const sqlQuery = this.generateSQL(formData, window.State.currentEditColumn);
        
        // Redirect to SQL Query Builder with the generated SQL
        const queryParam = encodeURIComponent(sqlQuery);
        const tableParam = encodeURIComponent(window.State.currentTable);
        // Set table in session before navigating
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentTable',
                table: window.State.currentTable
            },
            dataType: 'json',
            success: function() {
                window.location.href = `../query_builder/?sql=${queryParam}`;
            },
            error: function() {
                // If setting table fails, still navigate with URL parameter as fallback
                window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
            }
        });
    },

    /**
     * Generate SQL for column modification
     */
    generateSQL: function(formData, editColumn) {
        let sql = '';
        
        // Determine the type to use - if "--" is selected and editing existing column, use original type
        let columnType = formData.type;
        if (editColumn && formData.type === '--') {
            columnType = editColumn.type;
        }
        
        // Build column definition
        let columnDef = `\`${formData.name}\` ${columnType}`;
        
        // Add NOT NULL or NULL
        if (!formData.null) {
            columnDef += ' NOT NULL';
        } else {
            columnDef += ' NULL';
        }
        
        // Add DEFAULT value
        if (formData.default !== null && formData.default !== '') {
            // Check if default should be quoted (non-numeric types)
            if (columnType.toUpperCase().includes('INT') || 
                columnType.toUpperCase().includes('DECIMAL') || 
                columnType.toUpperCase().includes('FLOAT') ||
                columnType.toUpperCase().includes('DOUBLE')) {
                columnDef += ` DEFAULT ${formData.default}`;
            } else {
                columnDef += ` DEFAULT '${formData.default.replace(/'/g, "''")}'`;
            }
        }
        
        // Add AUTO_INCREMENT
        if (formData.auto_increment) {
            columnDef += ' AUTO_INCREMENT';
        }
        
        // Add extra attributes
        if (formData.extra && formData.extra.trim() !== '') {
            columnDef += ' ' + formData.extra.trim();
        }
        
        if (editColumn) {
            const isRenaming = editColumn.name !== formData.name;
            
            // Check if column definition actually changed
            // If type is "--", use original type, so it won't be considered changed
            const typeChanged = formData.type !== '--' && columnType !== editColumn.type;
            const nullChanged = formData.null !== editColumn.null;
            
            // Compare default values
            // Normalize: treat null, undefined, and empty string as "no default"
            const oldDefault = editColumn.default !== null && editColumn.default !== undefined && String(editColumn.default).trim() !== '' ? String(editColumn.default).trim() : null;
            const newDefault = formData.default !== null && formData.default !== undefined && String(formData.default).trim() !== '' ? String(formData.default).trim() : null;
            const defaultChanged = oldDefault !== newDefault;
            
            // Compare auto_increment
            const oldAutoIncrement = editColumn.extra ? editColumn.extra.toLowerCase().includes('auto_increment') : false;
            const autoIncrementChanged = formData.auto_increment !== oldAutoIncrement;
            
            // Compare extra attributes (excluding auto_increment which we handle separately)
            const oldExtra = editColumn.extra ? editColumn.extra.replace(/auto_increment/gi, '').trim() : '';
            const newExtra = formData.extra ? formData.extra.replace(/auto_increment/gi, '').trim() : '';
            const extraChanged = oldExtra !== newExtra;
            
            // Check if column definition needs to be modified
            // If "-- Don't change --" is selected, ignore type changes and only check other fields
            const strictMode = formData.type === '--';
            const columnDefChanged = isRenaming || 
                (!strictMode && typeChanged) ||  // Only check typeChanged if not in strict mode
                nullChanged || 
                defaultChanged || 
                autoIncrementChanged || 
                extraChanged;
            
            const warningComment = isRenaming
                ? [
                    '-- WARNING: Column rename detected.',
                    `-- Old name: ${editColumn.name}`,
                    `-- New name: ${formData.name}`,
                    '-- Ensure dependent queries, views, and application code are updated before executing.'
                ].join('\n')
                : '';

            // Only generate MODIFY/CHANGE statement if column definition actually changed
            if (columnDefChanged) {
                if (isRenaming) {
                    sql = `ALTER TABLE \`${window.State.currentTable}\` CHANGE COLUMN \`${editColumn.name}\` ${columnDef};`;
                } else {
                    sql = `ALTER TABLE \`${window.State.currentTable}\` MODIFY COLUMN ${columnDef};`;
                }
            }
            
            // Add index modifications if needed
            const indexSQL = [];
            
            // Handle PRIMARY KEY
            if (formData.primary && editColumn.key !== 'PRI') {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD PRIMARY KEY (\`${formData.name}\`);`);
            } else if (!formData.primary && editColumn.key === 'PRI') {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` DROP PRIMARY KEY;`);
            }
            
            // Handle UNIQUE
            if (formData.unique && editColumn.key !== 'UNI') {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD UNIQUE (\`${formData.name}\`);`);
            } else if (!formData.unique && editColumn.key === 'UNI') {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` DROP INDEX \`${formData.name}\`;`);
            }
            
            // Handle INDEX (regular index, not PRIMARY or UNIQUE)
            // Only add/remove index if not PRIMARY or UNIQUE (they already create indexes)
            const currentHasIndex = editColumn.key === 'MUL';
            const wantsRegularIndex = formData.index && !formData.primary && !formData.unique;
            
            if (wantsRegularIndex && !currentHasIndex) {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD INDEX (\`${formData.name}\`);`);
            } else if (!wantsRegularIndex && currentHasIndex && !formData.primary && !formData.unique) {
                // Drop index - MySQL typically names the index after the column if created with ADD INDEX(column)
                // However, it might have a different name, so we try the column name first
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` DROP INDEX \`${formData.name}\`;`);
            }
            
            // Handle FOREIGN KEY
            const hasOldFK = editColumn.foreignKey;
            const hasNewFK = formData.foreignKey && formData.foreignKey.referenced_table && formData.foreignKey.referenced_column;
            
            if (hasOldFK && !hasNewFK) {
                // Drop existing foreign key
                const fkName = editColumn.foreignKey.constraint_name || `fk_${editColumn.name}`;
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` DROP FOREIGN KEY \`${fkName}\`;`);
            } else if (hasNewFK) {
                // Generate constraint name
                const fkName = hasOldFK ? editColumn.foreignKey.constraint_name : `fk_${formData.name}`;
                
                // Check if FK changed
                const fkChanged = !hasOldFK || 
                    editColumn.foreignKey.referenced_table !== formData.foreignKey.referenced_table ||
                    editColumn.foreignKey.referenced_column !== formData.foreignKey.referenced_column ||
                    editColumn.foreignKey.update_rule !== formData.foreignKey.update_rule ||
                    editColumn.foreignKey.delete_rule !== formData.foreignKey.delete_rule;
                
                if (fkChanged) {
                    // Drop old FK if exists
                    if (hasOldFK) {
                        const oldFkName = editColumn.foreignKey.constraint_name || `fk_${editColumn.name}`;
                        indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` DROP FOREIGN KEY \`${oldFkName}\`;`);
                    }
                    
                    // Add new FK
                    const updateRule = formData.foreignKey.update_rule || 'RESTRICT';
                    const deleteRule = formData.foreignKey.delete_rule || 'RESTRICT';
                    indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD CONSTRAINT \`${fkName}\` FOREIGN KEY (\`${formData.name}\`) REFERENCES \`${formData.foreignKey.referenced_table}\` (\`${formData.foreignKey.referenced_column}\`) ON UPDATE ${updateRule} ON DELETE ${deleteRule};`);
                }
            }
            
            if (indexSQL.length > 0) {
                if (sql) {
                    sql = sql + '\n\n' + indexSQL.join('\n');
                } else {
                    sql = indexSQL.join('\n');
                }
            }

            if (warningComment) {
                sql = warningComment + '\n' + sql;
            }
            
            // If no SQL was generated at all, return empty string
            if (!sql) {
                return '';
            }

        } else {
            // ADD new column
            sql = `ALTER TABLE \`${window.State.currentTable}\` ADD COLUMN ${columnDef}`;
            
            // Add position
            const position = formData.position || 'end';
            if (position === 'first') {
                sql += ' FIRST';
            } else if (position.startsWith('after_')) {
                const afterColumn = position.substring(6);
                sql += ` AFTER \`${afterColumn}\``;
            }
            
            sql += ';';
            
            // Add index if needed
            const indexSQL = [];
            if (formData.primary) {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD PRIMARY KEY (\`${formData.name}\`);`);
            }
            if (formData.unique) {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD UNIQUE (\`${formData.name}\`);`);
            }
            // Add regular index only if not PRIMARY or UNIQUE (they already create indexes)
            if (formData.index && !formData.primary && !formData.unique) {
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD INDEX (\`${formData.name}\`);`);
            }
            
            // Add foreign key if specified
            if (formData.foreignKey && formData.foreignKey.referenced_table && formData.foreignKey.referenced_column) {
                const fkName = `fk_${formData.name}`;
                const updateRule = formData.foreignKey.update_rule || 'RESTRICT';
                const deleteRule = formData.foreignKey.delete_rule || 'RESTRICT';
                indexSQL.push(`ALTER TABLE \`${window.State.currentTable}\` ADD CONSTRAINT \`${fkName}\` FOREIGN KEY (\`${formData.name}\`) REFERENCES \`${formData.foreignKey.referenced_table}\` (\`${formData.foreignKey.referenced_column}\`) ON UPDATE ${updateRule} ON DELETE ${deleteRule};`);
            }
            
            if (indexSQL.length > 0) {
                sql = sql + '\n\n' + indexSQL.join('\n');
            }
        }
        
        return sql;
    },

    /**
     * Delete column
     */
    delete: function() {
        if (!window.State.currentEditColumn) return;
        
        // Generate SQL query for column deletion
        const warningComment = [
            '-- WARNING: Column drop operation.',
            `-- Column to drop: ${window.State.currentEditColumn.name}`,
            '-- Verify no queries, views, or application code still rely on this column before executing.'
        ].join('\n');
        const sqlQuery = `${warningComment}\nALTER TABLE \`${window.State.currentTable}\` DROP COLUMN \`${window.State.currentEditColumn.name}\`;`;
        
        // Redirect to SQL Query Builder with the generated SQL
        const queryParam = encodeURIComponent(sqlQuery);
        const tableParam = encodeURIComponent(window.State.currentTable);
        // Set table in session before navigating
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentTable',
                table: window.State.currentTable
            },
            dataType: 'json',
            success: function() {
                window.location.href = `../query_builder/?sql=${queryParam}`;
            },
            error: function() {
                // If setting table fails, still navigate with URL parameter as fallback
                window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.ColumnOperations = ColumnOperations;
}

