# Profile Picture Feature - Setup Instructions

## Overview
This feature allows employees to upload and change their profile pictures, which will be displayed in:
- Employee Self-Service (ESS) Profile Page
- Employee List (Admin/HR view)
- Attendance logs
- Other areas where employee avatars are shown

## Setup Steps

### 1. Run Database Migration
Visit this URL in your browser to add the `profile_picture` column to the employees table:
```
http://localhost/updated%20biometrics/main3/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/AI-ML-Test-Bench/run-migration.php
```

You should see: "✓ Migration successful! profile_picture column added."

### 2. Verify Uploads Directory
The following directory has been created:
```
AI-ML-Test-Bench/uploads/profiles/
```

Ensure this directory has proper write permissions (755).

### 3. Test the Feature

#### For Employees:
1. Login to the Employee Self-Service portal (ess.php)
2. Navigate to the "Profile" page
3. Click on the camera icon on the profile picture
4. Select an image file (JPG, PNG, GIF, or WebP, max 5MB)
5. The picture will upload and display immediately

#### For Admin/HR:
1. Login to the main dashboard (index.php)
2. Navigate to "Employees" page
3. You should see profile pictures next to employee names
4. If no picture is uploaded, a generated avatar with initials will show

## Features Implemented

✅ Database column for storing profile picture path
✅ API endpoint for uploading profile pictures (`upload_profile_picture`)
✅ File validation (type, size)
✅ Automatic deletion of old profile pictures
✅ Profile picture upload UI in ESS with camera icon
✅ Real-time preview after upload
✅ Employee list displays profile pictures
✅ Fallback to UI Avatars if no picture uploaded
✅ Cache busting for updated images

## Technical Details

### API Endpoint
- **Action**: `upload_profile_picture`
- **Method**: POST (multipart/form-data)
- **Parameters**: 
  - `profile_picture`: File input
- **Response**: 
  ```json
  {
    "success": true,
    "picture_url": "uploads/profiles/profile_123_1234567890.jpg"
  }
  ```

### File Storage
- **Location**: `AI-ML-Test-Bench/uploads/profiles/`
- **Naming**: `profile_{user_id}_{timestamp}.{extension}`
- **Max Size**: 5MB
- **Allowed Types**: JPEG, PNG, GIF, WebP

### Security Features
- Session authentication required
- File type validation
- File size limit (5MB)
- Unique filename generation
- Old file cleanup on new upload

## Troubleshooting

### Profile picture not uploading?
1. Check file size (must be under 5MB)
2. Check file type (only JPG, PNG, GIF, WebP)
3. Check uploads directory permissions
4. Check PHP upload_max_filesize in php.ini

### Profile picture not displaying?
1. Check if the file exists in uploads/profiles/
2. Check browser console for errors
3. Clear browser cache
4. Verify database has the correct path

### Database migration failed?
1. Check database connection in run-migration.php
2. Verify the column doesn't already exist
3. Check MySQL error logs

## Notes
- Profile pictures are automatically deleted when a new one is uploaded
- The system falls back to UI Avatars (initials) if no picture is set
- Images are not resized/cropped on upload - they're displayed with CSS object-fit: cover
- Cache busting (?t=timestamp) is used to show updated images immediately
