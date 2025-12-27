<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change - Boostio.uz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 100%;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 30px;
        }

        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 50px;
        }

        .error-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .error-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        h1 {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin: 16px 0;
        }

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }

        .success-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .success-box p {
            margin: 0;
            color: #065f46;
            font-size: 15px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">Boostio.uz</div>

        @if($success)
            <div class="icon">✅</div>
            <h1>Email Updated!</h1>

            <div class="success-box">
                <p>
                    <strong>Success!</strong> Your email address has been updated successfully.
                </p>
            </div>

            <div class="warning-box">
                <p>
                    <strong>⚠️ Important:</strong> Please verify your new email address to continue using all features.
                    Check your inbox for the verification email.
                </p>
            </div>

            <p style="font-size: 14px; margin-top: 20px;">
                You can close this window and return to the application.
            </p>
        @else
            <div class="icon error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1>Verification Failed</h1>
            <p>{{ $message ?? 'The verification link is invalid or has expired.' }}</p>
        @endif
    </div>
</body>

</html>