{{-- <!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body>
    <h1>Welcome to ClexoMart!</h1>
    <p>Thank you for signing up. Please use the following OTP to verify your email address:</p>
    <h2>{{ $otp }}</h2>
    <p>This OTP is valid for 10 minutes. If you did not request this, please ignore this email.</p>
    <p>Best regards,<br>ClexoMart Team</p>
</body>
</html> --}}


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 40px 20px; color: #333;">

    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1);">

        <div style="text-align: center; margin-bottom: 20px;">
            <P  alt="ClexoMart Logo" style="height: 60px;">😊</P>
        </div>

        <h1 style="color: #2c3e50; text-align: center;">Welcome to <span style="color:#F0355E;">ClexoMart</span>!</h1>

        <p style="font-size: 16px; line-height: 1.6;">
            Thank you for signing up. Please use the following OTP to verify your email address:
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #00aaff;">{{ $otp }}</span>
        </div>

        <p style="font-size: 16px; line-height: 1.6;">
            This OTP is valid for <strong>10 minutes</strong>. If you did not request this, please ignore this email.
        </p>

        <p style="margin-top: 30px; font-size: 14px; color: #888;">
            Best regards,<br>
            <strong>ClexoMart Team</strong>
        </p>

    </div>

</body>
</html>
