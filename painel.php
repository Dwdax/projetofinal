<?php
// Arquivo principal do sistema de controle de provas.
// Simplesmente define a estrutura HTML e os links de navegação.

// Configurações básicas (para simular a estrutura do sistema)
$sistema_nome = "Sistema de Controle de Provas";
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title><?= $sistema_nome ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css" >
    <style>
        /* Estilos adicionais para o dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        .module-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        .module-card h3 {
            color: #058E4A; /* Verde Principal */
            margin-top: 0;
            font-size: 1.5rem;
        }
        .module-card p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .module-card .btn {
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <!-- Você pode substituir por h1 class="logo-text" se tiver a imagem logo.png -->
        <h1><?= $sistema_nome ?></h1>
        <nav>
            <a href="Professor.php" class="btn secondary">Cadastro do Professor</a>
        </nav>
    </div>
</header>

<main class="container">
    <h2>Painel de Controle</h2>
    <p>Bem-vindo ao sistema. Selecione um módulo para gerenciar os dados.</p>

    <div class="dashboard-grid">

        <!-- Módulo de Disciplinas (SEPARADO) -->
        <div class="module-card">
            <h3>Gerenciar Disciplinas</h3>
            <p>Cadastre e visualize as diferentes disciplinas oferecidas no sistema. Essencial para associar provas.</p>
            <a href="Disciplina.php" class="btn primary">Acessar Disciplinas</a>
        </div>
        
       
        <!-- Módulo de Provas -->
        <div class="module-card">
            <h3>Gerenciar Provas</h3>
            <p>Crie, edite e organize os exames e avaliações do sistema. Uma questão deve estar associada a uma prova.</p>
            <a href="Prova.php" class="btn">Acessar Provas</a>
        </div>

        <!-- Módulo de Questões -->
        <div class="module-card">
            <h3>Gerenciar Questões</h3>
            <p>Cadastre e mantenha as perguntas que compõem suas provas. Este módulo também permite a visualização rápida do enunciado.</p>
            <a href="Questao.php" class="btn">Acessar Questões</a>
        </div>

        <!-- Módulo de Alternativas -->
        <div class="module-card">
            <h3>Gerenciar Alternativas</h3>
            <p>Defina as opções de resposta para cada questão. É aqui que você informa qual alternativa é a correta.</p>
            <a href="Alternativa.php" class="btn secondary">Acessar Alternativas (Placeholder)</a>
        </div>
        
    </div>
</main>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — <?= $sistema_nome ?></small>
    </div>
</footer>
</body>
</html>