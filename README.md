# ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban
# System Files Only

The Biometric Attendance Payroll System is a collaborative class project undertaken by the BSIT-3A students of ACLC College Tacloban under the course Application Lifecycle Management (ALM). This project serves as the primary academic requirement for the semester, demonstrating the students' ability to apply software development methodologies to solve real-world problems.

The project partners with ACLC College Tacloban, specifically its Payroll Office, to develop a system that automates the integration of biometric attendance data with payroll computation. By addressing the actual needs of a functioning institution, the project provides students with invaluable hands-on experience in requirements gathering, system design, development, testing, and deployment.

---

## 📋 **Current Branches**
- **`main`** – Production/stable code (protected, only merged via pull requests)
- **`Frontend`** – All frontend development (React, HTML, CSS, JS)
- **`Backend`** – All backend development (Node.js/Python, APIs, database)

---

## 🛠 **Prerequisites**
- Git installed on your computer ([Download Git](https://git-scm.com/downloads))
- A GitHub account with access to this repository
- (Optional) A code editor like VS Code

---

## 🔑 **Git Configuration (One-Time Setup)**
Before you start, configure your Git identity so your commits are properly attributed:

```bash
git config --global user.name "Your Full Name"
git config --global user.email "your.email@example.com"
```

Example:
```bash
git config --global user.name "JohnLC Studios"
git config --global user.email "jlcabajaan@gmail.com"
```

Verify the settings:
```bash
git config --global --list
```

---

## 🔄 **Step-by-Step: Pushing & Pulling (Workflow)**

### **1. Clone the Repository (First Time Only)**
```bash
git clone https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban.git
cd ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban
```

### **2. Switch to Your Team’s Branch**
- **Frontend Team:**
  ```bash
  git checkout Frontend
  ```
- **Backend Team:**
  ```bash
  git checkout Backend
  ```

### **3. Pull the Latest Changes (Always Do This Before Working)**
Always start by pulling the latest changes from the remote branch to avoid conflicts:
```bash
git pull origin Frontend   # or Backend
```

### **4. Add Your Files**
Place your files in the appropriate folders (see folder structure below). Then stage them:
```bash
git add .                  # Stages all new and modified files
```
You can also stage specific files:
```bash
git add src/components/Login.js
```

### **5. Commit Your Changes**
Write a clear, descriptive commit message:
```bash
git commit -m "feat: add login page and dashboard components"
```
Commit message conventions:
- `feat:` – new feature
- `fix:` – bug fix
- `docs:` – documentation updates
- `style:` – formatting, no code change
- `refactor:` – code refactoring

### **6. Push Your Changes to the Remote Branch**
```bash
git push origin Frontend   # or Backend
```
If this is your first push and you created a new branch locally, use:
```bash
git push -u origin Frontend
```
(Then later you can just use `git push`)

### **7. Verify on GitHub**
Go to the repository on GitHub and confirm your branch contains the latest commit.

---

## 🔁 **Working with Others – Pulling Updates**
If your teammates have pushed new changes while you were working, you need to pull them before you push:

```bash
git pull origin Frontend   # or Backend
```

If there are **merge conflicts**, Git will notify you. Resolve them manually in your code editor, then:
```bash
git add .
git commit -m "resolve merge conflicts"
git push origin Frontend
```

---

## 🚀 **Creating a Pull Request to Merge into `main`**
Once your feature is complete and tested on your branch, create a pull request (PR) to merge into `main`:

1. Go to the repository on GitHub.
2. Click **“Pull requests”** → **“New pull request”**.
3. Set **base** = `main` and **compare** = `Frontend` (or `Backend`).
4. Add a description of the changes.
5. Request a review from a team member.
6. After approval, click **“Merge pull request”**.

**Note:** Only merge into `main` after thorough testing and approval. Never commit directly to `main`.

---

## 📁 **Recommended Folder Structure**

### **Frontend Branch:**
```
├── src/
│   ├── components/      # Reusable UI components
│   ├── pages/           # Page-level components
│   ├── assets/          # Images, fonts, etc.
│   ├── styles/          # CSS/SCSS files
│   ├── services/        # API calls
│   └── config/          # Configuration files
├── public/
├── package.json
└── README.md
```

### **Backend Branch:**
```
├── src/
│   ├── controllers/     # Request handlers
│   ├── models/          # Database models
│   ├── routes/          # API routes
│   ├── middleware/      # Auth, logging, etc.
│   ├── config/          # Environment config
│   └── utils/           # Helper functions
├── tests/
├── requirements.txt     # Python dependencies
├── package.json         # Node.js dependencies
└── README.md
```

---

## ✅ **Best Practices**
- ✅ **Always work on your team’s branch** – never directly on `main`
- ✅ **Pull before you push** – avoid conflicts by staying up to date
- ✅ **Write meaningful commit messages** – describe what and why
- ✅ **Keep commits atomic** – one logical change per commit
- ✅ **Test your code** before pushing
- ✅ **Use pull requests** for merging into `main`
- ✅ **Update the README** when setup instructions change
- ✅ **Review team members’ code** before merging

---

## ❓ **Common Git Commands Cheat Sheet**
| Command | Purpose |
|---------|---------|
| `git status` | Check current branch and changes |
| `git log --oneline` | View commit history |
| `git branch` | List local branches |
| `git checkout -b new-branch` | Create and switch to a new branch |
| `git fetch` | Download remote changes without merging |
| `git diff` | See unstaged changes |
| `git reset --soft HEAD~1` | Undo last commit but keep changes |

---

## 🆘 **Troubleshooting**
- **“fatal: not a git repository”** – make sure you're inside the cloned folder.
- **“Permission denied”** – check your SSH/GitHub authentication.
- **“Merge conflict”** – open the conflicted files, look for `<<<<<<<`, fix the code, then commit.
- **“Updates were rejected”** – you forgot to pull; do `git pull` then push again.

---

**Your branches are ready! Start collaborating using the steps above!** 🎉
