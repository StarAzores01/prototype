<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">

    <h2>Welcome to PAThrive</h2>

    <p>Hello {{ $name }},</p>

    <p>
        An account has been created for you as a
        <strong>{{ $roleLabel }}</strong>.
    </p>

    <p>
        <strong>Email:</strong> {{ $email }}<br>
        <strong>Temporary password:</strong> {{ $temporaryPassword }}
    </p>

    <p>
        You can sign in here:
        <a href="{{ url('/login') }}">{{ url('/login') }}</a>
    </p>

    <p>
        For security, you will be required to create a new password
        the first time you sign in.
    </p>

    <p>PAThrive</p>

</body>
</html>
