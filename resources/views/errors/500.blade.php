<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 Internal Server Error — Whistle-Works</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #1E1E2C;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 500px;
            padding: 2rem;
        }
        .code {
            font-size: 5rem;
            font-weight: 800;
            color: #EF4444;
            margin: 0;
            line-height: 1;
        }
        .title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background-color: #F29F67;
            color: #14141F;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="code">500</h1>
        <div class="title">Internal Server Anomaly</div>
        <p class="desc">An unexpected server error occurred. Our engineering team has been notified.</p>
        <a href="/admin/v2/dashboard" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>