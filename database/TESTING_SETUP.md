# Database Testing Setup

## Overview
This project uses a separate database for testing to prevent test data from affecting the development/production database.

## Configuration

### 1. Testing Database Configuration
The testing database is configured in two places:

#### `.env.testing` file
- Contains all testing-specific environment variables
- Uses database: `apku_testing`
- Uses credentials: `root` / `root`

#### `phpunit.xml` file
- Sets environment variables for PHPUnit test execution
- Database: `apku_testing`
- Connection: `mariadb`

### 2. Development Database
The development database remains unchanged:
- Database: `apku`
- Configured in `.env` file

## Setup Instructions

### Step 1: Create Testing Database

**Option A: Using Laravel Artisan (Recommended - No MySQL CLI needed)**

```bash
# Create the database manually first using one of the methods below, then:
php artisan migrate --env=testing
```

**Option B: Find and Use MariaDB Directly**

If MariaDB is installed but not in PATH, find it first:

```powershell
# Common MariaDB locations on Windows:
# XAMPP: C:\xampp\mysql\bin\mysql.exe
# WAMP: C:\wamp64\bin\mariadb\mysql10.x.x\bin\mysql.exe
# Laragon: C:\laragon\bin\mysql\mariadb-10.x.x\bin\mysql.exe

# Example with full path (adjust to your installation):
& "C:\xampp\mysql\bin\mysql.exe" -u root -p < database\create_testing_db.sql
```

**Option C: Add MariaDB to PATH (Permanent Solution)**

1. Find your MariaDB installation folder (e.g., `C:\xampp\mysql\bin`)
2. Add it to System PATH:
   - Search "Environment Variables" in Windows
   - Edit "Path" variable
   - Add your MariaDB bin folder path
3. Restart PowerShell/Command Prompt
4. Then run:
   ```powershell
   mysql -u root -p < database\create_testing_db.sql
   ```

**Option D: Manual Database Creation**

Connect to MySQL using a GUI tool (phpMyAdmin, MySQL Workbench, etc.) and run:
```sql
CREATE DATABASE IF NOT EXISTS apku_testing;
GRANT ALL PRIVILEGES ON apku_testing.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON apku_testing.* TO 'root'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### Step 2: Run Migrations on Testing Database
When running tests, Laravel will automatically run migrations on the testing database. To manually run migrations:

```bash
# Using artisan with testing environment
php artisan migrate --env=testing

# Or using refresh (rollback and re-run all migrations)
php artisan migrate:refresh --env=testing
```

### Step 3: Run Tests
```bash
# Run all tests
php artisan test

# Run specific test group
php artisan test --group=render

# Run with coverage
php artisan test --coverage
```

## How It Works

1. When `APP_ENV=testing` (set in phpunit.xml), Laravel automatically loads `.env.testing` file
2. The `.env.testing` file contains database configuration pointing to `apku_testing`
3. All tests run against the `apku_testing` database
4. The development database (`apku`) remains untouched

## Important Notes

- **Never** run tests with `APP_ENV=local` or `APP_ENV=production` as it will use the development database
- The `.env.testing` file is gitignored to prevent accidental commits
- Each test run can be configured to refresh the database using migration refresh
- For isolated tests, consider using `RefreshDatabase` trait (currently commented out in tests)

## Troubleshooting

### Database Connection Error
If you get a database connection error:
1. Verify MariaDB is running
2. Check credentials in `.env.testing` match your MariaDB setup
3. Ensure the `apku_testing` database exists

### Tests Using Wrong Database
If tests are using the wrong database:
1. Check that `APP_ENV=testing` is set in `phpunit.xml`
2. Verify `.env.testing` file exists and has correct database name
3. Clear config cache: `php artisan config:clear`

## Security
- `.env.testing` is added to `.gitignore` to prevent committing sensitive testing credentials
- The testing database should only be used for test data
- Never use production database credentials in `.env.testing`