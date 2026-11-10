<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cambiar contraseña docente</title>
<style>body{background:#090f20;color:#e2e8f0;font:16px system-ui;min-height:100vh;display:grid;place-items:center;margin:0}main{width:min(420px,calc(100% - 40px));background:#111b30;padding:30px;border:1px solid #34425c;border-radius:16px}label{display:block;margin-top:16px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #52627e;border-radius:8px;background:#0c1425;color:white;margin-top:5px}button{margin-top:22px;border:0;background:#2563eb;color:white;padding:12px 18px;border-radius:8px;width:100%;cursor:pointer}.error{color:#fca5a5}</style></head>
<body><main><h1>Configurar contraseña</h1><p>Tu institución te proporcionó una contraseña inicial. Cámbiala para continuar.</p>
@if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('teacher.password.update') }}">@csrf
<label>Contraseña inicial<input type="password" name="current_password" required autocomplete="current-password"></label>
<label>Nueva contraseña (mínimo 12 caracteres)<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
<label>Confirmar contraseña<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
<button type="submit">Cambiar contraseña y continuar</button></form></main></body></html>
