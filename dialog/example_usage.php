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
                message: 'Are you sure you want to delete the database "bookstack-roc"?<br>This action cannot be undone!',
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
                        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                            <button class="option-btn" data-value="option1" style="
                                padding: 12px 16px; 
                                border: 2px solid var(--color-border-light); 
                                border-radius: 8px; 
                                background: var(--color-bg-white); 
                                color: var(--color-text-primary); 
                                cursor: pointer; 
                                transition: all 0.2s ease;
                                text-align: left;
                                font-size: 14px;
                            ">
                                <strong>Option 1</strong><br>
                                <span style="color: var(--color-text-secondary); font-size: 12px;">Description for option 1</span>
                            </button>
                            <button class="option-btn" data-value="option2" style="
                                padding: 12px 16px; 
                                border: 2px solid var(--color-border-light); 
                                border-radius: 8px; 
                                background: var(--color-bg-white); 
                                color: var(--color-text-primary); 
                                cursor: pointer; 
                                transition: all 0.2s ease;
                                text-align: left;
                                font-size: 14px;
                            ">
                                <strong>Option 2</strong><br>
                                <span style="color: var(--color-text-secondary); font-size: 12px;">Description for option 2</span>
                            </button>
                            <button class="option-btn" data-value="option3" style="
                                padding: 12px 16px; 
                                border: 2px solid var(--color-border-light); 
                                border-radius: 8px; 
                                background: var(--color-bg-white); 
                                color: var(--color-text-primary); 
                                cursor: pointer; 
                                transition: all 0.2s ease;
                                text-align: left;
                                font-size: 14px;
                            ">
                                <strong>Option 3</strong><br>
                                <span style="color: var(--color-text-secondary); font-size: 12px;">Description for option 3</span>
                            </button>
                        </div>
                    </div>
                `,
                buttons: [
                    {
                        text: 'Cancel',
                        class: 'btn-secondary',
                        action: function() {
                            console.log('Cancelled');
                        }
                    }
                ],
                width: '500px'
            });

            // Add click handlers for option buttons after dialog is shown
            setTimeout(function() {
                const optionButtons = document.querySelectorAll('.option-btn');
                optionButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        console.log('Selected:', value);
                        
                        // Visual feedback - highlight selected option
                        optionButtons.forEach(b => {
                            b.style.borderColor = 'var(--color-border-light)';
                            b.style.background = 'var(--color-bg-white)';
                        });
                        this.style.borderColor = 'var(--color-primary)';
                        this.style.background = 'var(--color-primary-lightest)';
                        
                        // Close dialog after selection
                        setTimeout(function() {
                            Dialog.close();
                        }, 300);
                    });

                    // Hover effects
                    btn.addEventListener('mouseenter', function() {
                        if (this.style.borderColor !== 'var(--color-primary)') {
                            this.style.borderColor = 'var(--color-primary-light)';
                            this.style.background = 'var(--color-bg-hover)';
                        }
                    });

                    btn.addEventListener('mouseleave', function() {
                        if (this.style.borderColor !== 'var(--color-primary)') {
                            this.style.borderColor = 'var(--color-border-light)';
                            this.style.background = 'var(--color-bg-white)';
                        }
                    });
                });
            }, 100);
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