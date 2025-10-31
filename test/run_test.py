#!/usr/bin/env python3
"""
Simple test runner for Database Manager Selenium Test
Provides an easy way to run the test with common configurations
"""

import os
import sys
import subprocess
from database_manager_test import DatabaseManagerTest

def check_dependencies():
    """Check if required dependencies are installed"""
    try:
        import selenium
        from webdriver_manager.chrome import ChromeDriverManager
        print("✅ All dependencies are installed")
        return True
    except ImportError as e:
        print(f"❌ Missing dependency: {e}")
        print("Please install dependencies with: pip install -r requirements.txt")
        return False

def main():
    """Main function to run the test with user-friendly options"""
    print("🚀 Database Manager Test Runner")
    print("=" * 40)
    
    # Check dependencies
    if not check_dependencies():
        sys.exit(1)
    
    # Get configuration from user
    print("\n📋 Test Configuration:")
    
    # Base URL
    base_url = input("Enter base URL (default: http://localhost): ").strip()
    if not base_url:
        base_url = "http://localhost"
    
    # Login credentials
    username = input("Enter username (default: max): ").strip()
    if not username:
        username = "max"
    
    password = input("Enter password (default: maxbis123): ").strip()
    if not password:
        password = "maxbis123"
    
    # Test speed
    print("\n⚡ Test Speed Options:")
    print("   fast   - Minimal delays (quick execution)")
    print("   normal - Balanced delays (default)")
    print("   slow   - Extended delays (easy to follow)")
    print("   Or enter a custom delay in seconds (e.g., 2.5)")
    
    speed = input("Enter test speed (default: normal): ").strip()
    if not speed:
        speed = "normal"
    
    # Headless mode
    headless_input = input("Run in headless mode? (y/N): ").strip().lower()
    headless = headless_input in ['y', 'yes', '1', 'true']
    
    print(f"\n🔧 Configuration:")
    print(f"   URL: {base_url}")
    print(f"   Username: {username}")
    print(f"   Password: {'*' * len(password)}")
    print(f"   Speed: {speed}")
    print(f"   Headless: {headless}")
    
    # Confirm before running
    confirm = input("\n🚀 Start test? (Y/n): ").strip().lower()
    if confirm in ['n', 'no']:
        print("❌ Test cancelled")
        sys.exit(0)
    
    # Run the test
    print("\n" + "=" * 60)
    try:
        test = DatabaseManagerTest(
            base_url=base_url, 
            headless=headless,
            username=username,
            password=password,
            delay_speed=speed
        )
        test.run_test()
    except KeyboardInterrupt:
        print("\n⚠️  Test interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ Test failed with error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
