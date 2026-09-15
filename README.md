# Healthy Bharat Mission - Backend

This directory contains the production-ready PHP backend for the Healthy Bharat Mission project. 

## Architecture
- **PHP 8.3+**
- **MySQL 8.x** with PDO
- **Modular MVC / Service-based Architecture**
- **Secure by Default:** Features secure sessions, HttpOnly cookies, CORS headers, password hashing, and no raw DB credentials in code.

## Directory Structure
- `config/` - Environment & configuration logic
- `database/` - Schema, seeds, and migrations
- `public/` - Entry point (`index.php`), `.htaccess`, and public assets
- `src/` - Core application code
  - `Controllers/` - Request handlers (e.g. `AuthController.php`)
  - `Core/` - Router, Database connections
  - `Helpers/` - Environment loader, Response formatter, Logger, Request parser
  - `Middleware/` - Auth, CORS, Security filters
  - `Models/` - Data structures (future)
  - `Repositories/` - Data access layer (e.g. `AuthRepository.php`)
  - `Routes/` - API endpoint definitions (`api.php`)
  - `Services/` - Business logic (`AuthService.php`)
- `storage/` 
  - `logs/` - Application error logs
  - `public/images/` - Publicly accessible uploaded images
  - `private/` - Secure storage for user documents and digital downloads
- `tests/` - Future test suites

## Environment Setup
1. Copy `.env.example` to `.env`
2. Update the `.env` variables with your local database credentials.

## How to Run the Backend (Local Development)
You can run the backend using PHP's built-in web server. Note: You must route requests through the `public` folder.
```bash
cd backend/public
php -S localhost:8000
```

## Phase 3: Authentication & Authorization

### Authentication Endpoints

- **`POST /api/auth/register`**
  - **Body:** `{ "first_name": "...", "last_name": "...", "email": "...", "phone": "...", "password": "..." }`
  - **Behavior:** Validates fields, checks for duplicates, hashes password, creates User role, returns user data.
- **`POST /api/auth/login`**
  - **Body:** `{ "email": "..." (or phone), "password": "..." }`
  - **Behavior:** Validates password, generates 32-byte hex token, sets HttpOnly secure cookie, records in `sessions` table, returns `{ "token": "..." }`.
- **`GET /api/auth/me`**
  - **Headers:** `Authorization: Bearer <token>` (or rely on HttpOnly cookie)
  - **Behavior:** Validates session against DB. Returns safe profile data. Rejects inactive users.
- **`POST /api/auth/logout`**
  - **Behavior:** Deletes session from DB and clears cookie.
- **`POST /api/auth/verify-otp`**
  - **Body:** `{ "identifier": "...", "otp": "...", "purpose": "..." }`
  - **Behavior:** Checks expiry, increments attempt count, marks as used.
- **`POST /api/auth/forgot-password`**
  - **Body:** `{ "email": "..." }` (or phone)
  - **Behavior:** Generates secure reset token in `password_resets`. Mocks email send via logger. Does NOT expose if email exists.
- **`POST /api/auth/reset-password`**
  - **Body:** `{ "token": "...", "new_password": "...", "confirm_password": "..." }`
  - **Behavior:** Validates token. Hashes new password. Invalidates ALL active sessions for that user.

### OTP Flow
1. User requests action requiring OTP (e.g. Registration verification).
2. Service generates a random 6-digit code and saves it to `otps` table with 10-minute expiry and specific `type`.
3. In local dev, the OTP is written to `storage/logs/app.log`.
4. User submits OTP to `/api/auth/verify-otp`.
5. DB checks `is_used = 0`, `expires_at > NOW()`, and `attempt_count < 3`.
6. OTP marked as `is_used = 1`.

### RBAC Flow (Role-Based Access Control)
- Seeded roles: Super Admin (1), Admin (2), Expert (3), User (4).
- `AuthMiddleware::handle()` protects standard routes (validates session/token).
- `AuthMiddleware::handleAdmin()` protects admin routes (checks if `role_slug` is `admin` or `superadmin`).
- Database mapping: `users.role_id` -> `roles.id`.

### Frontend Integration Preparation

Existing frontend pages must be wired to the new backend endpoints using `fetch()` or `axios`.

**1. Register Page (`register.html`)**
- Catch form submit.
- `POST` to `/api/auth/register` with JSON body (first_name, last_name, email, phone, password).
- If `201 Created`, redirect to `login.html` with a success message (or `verify-otp.html` if OTP flow is enabled).
- If `422`, display the validation error message.

