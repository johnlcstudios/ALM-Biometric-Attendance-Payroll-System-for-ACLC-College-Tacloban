import pytest
from playwright.sync_api import sync_playwright
import time
import subprocess
import os

@pytest.fixture(scope="module", autouse=True)
def php_server():
    process = subprocess.Popen(
        ["php", "-S", "localhost:8000", "-t", "AI-ML-Test-Bench"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(2)
    yield
    process.terminate()

def test_password_toggles():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        context = browser.new_context(viewport={'width': 1280, 'height': 1024})
        page = context.new_page()

        # Test Login Page
        page.goto("http://localhost:8000/login.php")
        # Just remove the splash screen if it's in the way
        page.evaluate("document.getElementById('splashScreen') ? document.getElementById('splashScreen').remove() : null")

        password_input = page.locator("#password")
        toggle_btn = page.locator(".toggle-password").first

        assert password_input.get_attribute("type") == "password"

        # Use dispatch_event if click() is failing due to viewport issues in headless mode
        toggle_btn.dispatch_event("click")
        assert password_input.get_attribute("type") == "text"

        toggle_btn.dispatch_event("click")
        assert password_input.get_attribute("type") == "password"

        # Keyboard accessibility
        toggle_btn.dispatch_event("keydown", {"key": "Enter"})
        assert password_input.get_attribute("type") == "text"

        # Test Signup Page
        page.goto("http://localhost:8000/signup.php")
        password_input = page.locator("#password")
        confirm_input = page.locator("#confirm_password")
        toggles = page.locator(".toggle-password")

        assert toggles.count() == 2
        assert password_input.get_attribute("type") == "password"

        toggles.nth(0).dispatch_event("click")
        assert password_input.get_attribute("type") == "text"

        # Space key
        toggles.nth(1).dispatch_event("keydown", {"key": " "})
        assert confirm_input.get_attribute("type") == "text"

        browser.close()

if __name__ == "__main__":
    pytest.main([__file__])
