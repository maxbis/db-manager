<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Custom Alert Demo</title>
<style>
#alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 9999;
}

.alert {
  min-width: 250px;
  padding: 12px 16px;
  border-radius: 8px;
  color: #fff;
  font-family: system-ui, sans-serif;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  animation: fadeIn 0.3s ease, fadeOut 0.3s ease 3.5s forwards;
}

.alert-success { background-color: #28a745; }
.alert-error   { background-color: #dc3545; }
.alert-warning { background-color: #ffc107; color: #222; }
.alert-info    { background-color: #007bff; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
  to { opacity: 0; transform: translateY(-10px); }
}
</style>
</head>

<body>

<!-- 🔔 Required container -->
<div id="alert-container"></div>

<script>
function showAlert(message, type = 'info', duration = 4000) {
  const container = document.getElementById('alert-container');
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.textContent = message;

  container.appendChild(alert);

  // Auto-remove after duration
  setTimeout(() => {
    alert.remove();
  }, duration);
}
</script>

<script>
// Demo alerts
showAlert("Database created successfully!", "success");
showAlert("Error connecting to server!", "error");
showAlert("Please confirm your action.", "warning", 9000);
</script>

</body>
</html>
