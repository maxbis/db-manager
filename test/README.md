# Database Manager Selenium Test

This directory contains automated tests for the Database Manager application using Selenium WebDriver.

## Test Overview

The `database_manager_test.py` script performs a complete workflow test:

1. **Login** - Authenticates with the application using provided credentials
2. **Create Database** - Creates a new database with custom name, charset, and collation
3. **Select Database** - Selects the newly created database
4. **Create Table** - Creates a table with multiple columns (ID, Name, Email, Created Date)
5. **Delete Database** - Deletes the test database to clean up

## Features

- **Visual Testing**: Runs in a visible browser window so you can follow each step
- **Configurable Delays**: Choose between fast, normal, slow, or custom delay speeds
- **Random Names**: Uses random database and table names to avoid conflicts
- **Error Handling**: Comprehensive error handling and reporting
- **Configurable**: Supports different base URLs, headless mode, and login credentials

## Prerequisites

1. **Python 3.7+** installed
2. **Chrome Browser** installed
3. **ChromeDriver** (will be managed automatically by webdriver-manager)
4. **Database Manager** application running and accessible

## Installation

1. Install Python dependencies:
```bash
pip install -r requirements.txt
```

2. Make sure your Database Manager application is running and accessible at the configured URL.

## Usage

### Basic Usage
```bash
python database_manager_test.py
```

### With Custom URL
```bash
python database_manager_test.py --url http://your-domain.com
```

### With Custom Login Credentials
```bash
python database_manager_test.py --username your_username --password your_password
```

### With Custom Speed Settings
```bash
# Fast execution (minimal delays)
python database_manager_test.py --speed fast

# Slow execution (extended delays for easy observation)
python database_manager_test.py --speed slow

# Custom delay (e.g., 2.5 seconds base delay)
python database_manager_test.py --speed 2.5
```

### Headless Mode (no browser window)
```bash
python database_manager_test.py --headless
```

### Help
```bash
python database_manager_test.py --help
```

## Test Configuration

### Speed Settings
- **Fast**: 0.3-1.5 second delays (quick execution)
- **Normal**: 1.0-4.0 second delays (balanced, default)
- **Slow**: 2.0-5.0 second delays (easy to follow)
- **Custom**: Specify base delay in seconds (e.g., 2.5)

### Test Data
The test creates:
- **Database Name**: `test_db_XXXX` (where XXXX is a random number)
- **Table Name**: `test_table_XXX` (where XXX is a random number)
- **Columns**:
  - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
  - `name` (VARCHAR(100))
  - `email` (VARCHAR(255), UNIQUE)
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

## Expected Behavior

When you run the test, you should see:

1. **Browser opens** and navigates to the Database Manager
2. **Login process** - If redirected to login, credentials are entered and submitted
3. **Database creation** - Modal opens, form is filled, database is created
4. **Database selection** - The new database is selected in the interface
5. **Table creation** - Modal opens, table name and columns are added, table is created
6. **Database deletion** - Confirmation dialog appears, database is deleted
7. **Browser closes** after completion

## Troubleshooting

### Common Issues

1. **ChromeDriver not found**: The script uses webdriver-manager to automatically download ChromeDriver
2. **Page not loading**: Check that your application is running and the URL is correct
3. **Login failed**: Verify the username and password are correct (default: max/maxbis123)
4. **Elements not found**: The application UI may have changed - check selectors in the script
5. **Authentication required**: The script automatically handles login, but check credentials if it fails

### Debug Mode

To see more detailed output, you can modify the script to add more print statements or run with verbose logging.

## Customization

You can customize the test by modifying:

- **Database/Table names**: Change the random name generation
- **Column definitions**: Modify the `add_column()` calls in `create_table()`
- **Delays**: Adjust the `time.sleep()` values for different timing
- **Selectors**: Update CSS selectors if the UI changes

## Notes

- The test is designed to be **non-destructive** - it creates and deletes its own test database
- **Random names** prevent conflicts with existing databases
- **Visual delays** allow you to observe each step of the process
- The test **cleans up after itself** by deleting the test database
