<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <h2 style="margin-top:0;">Welcome to Electroteca, {{ $user->name }}!</h2>

        <p>Thanks for creating an account. Please verify your email to start requesting loans and using your account fully.</p>

        <p style="margin:20px 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:12px 20px;background:#1f2937;color:#fff;text-decoration:none;border-radius:6px;">Verify your email</a>
        </p>

        <!--
        <p style="margin-top:30px;color:#666;font-size:13px;">If the button doesn't work, copy and paste this link into your browser:</p>
        <p style="word-break:break-all;font-size:12px;color:#666;margin-top:8px;">{{ $verificationUrl }}</p>
        -->

        <p style="margin-top:30px;color:#666;font-size:13px;">— Electroteca</p>
    </div>
</body>
</html>
