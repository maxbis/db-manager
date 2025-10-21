<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dialog Component Example</title>
    
    <!-- Include common styles first for CSS variables and base styles -->
    <link rel="stylesheet" href="../styles/common.css">
    
    <!-- Then include dialog-specific styles -->
    <link rel="stylesheet" href="dialog.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dialog Component Examples</h1>
        </div>
        
        <div class="content">
            <h2>Dialog Component Examples</h2>
            <p>This page demonstrates the generic dialog component. The "Delete Database" dialog should appear automatically when the page loads.</p>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
                <button onclick="showDeleteDatabaseDialog()" class="btn-danger">🗑️ Show Delete Database Dialog</button>
                <button onclick="testAlert()" class="btn-success">✅ Test Alert Dialog</button>
                <button onclick="testCustom()" class="btn-warning">⚙️ Test Custom Dialog</button>
            </div>
        </div>
    </div>

    <!-- Include the dialog component -->
    <?php include 'dialog.php'; ?>

    <!-- Include dialog JavaScript -->
    <script src="dialog.js"></script>
    
    <script>
        // Function to show the exact dialog from the image
        function showDeleteDatabaseDialog() {
            Dialog.confirm({
                title: 'Delete Database',
                message: 'Are you sure you want to delete the database "bookstack-roc"? This action cannot be undone!',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                confirmClass: 'btn-danger',
                cancelClass: 'btn-secondary',
                icon: '⚠️',
                onConfirm: function() {
                    console.log('Database "bookstack-roc" deleted!');
                    // Here you would typically make an AJAX call to delete the database
                },
                onCancel: function() {
                    console.log('Delete operation cancelled');
                }
            });
        }

        function testAlert() {
            Dialog.alert({
                title: 'Success',
                message: 'Your changes have been saved successfully!',
                confirmText: 'OK',
                confirmClass: 'btn-success',
                icon: '✅',
                onConfirm: function() {
                    console.log('Alert dismissed');
                }
            });
        }

        function testCustom() {
            Dialog.custom({
                title: 'Select an Option',
                body: `
                    <div style="padding: 10px;">
                        <p>Please choose how you want to proceed:</p>
                        <select id="customSelect" style="width: 100%; padding: 8px; margin-top: 10px;">
                            <option value="option1">Option 1</option>
                            <option value="option2">Option 2</option>
                            <option value="option3">Option 3</option>
                        </select>
                    </div>
                `,
                buttons: [
                    {
                        text: 'Cancel',
                        class: 'btn-secondary',
                        action: function() {
                            console.log('Cancelled');
                        }
                    },
                    {
                        text: 'Apply',
                        class: 'btn-primary',
                        action: function() {
                            const value = document.getElementById('customSelect').value;
                            console.log('Selected:', value);
                        }
                    }
                ],
                width: '600px'
            });
        }

        // Auto-show the delete database dialog when page loads (to match the image)
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure everything is loaded
            setTimeout(function() {
                showDeleteDatabaseDialog();
            }, 500);
        });
    </script>
</body>
</html>