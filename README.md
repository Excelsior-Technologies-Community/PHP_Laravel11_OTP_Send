
# 🔐 Laravel 11 – OTP Login System (Email + Twilio SMS)
![Laravel](https://img.shields.io/badge/Laravel-11-orange)
![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Twilio](https://img.shields.io/badge/Twilio-OTP-red)
![Email](https://img.shields.io/badge/SMTP-Mail-green)

A complete OTP-based authentication system using **Email (SMTP)** and **Twilio SMS**, built with Laravel 11.

---

# ⭐ Features
- Client Registration
- OTP login (Email + optional SMS)
- OTP expires in 5 minutes
- Secure session login
- Full Bootstrap UI
- Custom `clients` table (separate from default Laravel users)

---

# 🧱 1. Install Laravel

```
composer create-project laravel/laravel otp-system
php artisan serve
```

---

# 🛠 2. Configure Database + SMTP + Twilio

## Database (.env)
```
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=root
```

## SMTP Email Setup
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="My App"
```

## Twilio SMS Setup
```
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM=+1XXXXXXXX
```

---

# 🧱 3. Install Laravel UI Auth Scaffold

```
composer require laravel/ui
php artisan ui bootstrap --auth
npm install
npm run build
```

---

# 🧱 4. Create Clients Table Migration

```
php artisan make:migration create_clients_table
```

### Migration Fields
```php
$table->id();
$table->string('name')->nullable();
$table->string('email')->unique();
$table->string('phone')->nullable();
$table->string('password');
$table->string('login_otp')->nullable();
$table->timestamp('login_otp_expires_at')->nullable();
$table->timestamps();
```

Run migration:

```
php artisan migrate
```

---

# 👤 5. Client Model

```
php artisan make:model Client
```

```php
class Client extends Authenticatable
{
    protected $fillable = ['name','email','phone','password'];

    protected $hidden = ['password','login_otp'];

    protected $casts = [
        'login_otp_expires_at' => 'datetime',
    ];
}
```

---

# 🚏 6. Routes for OTP Authentication

```php
// Registration
Route::get('client/register', [ClientAuthController::class, 'registerForm']);
Route::post('client/register', [ClientAuthController::class, 'register']);

// Login
Route::get('client/login', [ClientAuthController::class, 'loginForm']);
Route::post('client/login/send-otp', [ClientAuthController::class, 'sendOtp']);

// OTP Verification
Route::get('client/login/verify-otp', [ClientAuthController::class, 'otpForm']);
Route::post('client/login/verify-otp', [ClientAuthController::class, 'verifyOtp']);

// Logout
Route::post('client/logout', [ClientAuthController::class, 'logout']);
```

---

# 🧠 7. OTP Authentication Logic (Controller Overview)

### ✔ Registration
- Validate fields
- Hash password
- Save client
- Send welcome email

### ✔ Send OTP
- Generate 6-digit OTP
- Save to DB with 5-minute expiry
- Email OTP
- Store client ID in session

### ✔ Verify OTP
- Check correct OTP
- Check expiry
- Login client
- Clear OTP fields

---

# 🎨 8. UI Views

The system includes:

### Registration Page
Fields:
- Full Name  
- Email  
- Phone  
- Password  

### Login Page
- Enter email → receive OTP  

### OTP Verification Page
- Enter 6-digit OTP  

### Layout Page
- Clean Bootstrap styling
- Center auth box  
- Styled buttons  

---

# ▶ Run Application

```
php artisan serve
```

Visit:

```
http://127.0.0.1:8000/client/register
```

<img width="628" height="462" alt="image" src="https://github.com/user-attachments/assets/72185ed7-bab7-455c-a9c1-38e642d24176" />

<img width="628" height="346" alt="image" src="https://github.com/user-attachments/assets/f2299fc5-5aa4-4c01-86df-3b849a2e4e25" />
<img width="979" height="767" alt="image" src="https://github.com/user-attachments/assets/3209e789-2792-4aee-83a9-4479a3acd762" />
