<?php
// Arquivo: login.php

// 1. INICIA A SESSÃO
// Deve ser a primeira linha do arquivo, antes de qualquer saída de HTML ou echo.
session_start();

// 2. INCLUI A CONEXÃO COM O BANCO
require 'conexao.php';

// 3. VERIFICA SE O FORMULÁRIO FOI ENVIADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 4. RECEBE OS DADOS DO FORMULÁRIO
    $cpf = $_POST['cpf'];
    $senha_digitada = $_POST['senha'];

    // Validação básica
    if (empty($cpf) || empty($senha_digitada)) {
        die("Erro: CPF e Senha são obrigatórios.");
    }

    try {
        // 5. BUSCA O USUÁRIO PELO CPF NO BANCO DE DADOS
        // Selecionamos o id (para a sessão) e a senha (para verificação)
        $sql = "SELECT id, senha FROM usuarios WHERE cpf = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cpf]);

        // Pega o resultado da consulta
        $usuario = $stmt->fetch();

        // 6. VERIFICA SE O USUÁRIO FOI ENCONTRADO E SE A SENHA ESTÁ CORRETA
        // password_verify() compara a senha digitada com o hash salvo no banco
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            
            // 7. LOGIN BEM-SUCEDIDO: CRIA A SESSÃO E REDIRECIONA
            
            // Armazena o ID do usuário na sessão. É assim que o site "lembra" quem está logado.
            $_SESSION['id_usuario'] = $usuario['id'];
            
            // Redireciona o usuário para o painel principal
            header("Location: pag3.php");
            exit(); // Encerra o script para garantir que o redirecionamento ocorra

        } else {
            // 8. LOGIN FALHOU: CPF NÃO ENCONTRADO OU SENHA INCORRETA
            // Você pode redirecionar de volta para o login com uma mensagem de erro
            // header("Location: sua_pagina_de_login.html?erro=1");
            die("CPF ou Senha inválidos.");
        }

    } catch (PDOException $e) {
        // Trata erros de banco de dados
        die("Erro ao tentar fazer login: " . $e->getMessage());
    }

} else {
    // Se alguém tentar acessar o arquivo diretamente sem enviar o formulário
    header('Location: login.html'); // Redireciona para a página de login
    exit();
}
?>