<?php
// Inicia a sessão para poder usar variáveis de sessão
if (!isset($_SESSION)) {
    session_start();
}

// 1. Inclui o arquivo de conexão PDO
include('Conec.php'); 

$erro_login = "";

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 2. Coleta as entradas
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // 3. Consulta SQL com Prepared Statement (PDO)
    $sql_code = "SELECT * FROM usuarios 
                 WHERE email = ? 
                 AND senha = ?";

    try {
        // Prepara e executa a consulta
        $stmt = $pdo->prepare($sql_code);
        if ($stmt->execute([$email, $senha])) {
            
            $quantidade = $stmt->rowCount();

            if ($quantidade == 1) {
                // Usuário encontrado, faz o login
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                // Armazena dados do usuário na sessão
                $_SESSION['id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];

                // Redireciona
                header("Location: painel.php");
                exit();

            } else {
                $erro_login = "E-mail ou senha incorretos.";
            }
        }
    } catch (PDOException $e) {
        $erro_login = "Erro interno ao consultar o banco de dados.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login de Usuário</title>
    
    <style>
        /* Variáveis de Cores */
        :root {
            --primary-color: #4CAF50; /* Verde principal */
            --primary-dark: #388E3C; /* Verde escuro (hover, foco) */
            --text-color: #333;
            --background-color: #f4f4f9;
        }

        /* 1. Estilos Globais e Fundo */
        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* 2. Estilo do Contêiner de Login */
        .login-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 380px;
            text-align: center;
        }

        /* 3. Título */
        h2 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-weight: 600;
        }

        /* 4. Estilo dos Inputs */
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--text-color);
            font-size: 0.9em;
        }

        .input-group input[type="email"],
        .input-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5); 
        }

        /* 5. Estilo do Botão Principal (Verde) */
        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            width: 100%;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: var(--primary-dark);
        }

        /* 6. Estilo para Mensagens de Erro */
        .error-message {
            color: red;
            background-color: #ffebee; 
            border: 1px solid #FFCDD2;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
    </style> 
    
</head>
<body>
    <div class="login-container">
        <h2>Acesso ao Sistema</h2>
        
        <?php if ($erro_login): ?>
            <p class="error-message">❌ <?php echo $erro_login; ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            
            <div class="input-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="input-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>