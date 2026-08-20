<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        {{ $title ?? 'Client Auth' }}
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <style>
        body {
            background:
                linear-gradient(135deg,
                    #eef2ff,
                    #f8fafc);

            min-height: 100vh;
        }

        .auth-box {

            max-width: 450px;

            margin: 50px auto;

            background: #ffffff;

            padding: 35px;

            border-radius: 18px;

            box-shadow:
                0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .auth-title {

            font-weight: 700;

            font-size: 28px;

            text-align: center;

            margin-bottom: 25px;
        }

        .form-control,
        .form-select {

            min-height: 46px;

            border-radius: 10px;
        }

        .btn-custom {

            background: #4A90E2;

            color: white;

            font-weight: 600;

            min-height: 46px;

            border-radius: 10px;
        }

        .btn-custom:hover {

            background: #3a78bf;

            color: white;
        }

        .card {

            border-radius: 14px;
        }
    </style>

</head>


<body>

    @yield('content')

</body>

</html>