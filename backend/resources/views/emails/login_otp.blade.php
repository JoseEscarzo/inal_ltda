<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .card {
            background-color: #1e293b;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #334155;
            text-align: center;
        }

        /* NUEVOS ESTILOS DEL ENCABEZADO */
        .header-branding {
            margin-bottom: 24px;
        }

        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 12px;
        }

        .company-title {
            color: #38bdf8;
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .company-subtitle {
            color: #94a3b8;
            font-size: 16px;
            font-weight: normal;
            margin-top: 4px;
            letter-spacing: 2px;
        }

        h1 {
            font-size: 20px;
            color: #f8fafc;
            margin-bottom: 8px;
            margin-top: 16px;
        }

        p {
            color: #94a3b8;
            font-size: 16px;
            line-height: 1.5;
        }

        .otp-box {
            background-color: #0f172a;
            border: 2px dashed #2dd4bf;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            font-size: 32px;
            font-weight: bold;
            color: #2dd4bf;
            letter-spacing: 8px;
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">

            <div class="header-branding">
                <img src="{{ $message->embed(public_path('images/helados.png')) }}" alt="Helados Panda de Bolivia"
                    class="company-logo">
                <div class="company-title">Industria de Alimentos Ltda.</div>
                <div class="company-subtitle">INAL</div>
            </div>

            <h1>Acceso desde un dispositivo nuevo</h1>
            <p>Introduce este código para marcar este equipo como de confianza. La próxima vez podrás entrar solo con tu
                contraseña.</p>
            <div class="otp-box">{{ $otp }}</div>
            <p>Si no fuiste tú, cambia tu contraseña y revisa la actividad de tu cuenta.</p>
        </div>
        <div class="footer">&copy; {{ date('Y') }} Industria de Alimentos Ltda. - INAL.</div>
    </div>
</body>

</html>
