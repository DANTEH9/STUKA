# STUCA

STUCA is a PHP and MySQL academic portal for course registration, teaching workflows, student resources, timetables, results, past papers, announcements, and academic operations.

## What is included

- Secure session login/logout with `password_hash`, `password_verify`, CSRF checks, and role-based page access.
- Role dashboards for Super Admin, Admin, Department Head, Lecturer, and Student users.
- Management pages for users, students, lecturers, departments, courses, modules, course registrations, materials, assignments, timetables, results, past papers, announcements, reports, settings, activity logs, profile, 403, and 404.
- PDO/MySQL repository with prepared statements. Demo data fallback is available only when explicitly enabled.
- Upload validation for `pdf`, `doc`, `docx`, `ppt`, `pptx`, `jpg`, `jpeg`, and `png` files.
- Full MySQL schema and seed data in `database/schema.sql`.
- AWS-ready `.env.example`, relative public paths, and RDS-friendly database configuration.

## Demo accounts

Seeded accounts use strong sample passwords for local testing:

| Role | Email | Password |
| --- | --- | --- |
| Super Admin | `super@stuca.local` | `StucaSuper@2026` |
| Admin | `admin@stuca.local` | `StucaAdmin@2026` |
| Department Head | `head@stuca.local` | `StucaHead@2026` |
| Lecturer | `lecturer@stuca.local` | `StucaLecturer@2026` |
| Student | `student@stuca.local` | `StucaStudent@2026` |

Change these passwords before using the project outside a local test environment.

## Local setup

1. Install PHP 8.1+ with PDO MySQL enabled and install MySQL 8+ or MariaDB.

2. Copy the environment sample:

```powershell
Copy-Item .env.example .env
```

3. Update `.env` if your database credentials differ:

```text
STUCA_DB_HOST=127.0.0.1
STUCA_DB_PORT=3306
STUCA_DB_NAME=stuca
STUCA_DB_USER=root
STUCA_DB_PASS=
STUCA_ALLOW_DEMO_FALLBACK=false
```

4. Create and seed the database:

```powershell
mysql -u root -p < database/schema.sql
```

5. Start the local PHP server from the project root:

```powershell
php -S localhost:8000 -t public
```

6. Open `http://localhost:8000`.

By default, STUCA fails loudly if MySQL is not connected so you know whether the live database is being used. Set `STUCA_ALLOW_DEMO_FALLBACK=true` only when you intentionally want to review the interface with built-in demo data.

## AWS deployment notes

- Use EC2 or Lightsail with Apache or Nginx, PHP 8.1+, and the required PDO MySQL extension.
- Set the web root to the `public/` directory.
- Store production environment values in the server environment or a protected `.env` file. Do not commit real credentials.
- Create an Amazon RDS MySQL database and set:
  - `STUCA_DB_HOST` to the RDS endpoint.
  - `STUCA_DB_PORT` to `3306`.
  - `STUCA_DB_NAME`, `STUCA_DB_USER`, and `STUCA_DB_PASS` to the RDS database credentials.
- Import `database/schema.sql` into RDS before first use.
- Ensure `public/uploads/` is writable by the web server user.
- Keep `public/uploads/.htaccess` or equivalent Nginx rules so uploaded scripts cannot execute.

## Folder structure

```text
app/
  config/        Runtime and database configuration
  data/          Demo fallback data
  includes/      Auth, helpers, layout, CSRF, upload handling
  models/        PDO database and demo repositories
database/        MySQL schema and seed records
docs/            Supplied project reference documents and UI assets
public/          Web root and all routable PHP pages
public/assets/   CSS, JavaScript, and images
public/uploads/  Validated user uploads
```

## Security checklist

- Passwords are hashed with `password_hash`.
- Login uses `password_verify`.
- Forms include CSRF tokens.
- Database access uses PDO prepared statements.
- Output is escaped with `htmlspecialchars`.
- Dashboard pages call role guards before sensitive actions.
- Upload extensions and file sizes are validated before saving.
- Production database credentials should be provided through environment variables.
