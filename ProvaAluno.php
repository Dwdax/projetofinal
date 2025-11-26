<?php
// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/Conec.php'; 

$msg = '';
$prova_selecionada = null;
$questoes = [];
$alternativas = [];

// --- 1. Busca de Provas Disponíveis ---
try {
    $provas_disponiveis = $pdo->query("SELECT id_prova, titulo FROM Prova ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $provas_disponiveis = [];
    // Mensagem de erro detalhada para debug
    $msg .= '<div class="alert alert-error">Erro ao carregar Provas. Verifique a tabela "Prova". Detalhes: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// --- 2. Processamento da Seleção de Prova ---
$id_prova = $_GET['id_prova'] ?? ($_POST['id_prova'] ?? null);

if ($id_prova !== null && is_numeric($id_prova)) {
    try {
        // A) Busca os dados da prova
        $stmt_prova = $pdo->prepare("SELECT id_prova, titulo FROM Prova WHERE id_prova = :id");
        $stmt_prova->execute([':id' => $id_prova]);
        $prova_selecionada = $stmt_prova->fetch(PDO::FETCH_ASSOC);

        if ($prova_selecionada) {
            // B) Busca as questões da prova
            $stmt_questoes = $pdo->prepare("SELECT id_questao, enunciado FROM Questao WHERE id_prova = :id_prova ORDER BY id_questao");
            $stmt_questoes->execute([':id_prova' => $id_prova]);
            $questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
            
            // Extrai os IDs das questões para buscar as alternativas
            $ids_questoes = array_column($questoes, 'id_questao');
            
            if (!empty($ids_questoes)) {
                $placeholders = implode(',', array_fill(0, count($ids_questoes), '?'));
                // C) Busca as alternativas para todas as questões
                // CORREÇÃO: Usando 'texto', conforme confirmado no diagrama do banco de dados.
                $stmt_alternativas = $pdo->prepare("SELECT id_alternativa, id_questao, texto FROM Alternativa WHERE id_questao IN ($placeholders) ORDER BY id_questao, id_alternativa");
                $stmt_alternativas->execute($ids_questoes);
                
                // Organiza as alternativas por id_questao
                $raw_alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
                foreach ($raw_alternativas as $alt) {
                    $alternativas[$alt['id_questao']][] = $alt;
                }
            }
        } else {
            $msg .= '<div class="alert alert-error">Prova não encontrada.</div>';
            $id_prova = null; // Volta ao modo de seleção
        }
    } catch (PDOException $e) {
        // Mensagem de erro detalhada para debug
        $msg .= '<div class="alert alert-error">Erro no banco de dados ao carregar a prova. Verifique as tabelas "Questao" e "Alternativa". Detalhes: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $id_prova = null;
    }
}

// --- 3. Processamento do Envio de Respostas (Simulação) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_respostas') {
    // Garante que a prova ainda está selecionada para referência
    $id_prova = $_POST['id_prova'] ?? null;
    if ($id_prova && is_numeric($id_prova)) {
        $stmt_prova = $pdo->prepare("SELECT id_prova, titulo FROM Prova WHERE id_prova = :id");
        $stmt_prova->execute([':id' => $id_prova]);
        $prova_selecionada = $stmt_prova->fetch(PDO::FETCH_ASSOC);
    }
    
    $respostas = $_POST['resposta'] ?? [];
    
    // Contagem simples para feedback
    $num_respostas = count($respostas);
    
    // Neste ponto, a lógica real de pontuação e registro seria implementada.
    // Estamos apenas simulando o sucesso.
    
    $titulo_prova = $prova_selecionada['titulo'] ?? 'Prova Desconhecida';
    $msg = '<div class="alert alert-success">Prova "' . htmlspecialchars($titulo_prova) . '" enviada com sucesso! Você respondeu ' . $num_respostas . ' questões.</div>';
    
    // Limpa a prova selecionada para o feedback de sucesso ser exibido.
    $id_prova = null; 
    $prova_selecionada = null;
}

?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Prova do Aluno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Estilos adicionais para a interface do aluno, se necessário */
        .prova-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .questao-card {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .questao-card h4 {
            margin-top: 0;
            color: var(--color-primary);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            font-size: 1.15em;
        }
        .alternativa-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .alternativa-item:hover {
            background-color: #f8f8f8;
        }
        .alternativa-item input[type="radio"] {
            margin-right: 10px;
            margin-top: 3px;
        }
        .enviar-btn {
            padding: 15px 30px;
            font-size: 1.1em;
            display: block;
            width: 100%;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1 class="logo-text">Modo Aluno: Fazer Prova</h1>
        <nav>
             <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Mudar Prova</a>
        </nav>
    </div>
</header>

<main class="container prova-container">
    <?= $msg ?>

    <?php if ($id_prova === null || $prova_selecionada === null): ?>
        <!-- Modo: Seleção de Prova -->
        <div class="form-card">
            <h2>Selecione a Prova</h2>
            <?php if (empty($provas_disponiveis)): ?>
                <p>Nenhuma prova disponível para ser realizada.</p>
            <?php else: ?>
                <form method="get" action="<?= basename($_SERVER['PHP_SELF']) ?>">
                    <div class="form-group">
                        <label for="select_prova">Provas</label>
                        <select id="select_prova" name="id_prova" required>
                            <option value="">-- Escolha uma Prova --</option>
                            <?php foreach ($provas_disponiveis as $p): ?>
                                <option value="<?= htmlspecialchars($p['id_prova']) ?>">
                                    <?= htmlspecialchars($p['titulo']) ?> (ID: <?= htmlspecialchars($p['id_prova']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn" type="submit">Começar Prova</button>
                </form>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Modo: Realizando a Prova -->
        <h2 style="text-align: center; margin-bottom: 30px;">Prova: <?= htmlspecialchars($prova_selecionada['titulo']) ?></h2>

        <?php if (empty($questoes)): ?>
            <div class="alert alert-info">Esta prova não possui questões cadastradas.</div>
        <?php else: ?>
            <form method="post" action="<?= basename($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="acao" value="enviar_respostas">
                <input type="hidden" name="id_prova" value="<?= htmlspecialchars($id_prova) ?>">

                <?php $contador_questao = 1; ?>
                <?php foreach ($questoes as $q): ?>
                    <div class="questao-card">
                        <h4>Questão <?= $contador_questao++ ?> (ID: <?= htmlspecialchars($q['id_questao']) ?>)</h4>
                        <p><strong><?= nl2br(htmlspecialchars($q['enunciado'])) ?></strong></p>
                        
                        <?php 
                        $id_questao_atual = $q['id_questao'];
                        // Verifica se a questão tem alternativas cadastradas
                        if (isset($alternativas[$id_questao_atual]) && !empty($alternativas[$id_questao_atual])): 
                        ?>
                            <div class="alternativas-list">
                                <?php foreach ($alternativas[$id_questao_atual] as $alt): ?>
                                    <label class="alternativa-item">
                                        <input type="radio" 
                                               name="resposta[<?= htmlspecialchars($id_questao_atual) ?>]" 
                                               value="<?= htmlspecialchars($alt['id_alternativa']) ?>" 
                                               required>
                                        <!-- Usando 'texto', conforme confirmado no diagrama do banco de dados -->
                                        <?= nl2br(htmlspecialchars($alt['texto'])) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">Esta questão não tem alternativas.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <button class="btn enviar-btn" type="submit">Enviar Respostas da Prova</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — Sistema Escola (Modo Aluno)</small>
    </div>
</footer>

</body>
</html>