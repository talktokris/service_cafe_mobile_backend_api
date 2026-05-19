<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Serve Cafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 10px;
        }
        .welcome-title {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #8B4513;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #A0522D;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">☕ Serve Cafe</div>
        </div>

        <h1 class="welcome-title">Welcome to Serve Cafe!</h1>

        <div class="content">
            <p>Hello {{ $user->name }},</p>
            
            <p>Welcome to <strong>Serve Cafe</strong>! We're excited to have you join our community of coffee lovers and cafe enthusiasts.</p>

            <div class="user-info">
                <h3>Your Account Details:</h3>
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Member Type:</strong> {{ ucfirst($user->member_type ?? 'Free') }}</p>
                <p><strong>Account Status:</strong> Active</p>
            </div>

            <div class="highlight">
                <h4>🚀 What's Next?</h4>
                <ul>
                    <li>Complete your profile setup</li>
                    <li>Explore our premium packages</li>
                    <li>Start earning rewards and commissions</li>
                    <li>Invite friends and grow your network</li>
                </ul>
            </div>

            <p>Ready to get started? Click the button below to access your account:</p>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Access My Account</a>
            </div>

            <p>If you have any questions or need assistance, don't hesitate to contact our support team. We're here to help!</p>

            <p>Thank you for choosing Serve Cafe!</p>

            <p>Best regards,<br>
            The Serve Cafe Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $user->email }} because you created an account with Serve Cafe.</p>
            <p>© {{ date('Y') }} Serve Cafe. All rights reserved.</p>
            <p>If you didn't create this account, please ignore this email.</p>
        </div>
    </div>
</body>
</html>
