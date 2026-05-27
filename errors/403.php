<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé (403) - GEC ORG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .error-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 72px;
            color: #e63757;
            margin-bottom: 24px;
        }
        .error-title {
            font-weight: 700;
            color: #1e2e40;
            margin-bottom: 12px;
        }
        .error-message {
            color: #6b7b8d;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="fas fa-ban error-icon"></i>
        <h3 class="error-title">Accès Refusé</h3>
        <p class="error-message">Désolé, vous ne possédez pas les autorisations nécessaires pour accéder à cette page.</p>
        <a href="http://localhost/gestion_personnel/" class="btn btn-primary px-4 py-2 rounded-pill">
            Retour à l'accueil
        </a>
    </div>
</body>
</html>
