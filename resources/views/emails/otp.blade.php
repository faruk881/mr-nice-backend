<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px; background:#f5f7fa;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; padding:30px; font-family:Arial, sans-serif;">
                
                <!-- Logo -->
                <tr>
                    <td align="center" style="padding-bottom:20px;">
                        {{-- <img src="{{ url('storage/images/logo.png') }}" alt="{{ config('app.name') }}" width="140"> --}}
                    </td>
                </tr>

                <!-- Title -->
                <tr>
                    <td align="center">


                        @if($otp_from === "register")
                        <h2 style="margin:0; color:#333;">Email Verification</h2>
                        @endif
                        
                        @if($otp_from === "password_reset")
                        <h2 style="margin:0; color:#333;">Email Password reset</h2>
                        @endif
                    
                    </td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td align="center" style="padding-top:15px; color:#555; font-size:15px;">
                        <p style="margin:0;">Hi <strong>{{ $name }}</strong>,</p>

                        @if($otp_from === "register")
                        <p style="margin:10px 0 0;">
                            Thank you for signing up with <strong>{{ config('app.name') }}</strong>.
                            Please use the following email verification code to continue:
                        </p>
                        @endif

                        @if($otp_from === "password_reset")
                        <p style="margin:10px 0 0;">
                            You requested a password reset for your <strong>{{ config('app.name') }}</strong> account.
                            Please use the following password reset code to proceed:
                        </p>
                        @endif

                    </td>
                </tr>

                <!-- OTP -->
                <tr>
                    <td align="center" style="padding:30px 0;">
                        <div style="
                            font-size:36px;
                            font-weight:bold;
                            letter-spacing:6px;
                            color:#D29D00;
                            background:#fff7e0;
                            display:inline-block;
                            padding:12px 24px;
                            border-radius:6px;
                        ">
                            {{ $otp }}
                        </div>
                    </td>
                </tr>

                <!-- Info -->
                <tr>
                    <td align="center" style="color:#555; font-size:14px;">
                        <p style="margin:0;">This verification code will expire in <strong>10 minutes</strong>.</p>
                        <p style="margin:10px 0 0;">
                            If you did not request this email, you can safely ignore it.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding-top:30px; font-size:12px; color:#999;">
                        <p style="margin:0;">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                        <p style="margin:5px 0 0;">
                            This is an automated message, please do not reply.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
