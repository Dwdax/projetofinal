<?php
// Inclui o arquivo de conexão com o banco de dados
// NOTA: 'conec.php' e a variável $pdo DEVEM estar acessíveis neste escopo.
require_once __DIR__ . '/conec.php'; 

// --- 1. Definições da Entidade Questão ---
$tabela = 'Questao';
$pk_coluna = 'id_questao';
$colunas = ['id_questao', 'id_prova', 'titulo', 'enunciado', 'resposta_correta'];

// Variável para armazenar mensagens de erro/sucesso
$msg = ''; // Inicializa a variável para evitar erro no HTML

// --- 2. Busca de Chaves Estrangeiras (Provas) ---
try {
    // Busca todas as provas para o campo FK
    $provas = $pdo->query("SELECT id_prova, titulo FROM Prova ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro, define o array de provas como vazio e gera uma mensagem
    $provas = [];
    $msg .= '<div class="alert alert-error">Erro ao carregar Provas. Verifique a tabela "Prova" e se o banco de dados está online.</div>';
    // Você pode querer logar $e->getMessage() aqui, mas não exibir para o usuário final.
}

// --- 3. Processamento de Ações POST (Adicionar e Atualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Captura e sanitiza os dados do formulário
    // Garante que o ID da questão seja um inteiro não negativo
    $id_questao = (int)trim($_POST['id_questao'] ?? 0);
    $original_id = (int)trim($_POST['original_id'] ?? 0); 
    
    // Campos da Questão
    $id_prova = (int)trim($_POST['id_prova'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $enunciado = trim($_POST['enunciado'] ?? '');
    // Garante que 'resposta_correta' seja null se estiver vazio ou 0, se for um número válido.
    $resposta_correta_input = trim($_POST['resposta_correta'] ?? '');
    $resposta_correta = ($resposta_correta_input === '') ? null : (int)$resposta_correta_input; 

    // Verifica se os campos obrigatórios estão preenchidos
    if ($id_questao > 0 && $id_prova > 0 && $enunciado !== '') {

        // --- AÇÃO: Adicionar Nova Questão ---
        if ($acao === 'adicionar') {
            try {
                $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id");
                $chk->execute([':id' => $id_questao]);
                if ($chk->fetch()) {
                    header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                    exit;
                }
    
                $sql = "INSERT INTO $tabela (id_questao, id_prova, titulo, enunciado, resposta_correta) 
                        VALUES (:id_q, :id_p, :tit, :enu, :resp)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_q' => $id_questao, 
                    ':id_p' => $id_prova, 
                    ':tit' => $titulo, 
                    ':enu' => $enunciado, 
                    ':resp' => $resposta_correta
                ]);
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?sucesso=adicionado');
                exit;
            } catch (PDOException $e) {
                // Se der erro de FK ou outro erro de DB no insert
                $msg .= '<div class="alert alert-error">Erro ao adicionar Questão: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }

        // --- AÇÃO: Atualizar Questão Existente ---
        if ($acao === 'atualizar' && $original_id > 0) {
            try {
                $params = [
                    ':id_p' => $id_prova, 
                    ':tit' => $titulo, 
                    ':enu' => $enunciado, 
                    ':resp' => $resposta_correta, 
                ];
                
                if ($original_id !== $id_questao) {
                    // Tenta verificar se o novo ID já existe
                    $chk = $pdo->prepare("SELECT 1 FROM $tabela WHERE $pk_coluna = :id AND $pk_coluna != :o");
                    $chk->execute([':id' => $id_questao, ':o' => $original_id]);
                    if ($chk->fetch()) {
                        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?erro=duplicidade');
                        exit;
                    }
                    $sql = "UPDATE $tabela SET id_questao=:id_q, id_prova=:id_p, titulo=:tit, enunciado=:enu, resposta_correta=:resp WHERE $pk_coluna=:o";
                    $params[':id_q'] = $id_questao;
                    $params[':o'] = $original_id;
                } else {
                    $sql = "UPDATE $tabela SET id_prova=:id_p, titulo=:tit, enunciado=:enu, resposta_correta=:resp WHERE $pk_coluna=:id_q";
                    $params[':id_q'] = $id_questao;
                }

                $up = $pdo->prepare($sql);
                $up->execute($params);
                
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?sucesso=atualizado');
                exit;
            } catch (PDOException $e) {
                // Se der erro de FK ou outro erro de DB no update
                $msg .= '<div class="alert alert-error">Erro ao atualizar Questão: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        $msg .= '<div class="alert alert-error">Por favor, preencha todos os campos obrigatórios (ID, Prova e Enunciado).</div>';
    }
}

// --- 4. Processamento de Ações GET (Excluir e Editar) ---

// --- AÇÃO: Excluir ---
if (($_GET['acao'] ?? '') === 'excluir') {
    $id = $_GET[$pk_coluna] ?? 0;
    $id = (int)$id;
    if ($id > 0) {
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
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?sucesso=excluido');
        exit;
    }
}

// --- AÇÃO: Editar (Prepara o Formulário) ---
$editando = false;
$questaoEdit = ['id_questao' => '', 'id_prova' => '', 'titulo' => '', 'enunciado' => '', 'resposta_correta' => ''];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = $_GET[$pk_coluna] ?? 0;
    $id = (int)$id;
    if ($id > 0) {
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
try {
    $lista = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista = [];
    $msg .= '<div class="alert alert-error">Erro ao listar Questões. Verifique as tabelas.</div>';
}


// --- 6. Mensagens de Erro e Sucesso ---
if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'duplicidade') {
        $msg .= '<div class="alert alert-error">ID da Questão já existente. Por favor, use um ID diferente.</div>';
    } elseif ($_GET['erro'] === 'dependencia') {
        $msg .= '<div class="alert alert-error">Erro ao excluir a Questão. Verifique se existem referências de outras tabelas, como alternativas.</div>';
    }
}

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] === 'adicionado') {
        $msg .= '<div class="alert alert-success">Questão adicionada com sucesso!</div>';
    } elseif ($_GET['sucesso'] === 'atualizado') {
        $msg .= '<div class="alert alert-success">Questão atualizada com sucesso!</div>';
    } elseif ($_GET['sucesso'] === 'excluido') {
        $msg .= '<div class="alert alert-success">Questão e suas alternativas foram excluídas com sucesso!</div>';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD de Questões</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link para o arquivo de estilo que define o tema de cores -->
    <link rel="stylesheet" href="style.css"> 
    
</head>
<body>

    <header>
        <div class="container">
        <!-- Adiciona a classe 'logo-text' para exibir o logo via CSS -->
        <h1 class="logo-text">Gerenciamento de Questões</h1>
        <!-- O botão 'Painel de Controle' terá o estilo de botão secundário/light definido no CSS -->
        <nav><a href="painel.php" class="btn secondary">Painel de Controle</a></nav>
        </div>
    </header>

    <main class="container">
    <!-- Exibe mensagens (erro/sucesso) -->
    <?= $msg ?>

    <div class="table-container">
        <h2>Questões Cadastradas</h2>
        <!-- O overflow-x: auto na classe .table garante que a tabela seja responsiva em mobile -->
        <div style="overflow-x: auto;"> 
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
                    <tr><td colspan="6">Nenhuma questão cadastrada.</td></tr> 
                <?php else: ?>
                    <?php foreach ($lista as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['id_questao']) ?></td>
                            <td><?= htmlspecialchars($q['titulo_prova']) ?></td>
                            <td><?= htmlspecialchars($q['titulo']) ?></td>
                            <td><?= htmlspecialchars($q['enunciado_curto']) . (strlen($q['enunciado_curto']) < 50 ? '' : '...') ?></td>
                            <td><?= htmlspecialchars($q['resposta_correta'] ?? 'N/A') ?></td>
                            <td class="acao">
                                <!-- Usa a classe de botão .secondary do seu CSS para Editar -->
                                <a class="btn secondary" href="?acao=editar&id_questao=<?= urlencode($q['id_questao']) ?>">Editar</a>
                                <!-- Botão de exclusão modificado para usar o modal de confirmação customizado -->
                                <a class="btn danger" href="#"
                                    onclick="showConfirmModal(
                                        'Tem certeza que deseja excluir a questão ID: <?= htmlspecialchars($q['id_questao']) ?>? Todas as alternativas relacionadas também serão APAGADAS.', 
                                        '?acao=excluir&id_questao=<?= urlencode($q['id_questao']) ?>'
                                    ); return false;">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Questão' : 'Adicionar Nova Questão' ?></h2>

        <?php if ($editando): ?>
            <!-- Campo oculto para rastrear o ID original durante a edição -->
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($questaoEdit['id_questao']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 0.5;">
                <label for="id_questao">ID Questão</label>
                <!-- Alterado para type="text" e pattern para aceitar apenas números, mantendo o valor string do BD em casos de edição -->
                <input type="number" id="id_questao" name="id_questao" required
                        value="<?= htmlspecialchars($questaoEdit['id_questao']) ?>" min="1" step="1">
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
                    placeholder="Deixe em branco por enquanto se não tiver a alternativa criada." min="1" step="1">
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <!-- Botão principal com estilo primário no CSS -->
                <button class="btn" type="submit">Salvar Alterações</button>
                <!-- Botão secundário com estilo secundário no CSS -->
                <a class="btn secondary" href="<?= basename($_SERVER['PHP_SELF']) ?>">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <!-- Botão principal com estilo primário no CSS -->
                <button class="btn" type="submit">Adicionar Questão</button>
            <?php endif; ?>
        </div>
    </form>
    </main>
</body>
<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — Sistema Escola</small>
    </div>
</footer>

<!-- Estrutura do Modal de Confirmação Customizado (Mantido como no original) -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Confirmação de Exclusão</h3>
        <p id="modalMessage">Você tem certeza que deseja excluir este item?</p>
        <div class="modal-buttons">
            <!-- Usa a classe de botão .secondary do seu CSS para Cancelar -->
            <button class="btn secondary" onclick="hideConfirmModal()">Cancelar</button>
            <!-- Usa a classe de botão .danger do seu CSS para Confirmar -->
            <a id="confirmButton" class="btn danger" href="#">Confirmar</a>
        </div>
    </div>
</div>

<script>
    /**
     * Variável global para armazenar a URL de exclusão.
     */
    let deletionUrl = '';
    const modal = document.getElementById('confirmModal');
    const modalMessage = document.getElementById('modalMessage');
    const confirmButton = document.getElementById('confirmButton');

    /**
     * Exibe o modal de confirmação com uma mensagem personalizada e define a URL de destino.
     * @param {string} message - A mensagem a ser exibida.
     * @param {string} url - A URL para a qual o link de confirmação deve apontar.
     */
    function showConfirmModal(message, url) {
        // Atualiza a mensagem no modal
        modalMessage.innerText = message;
        // Define o evento de clique para o botão de confirmação
        confirmButton.onclick = function() {
            window.location.href = url;
        };
        // Exibe o modal e adiciona classe para animação
        modal.style.display = 'flex';
        // O setTimeout garante que o navegador tenha tempo de aplicar o display:flex antes de aplicar a transição
        setTimeout(() => modal.classList.add('visible'), 10);
    }

    /**
     * Oculta o modal de confirmação.
     */
    function hideConfirmModal() {
        // Remove classe para animação
        modal.classList.remove('visible');
        // Oculta o modal após a transição (300ms, que é a duração da transição no CSS)
        setTimeout(() => modal.style.display = 'none', 300);
    }
</script>
</body>
</html>