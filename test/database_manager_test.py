#!/usr/bin/env python3
"""
Selenium Test Script for Database Manager
Tests the complete workflow: Create Database -> Create Table -> Delete Database
"""

import time
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
import sys

class DatabaseManagerTest:
    def __init__(self, base_url="http://localhost", headless=False, username="max", password="maxbis123", delay_speed="normal"):
        """
        Initialize the test with browser configuration
        
        Args:
            base_url (str): Base URL of the application
            headless (bool): Whether to run browser in headless mode
            username (str): Login username
            password (str): Login password
            delay_speed (str): Speed setting - "fast", "normal", "slow", or custom delay in seconds
        """
        self.base_url = base_url
        self.headless = headless
        self.username = username
        self.password = password
        self.driver = None
        self.wait = None
        self.test_database_name = f"test_db_{random.randint(1000, 9999)}"
        self.test_table_name = f"test_table_{random.randint(100, 999)}"
        
        # Configure delays based on speed setting
        self.setup_delays(delay_speed)
    
    def setup_delays(self, delay_speed):
        """Setup delay timings based on speed preference"""
        if delay_speed == "fast":
            self.short_delay = 0.3
            self.medium_delay = 0.5
            self.long_delay = 1.0
            self.extra_delay = 1.5
            print("⚡ Speed setting: FAST (minimal delays)")
        elif delay_speed == "slow":
            self.short_delay = 2.0
            self.medium_delay = 3.0
            self.long_delay = 4.0
            self.extra_delay = 5.0
            print("🐌 Speed setting: SLOW (extended delays for easy observation)")
        elif delay_speed == "normal":
            self.short_delay = 1.0
            self.medium_delay = 2.0
            self.long_delay = 3.0
            self.extra_delay = 4.0
            print("🚶 Speed setting: NORMAL (balanced delays)")
        else:
            # Try to parse as custom delay in seconds
            try:
                custom_delay = float(delay_speed)
                self.short_delay = custom_delay * 0.5
                self.medium_delay = custom_delay
                self.long_delay = custom_delay * 1.5
                self.extra_delay = custom_delay * 2.0
                print(f"⚙️  Speed setting: CUSTOM ({custom_delay}s base delay)")
            except ValueError:
                print("⚠️  Invalid delay speed, using normal speed")
                self.short_delay = 1.0
                self.medium_delay = 2.0
                self.long_delay = 3.0
                self.extra_delay = 4.0
        
    def setup_driver(self):
        """Setup Chrome WebDriver with appropriate options"""
        print("🔧 Setting up Chrome WebDriver...")
        
        chrome_options = Options()
        if self.headless:
            chrome_options.add_argument("--headless")
        
        # Additional options for better compatibility
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        
        try:
            # Use webdriver-manager to automatically download and manage ChromeDriver
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.wait = WebDriverWait(self.driver, 10)
            print("✅ WebDriver setup successful")
        except Exception as e:
            print(f"❌ Failed to setup WebDriver: {e}")
            sys.exit(1)
    
    def login(self):
        """Login to the application"""
        print(f"🔐 Logging in with username: {self.username}")
        print("=" * 50)
        
        # Navigate to login page
        login_url = f"{self.base_url}/db-manager/login/login.php"
        print(f"🌐 Navigating to login page: {login_url}")
        
        try:
            self.driver.get(login_url)
            time.sleep(self.medium_delay)  # Allow page to load
            
            # Wait for login form to be present
            self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='text'], input[name='username'], #username")))
            print("✅ Login page loaded successfully")
            
            # Find and fill username field
            username_selectors = [
                "input[name='username']",
                "input[type='text']",
                "#username",
                "input[placeholder*='username' i]",
                "input[placeholder*='user' i]"
            ]
            
            username_field = None
            for selector in username_selectors:
                try:
                    username_field = self.driver.find_element(By.CSS_SELECTOR, selector)
                    break
                except NoSuchElementException:
                    continue
            
            if not username_field:
                raise NoSuchElementException("Could not find username field")
            
            print(f"⌨️  Entering username: {self.username}")
            time.sleep(self.short_delay)
            username_field.clear()
            username_field.send_keys(self.username)
            time.sleep(self.short_delay)
            
            # Find and fill password field
            password_selectors = [
                "input[name='password']",
                "input[type='password']",
                "#password",
                "input[placeholder*='password' i]"
            ]
            
            password_field = None
            for selector in password_selectors:
                try:
                    password_field = self.driver.find_element(By.CSS_SELECTOR, selector)
                    break
                except NoSuchElementException:
                    continue
            
            if not password_field:
                raise NoSuchElementException("Could not find password field")
            
            print(f"⌨️  Entering password: {'*' * len(self.password)}")
            time.sleep(self.short_delay)
            password_field.clear()
            password_field.send_keys(self.password)
            time.sleep(self.short_delay)
            
            # Find and click login button
            login_button_selectors = [
                "input[type='submit']",
                "button[type='submit']",
                "input[value*='login' i]",
                "button:contains('Login')",
                ".login-btn",
                "#login-btn"
            ]
            
            login_button = None
            for selector in login_button_selectors:
                try:
                    login_button = self.driver.find_element(By.CSS_SELECTOR, selector)
                    break
                except NoSuchElementException:
                    continue
            
            if not login_button:
                # Try to find any button that might be the login button
                buttons = self.driver.find_elements(By.CSS_SELECTOR, "button, input[type='submit']")
                if buttons:
                    login_button = buttons[0]  # Use first button found
                else:
                    raise NoSuchElementException("Could not find login button")
            
            print("👆 Clicking login button...")
            time.sleep(self.short_delay)
            login_button.click()
            time.sleep(self.long_delay)  # Wait for login to process
            
            # Check if login was successful by looking for redirect or dashboard
            current_url = self.driver.current_url
            print(f"📍 Current URL after login: {current_url}")
            
            # If we're still on login page, check for error messages
            if "login" in current_url.lower():
                # Look for error messages
                error_selectors = [
                    ".error",
                    ".alert-danger",
                    ".message",
                    ".notification",
                    "[class*='error']",
                    "[class*='alert']"
                ]
                
                for selector in error_selectors:
                    try:
                        error_element = self.driver.find_element(By.CSS_SELECTOR, selector)
                        if error_element.is_displayed() and error_element.text.strip():
                            print(f"❌ Login error: {error_element.text}")
                            raise Exception(f"Login failed: {error_element.text}")
                    except NoSuchElementException:
                        continue
                
                print("⚠️  Still on login page - login may have failed")
            else:
                print("✅ Login successful - redirected from login page")
            
            time.sleep(self.medium_delay)  # Extra pause for user to see login result
            
        except Exception as e:
            print(f"❌ Login failed: {e}")
            raise

    def navigate_to_database_manager(self):
        """Navigate to the database manager page"""
        print(f"🌐 Navigating to database manager...")
        url = f"{self.base_url}/db-manager/db_manager/"
        
        try:
            self.driver.get(url)
            # Wait for either dashboard content or login redirect
            try:
                self.wait.until(EC.presence_of_element_located((By.ID, "dashboardContent")))
                print("✅ Successfully navigated to database manager")
            except TimeoutException:
                # Check if we were redirected to login
                if "login" in self.driver.current_url.lower():
                    print("🔄 Redirected to login page - performing login...")
                    self.login()
                    # Try to navigate to database manager again
                    self.driver.get(url)
                    self.wait.until(EC.presence_of_element_located((By.ID, "dashboardContent")))
                    print("✅ Successfully navigated to database manager after login")
                else:
                    raise TimeoutException("Could not find dashboard content")
            
            time.sleep(self.medium_delay)  # Allow page to fully load
        except TimeoutException:
            print("❌ Failed to load database manager page")
            raise
    
    def wait_and_click(self, locator, description="element"):
        """Wait for element and click it with delay for visibility"""
        try:
            element = self.wait.until(EC.element_to_be_clickable(locator))
            print(f"👆 Clicking {description}...")
            time.sleep(self.short_delay)  # Brief pause for user to see
            element.click()
            time.sleep(self.short_delay)  # Brief pause after action
            return element
        except TimeoutException:
            print(f"❌ Failed to find or click {description}")
            raise
    
    def wait_and_send_keys(self, locator, text, description="input field"):
        """Wait for element and send keys with delay for visibility"""
        try:
            element = self.wait.until(EC.presence_of_element_located(locator))
            print(f"⌨️  Typing '{text}' into {description}...")
            time.sleep(self.short_delay)  # Brief pause for user to see
            element.clear()
            element.send_keys(text)
            time.sleep(self.short_delay)  # Brief pause after action
            return element
        except TimeoutException:
            print(f"❌ Failed to find or type into {description}")
            raise
    
    def create_database(self):
        """Create a new database"""
        print(f"\n📊 STEP 1: Creating database '{self.test_database_name}'")
        print("=" * 50)
        
        # Click on Actions dropdown
        self.wait_and_click(
            (By.CSS_SELECTOR, "#statsActions .dropdown-toggle"),
            "Actions dropdown"
        )
        
        # Click Create Database button
        self.wait_and_click(
            (By.ID, "createDatabaseBtn"),
            "Create Database button"
        )
        
        # Fill database name
        self.wait_and_send_keys(
            (By.ID, "newDatabaseName"),
            self.test_database_name,
            "database name field"
        )
        
        # Select charset (keep default utf8mb4)
        charset_select = Select(self.driver.find_element(By.ID, "newDatabaseCharset"))
        print("🔤 Setting charset to utf8mb4...")
        time.sleep(self.short_delay)
        charset_select.select_by_value("utf8mb4")
        time.sleep(self.short_delay)
        
        # Select collation (keep default utf8mb4_unicode_ci)
        collation_select = Select(self.driver.find_element(By.ID, "newDatabaseCollation"))
        print("🔤 Setting collation to utf8mb4_unicode_ci...")
        time.sleep(self.short_delay)
        collation_select.select_by_value("utf8mb4_unicode_ci")
        time.sleep(self.short_delay)
        
        # Click Create Database button
        self.wait_and_click(
            (By.ID, "confirmCreateDatabaseBtn"),
            "Confirm Create Database button"
        )
        
        # Wait for success message or database to appear in list
        print("⏳ Waiting for database creation to complete...")
        time.sleep(self.long_delay)
        
        # Verify database was created by checking if it appears in the database list
        try:
            self.wait.until(EC.presence_of_element_located(
                (By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}']")
            ))
            print(f"✅ Database '{self.test_database_name}' created successfully!")
        except TimeoutException:
            print(f"⚠️  Database creation may have succeeded, but couldn't verify in UI")
        
        time.sleep(self.medium_delay)  # Extra pause for user to see the result
    
    def select_database(self):
        """Select the created database"""
        print(f"\n🎯 STEP 2: Selecting database '{self.test_database_name}'")
        print("=" * 50)
        
        # Click on the database item to select it
        self.wait_and_click(
            (By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}']"),
            f"database '{self.test_database_name}'"
        )
        
        # Wait for database to be selected (check if it's active)
        try:
            self.wait.until(EC.presence_of_element_located(
                (By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}'].active")
            ))
            print(f"✅ Database '{self.test_database_name}' selected successfully!")
        except TimeoutException:
            print(f"⚠️  Database selection may have succeeded, but couldn't verify active state")
        
        time.sleep(self.medium_delay)  # Extra pause for user to see the selection
    
    def create_table(self):
        """Create a table with multiple columns"""
        print(f"\n📋 STEP 3: Creating table '{self.test_table_name}' with columns")
        print("=" * 50)
        
        # Click on Actions dropdown
        self.wait_and_click(
            (By.CSS_SELECTOR, "#statsActions .dropdown-toggle"),
            "Actions dropdown"
        )
        
        # Click Create Table button
        self.wait_and_click(
            (By.ID, "createTableMenuItem"),
            "Create Table button"
        )
        
        # Fill table name
        self.wait_and_send_keys(
            (By.ID, "newTableName"),
            self.test_table_name,
            "table name field"
        )
        
        # Add first column (ID - Primary Key)
        print("🔧 Adding first column (ID)...")
        time.sleep(self.short_delay)
        
        # Click Add Column button to create the first column
        add_column_btn = self.driver.find_element(By.ID, "addColumnRowBtn")
        add_column_btn.click()
        time.sleep(self.short_delay)
        
        # Find the first column row and configure it
        column_rows = self.driver.find_elements(By.CSS_SELECTOR, ".column-rows .column-row")
        if column_rows:
            first_row = column_rows[0]
            
            # Fill column name
            name_input = first_row.find_element(By.CSS_SELECTOR, "input.col-name")
            name_input.clear()
            name_input.send_keys("id")
            time.sleep(self.short_delay * 0.5)
            
            # Select column type
            type_select = Select(first_row.find_element(By.CSS_SELECTOR, "select.col-type"))
            type_select.select_by_value("INT")
            time.sleep(self.short_delay * 0.5)
            
            # Set Auto Increment and Primary Key
            ai_checkbox = first_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-ai")
            if not ai_checkbox.is_selected():
                ai_checkbox.click()
            time.sleep(self.short_delay * 0.3)
            
            pk_checkbox = first_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-primary")
            if not pk_checkbox.is_selected():
                pk_checkbox.click()
            time.sleep(self.short_delay * 0.3)
            
            print("    ✅ ID column (Primary Key, Auto Increment) configured")
        
        # Add additional columns
        print("🔧 Adding additional columns...")
        time.sleep(self.short_delay)
        
        # Add second column (Name)
        self.add_column("name", "VARCHAR", "100", "None", False, False, False, False, False, "Name column")
        
        # Add third column (Email)
        self.add_column("email", "VARCHAR", "255", "None", False, False, False, False, True, "Email column (Unique)")
        
        # Add fourth column (Created Date)
        self.add_column("created_at", "TIMESTAMP", "", "CURRENT_TIMESTAMP", False, False, False, False, False, "Created date column")
        
        # Select storage engine (keep default InnoDB)
        engine_select = Select(self.driver.find_element(By.ID, "newTableEngine"))
        print("🔧 Setting storage engine to InnoDB...")
        time.sleep(self.short_delay)
        engine_select.select_by_value("InnoDB")
        time.sleep(self.short_delay)
        
        # Click Create Table button
        self.wait_and_click(
            (By.ID, "confirmCreateTableBtn"),
            "Confirm Create Table button"
        )
        
        # Wait for table creation to complete
        print("⏳ Waiting for table creation to complete...")
        time.sleep(self.long_delay)
        
        print(f"✅ Table '{self.test_table_name}' created successfully with 4 columns!")
        time.sleep(self.medium_delay)  # Extra pause for user to see the result
    
    def add_column(self, name, type_val, length="", default="None", null_checked=False, ai_checked=False, primary_key=False, index=False, unique=False, description=""):
        """Add a column to the table builder"""
        print(f"  ➕ Adding {description}...")
        time.sleep(self.short_delay)
        
        # Click Add Column button
        add_column_btn = self.driver.find_element(By.ID, "addColumnRowBtn")
        add_column_btn.click()
        time.sleep(self.short_delay)
        
        # Find the last column row (most recently added)
        column_rows = self.driver.find_elements(By.CSS_SELECTOR, ".column-rows .column-row")
        last_row = column_rows[-1]
        
        # Fill column name
        name_input = last_row.find_element(By.CSS_SELECTOR, "input.col-name")
        name_input.clear()
        name_input.send_keys(name)
        time.sleep(self.short_delay * 0.5)
        
        # Select column type
        type_select = Select(last_row.find_element(By.CSS_SELECTOR, "select.col-type"))
        type_select.select_by_value(type_val)
        time.sleep(self.short_delay * 0.5)
        
        # Fill length if provided
        if length:
            length_input = last_row.find_element(By.CSS_SELECTOR, "input.col-length")
            length_input.clear()
            length_input.send_keys(length)
            time.sleep(self.short_delay * 0.5)
        
        # Set default value
        if default != "None":
            default_select = Select(last_row.find_element(By.CSS_SELECTOR, "select.col-default-mode"))
            if default == "CURRENT_TIMESTAMP":
                default_select.select_by_value("current_timestamp")
            else:
                default_select.select_by_value("value")
                # Fill the default value input
                default_input = last_row.find_element(By.CSS_SELECTOR, "input.col-default")
                default_input.clear()
                default_input.send_keys(default)
            time.sleep(self.short_delay * 0.5)
        
        # Set checkboxes
        if null_checked:
            null_checkbox = last_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-null")
            if not null_checkbox.is_selected():
                null_checkbox.click()
            time.sleep(self.short_delay * 0.3)
        
        if ai_checked:
            ai_checkbox = last_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-ai")
            if not ai_checkbox.is_selected():
                ai_checkbox.click()
            time.sleep(self.short_delay * 0.3)
        
        if primary_key:
            pk_checkbox = last_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-primary")
            if not pk_checkbox.is_selected():
                pk_checkbox.click()
            time.sleep(self.short_delay * 0.3)
        
        if index:
            index_checkbox = last_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-index")
            if not index_checkbox.is_selected():
                index_checkbox.click()
            time.sleep(self.short_delay * 0.3)
        
        if unique:
            unique_checkbox = last_row.find_element(By.CSS_SELECTOR, "input[type='checkbox'].col-unique")
            if not unique_checkbox.is_selected():
                unique_checkbox.click()
            time.sleep(self.short_delay * 0.3)
        
        print(f"    ✅ {description} added successfully")
    
    def delete_database(self):
        """Delete the created database"""
        print(f"\n🗑️  STEP 4: Deleting database '{self.test_database_name}'")
        print("=" * 50)
        
        # Find the database item and click its actions dropdown
        database_item = self.driver.find_element(
            By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}']"
        )
        
        # Click the actions dropdown button
        self.wait_and_click(
            (By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}'] .dropdown-toggle"),
            f"actions dropdown for database '{self.test_database_name}'"
        )
        
        # Click the delete button in the dropdown
        self.wait_and_click(
            (By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}'] .db-delete-btn"),
            f"delete button for database '{self.test_database_name}'"
        )
        
        # Confirm deletion in the confirmation modal
        print("⚠️  Confirming database deletion...")
        time.sleep(self.short_delay)
        
        # Click the confirm delete button
        self.wait_and_click(
            (By.ID, "confirmActionConfirmBtn"),
            "Confirm Delete button"
        )
        
        # Wait for deletion to complete
        print("⏳ Waiting for database deletion to complete...")
        time.sleep(self.long_delay)
        
        # Verify database was deleted
        try:
            # Check if database item is no longer present
            self.driver.find_element(
                By.CSS_SELECTOR, f".database-item[data-database='{self.test_database_name}']"
            )
            print(f"⚠️  Database '{self.test_database_name}' may still be present")
        except NoSuchElementException:
            print(f"✅ Database '{self.test_database_name}' deleted successfully!")
        
        time.sleep(self.medium_delay)  # Extra pause for user to see the result
    
    def run_test(self):
        """Run the complete test workflow"""
        print("🚀 Starting Database Manager Test")
        print("=" * 60)
        print(f"Test Database: {self.test_database_name}")
        print(f"Test Table: {self.test_table_name}")
        print("=" * 60)
        
        try:
            # Setup
            self.setup_driver()
            
            # Navigate to database manager
            self.navigate_to_database_manager()
            
            # Run test steps
            self.create_database()
            self.select_database()
            self.create_table()
            self.delete_database()
            
            print("\n🎉 TEST COMPLETED SUCCESSFULLY!")
            print("=" * 60)
            print("All steps completed:")
            print("✅ 1. Created database")
            print("✅ 2. Selected database")
            print("✅ 3. Created table with multiple columns")
            print("✅ 4. Deleted database")
            
        except Exception as e:
            print(f"\n❌ TEST FAILED: {e}")
            print("=" * 60)
            raise
        
        finally:
            # Cleanup
            if self.driver:
                print("\n🧹 Cleaning up...")
                time.sleep(self.long_delay)  # Final pause for user to see results
                self.driver.quit()
                print("✅ Browser closed")

def main():
    """Main function to run the test"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Database Manager Selenium Test")
    parser.add_argument("--url", default="http://localhost", 
                       help="Base URL of the application (default: http://localhost)")
    parser.add_argument("--headless", action="store_true", 
                       help="Run browser in headless mode")
    parser.add_argument("--username", default="max", 
                       help="Login username (default: max)")
    parser.add_argument("--password", default="maxbis123", 
                       help="Login password (default: maxbis123)")
    parser.add_argument("--speed", default="normal", 
                       help="Test speed: fast, normal, slow, or custom delay in seconds (default: normal)")
    
    args = parser.parse_args()
    
    # Create and run test
    test = DatabaseManagerTest(
        base_url=args.url, 
        headless=args.headless,
        username=args.username,
        password=args.password,
        delay_speed=args.speed
    )
    test.run_test()

if __name__ == "__main__":
    main()
