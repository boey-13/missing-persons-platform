<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reply to your message</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #335a3b;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .message-box {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #335a3b;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Missing Persons Platform</h1>
        <p>Response to your inquiry</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $userName }},</p>
        
        <p>Thank you for contacting us. We have received your message regarding "<strong>{{ $originalSubject }}</strong>" and would like to provide you with the following response:</p>
        
        <div class="message-box">
            {!! nl2br(e($adminMessage)) !!}
        </div>
        
        <p>If you have any further questions or need additional assistance, please don't hesitate to contact us again.</p>
        
        <p>Best regards,<br>
        The Missing Persons Platform Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated response from the Missing Persons Platform.</p>
        <p>For urgent matters, please call our emergency hotline: 011-11223344</p>
    </div>
</body>
</html>
