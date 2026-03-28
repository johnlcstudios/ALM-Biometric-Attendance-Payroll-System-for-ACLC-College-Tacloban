import random
import json
import os

def generate_synthetic_descriptor(label="test_user"):
    """
    Generates a mock 128-float descriptor for testing facial matching
    without needing a physical camera input.
    """
    descriptor = [round(random.uniform(-0.1, 0.1), 8) for _ in range(128)]
    return {
        "label": label,
        "descriptor": descriptor
    }

def export_test_suite(count=10):
    suite = [generate_synthetic_descriptor(f"User_{i}") for i in range(count)]
    with open("test_descriptors.json", "w") as f:
        json.dump(suite, f, indent=4)
    print(f"Generated {count} synthetic descriptors for matching tests.")

if __name__ == "__main__":
    # Useful for stress-testing the backend matching API
    export_test_suite(50)
