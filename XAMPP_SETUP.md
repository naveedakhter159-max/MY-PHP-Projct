# XAMPP Setup Instructions

## Step 1: Check Your XAMPP Location
Your XAMPP installation should be in one of these locations:
- `C:\xampp\htdocs\` (default)
- `D:\xampp\htdocs\` (if on D: drive)
- Or wherever you installed XAMPP

## Step 2: Create Project Folder
1. Open Windows Explorer
2. Go to your XAMPP `htdocs` folder
3. Create a new folder called `MY-PHP-Project`
   - Full path should be: `C:\xampp\htdocs\MY-PHP-Project\`

## Step 3: Copy Project Files
Copy ALL these files/folders to `C:\xampp\htdocs\MY-PHP-Project\`:
- index.php
- login.php
- register.php
- dashboard.php
- create-story.php
- view-child.php
- view-story.php
- add-entry.php
- logout.php
- css/ (folder with style.css)
- js/ (folder with main.js)
- src/ (folder with all PHP classes)
- database/ (folder with schema.sql)
- images/ (folder)
- uploads/ (folder)
- .env (file)

## Step 4: Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Go to "SQL" tab
3. Paste contents of `database/schema.sql`
4. Click "Go"

## Step 5: Update .env File
1. Edit `.env` file
2. Update with your MySQL credentials:
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=        (leave empty if no password)
   DB_NAME=made_for_keeps
   APP_URL=http://localhost/MY-PHP-Project
   ```

## Step 6: Start XAMPP
1. Open XAMPP Control Panel
2. Start Apache
3. Start MySQL

## Step 7: Access the App
Open browser and go to:
```
http://localhost/MY-PHP-Project/
```

## Troubleshooting

### "Not Found" Error
- Check folder is in correct location
- Verify folder name matches URL (case-sensitive on some systems)
- Restart Apache after copying files

### "Undefined constant APP_NAME"
- Make sure .env file exists in root folder
- Check database.php can read .env

### "Cannot connect to database"
- Verify MySQL is running
- Check .env database credentials
- Make sure database is created via phpMyAdmin

## File Locations Reference
```
C:\xampp\htdocs\MY-PHP-Project\
├── index.php              ← Home page
├── login.php              ← Login
├── register.php           ← Register
├── dashboard.php          ← Dashboard
├── css/
│   └── style.css
├── js/
│   └── main.js
├── src/
│   ├── config/database.php
│   ├── Auth.php
│   └── helpers.php
├── database/
│   └── schema.sql
└── .env                   ← Configuration
```

