# Generic Dialog Component

A reusable, accessible dialog component for all applications in the db-manager project.

## Installation

1. Include the dialog files in your application's HTML:

```html
<!-- In your main PHP file (e.g., index.php) -->
<?php include '../dialog/dialog.php'; ?>

<!-- In your <head> section -->
<link rel="stylesheet" href="../dialog/dialog.css">

<!-- Before closing </body> tag -->
<script src="../dialog/dialog.js"></script>
```

## Usage Examples

### 1. Confirmation Dialog

```javascript
Dialog.confirm({
    title: 'Delete Database',
    message: 'Are you sure you want to delete this database? This action cannot be undone!',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    confirmClass: 'btn-danger',
    icon: '⚠️',
    onConfirm: function() {
        // Handle confirmation
        deleteDatabase();
    },
    onCancel: function() {
        // Optional: handle cancellation
        console.log('User cancelled');
    }
});
```

### 2. Alert Dialog

```javascript
Dialog.alert({
    title: 'Success',
    message: 'Your changes have been saved successfully!',
    confirmText: 'OK',
    confirmClass: 'btn-success',
    icon: '✅',
    onConfirm: function() {
        // Optional: do something after alert is dismissed
    }
});
```

### 3. Custom Dialog

```javascript
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
```

## API Reference

### `Dialog.confirm(options)`

Shows a confirmation dialog with Cancel and Confirm buttons.

**Options:**
- `title` (string): Dialog title
- `message` (string): Dialog message
- `confirmText` (string): Text for confirm button (default: 'Confirm')
- `cancelText` (string): Text for cancel button (default: 'Cancel')
- `confirmClass` (string): CSS class for confirm button (default: 'btn-primary')
- `cancelClass` (string): CSS class for cancel button (default: 'btn-secondary')
- `icon` (string): Emoji or HTML for icon (default: '⚠️')
- `onConfirm` (function): Callback when confirmed
- `onCancel` (function): Callback when cancelled
- `closeOnOutsideClick` (boolean): Close when clicking outside (default: true)
- `closeOnEscape` (boolean): Close on Escape key (default: true)

### `Dialog.alert(options)`

Shows an alert dialog with only an OK button.

**Options:**
- `title` (string): Dialog title
- `message` (string): Dialog message
- `confirmText` (string): Text for confirm button (default: 'OK')
- `confirmClass` (string): CSS class for confirm button (default: 'btn-primary')
- `icon` (string): Emoji or HTML for icon (default: 'ℹ️')
- `onConfirm` (function): Callback when confirmed
- `closeOnOutsideClick` (boolean): Close when clicking outside (default: true)
- `closeOnEscape` (boolean): Close on Escape key (default: true)

### `Dialog.custom(options)`

Shows a custom dialog with custom body and buttons.

**Options:**
- `title` (string): Dialog title
- `body` (string): HTML content for dialog body
- `buttons` (array): Array of button objects
  - `text` (string): Button text
  - `class` (string): CSS class for button
  - `action` (function): Click handler
  - `closeOnClick` (boolean): Close dialog on click (default: true)
- `width` (string): Custom width (e.g., '600px')
- `closeOnOutsideClick` (boolean): Close when clicking outside (default: true)
- `closeOnEscape` (boolean): Close on Escape key (default: true)

### `Dialog.close()`

Manually close the current dialog.

### `Dialog.isOpen()`

Returns `true` if a dialog is currently open, `false` otherwise.

## Accessibility

The dialog component includes:
- Proper ARIA attributes (`role="dialog"`, `aria-modal="true"`, `aria-labelledby`)
- Keyboard navigation (Escape to close)
- Focus management
- Screen reader support

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers