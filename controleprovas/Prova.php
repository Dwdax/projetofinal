<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

// --- 1. Definições da Entidade Prova ---
$tabela = 'Prova';
$pk_coluna = 'id_prova';
// Colunas principais da tabela Prova
$colunas = ['id_prova', 'id_disciplina', 'titulo', 'data_aplicacao', 'tempo_limite'];

// --- 2. Busca de Chaves Estrangeiras (Disciplinas) ---
$disciplinas = [];
$msg_disciplina = '';
try {
    $disciplinas = $pdo->query("SELECT id_disciplina, nome FROM Disciplina ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Trata erro caso a tabela Disciplina não exista ou a conexão falhe
    $msg_disciplina = '<div class="alert alert-error">Erro ao carregar Disciplinas. Verifique a tabela "Disciplina".</div>';
}

// --- 3. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Captura e sanitiza os dados do formulário
    $id_prova = trim($_POST['id_prova'] ?? '');
    $original_id = $_POST['original_id'] ?? ''; // ID original para a atualização
    
    // Campos da Prova
    $id_disciplina = trim($_POST['id_disciplina'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $data_aplicacao = trim($_POST['data_aplicacao'] ?? '');
    $tempo_limite = trim($_POST['tempo_limite'] ?? '');

    // Verifica se todos os campos obrigatórios estão preenchidos
    if ($id_prova !== '' && $id_disciplina !== '' && $titulo !== '' && $data_aplicacao !== '' && $tempo_limite !== '') {

        // --- AÇÃO: Adicionar Nova Prova ---
        if ($acao === 'adicionar') {
            // Verificação de duplicidade na chave primária
            $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id");
            $chk->execute([':id' => $id_prova]);
            if ($chk->fetch()) {
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                exit;
            }

            // Insere a nova prova
            $sql = "INSERT INTO $tabela (id_prova, id_disciplina, titulo, data_aplicacao, tempo_limite) 
                    VALUES (:id_p, :id_d, :tit, :data, :tempo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_p' => $id_prova, 
                ':id_d' => $id_disciplina, 
                ':tit' => $titulo, 
                ':data' => $data_aplicacao, 
                ':tempo' => $tempo_limite
            ]);
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;
        }

        // --- AÇÃO: Atualizar Prova Existente ---
        if ($acao === 'atualizar' && $original_id !== '') {
            $up = null;

            if ($original_id !== $id_prova) {
                // Se o ID foi alterado, verifica duplicidade
                $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id AND $pk_coluna != :o");
                $chk->execute([':id' => $id_prova, ':o' => $original_id]);
                if ($chk->fetch()) {
                    header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                    exit;
                }
                // Atualiza ID e demais campos
                $sql = "UPDATE $tabela SET id_prova=:id_p, id_disciplina=:id_d, titulo=:tit, data_aplicacao=:data, tempo_limite=:tempo WHERE $pk_coluna=:o";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_p' => $id_prova, 
                    ':id_d' => $id_disciplina, 
                    ':tit' => $titulo, 
                    ':data' => $data_aplicacao, 
                    ':tempo' => $tempo_limite, 
                    ':o' => $original_id
                ]);
            } else {
                // Atualiza demais campos (ID não foi alterado)
                $sql = "UPDATE $tabela SET id_disciplina=:id_d, titulo=:tit, data_aplicacao=:data, tempo_limite=:tempo WHERE $pk_coluna=:id_p";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_d' => $id_disciplina, 
                    ':tit' => $titulo, 
                    ':data' => $data_aplicacao, 
                    ':tempo' => $tempo_limite, 
                    ':id_p' => $id_prova
                ]);
            }
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;
        }
    }
}

// --- 4. Processamento de Ações GET (Excluir e Editar) ---

// --- AÇÃO: Excluir ---
if (($_GET['acao'] ?? '') === 'excluir') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        // ATENÇÃO: A exclusão de prova pode ser restrita devido a Questões (tabela filha)
        try {
            $del = $pdo->prepare("DELETE FROM $tabela WHERE $pk_coluna = :id");
            $del->execute([':id' => $id]);
        } catch (PDOException $e) {
            header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=dependencia');
            exit;
        }
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- AÇÃO: Editar (Prepara o Formulário) ---
$editando = false;
$provaEdit = ['id_prova' => '', 'id_disciplina' => '', 'titulo' => '', 'data_aplicacao' => '', 'tempo_limite' => ''];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        $s = $pdo->prepare("SELECT * FROM $tabela WHERE $pk_coluna = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $provaEdit = $row;
        }
    }
}

