# ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban
# System Files Only

The Biometric Attendance Payroll System is a collaborative class project undertaken by the BSIT-3A students of ACLC College Tacloban under the course Application Lifecycle Management (ALM) . This project serves as the primary academic requirement for the semester, demonstrating the students' ability to apply software development methodologies to solve real-world problems.

The project partners with ACLC College Tacloban, specifically its Payroll Office, to develop a system that automates the integration of biometric attendance data with payroll computation. By addressing the actual needs of a functioning institution, the project provides students with invaluable hands-on experience in requirements gathering, system design, development, testing, and deployment.


---

## 📋 **Current Branches**
✅ **Frontend** - For frontend development  
✅ **Backend** - For backend development  
✅ **main** - Production/stable code (protected)

---

## **🎨 FRONTEND TEAM - Upload Files to Frontend Branch**

### **Step 1: Switch to Frontend Branch**
1. Go to your repository: https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban
2. Click the **branch dropdown** (currently showing "main")
3. Select **"Frontend"**

### **Step 2: Upload Your Files**
1. Click **"Add file"** → **"Upload files"**
2. Drag and drop your frontend files (HTML, CSS, JS, React components, etc.)
3. Add a descriptive commit message: `feat: add [component/page name]`
4. Click **"Commit changes"**

### **Step 3: Using Git Command Line (Recommended)**
```bash
# Clone the repo
git clone https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban.git
cd ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban

# Switch to Frontend branch
git checkout Frontend

#Config Git 
git config --global user.email "email address"
git config --global user.name "your name"

# Add your frontend files
# (Place files in appropriate folders)

# Stage and commit
git add .
git commit -m "feat: add login page and dashboard components"

# Push to Frontend branch
git push origin Frontend
```

---

## **⚙️ BACKEND TEAM - Upload Files to Backend Branch**

### **Step 1: Switch to Backend Branch**
1. Go to your repository
2. Click the **branch dropdown** (currently showing "main")
3. Select **"Backend"**

### **Step 2: Upload Your Files**
1. Click **"Add file"** → **"Upload files"**
2. Drag and drop your backend files (Python, Node.js, API endpoints, database models, etc.)
3. Add a descriptive commit message: `feat: add [module/API name]`
4. Click **"Commit changes"**

### **Step 3: Using Git Command Line (Recommended)**
```bash
# Clone the repo
git clone https://github.com/johnlcstudios/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban.git
cd ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban

#Config Git 
git config --global user.email "email address"
git config --global user.name "your name"

# Switch to Backend branch
git checkout Backend

# Add your backend files
# (Place files in appropriate folders)

# Stage and commit
git add .
git commit -m "feat: add user authentication API and database models"

# Push to Backend branch
git push origin Backend
```

---

## **📁 Recommended Folder Structure**

### **Frontend Branch:**
```
├── src/
│   ├── components/
│   ├── pages/
│   ├── assets/
│   ├── styles/
│   └── config/
├── public/
└── package.json
```

### **Backend Branch:**
```
├── src/
│   ├── controllers/
│   ├── models/
│   ├── routes/
│   ├── middleware/
│   ├── config/
│   └── utils/
├── tests/
└── requirements.txt (or package.json)
```

---

## **🔄 Workflow Summary**

| Step | Frontend Team | Backend Team |
|------|---------------|--------------|
| **1. Clone** | `git clone [repo URL]` | `git clone [repo URL]` |
| **2. Checkout** | `git checkout Frontend` | `git checkout Backend` |
| **3. Add Files** | Upload to Frontend branch | Upload to Backend branch |
| **4. Commit** | `git commit -m "feat: ..."` | `git commit -m "feat: ..."` |
| **5. Push** | `git push origin Frontend` | `git push origin Backend` |
| **6. Merge** | Create PR to main when ready | Create PR to main when ready |

---

## **✅ Best Practices**

- ✅ **Always work on your respective branch** (Frontend or Backend)
- ✅ **Use descriptive commit messages**
- ✅ **Keep the main branch stable** - Only merge tested code
- ✅ **Create Pull Requests (PRs)** before merging to main
- ✅ **Update README.md** with setup instructions
- ✅ **Review code** before merging

---

## **🚀 When Ready to Deploy**

Create a **Pull Request (PR)** from your branch → main:
1. Go to **"Pull requests"** tab
2. Click **"New pull request"**
3. Select **Frontend/Backend** → **main**
4. Add description and request reviews
5. Merge when approved

---

**Your branches are ready! Start uploading files now!** 🎉
