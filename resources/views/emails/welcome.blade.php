<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FindMe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #6B4C3B;
            margin-bottom: 10px;
        }
        .tagline {
            color: #666;
            font-size: 16px;
        }
        .welcome-message {
            margin-bottom: 30px;
        }
        .welcome-message h1 {
            color: #6B4C3B;
            margin-bottom: 15px;
        }
        .welcome-message p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        .features {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .features h3 {
            color: #6B4C3B;
            margin-bottom: 15px;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        .cta-button {
            display: inline-block;
            background-color: #6B4C3B;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">FindMe</div>
            <div class="tagline">Bringing Families Back Together</div>
        </div>

        <div class="welcome-message">
            <h1>Welcome to FindMe, {{ $user->name }}! 👋</h1>
            <p>Thank you for joining our community dedicated to helping find missing persons and reuniting families.</p>
            <p>Your account has been successfully created and you're now part of a network of volunteers, families, and community members working together to make a difference.</p>
        </div>

        <div class="highlight">
            <strong>🎉 Your account is ready!</strong><br>
            You can now log in to your FindMe account and start contributing to our mission.
        </div>

        <div class="features">
            <h3>What you can do with FindMe:</h3>
            <ul>
                <li>Report missing persons and share important details</li>
                <li>Receive community alerts about missing persons in your area</li>
                <li>Share verified leads and tips</li>
                <li>Join volunteer search efforts</li>
                <li>Earn points for helpful contributions</li>
                <li>Access resources and support for families</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ url('/login') }}" class="cta-button">Log In to Your Account</a>
        </div>

        <div class="footer">
            <p><strong>FindMe</strong> - Making a difference, one family at a time.</p>
            <p>If you have any questions, please don't hesitate to contact our support team.</p>
            <p>&copy; 2025 FindMe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
