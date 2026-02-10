<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .container {
            background-color: #f4f4f4;
            padding: 30px;
            border-radius: 10px;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
        }

        .content {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <div class="content">
            <h2>Welcome, {{ $user->first_name }}!</h2>

            <p>Thank you for registering with {{ config('app.name') }}. To complete your registration, please verify
                your email address by clicking the button below:</p>

            <center>
                <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
            </center>

            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #007bff;">{{ $verificationUrl }}</p>

            <div class="warning">
                <strong>Important:</strong> This verification link will expire in 24 hours. If you don't verify your
                email within this time, you'll need to register again.
            </div>

            <p>If you didn't create an account with us, please ignore this email.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
