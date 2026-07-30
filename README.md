
# PHP_Laravel11_OTP_Send

This README explains how to send OTP via email and authenticate a user using OTP-based login.  
It includes Twilio setup, SMTP email setup, migrations, controller logic, and blade files.  
(Exactly based on the steps from your provided file.)

---

## Step 1: Install Laravel

If you have not created a Laravel project yet, run:

```
composer create-project laravel/laravel your-project-name
php artisan serve
```

---

## Step 2: Setup Database Configuration

Open `.env` and update database settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Add EMAIL + TWILIO credentials in `.env`

```
# SMTP Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_pass
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="My App"

# Twilio SMS
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM=+1XXXXXXXXXX
```

---

## Step 3: Install Auth Scaffold

Install Laravel UI:

```
composer require laravel/ui
```

Generate UI with Bootstrap:

```
php artisan ui bootstrap --auth
```

Install npm dependencies:

```
npm install
npm run build
```

---

## Step 4: Create Migration

Run:

```
php artisan make:migration create_clients_table
```

Migration file:

```php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->string('name')->nullable();
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('password');

    // OTP
    $table->string('login_otp')->nullable();
    $table->timestamp('login_otp_expires_at')->nullable();

    $table->timestamps();
});
```

Apply migration:

```
php artisan migrate
```

---

## Step 5: Create Model (Client)

Run:

```
php artisan make:model Client
```

`app/Models/Client.php`:

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

## Step 6: Create Routes

Add routes in `routes/web.php`:

```php
use App\Http\Controllers\ClientAuthController;

// Registration
Route::get('client/register', [ClientAuthController::class, 'registerForm'])->name('client.register.form');
Route::post('client/register', [ClientAuthController::class, 'register'])->name('client.register');

// Login
Route::get('client/login', [ClientAuthController::class, 'loginForm'])->name('client.login.form');
Route::post('client/login/send-otp', [ClientAuthController::class, 'sendOtp'])->name('client.login.sendOtp');

// OTP Verify
Route::get('client/login/verify-otp', [ClientAuthController::class, 'otpForm'])->name('client.otp.form');
Route::post('client/login/verify-otp', [ClientAuthController::class, 'verifyOtp'])->name('client.otp.verify');

// Logout
Route::post('client/logout', [ClientAuthController::class, 'logout'])->name('client.logout');

// Home
Route::get('/', function () { return view('welcome'); });
```

---

## Step 7: Create Controller (ClientAuthController)

Location: `app/Http/Controllers/ClientAuthController.php`

### Registration Logic

```php
public function register(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email'=> 'required|email|unique:clients',
        'password'=> 'required|min:6',
        'phone'=> 'nullable'
    ]);

    $client = Client::create([
        'name'=>$request->name,
        'email'=>$request->email,
        'phone'=>$request->phone,
        'password'=>Hash::make($request->password),
    ]);

    Mail::raw("Hello {$client->name}, registration successful!", function($msg) use ($client) {
        $msg->to($client->email)->subject("Welcome to Our App");
    });

    return redirect()->route('client.login.form')
        ->with('success','Registration successful! Please login.');
}
```

---

### Send OTP Logic

```php
public function sendOtp(Request $request)
{
    $request->validate(['email'=>'required|email']);

    $client = Client::where('email',$request->email)->first();

    if (!$client) {
        return back()->withErrors(['email'=>'Client not found']);
    }

    $otp = rand(100000, 999999);

    $client->login_otp = $otp;
    $client->login_otp_expires_at = now()->addMinutes(5);
    $client->save();

    Mail::raw("Your OTP is: {$otp}", function($mail) use ($client) {
        $mail->to($client->email)->subject("Your Login OTP");
    });

    session(['client_login_id'=>$client->id]);

    return redirect()->route('client.otp.form')
        ->with('success','OTP sent to your email!');
}
```

---

### Verify OTP Logic

```php
public function verifyOtp(Request $request)
{
    $request->validate(['otp'=>'required|digits:6']);

    $client = Client::find(session('client_login_id'));

    if (!$client) {
        return redirect()->route('client.login.form')
            ->withErrors(['otp'=>'Session expired']);
    }

    if ($client->login_otp != $request->otp) {
        return back()->withErrors(['otp'=>'Invalid OTP']);
    }

    if (now()->gt($client->login_otp_expires_at)) {
        return back()->withErrors(['otp'=>'OTP expired']);
    }

    $client->login_otp = null;
    $client->login_otp_expires_at = null;
    $client->save();

    Auth::login($client);

    return redirect('/')->with('success','Login successful!');
}
```

---

### Logout

```php
public function logout()
{
    Auth::logout();
    return redirect()->route('client.login.form');
}
```

---

## Step 8: Blade Templates

### Registration View (`client/registration.blade.php`)
- Full name  
- Email  
- Phone  
- Password  
- Submit button  

### Login View (`client/login.blade.php`)
- Email input  
- "Send OTP" button  

### OTP Verification (`client/verify-otp.blade.php`)
- OTP input  
- Verify button  

### Layout File (`client/layout.blade.php`)
- Bootstrap UI layout  
- Authentication box styling  

---

## Run Laravel App

Start server:

```
php artisan serve
```

Open:

```
http://127.0.0.1:8000/client/register
```

<img width="628" height="462" alt="image" src="https://github.com/user-attachments/assets/72185ed7-bab7-455c-a9c1-38e642d24176" />

<img width="628" height="346" alt="image" src="https://github.com/user-attachments/assets/f2299fc5-5aa4-4c01-86df-3b849a2e4e25" />
<img width="979" height="767" alt="image" src="https://github.com/user-attachments/assets/3209e789-2792-4aee-83a9-4479a3acd762" />
