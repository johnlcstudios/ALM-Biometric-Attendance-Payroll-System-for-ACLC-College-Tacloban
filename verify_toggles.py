import asyncio
from playwright.async_api import async_playwright
import os
import subprocess
import time
import socket

def find_free_port():
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('', 0))
        return s.getsockname()[1]

async def verify_toggles():
    port = find_free_port()
    print(f"Starting PHP server on port {port}...")
    # Start PHP server
    php_server = subprocess.Popen(
        ["php", "-S", f"localhost:{port}", "-t", "."],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(2)  # Wait for server to start

    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch()
            page = await browser.new_page()

            # 1. Verify Login Page
            print("Verifying Login Page...")
            await page.goto(f"http://localhost:{port}/AI-ML-Test-Bench/login.php")

            # Wait for content to load
            await page.wait_for_selector("input[name='password']")

            password_input = page.locator("input[name='password']")
            toggle = page.locator(".toggle-password")

            # Check initial state
            assert await password_input.get_attribute("type") == "password"
            assert await toggle.get_attribute("role") == "button"
            assert await toggle.get_attribute("tabindex") == "0"

            # Click to show
            await toggle.click()
            assert await password_input.get_attribute("type") == "text"
            await page.screenshot(path="login_visible.png")

            # Press Enter to hide
            await toggle.focus()
            await page.keyboard.press("Enter")
            assert await password_input.get_attribute("type") == "password"

            # 2. Verify Signup Page
            print("Verifying Signup Page...")
            await page.goto(f"http://localhost:{port}/AI-ML-Test-Bench/signup.php")

            await page.wait_for_selector("input[name='password']")

            pass_input = page.locator("input[name='password']")
            confirm_input = page.locator("input[name='confirm_password']")
            toggles = page.locator(".toggle-password")

            # Wait for toggles to be available
            await toggles.first.wait_for()
            assert await toggles.count() == 2

            await toggles.nth(0).click()
            assert await pass_input.get_attribute("type") == "text"

            await toggles.nth(1).click()
            assert await confirm_input.get_attribute("type") == "text"
            await page.screenshot(path="signup_visible.png")

            print("Verification successful!")

            await browser.close()
    finally:
        php_server.terminate()

if __name__ == "__main__":
    asyncio.run(verify_toggles())
