<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: white;
            color: #333;
            padding: 40px 20px;
            text-align: center;
            padding-bottom: 0;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header p {
            opacity: 0.9;
            margin: 0;
        }

        .login-body {
            padding: 40px;
        }

        .form-control {
            background-color: #f7f9fc;
            border: 2px solid #f7f9fc;
            border-radius: 10px;
            padding: 12px;
            font-size: 0.95rem;
            height: 50px;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #667eea;
            box-shadow: none;
        }

        .input-group-text {
            background-color: #f7f9fc;
            border: 2px solid #f7f9fc;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #667eea;
        }

        .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .form-control:focus+.input-group-text,
        .input-group:focus-within .input-group-text {
            background-color: #fff;
            border-color: #667eea;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #555;
        }

        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .role-card input[type="radio"] {
            display: none;
        }

        .role-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
            text-align: center;
        }

        .role-card label:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .role-card input[type="radio"]:checked+label {
            border-color: #667eea;
            background: #edf2ff;
            color: #667eea;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.15);
        }

        .role-card i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: #a0a0a0;
        }

        .role-card input[type="radio"]:checked+label i {
            color: #667eea;
        }

        .role-card span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .input-group-text.password-toggle {
            border-left: none;
            border-right: 2px solid #f7f9fc;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            background: #f7f9fc;
        }

        .input-group:focus-within .input-group-text.password-toggle {
            border-color: #667eea;
            background: #fff;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-header">
            <h1>
                <i class="fas fa-box-open"></i> StockMaster
            </h1>
            <p>Gestion d'Inventaire Intelligente</p>
        </div>

        <div class="login-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Erreur !</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('auth.login') }}">
                @csrf

                <!-- Sélection du Rôle (Mis en avant) -->
                <label class="form-label mb-2 d-block">Sélectionnez votre espace</label>
                <div class="role-selection">
                    <div class="role-card">
                        <input type="radio" id="front_office" name="role" value="front_office"
                            {{ old('role') === 'front_office' ? 'checked' : '' }} required>
                        <label for="front_office">
                            <i class="fas fa-cash-register"></i>
                            <span>Front Office</span>
                        </label>
                    </div>

                    <div class="role-card">
                        <input type="radio" id="back_office" name="role" value="back_office"
                            {{ old('role', 'back_office') === 'back_office' ? 'checked' : '' }} required>
                        <label for="back_office">
                            <i class="fas fa-user-cog"></i>
                            <span>Back Office</span>
                        </label>
                    </div>
                </div>
                @error('role')
                    <span class="error-message mb-3 d-block">{{ $message }}</span>
                @enderror

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}" placeholder="votre@email.com" required>
                    </div>
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de Passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" placeholder="Votre mot de passe" required
                            style="border-right: none; border-radius: 0;">
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="far fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-muted" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se Connecter
                </button>
            </form>

            <div class="register-link">
                Pas encore inscrit ? <a href="{{ route('register') }}">Créer un compte</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>
