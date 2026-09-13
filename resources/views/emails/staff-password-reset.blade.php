<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">

    <h2>Reset your PAThrive password</h2>

    <p>Hello {{ $name }},</p>

    <p>
        We received a request to reset the password for your PAThrive
        account. Click the button below to choose a new password:
    </p>

    <p>
        <a
            href="{{ $resetUrl }}"
            style="display:inline-block;padding:12px 22px;background:#1A56DB;color:#fff;text-decoration:none;border-radius:6px;font-weight:600"
        >
            Reset My Password
        </a>
    </p>

    <p>
        Or copy and paste this link into your browser:<br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>

    <p>
        This link will expire in {{ $expiresInMinutes }} minutes and can only
        be used once.
    </p>

    <p>
        If you didn't request a password reset, you can safely ignore this
        email — your password will not be changed.
    </p>

    <p>PAThrive</p>

</body>
</html>
