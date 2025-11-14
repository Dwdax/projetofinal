<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

// --- 1. Definições da Entidade Questão ---
$tabela = 'Questao';
$pk_coluna = 'id_questao';
$colunas = ['id_questao', 'id_prova', 'titulo', 'enunciado', 'resposta_correta']; // resposta_correta pode ser NULL inicialmente

// Variável para armazenar mensagens de erro/sucesso
$msg = '';

// --- 2. Busca de Chaves Estrangeiras (Provas) ---
try {
    // Busca todas as provas para o campo FK
    $provas = $pdo->query("SELECT id_prova, titulo FROM Prova ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $provas = [];
    $msg .= '<div class="alert alert-error">Erro ao carregar Provas. Verifique a tabela "Prova".</div>';
}

// --- 3. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Captura e sanitiza os dados do formulário
    $id_questao = trim($_POST['id_questao'] ?? '');
    $original_id = $_POST['original_id'] ?? ''; 
    
    // Campos da Questão
    $id_prova = trim($_POST['id_prova'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $enunciado = trim($_POST['enunciado'] ?? '');
    // resposta_correta será tratado depois, pois exige que a alternativa exista
    $resposta_correta = null; 

    // Verifica se os campos obrigatórios estão preenchidos (id_questao, id_prova e enunciado)
    if ($id_questao !== '' && $id_prova !== '' && $enunciado !== '') {

        // --- AÇÃO: Adicionar Nova Questão ---
        if ($acao === 'adicionar') {
            // Verificação de duplicidade na chave primária
            $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id");
            $chk->execute([':id' => $id_questao]);
            if ($chk->fetch()) {
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                exit;
            }

            // Insere a nova questão
            $sql = "INSERT INTO $tabela (id_questao, id_prova, titulo, enunciado, resposta_correta) 
                    VALUES (:id_q, :id_p, :tit, :enu, :resp)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_q' => $id_questao, 
                ':id_p' => $id_prova, 
                ':tit' => $titulo, 
                ':enu' => $enunciado, 
                ':resp' => $resposta_correta // NULL ou o ID da alternativa correta, se inserido
            ]);
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;
        }

        // --- AÇÃO: Atualizar Questão Existente ---
        if ($acao === 'atualizar' && $original_id !== '') {

            if ($original_id !== $id_questao) {
                // Se o ID foi alterado, verifica duplicidade
                $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id AND $pk_coluna != :o");
                $chk->execute([':id' => $id_questao, ':o' => $original_id]);
                if ($chk->fetch()) {
                    header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                    exit;
                }
                // Atualiza ID e demais campos
                $sql = "UPDATE $tabela SET id_questao=:id_q, id_prova=:id_p, titulo=:tit, enunciado=:enu, resposta_correta=:resp WHERE $pk_coluna=:o";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_q' => $id_questao, 
                    ':id_p' => $id_prova, 
                    ':tit' => $titulo, 
                    ':enu' => $enunciado, 
                    ':resp' => $resposta_correta, 
                    ':o' => $original_id
                ]);
            } else {
                // Atualiza demais campos (ID não foi alterado)
                $sql = "UPDATE $tabela SET id_prova=:id_p, titulo=:tit, enunciado=:enu, resposta_correta=:resp WHERE $pk_coluna=:id_q";
                $up = $pdo->prepare($sql);
                $up->execute([
                    ':id_p' => $id_prova, 
                    ':tit' => $titulo, 
                    ':enu' => $enunciado, 
                    ':resp' => $resposta_correta, 
                    ':id_q' => $id_questao
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
        // ATENÇÃO: A exclusão de questão pode ser restrita devido a Alternativas (tabela filha)
        try {
            // Primeiro, apaga as alternativas relacionadas (se o CASCADE não estiver ativo)
            $pdo->prepare("DELETE FROM Alternativa WHERE id_questao = :id")->execute([':id' => $id]);
            // Depois, apaga a questão
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
$questaoEdit = ['id_questao' => '', 'id_prova' => '', 'titulo' => '', 'enunciado' => '', 'resposta_correta' => ''];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET[$pk_coluna] ?? '';
    if ($id !== '') {
        $s = $pdo->prepare("SELECT * FROM $tabela WHERE $pk_coluna = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $questaoEdit = $row;
        }
    }
}

// --- 5. Listagem (Read) ---
// Junta a tabela Questao com a Prova para exibir o título da prova
$sql_list = "SELECT 
                q.id_questao, 
                q.titulo, 
                SUBSTRING(q.enunciado, 1, 50) AS enunciado_curto,
                p.titulo AS titulo_prova, 
                q.resposta_correta
             FROM $tabela q
             JOIN Prova p ON q.id_prova = p.id_prova
             ORDER BY q.id_questao DESC";
$lista = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);


// --- 6. Mensagem de Erro ---
if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'duplicidade') {
        $msg .= '<div class="alert alert-error">ID da Questão já existente.</div>';
    } elseif ($_GET['erro'] === 'dependencia') {
        $msg .= '<div class="alert alert-error">Erro ao excluir a Questão. Verifique as alternativas associadas.</div>';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD de Questões</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css" > </head>
<body>

<header>
    <div class="container">
        <h1>Gerenciamento de Questões</h1>
        <nav><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Nova Questão</a></nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>

    <h2>Questões Cadastradas</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Prova</th>
                <th>Título</th>
                <th>Enunciado (Início)</th>
                <th>Resp. Correta (ID Alt.)</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lista)): ?>
            <tr><td colspan="6">Nenhuma questão cadastrada.</td></tr> <?php else: ?>
            <?php foreach ($lista as $q): ?>
                <tr>
                    <td><?= htmlspecialchars($q['id_questao']) ?></td>
                    <td><?= htmlspecialchars($q['titulo_prova']) ?></td>
                    <td><?= htmlspecialchars($q['titulo']) ?></td>
                    <td><?= htmlspecialchars($q['enunciado_curto']) . (strlen($q['enunciado_curto']) < 50 ? '' : '...') ?></td>
                    <td><?= htmlspecialchars($q['resposta_correta'] ?? 'N/A') ?></td>
                    <td class="acao">
                        <a class="btn btn-secondary" href="?acao=editar&id_questao=<?= urlencode($q['id_questao']) ?>">Editar</a>
                        <a class="btn btn-danger" href="?acao=excluir&id_questao=<?= urlencode($q['id_questao']) ?>"
                            onclick="return confirm('Excluir esta questão? As alternativas relacionadas serão APAGADAS.');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Questão' : 'Adicionar Nova Questão' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($questaoEdit['id_questao']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 0.5;">
                <label for="id_questao">ID Questão</label>
                <input type="number" id="id_questao" name="id_questao" required
                        value="<?= htmlspecialchars($questaoEdit['id_questao']) ?>">
            </div>
            <div class="form-group" style="flex: 2;">
                <label for="titulo">Título Curto (Opcional)</label>
                <input type="text" id="titulo" name="titulo" maxlength="150" 
                        value="<?= htmlspecialchars($questaoEdit['titulo']) ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="id_prova">Prova Associada</label>
                <select id="id_prova" name="id_prova" required>
                    <option value="">Selecione uma Prova</option>
                    <?php foreach ($provas as $p): ?>
                        <option value="<?= htmlspecialchars($p['id_prova']) ?>"
                            <?= ($p['id_prova'] == $questaoEdit['id_prova']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['titulo']) ?> (ID: <?= htmlspecialchars($p['id_prova']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="enunciado">Enunciado da Questão</label>
                <textarea id="enunciado" name="enunciado" rows="5" required style="width: 100%;"><?= htmlspecialchars($questaoEdit['enunciado']) ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="resposta_correta">ID da Alternativa Correta (Opcional/Avançado)</label>
                <input type="number" id="resposta_correta" name="resposta_correta" 
                    value="<?= htmlspecialchars($questaoEdit['resposta_correta']) ?>"
                    placeholder="Deixe em branco por enquanto se não tiver a alternativa criada.">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <a class="btn btn-secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <button class="btn" type="submit">Adicionar Questão</button>
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