**2. Login Page (`login.html`)**
- `POST` to `/api/auth/login` with `email` and `password`.
- If `200 OK`, store the token in `localStorage` (if you prefer JS access) OR simply rely on the automatically set `HttpOnly` cookie. Redirect to `/store.html` or `/dashboard.html`.
- If `401`, display "Invalid credentials".

**3. Forgot Password (`forgot-password.html`)**
- `POST` to `/api/auth/forgot-password` with `email`.
- Always show "If this account exists, an email was sent" regardless of response to prevent enumeration.

**4. Reset Password (`reset-password.html`)**
- Requires the token from the URL (`?token=xyz`).
- `POST` to `/api/auth/reset-password` with `token`, `new_password`, `confirm_password`.
- If `200 OK`, redirect to `login.html`.

### Security Measures Implemented
- **Password Hashing:** Uses `PASSWORD_DEFAULT` (Bcrypt).
- **Session Management:** Secure Hex token in DB, matching HttpOnly cookie configuration.
- **SQL Injection:** Strict PDO prepared statements across `AuthRepository`.
- **Enumeration Protection:** Login error generically says "Invalid credentials". Forgot Password generically says "If account exists...".
- **Sensitive Logging:** Passwords, tokens, and OTPs are redacted or mocked appropriately in `Logger.php`.

## Phase 4: Content & CMS APIs

### Public Content Endpoints
All lists are paginated and return standardized `{success, message, data, meta}` objects.

- **`GET /api/health-conditions`** (List active health conditions)
- **`GET /api/health-conditions/{slug}`** (Retrieve detailed condition data)
- **`GET /api/programs`** (List active programs)
- **`GET /api/programs/{slug}`** (Retrieve program with its modules)
- **`GET /api/articles?category={slug}&search={keyword}`** (List published articles with filtering)
- **`GET /api/articles/{slug}`** (Retrieve full article)
- **`GET /api/article-categories`** & **`GET /api/article-tags`** (For filter sidebars)
- **`GET /api/faqs`** (List all active FAQs)
- **`GET /api/success-stories`** (List verified success stories)

### Public Form Submissions
- **`POST /api/contact`**
  - **Body:** `{ "name": "...", "email": "...", "phone": "...", "subject": "...", "message": "..." }`
  - **Behavior:** Stores in `contact_inquiries`.
- **`POST /api/newsletter/subscribe`**
  - **Body:** `{ "email": "..." }`
  - **Behavior:** Safely stores unique emails.

### Protected Admin CMS Endpoints
Requires `AuthMiddleware::handleAdmin()` (Bearer Token from Admin/SuperAdmin User).

- **`GET /api/admin/programs`** (Includes inactive/drafts)
- **`POST /api/admin/programs`** (Create program)
- **`PUT /api/admin/programs/{id}`** (Update program)
- **`DELETE /api/admin/programs/{id}`** (Soft delete program)
- **`GET /api/admin/articles`** (List all, including drafts)
- **`POST /api/admin/articles`** (Create draft/published article)
- **`GET /api/admin/contact-inquiries`** (List inquiries)
- **`GET /api/admin/newsletter-subscribers`** (List subscribers)

### Frontend Integration Mapping

- **`index.html` (Newsletter Footer)**: Catch form submit -> `POST /api/newsletter/subscribe` -> Show success toast.
- **`contact.html` (Contact Form component)**: Bind to `hbm-contact-form`. `POST /api/contact` -> Show success alert.
- **`health-condition.html`**: Call `GET /api/health-conditions` -> render condition cards dynamically.
- **`healthlibrary.html`**: Call `GET /api/articles` -> render list. Call `GET /api/article-categories` for the sidebar.
- **`successtories.html`**: Call `GET /api/success-stories` -> render videos/testimonials.

## Testing Instructions (Local)
1. Run server: `php -S localhost:8000 -t public/`
2. Open Postman or cURL.
3. Test Programs: `curl http://localhost:8000/api/programs`
4. Test Contact: `curl -X POST http://localhost:8000/api/contact -d '{"name":"Abhi", "email":"a@b.com", "subject":"Test", "message":"Hello"}'`
5. Admin route (Fail without token): `curl http://localhost:8000/api/admin/programs`
