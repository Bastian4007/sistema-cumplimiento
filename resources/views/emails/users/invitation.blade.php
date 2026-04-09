<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación a VIGIA</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Hola, {{ $user->name }}</h2>

    <p>Has sido invitado a la plataforma VIGIA.</p>

    <p>
        <strong>Empresa:</strong> {{ $user->company->name ?? 'N/A' }}<br>
        <strong>Rol:</strong> {{ $user->role->name ?? 'N/A' }}
    </p>

    <p>
        Da clic en el siguiente enlace para verificar tu cuenta y crear tu contraseña:
    </p>

    <p>
        <a href="{{ route('invitation.accept', $user->invite_token) }}">
            Activar cuenta
        </a>
    </p>

    <p>
        Esta invitación expira el
        {{ optional($user->invite_expires_at)->format('d/m/Y H:i') }}.
    </p>
</body>
</html>