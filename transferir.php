<?php
// Arquivo: transferir.php (VERSÃO FINAL - Responde com JSON)

session_start();
require 'conexao.php';

// --- INÍCIO DA MUDANÇA ---
// Define que a resposta será em formato JSON
header('Content-Type: application/json');

// Cria uma função para padronizar as respostas
function json_response($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}
// --- FIM DA MUDANÇA ---


// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    json_response('error', 'Usuário não está logado. Faça o login novamente.');
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario_origem = $_SESSION['id_usuario'];
    $cpf_destinatario = $_POST['cpf_destinatario'];
    $valor = $_POST['valor'];

    // Validação básica
    if (empty($cpf_destinatario) || !is_numeric($valor) || $valor <= 0) {
        json_response('error', 'Dados da transferência inválidos. Verifique o CPF e o valor.');
    }

    try {
        $pdo->beginTransaction();

        // 1. Pega dados da conta de ORIGEM
        $sql_origem = "SELECT id, saldo FROM contas WHERE id_usuario = ?";
        $stmt_origem = $pdo->prepare($sql_origem);
        $stmt_origem->execute([$id_usuario_origem]);
        $conta_origem = $stmt_origem->fetch();

        // 2. Pega dados da conta de DESTINO pelo CPF
        $sql_destino = "SELECT c.id, u.cpf FROM contas c JOIN usuarios u ON c.id_usuario = u.id WHERE u.cpf = ?";
        $stmt_destino = $pdo->prepare($sql_destino);
        $stmt_destino->execute([$cpf_destinatario]);
        $conta_destino = $stmt_destino->fetch();

        // 3. VERIFICAÇÕES DE SEGURANÇA
        if (!$conta_destino) {
            throw new Exception("CPF do destinatário não encontrado.");
        }
        if ($conta_origem['id'] === $conta_destino['id']) {
            throw new Exception("Você não pode transferir para si mesmo.");
        }
        if ($conta_origem['saldo'] < $valor) {
            throw new Exception("Saldo insuficiente para realizar a transferência.");
        }

        // 4. EXECUTA AS OPERAÇÕES
        // Debita da origem
        $sql_debito = "UPDATE contas SET saldo = saldo - ? WHERE id = ?";
        $pdo->prepare($sql_debito)->execute([$valor, $conta_origem['id']]);

        // Credita no destino
        $sql_credito = "UPDATE contas SET saldo = saldo + ? WHERE id = ?";
        $pdo->prepare($sql_credito)->execute([$valor, $conta_destino['id']]);

        // Registra a transação
        $sql_transacao = "INSERT INTO transacoes (id_conta_origem, id_conta_destino, tipo, valor) VALUES (?, ?, 'TRANSFERENCIA', ?)";
        $pdo->prepare($sql_transacao)->execute([$conta_origem['id'], $conta_destino['id'], $valor]);
        
        $pdo->commit();

        // --- MUDANÇA ---
        // Em vez de redirecionar, envia uma resposta de sucesso em JSON
        json_response('success', 'Transferência realizada com sucesso!');

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // --- MUDANÇA ---
        // Em vez de "morrer", envia uma resposta de erro em JSON
        json_response('error', $e->getMessage());
    }
}
?>