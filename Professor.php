<?php
require_once __DIR__ . '/Conec.php'; // Arquivo de conexão com o PDO

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
  <link rel="stylesheet" href="style.css" >
</head>
<body>

<header>
  <div class="container">
    <h1>Professores</h1>
    <nav><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn">Novo Professor</a></nav>
  </div>
</header>

<main class="container">
  <?= $msg ?>

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
          <td><?= htmlspecialchars($p['id_professor']) ?></td>
          <td><?= htmlspecialchars($p['nome']) ?></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td class="acao">
            <a class="btn btn-secondary" href="?acao=editar&id_professor=<?= urlencode($p['id_professor']) ?>">Editar</a>
            <a class="btn btn-danger" href="?acao=excluir&id_professor=<?= urlencode($p['id_professor']) ?>"
                onclick="return confirm('Excluir este professor?');">Excluir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <form class="form-card" method="post">
    <h2><?= $editando ? 'Editar Professor' : 'Adicionar Professor' ?></h2>

    <?php if ($editando): ?>
      <input type="hidden" name="original_id" value="<?= htmlspecialchars($professorEdit['id_professor']) ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="id_professor">ID Professor (máx. 10)</label>
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