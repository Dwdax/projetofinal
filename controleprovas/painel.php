<?php

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
       
        :root {
            --color-primary: #058E4A; 
            --color-secondary: #A1D5BB; 
            --color-text: #000000; 
            --color-background: #FFFFFF; 
        }

        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        .module-card {
            background: var(--color-background);
            padding: 20px;
            
            border: 1px solid var(--color-secondary); 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .module-card:hover {
            transform: translateY(-5px);
            
            box-shadow: 0 8px 16px rgba(5, 142, 74, 0.1);
        }
        .module-card h3 {
            
            color: var(--color-primary); 
            margin-top: 0;
            font-size: 1.5rem;
        }
        .module-card p {
            
            color: var(--color-text); 
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
        
        <h1><?= $sistema_nome ?></h1>
        <nav>
            
            <a href="Professor.php" class="btn">Cadastro do Professor</a>
        </nav>
    </div>
</header>

<main class="container">
    <h2>Painel de Controle</h2>
    <p>Bem-vindo ao sistema. Selecione um módulo para gerenciar os dados.</p>

    <div class="dashboard-grid">

       
        <div class="module-card">
            <h3>Gerenciar Disciplinas</h3>
            <p>Cadastre e visualize as diferentes disciplinas oferecidas no sistema. Essencial para associar provas.</p>
            
            <a href="Disciplina.php" class="btn">Acessar Disciplinas</a>
        </div>
        
        
        <div class="module-card">
            <h3>Gerenciar Provas</h3>
            <p>Crie, edite e organize os exames e avaliações do sistema. Uma questão deve estar associada a uma prova.</p>
            <a href="Prova.php" class="btn">Acessar Provas</a>
        </div>

        
        <div class="module-card">
            <h3>Gerenciar Questões</h3>
            <p>Cadastre e mantenha as perguntas que compõem suas provas. Este módulo também permite a visualização rápida do enunciado.</p>
            <a href="Questao.php" class="btn">Acessar Questões</a>
        </div>

        
        <div class="module-card">
            <h3>Gerenciar Alternativas</h3>
            <p>Defina as opções de resposta para cada questão. É aqui que você informa qual alternativa é a correta.</p>
           
            <a href="Alternativa.php" class="btn">Acessar Alternativas (Placeholder)</a>
        </div>

        
        <div class="module-card">
            <h3>Realizar Prova </h3>
            <p>Ponto de acesso para o aluno selecionar e responder as provas disponíveis no sistema.</p>
            
            <a href="ProvaAluno.php" class="btn btn-success">Acessar Modo Aluno</a>
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