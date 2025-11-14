<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

// --- 1. Definições da Entidade Prova ---
$tabela = 'Prova';
$pk_coluna = 'id_prova';
// Colunas principais da tabela Prova
$colunas = ['id_prova', 'id_disciplina', 'titulo', 'data_aplicacao', 'tempo_limite'];

// --- 2. Busca de Chaves Estrangeiras (Disciplinas) ---
try {
    $disciplinas = $pdo->query("SELECT id_disciplina, nome FROM Disciplina ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Trata erro caso a tabela Disciplina não exista ou a conexão falhe
    $disciplinas = [];
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
                p.tempo_limite
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
    <link rel="stylesheet" href="style.css" > </head>
<body>

<header>
    <div class="container">
        <h1>Gerenciamento de Provas</h1>
        <nav><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Nova Prova</a></nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>

    <h2>Provas Cadastradas</h2>
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
                    <td><?= htmlspecialchars($p['id_prova']) ?></td>
                    <td><?= htmlspecialchars($p['titulo']) ?></td>
                    <td><?= htmlspecialchars($p['nome_disciplina']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['data_aplicacao']))) ?></td>
                    <td><?= htmlspecialchars($p['tempo_limite']) ?></td>
                    <td class="acao">
                        <a class="btn btn-secondary" href="?acao=editar&id_prova=<?= urlencode($p['id_prova']) ?>">Editar</a>
                        <a class="btn btn-danger" href="?acao=excluir&id_prova=<?= urlencode($p['id_prova']) ?>"
                            onclick="return confirm('Excluir esta prova? Se houver questões, a exclusão falhará.');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

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
                <label for="titulo">Título</label>
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
                            <?= ($d['id_disciplina'] == $provaEdit['id_disciplina']) ? 'selected' : '' ?>>
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
                        value="<?= htmlspecialchars($provaEdit['data_aplicacao']) ?>">
            </div>
            <div class="form-group">
                <label for="tempo_limite">Tempo Limite (HH:MM:SS)</label>
                <input type="time" id="tempo_limite" name="tempo_limite" step="1" required
                        value="<?= htmlspecialchars($provaEdit['tempo_limite']) ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
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