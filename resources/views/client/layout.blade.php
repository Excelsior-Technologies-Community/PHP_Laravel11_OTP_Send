<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Client Auth' }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f2f5;
        }
        .auth-box {
            max-width: 420px;
            margin: 50px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .auth-title {
            font-weight: 700;
            font-size: 26px;
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-custom {
            background: #4A90E2;
            color: white;
            font-weight: 600;
        }
        .btn-custom:hover {
            background: #3a78bf;
        }
    </style>
</head>
<body>

    @yield('content')

</body>
</html>
