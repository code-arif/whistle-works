<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
            background-color: #dc3545;
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
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
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
            <h2>Password Reset Request</h2>

            <p>Hello {{ $user->first_name }},</p>

            <p>We received a request to reset your password. Click the button below to create a new password:</p>

            <center>
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </center>

            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #dc3545;">{{ $resetUrl }}</p>

            <div class="warning">
                <strong>Security Notice:</strong> This password reset link will expire in 1 hour for your security.
            </div>

            <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
