<?php
// Certifique-se de que o arquivo de conexão com o PDO existe e está acessível.
require_once __DIR__ . '/Conec.php'; 

// --- 1. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_professor = trim($_POST['id_professor'] ?? ''); // Chave primária
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $original_id = $_POST['original_id'] ?? ''; // ID original para a atualização

    // --- AÇÃO: Adicionar Novo Professor ---
    if ($acao === 'adicionar' && $id_professor !== '' && $nome !== '' && $email !== '') {
        // Verificação de duplicidade na chave primária
        $chk = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = :id");
        $chk->execute([':id' => $id_professor]);
        if ($chk->fetch()) {
            header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO professor (id_professor, nome, email) VALUES (:id, :n, :e)");
        $stmt->execute([':id' => $id_professor, ':n' => $nome, ':e' => $email]);
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }

    // --- AÇÃO: Atualizar Professor Existente ---
    if ($acao === 'atualizar' && $original_id !== '' && $id_professor !== '' && $nome !== '' && $email !== '') {
        if ($original_id !== $id_professor) {
            // Se o ID foi alterado, verifica duplicidade
            $chk = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = :id AND id_professor != :o");
            $chk->execute([':id' => $id_professor, ':o' => $original_id]);
            if ($chk->fetch()) {
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                exit;
            }

            // Atualiza ID e demais campos
            $up = $pdo->prepare("UPDATE professor SET id_professor=:id, nome=:n, email=:e WHERE id_professor=:o");
            $up->execute([':id' => $id_professor, ':n' => $nome, ':e' => $email, ':o' => $original_id]);
        } else {
            // Atualiza apenas nome e email (ID não foi alterado)
            $up = $pdo->prepare("UPDATE professor SET nome=:n, email=:e WHERE id_professor=:id");
            $up->execute([':n' => $nome, ':e' => $email, ':id' => $id_professor]);
        }
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- 2. Processamento de Ações GET (Excluir e Editar) ---

// --- AÇÃO: Excluir ---
if (($_GET['acao'] ?? '') === 'excluir') {
    $id = $_GET['id_professor'] ?? '';
    if ($id !== '') {
        // Adicionando verificação de segurança: não exclua se houver dependências
        // Neste exemplo, vamos apenas deletar, assumindo que a restrição FK está no banco ou será resolvida.
        $del = $pdo->prepare("DELETE FROM professor WHERE id_professor = :id");
        $del->execute([':id' => $id]);
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- AÇÃO: Editar (Prepara o Formulário) ---
$editando = false;
$professorEdit = ['id_professor' => '', 'nome' => '', 'email' => ''];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET['id_professor'] ?? '';
    if ($id !== '') {
        $s = $pdo->prepare("SELECT id_professor, nome, email FROM professor WHERE id_professor = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $professorEdit = $row;
        }
    }
}

// --- 3. Listagem (Read) ---
$lista = $pdo->query("SELECT id_professor, nome, email FROM professor ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);


// --- 4. Mensagem de Erro ---
$msg = '';
if (isset($_GET['erro']) && $_GET['erro'] === 'duplicidade') {
    $msg = '<div class="alert alert-error">ID do Professor já existente.</div>';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD Simples de Professores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Removido o link externo para style.css e adicionado estilo interno com as cores do tema -->
    <style>
        :root {
            --color-primary: #058E4A; /* Verde Escuro */
            --color-secondary: #A1D5BB; /* Verde Claro */
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

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            box-sizing: border-box; /* Inclui padding e borda na largura total */
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
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
        <h1>Professores</h1>
        <!-- Link de Navegação Principal para voltar ao Dashboard -->
        <nav>
            <a href="painel.php" class="btn btn-secondary">← Painel de Controle</a>
        </nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>
    
    <!-- Botão de Novo Professor acima da tabela, usando a classe de botão primária (btn) -->
    <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn" style="margin-bottom: 20px;">+ Novo Professor</a>

    <div class="data-section">
        <h2 class="section-title">Lista de Professores Cadastrados</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Professor</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lista)): ?>
                <tr><td colspan="4">Nenhum professor cadastrado.</td></tr> <?php else: ?>
                <?php foreach ($lista as $p): ?>
                    <tr>
                        <td data-label="ID Professor"><?= htmlspecialchars($p['id_professor']) ?></td>
                        <td data-label="Nome"><?= htmlspecialchars($p['nome']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($p['email']) ?></td>
                        <td data-label="Ações" class="acao">
                            <!-- Botão Editar (verde claro) -->
                            <a class="btn btn-secondary btn-small" href="?acao=editar&id_professor=<?= urlencode($p['id_professor']) ?>">Editar</a>
                            <!-- Botão Excluir (vermelho) -->
                            <a class="btn btn-danger btn-small" href="?acao=excluir&id_professor=<?= urlencode($p['id_professor']) ?>"
                                onclick="return confirm('ATENÇÃO: Excluir este professor? Todas as disciplinas e provas vinculadas podem ser afetadas. Confirma a exclusão?');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Formulário de Cadastro/Edição -->
    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Professor' : 'Adicionar Novo Professor' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($professorEdit['id_professor']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="id_professor">ID Professor (máx. 10)</label>
                <!-- O campo ID é a chave primária, deve ser imutável ao editar para evitar conflitos, 
                     mas como o código original permite a edição, mantenho-o. -->
                <input type="text" id="id_professor" name="id_professor" maxlength="10" required
                        value="<?= htmlspecialchars($professorEdit['id_professor']) ?>">
            </div>
            <div class="form-group">
                <label for="nome">Nome (máx. 100)</label>
                <input type="text" id="nome" name="nome" maxlength="100" required
                        value="<?= htmlspecialchars($professorEdit['nome']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Email (máx. 100)</label>
                <input type="email" id="email" name="email" maxlength="100" required
                        value="<?= htmlspecialchars($professorEdit['email']) ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <!-- Botão Cancelar (verde claro) -->
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <!-- Botão Adicionar (verde principal) -->
                <button class="btn" type="submit">Adicionar</button>
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