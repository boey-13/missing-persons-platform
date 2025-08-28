<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - FindMe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6B4C3B 0%, #8B6B5B 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .header-title {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.7;
        }
        .button-container {
            text-align: center;
            margin: 40px 0;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #6B4C3B 0%, #8B6B5B 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(107, 76, 59, 0.3);
        }
        .security-note {
            background-color: #f7fafc;
            border-left: 4px solid #6B4C3B;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .security-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .security-text {
            color: #4a5568;
            font-size: 14px;
            margin: 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer-text {
            color: #718096;
            font-size: 14px;
            margin: 0;
        }
        .link {
            color: #6B4C3B;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
        .expiry-note {
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .expiry-text {
            color: #c53030;
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">FindMe</div>
            <h1 class="header-title">Reset Your Password</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hello!</div>
            
            <div class="message">
                You are receiving this email because we received a password reset request for your FindMe account.
            </div>

            <div class="button-container">
                <a href="{{ $url }}" class="reset-button">
                    Reset Password
                </a>
            </div>

            <div class="expiry-note">
                <p class="expiry-text">
                    ⏰ This password reset link will expire in 60 minutes.
                </p>
            </div>

            <div class="security-note">
                <div class="security-title">🔒 Security Notice</div>
                <p class="security-text">
                    If you did not request a password reset, no further action is required. 
                    Your password will remain unchanged. This link is unique to your account 
                    and should not be shared with anyone.
                </p>
            </div>

            <div class="message">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
            </div>

            <div style="word-break: break-all; color: #6B4C3B; font-size: 14px; margin-top: 15px;">
                {{ $url }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                This email was sent to you because you requested a password reset for your FindMe account.
            </p>
            <p class="footer-text" style="margin-top: 10px;">
                If you have any questions, please contact our support team at 
                <a href="mailto:support@findme.com" class="link">support@findme.com</a>
            </p>
            <p class="footer-text" style="margin-top: 15px; font-size: 12px;">
                © 2025 FindMe. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
