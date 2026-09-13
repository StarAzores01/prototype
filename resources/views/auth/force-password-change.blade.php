<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | PAThrive</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            font-size: 24px;
        }

        p {
            color: #666;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: 0;
            border-radius: 6px;
            background: #222;
            color: white;
            cursor: pointer;
        }

        .error {
            color: #c62828;
            margin-top: 5px;
            font-size: 14px;
        }

        .logout-form {
            margin-top: 18px;
            text-align: center;
        }

        .logout-form button {
            width: auto;
            margin-top: 0;
            padding: 0;
            border: 0;
            background: none;
            color: #666;
            font-size: 13px;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="card">
    <h1>Create a new password</h1>

    <p>
        You're currently using a temporary password.
        Please create your own password before continuing to PAThrive.
    </p>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf

        <label for="password">New password</label>

        <input
            id="password"
            type="password"
            name="password"
            required
            autofocus
        >

        @error('password')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="password_confirmation">Confirm new password</label>

        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
        >

        <button type="submit">
            Set New Password
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit">Log out instead</button>
    </form>
</div>

</body>
</html>
