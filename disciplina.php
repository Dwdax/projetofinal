<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

// --- 1. Definições da Entidade Disciplina ---
// A chave primária é 'id_disciplina'
$tabela = 'disciplina';
$pk_coluna = 'id_disciplina';
$colunas = ['id_disciplina', 'cod', 'nome'];

// --- 2. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    // Captura e sanitiza os dados do formulário
    $id_disciplina = trim($_POST['id_disciplina'] ?? '');
    $cod = trim($_POST['cod'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $original_id = $_POST['original_id'] ?? ''; // ID original para a atualização

    // --- AÇÃO: Adicionar Nova Disciplina ---
    if ($acao === 'adicionar' && $id_disciplina !== '' && $cod !== '' && $nome !== '') {
        // Verificação de duplicidade na chave primária
        $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id");
        $chk->execute([':id' => $id_disciplina]);
        if ($chk->fetch()) {
            header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
            exit;
        }

        // Insere a nova disciplina
        $stmt = $pdo->prepare("INSERT INTO $tabela (id_disciplina, cod, nome) VALUES (:id, :c, :n)");
        $stmt->execute([':id' => $id_disciplina, ':c' => $cod, ':n' => $nome]);
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }

    // --- AÇÃO: Atualizar Disciplina Existente ---
    if ($acao === 'atualizar' && $original_id !== '' && $id_disciplina !== '' && $cod !== '' && $nome !== '') {
        if ($original_id !== $id_disciplina) {
            // Se o ID foi alterado, verifica duplicidade
            $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id AND $pk_coluna != :o");
            $chk->execute([':id' => $id_disciplina, ':o' => $original_id]);
            if ($chk->fetch()) {
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                exit;
            }

            // Atualiza ID e demais campos
            $up = $pdo->prepare("UPDATE $tabela SET id_disciplina=:id, cod=:c, nome=:n WHERE $pk_coluna=:o");
            $up->execute([':id' => $id_disciplina, ':c' => $cod, ':n' => $nome, ':o' => $original_id]);
        } else {
            // Atualiza apenas cod e nome (ID não foi alterado)
            $up = $pdo->prepare("UPDATE $tabela SET cod=:c, nome=:n WHERE $pk_coluna=:id");
            $up->execute([':c' => $cod, ':n' => $nome, ':id' => $id_disciplina]);
        }
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- 3. Processamento de Ações GET (Excluir e Editar) ---

// --- AÇÃO: Excluir ---
if (($_GET['acao'] ?? '') === 'excluir') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        $del = $pdo->prepare("DELETE FROM $tabela WHERE $pk_coluna = :id");
        $del->execute([':id' => $id]);
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- AÇÃO: Editar (Prepara o Formulário) ---
$editando = false;
$disciplinaEdit = ['id_disciplina' => '', 'cod' => '', 'nome' => ''];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        $s = $pdo->prepare("SELECT id_disciplina, cod, nome FROM $tabela WHERE $pk_coluna = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $disciplinaEdit = $row;
        }
    }
}

// --- 4. Listagem (Read) ---
$lista = $pdo->query("SELECT id_disciplina, cod, nome FROM $tabela ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);


// --- 5. Mensagem de Erro ---
$msg = '';
if (isset($_GET['erro']) && $_GET['erro'] === 'duplicidade') {
    $msg = '<div class="alert alert-error">ID da Disciplina já existente.</div>';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD Simples de Disciplinas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css" >
</head>
<body>

<header>
    <div class="container">
        <h1>Disciplinas</h1>
        <nav><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Nova Disciplina</a></nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>

    <table class="table">
        <thead>
            <tr>
                <th>ID Disciplina</th>
                <th>Código</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lista)): ?>
            <tr><td colspan="4">Nenhuma disciplina cadastrada.</td></tr> <?php else: ?>
            <?php foreach ($lista as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['id_disciplina']) ?></td>
                    <td><?= htmlspecialchars($d['cod']) ?></td>
                    <td><?= htmlspecialchars($d['nome']) ?></td>
                    <td class="acao">
                        <a class="btn btn-secondary" href="?acao=editar&id_disciplina=<?= urlencode($d['id_disciplina']) ?>">Editar</a>
                        <a class="btn btn-danger" href="?acao=excluir&id_disciplina=<?= urlencode($d['id_disciplina']) ?>"
                            onclick="return confirm('Excluir esta disciplina?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Disciplina' : 'Adicionar Disciplina' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($disciplinaEdit['id_disciplina']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="id_disciplina">ID Disciplina</label>
                <input type="text" id="id_disciplina" name="id_disciplina" required
                        value="<?= htmlspecialchars($disciplinaEdit['id_disciplina']) ?>">
            </div>
            <div class="form-group">
                <label for="cod">Código (Cod)</label>
                <input type="text" id="cod" name="cod" required
                        value="<?= htmlspecialchars($disciplinaEdit['cod']) ?>">
            </div>
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" maxlength="100" required
                        value="<?= htmlspecialchars($disciplinaEdit['nome']) ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
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