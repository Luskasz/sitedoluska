<?php
// Arquivo: depositar.php (VERSÃO CORRETA E FOCADA)

session_start();
require 'conexao.php';

// Define o cabeçalho da resposta como JSON para conversar com o JavaScript
header('Content-Type: application/json');

// Cria uma função para padronizar as respostas JSON
function json_response($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    json_response('error', 'Usuário não está logado. Faça o login novamente.');
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $valor = $_POST['valor'];

    // Validação do valor recebido
    if (!isset($valor) || !is_numeric($valor) || $valor <= 0) {
        json_response('error', 'Valor de depósito inválido.');
    }

    try {
        $pdo->beginTransaction();

        // 1. Pega o ID da conta do usuário logado
        $sql_get_conta = "SELECT id FROM contas WHERE id_usuario = ?";
        $stmt_get_conta = $pdo->prepare($sql_get_conta);
        $stmt_get_conta->execute([$id_usuario]);
        $conta = $stmt_get_conta->fetch();

        if (!$conta) {
            throw new Exception("Conta do usuário não encontrada.");
        }
        $id_conta = $conta['id'];

        // 2. Adiciona o valor ao saldo na tabela 'contas'
        $sql_update = "UPDATE contas SET saldo = saldo + ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$valor, $id_conta]);

        // 3. Registra a transação no extrato
        $sql_insert = "INSERT INTO transacoes (id_conta_destino, tipo, valor, descricao) VALUES (?, 'DEPOSITO', ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$id_conta, $valor, 'Depósito em conta']);

        // Se tudo deu certo, confirma as operações
        $pdo->commit();
        
        // Envia a resposta de sucesso para o JavaScript
        json_response('success', 'Depósito realizado com sucesso!');

    } catch (Exception $e) {
        // Se algo deu errado, desfaz tudo
        $pdo->rollBack();
        // Envia a resposta de erro para o JavaScript
        json_response('error', 'Erro ao processar o depósito: ' . $e->getMessage());
    }
} else {
    // Se o arquivo for acessado diretamente
    json_response('error', 'Acesso inválido.');
}
?>