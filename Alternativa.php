<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

// --- 1. Definições da Entidade Alternativa ---
$tabela = 'Alternativa';
$pk_coluna = 'id_alternativa';
$colunas = ['id_alternativa', 'id_questao', 'texto', 'correto'];

// Variável para armazenar mensagens de erro/sucesso
$msg = '';

// --- 2. Busca de Chaves Estrangeiras (Questões) ---
try {
    // Busca todas as questões para o campo FK, exibindo o ID e o título
    $questoes = $pdo->query("SELECT id_questao, titulo FROM Questao ORDER BY id_questao DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $questoes = [];
    $msg .= '<div class="alert alert-error">Erro ao carregar Questões. Verifique a tabela "Questao".</div>';
}

// --- 3. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Captura e sanitiza os dados do formulário
    $id_alternativa = trim($_POST['id_alternativa'] ?? '');
    $original_id = $_POST['original_id'] ?? ''; 
    
    // Campos da Alternativa
    $id_questao = trim($_POST['id_questao'] ?? '');
    $texto = trim($_POST['texto'] ?? '');
    // O campo 'correto' é booleano (checkbox), se marcado é 1, se não, é 0
    $correto = isset($_POST['correto']) ? 1 : 0; 

    // Verifica se os campos obrigatórios estão preenchidos (id_alternativa, id_questao e texto)
    if ($id_alternativa !== '' && $id_questao !== '' && $texto !== '') {

        // --- AÇÃO: Adicionar Nova Alternativa ---
        if ($acao === 'adicionar') {
            // Verificação de duplicidade na chave primária
            $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id");
            $chk->execute([':id' => $id_alternativa]);
            if ($chk->fetch()) {
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                exit;
            }

            // Insere a nova alternativa
            $sql = "INSERT INTO $tabela (id_alternativa, id_questao, texto, correto) 
                    VALUES (:id_a, :id_q, :txt, :corr)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_a' => $id_alternativa, 
                ':id_q' => $id_questao, 
                ':txt' => $texto, 
                ':corr' => $correto
            ]);
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;
        }

        // --- AÇÃO: Atualizar Alternativa Existente ---
        if ($acao === 'atualizar' && $original_id !== '') {

            if ($original_id !== $id_alternativa) {
                // Se o ID foi alterado, verifica duplicidade
                $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id AND $pk_coluna != :o");
                $chk->execute([':id' => $id_alternativa, ':o' => $original_id]);
                if ($chk->fetch()) {
                    header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                    exit;
                }
                // Atualiza ID e demais campos
                $sql = "UPDATE $tabela SET id_alternativa=:id_a, id_questao=:id_q, texto=:txt, correto=:corr WHERE $pk_coluna=:o";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_a' => $id_alternativa, 
                    ':id_q' => $id_questao, 
                    ':txt' => $texto, 
                    ':corr' => $correto, 
                    ':o' => $original_id
                ]);
            } else {
                // Atualiza apenas texto e correto (ID não foi alterado)
                $sql = "UPDATE $tabela SET id_questao=:id_q, texto=:txt, correto=:corr WHERE $pk_coluna=:id_a";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_q' => $id_questao, 
                    ':txt' => $texto, 
                    ':corr' => $correto, 
                    ':id_a' => $id_alternativa
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
        // ATENÇÃO: Se esta alternativa for a resposta_correta de alguma questão, a exclusão falhará.
        try {
            $del = $pdo->prepare("DELETE FROM $tabela WHERE $pk_coluna = :id");
            $del->execute([':id' => $id]);
        } catch (PDOException $e) {
            header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=fk_questao');
            exit;
        }
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

// --- AÇÃO: Editar (Prepara o Formulário) ---
$editando = false;
$alternativaEdit = ['id_alternativa' => '', 'id_questao' => '', 'texto' => '', 'correto' => 0];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        $s = $pdo->prepare("SELECT * FROM $tabela WHERE $pk_coluna = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $alternativaEdit = $row;
        }
    }
}

// --- 5. Listagem (Read) ---
// Junta a tabela Alternativa com a Questao para exibir o ID e parte do enunciado da questão
$sql_list = "SELECT 
                a.id_alternativa, 
                a.texto, 
                a.correto,
                q.id_questao,
                SUBSTRING(q.enunciado, 1, 50) AS enunciado_curto
             FROM $tabela a
             JOIN Questao q ON a.id_questao = q.id_questao
             ORDER BY a.id_questao, a.id_alternativa";
$lista = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);


// --- 6. Mensagem de Erro ---
if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'duplicidade') {
        $msg .= '<div class="alert alert-error">ID da Alternativa já existente.</div>';
    } elseif ($_GET['erro'] === 'fk_questao') {
        $msg .= '<div class="alert alert-error">Não é possível excluir a Alternativa. Ela está sendo usada como "Resposta Correta" em uma Questão.</div>';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD de Alternativas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css" > </head>
<body>

<header>
    <div class="container">
        <h1>Gerenciamento de Alternativas</h1>
        <nav><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Nova Alternativa</a></nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>

    <h2>Alternativas Cadastradas</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID Alt.</th>
                <th>ID Questão</th>
                <th>Questão (Início)</th>
                <th>Texto</th>
                <th>Correta?</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lista)): ?>
            <tr><td colspan="6">Nenhuma alternativa cadastrada.</td></tr> <?php else: ?>
            <?php foreach ($lista as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['id_alternativa']) ?></td>
                    <td><?= htmlspecialchars($a['id_questao']) ?></td>
                    <td><?= htmlspecialchars($a['enunciado_curto']) . (strlen($a['enunciado_curto']) < 50 ? '' : '...') ?></td>
                    <td><?= htmlspecialchars($a['texto']) ?></td>
                    <td><?= $a['correto'] ? '✅ Sim' : '❌ Não' ?></td>
                    <td class="acao">
                        <a class="btn btn-secondary" href="?acao=editar&id_alternativa=<?= urlencode($a['id_alternativa']) ?>">Editar</a>
                        <a class="btn btn-danger" href="?acao=excluir&id_alternativa=<?= urlencode($a['id_alternativa']) ?>"
                            onclick="return confirm('Excluir esta alternativa? ATENÇÃO: Se for a resposta correta de uma questão, a exclusão falhará.');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Alternativa' : 'Adicionar Nova Alternativa' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($alternativaEdit['id_alternativa']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 0.5;">
                <label for="id_alternativa">ID Alternativa</label>
                <input type="number" id="id_alternativa" name="id_alternativa" required
                        value="<?= htmlspecialchars($alternativaEdit['id_alternativa']) ?>">
            </div>
            <div class="form-group" style="flex: 2;">
                <label for="id_questao">Questão Associada</label>
                <select id="id_questao" name="id_questao" required>
                    <option value="">Selecione uma Questão</option>
                    <?php foreach ($questoes as $q): ?>
                        <option value="<?= htmlspecialchars($q['id_questao']) ?>"
                            <?= ($q['id_questao'] == $alternativaEdit['id_questao']) ? 'selected' : '' ?>>
                            ID <?= htmlspecialchars($q['id_questao']) ?>: <?= htmlspecialchars(substr($q['titulo'] ?? '', 0, 50)) ?>...
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="texto">Texto da Alternativa</label>
                <textarea id="texto" name="texto" rows="3" required style="width: 100%;"><?= htmlspecialchars($alternativaEdit['texto']) ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="correto" value="1" <?= $alternativaEdit['correto'] ? 'checked' : '' ?>> 
                    Esta é a Alternativa Correta?
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <button class="btn" type="submit">Adicionar Alternativa</button>
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