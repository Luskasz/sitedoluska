<?php
// Arquivo: cadastrar.php

// 1. INCLUI A CONEXÃO SEGURA COM PDO
require 'conexao.php';

// 2. RECEBE OS DADOS DO FORMULÁRIO
// Garanta que os atributos 'name' do seu formulário HTML correspondam a estas chaves
$nome = $_POST['nome'];
$email = $_POST['email'];
$cpf = $_POST['cpf'];
$telefone = $_POST['telefone'];
$dt_nasc = $_POST['dt_nasc'];
$senha_pura = $_POST['senha'];

// 3. VALIDAÇÃO BÁSICA (pode ser melhorada)
if (empty($nome) || empty($email) || empty($cpf) || empty($senha_pura)) {
    die("Erro: Por favor, preencha todos os campos obrigatórios.");
}

// 4. CRIA O HASH SEGURO DA SENHA
$senhaHash = password_hash($senha_pura, PASSWORD_DEFAULT);

try {
    // 5. INICIA A TRANSAÇÃO (OPERAÇÃO "TUDO OU NADA")
    $pdo->beginTransaction();

    // --- Etapa A: Insere na tabela 'usuarios' ---
    $sql_usuario = "INSERT INTO usuarios (nome, email, cpf, telefone, dt_nasc, senha) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_usuario = $pdo->prepare($sql_usuario);
    $stmt_usuario->execute([$nome, $email, $cpf, $telefone, $dt_nasc, $senhaHash]);
    
    // Pega o ID do usuário que acabamos de criar para usar na próxima etapa
    $id_usuario_novo = $pdo->lastInsertId();

    // --- Etapa B: Insere na tabela 'contas', ligada ao novo usuário ---
    // Gera um número de conta e agência (exemplo simples)
    $agencia = '0001';
    $numero_conta = rand(10000, 99999) . '-' . rand(0, 9);
    $saldo_inicial = 0.00;
    
    $sql_conta = "INSERT INTO contas (id_usuario, agencia, numero_conta, saldo) VALUES (?, ?, ?, ?)";
    $stmt_conta = $pdo->prepare($sql_conta);
    $stmt_conta->execute([$id_usuario_novo, $agencia, $numero_conta, $saldo_inicial]);

    // 6. SE AMBAS AS ETAPAS FUNCIONARAM, CONFIRMA AS MUDANÇAS
    $pdo->commit();

    // Redireciona para uma página de sucesso ou de login
    // Você pode criar uma página simples 'cadastro_sucesso.html'
    header('Location: pag3.php?status=sucesso');
    exit();

} catch (PDOException $e) {
    // 7. SE QUALQUER ETAPA FALHOU, DESFAZ TUDO
    $pdo->rollBack();
    
    // Verifica se o erro foi de duplicidade (CPF ou E-mail já existem)
    if ($e->getCode() == 23000) {
        die("Erro ao cadastrar: O CPF ou E-mail informado já está em uso.");
    } else {
        die("Erro ao cadastrar. Por favor, tente novamente. Detalhe: " . $e->getMessage());
    }
}
?>