// --- 5. Listagem (Read) ---
// Junta a tabela Prova com a Disciplina para exibir o nome da disciplina
$sql_list = "SELECT 
                p.id_prova, 
                p.titulo, 
                d.nome AS nome_disciplina, 
                p.data_aplicacao, 
                p.tempo_limite,
                p.id_disciplina -- Inclui ID da disciplina para link de edição
              FROM Prova p
              JOIN Disciplina d ON p.id_disciplina = d.id_disciplina
              ORDER BY p.data_aplicacao DESC";
$lista = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);


// --- 6. Mensagem de Erro ---
$msg = $msg_disciplina ?? ''; // Mensagem de erro ao carregar disciplinas
if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'duplicidade') {
        $msg .= '<div class="alert alert-error">ID da Prova já existente.</div>';
    } elseif ($_GET['erro'] === 'dependencia') {
        $msg .= '<div class="alert alert-error">Não é possível excluir a Prova. Existem Questões associadas.</div>';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD Simples de Provas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Estilo unificado do Professor.php, adaptado para Prova.php -->
    <style>
        :root {
            --color-primary: #058E4A; /* Verde Escuro da paleta */
            --color-secondary: #A1D5BB; /* Verde Claro da paleta */
            --color-danger: #D9534F; /* Vermelho padrão para exclusão */
            --color-text: #000000;
            --color-background: #FFFFFF;
            --color-light-bg: #F8F9FA;
            --color-border: #CCCCCC;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-light-bg);
            color: var(--color-text);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background-color: var(--color-primary);
            color: var(--color-background);
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            margin: 0;
            display: inline-block;
            vertical-align: middle;
        }

        header nav {
            float: right;
            line-height: 2;
        }

        main.container {
            padding-top: 30px;
            padding-bottom: 50px;
        }

        /* --- Botões --- */
        .btn {
            display: inline-block;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        /* Botão Primário (Verde Escuro) */
        .btn {
            background-color: var(--color-primary);
            color: var(--color-background);
        }

        .btn:hover {
            background-color: #046A36; /* Tom mais escuro */
        }
        
        /* Botão Secundário (Verde Claro) */
        .btn-secondary {
            background-color: var(--color-secondary);
            color: var(--color-text);
            border: 1px solid var(--color-secondary);
        }

        .btn-secondary:hover {
            background-color: #8FCCAA; /* Tom mais escuro */
        }

        /* Botão de Perigo (Excluir) */
        .btn-danger {
            background-color: var(--color-danger);
            color: var(--color-background);
        }

        .btn-danger:hover {
            background-color: #C9302C;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }


        /* --- Tabelas --- */
        .data-section {
            margin-bottom: 40px;
            background-color: var(--color-background);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .table th, .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #E0E0E0;
        }

        .table thead th {
            background-color: var(--color-secondary);
            color: var(--color-text);
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .table tbody tr:hover {
            background-color: #F0F0F0;
        }
        
        .table .acao a {
            margin-right: 5px;
        }

        /* --- Formulário --- */
        .form-card {
            background-color: var(--color-background);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-card h2 {
            margin-top: 0;
            color: var(--color-primary);
            margin-bottom: 25px;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 10px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Adicionado para responsividade */
        }

        .form-group {
            flex: 1;
            min-width: 200px; /* Garante que os campos não fiquem muito estreitos */
        }
        
        /* Ajuste para o select e input[type=date/time] */
        .form-group select,
        .form-group input[type="date"],
        .form-group input[type="time"] {
            /* Adicionado para que o select e os campos de data/hora sigam o estilo dos inputs de texto/email */
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            box-sizing: border-box; 
            transition: border-color 0.3s ease;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            box-sizing: border-box; /* Inclui padding e borda na largura total */
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--color-primary);
            outline: none;
        }

        .form-actions {
            margin-top: 20px;
        }

        .form-actions .btn {
            margin-right: 10px;
        }

        /* --- Alertas --- */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* --- Footer --- */
        footer {
            background-color: #333;
            color: #ccc;
            padding: 15px 0;
            text-align: center;
        }

        /* Responsividade básica */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group {
                margin-bottom: 15px;
            }
            
            header .container, footer .container {
                text-align: center;
            }

            header nav {
                float: none;
                display: block;
                margin-top: 10px;
            }

            .table thead {
                display: none; /* Esconde o cabeçalho em telas pequenas */
            }

            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }

            .table tr {
                margin-bottom: 10px;
                border: 1px solid #E0E0E0;
                border-radius: 4px;
            }

            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: calc(50% - 30px);
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--color-primary);
            }
            
            .table td.acao {
                text-align: center;
                padding-left: 15px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Gerenciamento de Provas</h1>
        <!-- Link de Navegação Principal para voltar ao Dashboard -->
        <nav>
            <a href="painel.php" class="btn btn-secondary">← Painel de Controle</a>
        </nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>
    
    <!-- Botão de Novo Professor acima da tabela, usando a classe de botão primária (btn) -->
    <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn" style="margin-bottom: 20px;">+ Nova Prova</a>

    <div class="data-section">
        <h2 class="section-title">Lista de Provas Cadastradas</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Prova</th>
                    <th>Título</th>
                    <th>Disciplina</th>
                    <th>Data</th>
                    <th>Tempo Limite</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lista)): ?>
                <tr><td colspan="6">Nenhuma prova cadastrada.</td></tr> <?php else: ?>
                <?php foreach ($lista as $p): ?>
                    <tr>
                        <td data-label="ID Prova"><?= htmlspecialchars($p['id_prova']) ?></td>
                        <td data-label="Título"><?= htmlspecialchars($p['titulo']) ?></td>
                        <td data-label="Disciplina"><?= htmlspecialchars($p['nome_disciplina']) ?></td>
                        <td data-label="Data"><?= htmlspecialchars(date('d/m/Y', strtotime($p['data_aplicacao']))) ?></td>
                        <td data-label="Tempo Limite"><?= htmlspecialchars($p['tempo_limite']) ?></td>
                        <td data-label="Ações" class="acao">
                            <!-- Botão Editar (verde claro) -->
                            <a class="btn btn-secondary btn-small" href="?acao=editar&id_prova=<?= urlencode($p['id_prova']) ?>">Editar</a>
                            <!-- Botão Excluir (vermelho) -->
                            <a class="btn btn-danger btn-small" href="?acao=excluir&id_prova=<?= urlencode($p['id_prova']) ?>"
                                onclick="return confirm('ATENÇÃO: Excluir esta prova? Se houver questões, a exclusão falhará.');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Prova' : 'Adicionar Nova Prova' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($provaEdit['id_prova']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 0.5;">
                <label for="id_prova">ID Prova</label>
                <input type="number" id="id_prova" name="id_prova" required
                        value="<?= htmlspecialchars($provaEdit['id_prova']) ?>">
            </div>
            <div class="form-group" style="flex: 2;">
                <label for="titulo">Título (máx. 150)</label>
                <input type="text" id="titulo" name="titulo" maxlength="150" required
                        value="<?= htmlspecialchars($provaEdit['titulo']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="id_disciplina">Disciplina</label>
                <select id="id_disciplina" name="id_disciplina" required>
                    <option value="">Selecione uma Disciplina</option>
                    <?php foreach ($disciplinas as $d): ?>
                        <option value="<?= htmlspecialchars($d['id_disciplina']) ?>"
                            <?= ($d['id_disciplina'] == ($provaEdit['id_disciplina'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nome']) ?> (ID: <?= htmlspecialchars($d['id_disciplina']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="data_aplicacao">Data de Aplicação</label>
                <input type="date" id="data_aplicacao" name="data_aplicacao" required
                        value="<?= htmlspecialchars($provaEdit['data_aplicacao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="tempo_limite">Tempo Limite (HH:MM:SS)</label>
                <!-- O input type="time" deve ser formatado para aceitar HH:MM:SS. No HTML, isso é feito com step="1" -->
                <input type="time" id="tempo_limite" name="tempo_limite" step="1" required
                        value="<?= htmlspecialchars($provaEdit['tempo_limite'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <!-- Botão Primário (Verde Escuro) -->
                <button class="btn" type="submit">Salvar Alterações</button>
                <!-- Botão Secundário (Verde Claro) -->
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <!-- Botão Primário (Verde Escuro) -->
                <button class="btn" type="submit">Adicionar Prova</button>
            <?php endif; ?>
        </div>
    </form>
</main>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — Sistema Escola</small>
    </div>
</footer>
</body>
</html>