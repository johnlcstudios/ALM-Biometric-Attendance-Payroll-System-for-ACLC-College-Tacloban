# ALM Biometric Attendance & Payroll System

**ACLC College Tacloban**

## Overview

The Biometric Attendance Payroll System is a collaborative class project developed by BSIT-3A students at ACLC College Tacloban as part of the Application Lifecycle Management (ALM) course. This system automates the integration of biometric attendance data with payroll computation.

Developed in partnership with ACLC College Tacloban's Payroll Office, the system streamlines attendance tracking and payroll processing, reducing manual data entry and improving accuracy.

**Submission Date:** April 30, 2026

## Documentation

- [System Description](SystemDescription.md) - Comprehensive system overview
- [System Requirements](SystemRequirements.md) - Functional and non-functional requirements
- [System Architecture](SystemArchitecture.md) - Technical architecture and design patterns
- [System Design](SystemDesign.md) - Detailed design specifications
- [System Testing](SystemTesting.md) - Test plans and test cases
- [System Deployment](SystemDeployment.md) - Deployment procedures
- [System Maintenance](SystemMaintenance.md) - Maintenance guidelines
- [Features Implementation Guide](FEATURES_IMPLEMENTATION_GUIDE.md) - Feature implementation details
- [Changelog](Changelog.md) - Version history and updates

## Quick Start

### Prerequisites

**Git Installation**

Verify Git is installed:
```bash
git --version
```

If not installed, run:
```bash
winget install --id Git.Git -e --source winget 
$env:Path += ";C:\Program Files\Git\cmd"
git --version
```

**Git Configuration**

Configure your Git identity for proper commit attribution:
```bash
git config --global user.name "Your Full Name"
git config --global user.email "your.email@example.com"
```

Verify your configuration:
```bash
git config --global --list
```

### Initial Setup

1. Start XAMPP: Open the Control Panel and start Apache and MySQL services
2. Navigate to: `http://localhost/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/AI-ML-Test-Bench/`
3. Run database setup: Navigate to `setup-db.php`
   - This will create the database and apply all migrations automatically
4. Complete secure setup: Navigate to `secure-setup.php`
   - Create your company and administrator account
5. Log in with your created credentials

### Updating Existing Installation

For updates from a previous version:

1. Pull the latest code: `git pull origin main`
2. Log in as Administrator
3. Navigate to: `run-migrations.php`
   - The system automatically detects and applies pending migrations
4. New features are now available


## Repository Structure

### Branch Management

| Branch | Purpose |
|--------|---------|
| `main` | Production/stable code (protected, merge via pull request only) |
| `Frontend` | Frontend development (React, HTML, CSS, JavaScript) |
| `Backend` | Backend development (Node.js/Python, APIs, database) |
| `Biometric` | Biometric-related code (face-api.js, models, database schema) |

**Note:** The `main` branch is protected. All changes must be made through pull requests. Do not commit directly to `main`.

## Git Workflow

### Clone Repository (First Time Only)

```bash
git clone https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban.git
cd ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban
```

**Post-Clone Steps:**
- Rename all `.env.example` files to `.env`
- Download model files from [Releases](https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/releases/download/Alpha/models.zip) and extract
- Use `complete_schema.sql` for database initialization

### Switch to Your Team Branch

```bash
git checkout Frontend    # or Backend or Biometric
```

### Before Starting Work

Always fetch the latest changes:

```bash
git stash                          # Temporarily save local changes
git pull origin Frontend           # Fetch latest from remote
git stash pop                      # Restore your changes
```

### Make Changes and Commit

1. **Stage your changes:**
   ```bash
   git add .                       # or git add src/ for specific files
   ```

2. **Commit with a descriptive message:**
   ```bash
   git commit -m "feat: add login page and dashboard"
   ```

   **Commit Message Conventions:**
   - `feat:` – new feature
   - `fix:` – bug fix
   - `docs:` – documentation updates
   - `style:` – formatting, no code change
   - `refactor:` – code refactoring

### Push Changes

```bash
git push origin Frontend          # or Backend/Biometric
```

For the first push to a new branch:
```bash
git push -u origin Frontend
```

### Handle Merge Conflicts

If others have pushed while you were working:

```bash
git pull origin Frontend          # Attempt to merge remote changes
# Resolve any conflicts in your editor
git add .
git commit -m "resolve: merge conflicts"
git push origin Frontend
```

### Create a Pull Request

When your feature is complete and tested:

1. Go to the repository on GitHub
2. Click **Pull requests** → **New pull request**
3. Set base branch to `main` and compare branch to your working branch
4. Add a clear description of your changes
5. Request a review from a team member
6. After approval, merge the pull request

**Do not commit directly to `main`.** All changes must be reviewed and merged via pull request.

## Best Practices

- Work exclusively on your team's branch—never directly on `main`
- Pull before you push to avoid conflicts
- Write meaningful commit messages that describe what and why
- Keep commits atomic—one logical change per commit
- Test your code before pushing
- Create pull requests for all changes to `main`
- Review team members' pull requests before merging
- Update documentation when setup instructions change

## Common Git Commands

| Command | Purpose |
|---------|---------|
| `git status` | Check current branch and changes |
| `git log --oneline` | View commit history |
| `git branch` | List local branches |
| `git checkout -b new-branch` | Create and switch to a new branch |
| `git fetch` | Download remote changes without merging |
| `git diff` | View unstaged changes |
| `git reset --soft HEAD~1` | Undo last commit but keep changes |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "fatal: not a git repository" | Ensure you are inside the cloned folder |
| "Permission denied" | Check your SSH/GitHub authentication settings |
| "Merge conflict" | Open conflicted files, locate `<<<<<<<` markers, resolve differences, then commit |
| "Updates were rejected" | Run `git pull` before pushing |

## Support

For issues or additional information, refer to:
- `INSTALLATION_GUIDE.md` – Detailed installation steps
- `SystemArchitecture.md` – Technical specifications
- `Changelog.md` – Version history and updates
