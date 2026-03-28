# Royal Family Junior School — Results Portal (RFJS)

PHP/MySQL web application for **Royal Family Junior School** (*committed to excellence*). Staff manage learner marks, records, publishing, reporting, and staff directory data; parents and students use the public portal to look up published results.

**Repository:** [github.com/CharlesMabhande/attendance_royal](https://github.com/CharlesMabhande/attendance_royal)

---

## Features

| Area | What it does |
|------|----------------|
| **Public site** | Landing page (`index.php`), published results lookup (`result.php`), contact form |
| **Admin authentication** | Session-based login (`login.php`); passwords stored as plain text in DB (consider hashing for production) |
| **Role-based access** | Roles: `full`, `coordinator`, `teacher`, `records_officer`, `communications` — each unlocks a subset of dashboard tools (`admin/role_helpers.php`) |
| **Class-scoped logins** | Usernames like `grade3`, `ecda`, `ecdb` limit visibility to one grade/ECD group |
| **Marks** | Add/update/delete marks (`addmark.php`, multi-step flows, `updatemark*.php`, `deleteform.php`) |
| **Publish results** | Toggle visibility on `user_mark.published` so `result.php` only shows approved rows (`publish_results.php`) |
| **Bulk operations** | Promote term/grade, ECD year transitions, bulk delete term records (super-admin) |
| **Records** | Student listing, PDF/print exports (`allstudentdata.php`, jsPDF) |
| **School reports** | Filtered HTML print views plus CSV and PDF export (`school_reports.php`, `report_run.php`, FPDF in `admin/lib/`) |
| **Staff directory** | Staff CRUD, optional photos and certificate PDFs (`staff_list.php`, `staff_edit.php`) |
| **Messages** | View contact form submissions (`usermassage.php`) |
| **User management** | Create/edit/remove admin users (`manage_users.php`, super-admin only) |

---

## Tech stack

- **Backend:** PHP (procedural, `mysqli`)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS (`csss/rfjs-theme.css` — purple/gold RFJS branding), Font Awesome, Animate.css
- **PDF:** FPDF (`admin/lib/fpdf.php`) for report downloads
- **Typical stack:** [XAMPP](https://www.apachefriends.org/) (Apache + PHP + MySQL) on Windows, or any LAMP/WAMP host

---

## Project layout (high level)

```
attendance_royal/
├── index.php              # Public home
├── login.php              # Admin login
├── result.php             # Public results lookup (published rows only when column exists)
├── dbcon.php              # Database connection — edit for your environment
├── schema_helpers.php     # Schema checks (e.g. published column)
├── includes/
│   └── rfjs_branding.php  # Logo path helpers
├── csss/                  # Stylesheets (rfjs-theme.css = brand tokens)
├── image/
│   └── logo-rfjs.png      # School crest (UI + reports)
├── sql/                   # Migration / setup scripts (see below)
└── admin/
    ├── admindash.php      # Dashboard (role-gated cards)
    ├── role_helpers.php   # Permissions & class scope
    ├── report_helpers.php # Report data + CSV/PDF
    ├── school_reports.php / report_run.php
    ├── publish_results.php, manage_users.php
    ├── staff_*.php, bulk_*.php
    └── …                  # Marks, records, contact, about, etc.
```

Legacy duplicate tree under `image/ClickResult/` is kept for reference; primary entry points are at the paths above.

---

## Database setup

1. Create a MySQL database (default name in code: **`royalfam_sql`**).
2. Import the baseline schema, e.g. `sql/royalfam_sql.sql`, or your existing dump that includes tables such as `admin`, `student_data`, `user_mark`, `user_massage`.
3. Apply incremental updates from `sql/` as needed:
   - `alter_admin_role.sql` — `role` on `admin`
   - `alter_user_mark_published.sql` — `published` on `user_mark` (required for publish + public portal filtering)
   - `staff_directory.sql` / `staff_directory_photo.sql` — staff module

---

## Configuration

Edit **`dbcon.php`**:

```php
$con = mysqli_connect('host', 'user', 'password', 'database_name');
```

Default XAMPP local settings often use `localhost`, user `root`, empty password, and database `royalfam_sql`. On production/cPanel, use your host’s credentials and **avoid committing real passwords** (use ignored local overrides or environment variables if you refactor).

---

## Running locally (XAMPP)

1. Copy the project folder into your web root, e.g. `C:\xampp\htdocs\attendance_royal`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Create/import the database (see above).
4. Open `http://localhost/attendance_royal/` in a browser.
5. Log in via `http://localhost/attendance_royal/login.php` with an `admin` table user.

---

## Branding

School crest and favicon: **`image/logo-rfjs.png`**. Shared styling tokens live in **`csss/rfjs-theme.css`**.

---

## Security notes

- Passwords are currently compared/stored in plain text in the `admin` table; migrating to `password_hash()` / `password_verify()` is strongly recommended before production.
- Use HTTPS on public servers.
- Restrict file permissions on `dbcon.php` and upload directories.

---

## Screenshots (legacy gallery)

Older UI captures from an earlier “ClickResult” build may still illustrate the flow:

![Screenshot 1](https://user-images.githubusercontent.com/76203729/200107554-cd437b1b-fffa-4635-9204-3a1c9d69ec07.png)
![Screenshot 2](https://user-images.githubusercontent.com/76203729/200107517-ed6e5417-a0cb-465c-92da-ca6ed03b2529.png)
![Screenshot 3](https://user-images.githubusercontent.com/76203729/200107586-9590f071-c27f-4283-b80d-1ed23a0bd8a8.png)
![Screenshot 4](https://user-images.githubusercontent.com/76203729/200107597-af5b3fda-f725-4a60-a1c5-7281b78ff69e.png)
![Screenshot 5](https://user-images.githubusercontent.com/76203729/200107615-1fbd4e00-238c-47c7-9ef2-5c5a46e372a1.png)

---

## License

Use and deployment are subject to your school’s policies. Add a `LICENSE` file if you want an explicit open-source or proprietary terms.
