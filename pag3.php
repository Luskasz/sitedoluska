<?php
// Arquivo: pag3.php (VERSÃO COMPLETA E REVISADA)

session_start();
require 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: pag2.html');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = '';
$saldo_formatado = 'R$ 0,00';
$id_conta_logada = null; // Vamos precisar do ID da conta para a lógica do extrato
$transacoes = [];

try {
    // Busca dados do usuário e da conta, incluindo o ID da conta
    $sql_conta = "SELECT u.nome, c.id, c.saldo FROM usuarios u JOIN contas c ON u.id = c.id_usuario WHERE u.id = ?";
    $stmt_conta = $pdo->prepare($sql_conta);
    $stmt_conta->execute([$id_usuario]);
    $dados_usuario_conta = $stmt_conta->fetch(PDO::FETCH_ASSOC);

    if ($dados_usuario_conta) {
        $nome_usuario = $dados_usuario_conta['nome'];
        $id_conta_logada = $dados_usuario_conta['id']; 
        $saldo_formatado = "R$ " . number_format($dados_usuario_conta['saldo'], 2, ',', '.');
    }

    // Busca o extrato de transações se o ID da conta foi encontrado
    if ($id_conta_logada) {
        $sql_transacoes = "SELECT id_conta_origem, id_conta_destino, tipo, valor, data_transacao, descricao 
                           FROM transacoes 
                           WHERE id_conta_origem = ? OR id_conta_destino = ? 
                           ORDER BY data_transacao DESC LIMIT 5";
        $stmt_transacoes = $pdo->prepare($sql_transacoes);
        $stmt_transacoes->execute([$id_conta_logada, $id_conta_logada]);
        $transacoes = $stmt_transacoes->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("Erro Crítico: Não foi possível carregar o painel. Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HX BANK - Painel</title>
    <link rel="stylesheet" href="css/pag3.css" />
    <link rel="shortcut icon" href="money.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>

<body>
  <header class="parte_superior">
    <h1>HX BANK</h1>
    <div class="icons">
      <span class="nome-usuario">Olá, <?php echo htmlspecialchars($nome_usuario); ?></span>
      <a href="logout.php" title="Sair">
        <img src="icon/exit.png" id="saida" />
      </a>
    </div>
  </header>

  <main class="meio_conteudo">
    <section class="card saldo">
      <h2>Saldo Atual</h2>
      <p class="valor"><?php echo $saldo_formatado; ?></p>
      <div class="botoes">
        <button id="transferir"><img src="icon/seta.png" width="25px" height="20px" /> Transferir</button>
        <button id="depositar"><img src="icon/seta_deposito.png" height="20px" width="25px" /> Depositar</button>
      </div>
    </section>
    <section class="card">
      <h2>Cartão de Crédito</h2>
      <p class="valor">Em breve</p>
    </section>
    <section class="card_inv">
      <h2>Investimentos</h2>
      <div class="botoes_inv">
         <button class="botao_inv" id="site_inv">teste</button>
      </div>
     
    </section>
  </main>

  <br><br><br>
  <section class="transacoes">
    <h3>Transações Recentes</h3>
    <div class="transacoes-list">
        <?php if (count($transacoes) > 0): ?>
            <?php foreach ($transacoes as $transacao): ?>
                <?php
                    $isCredito = ($transacao['id_conta_destino'] == $id_conta_logada && $transacao['id_conta_origem'] != $id_conta_logada);
                    $sinal_valor = $isCredito ? '+ ' : '- ';
                    $classe_valor = $isCredito ? 'credito' : 'debito';
                    
                    $icone_classe = 'bi ';
                    if ($transacao['tipo'] === 'DEPOSITO') {
                        $icone_classe .= 'bi-arrow-down-circle-fill';
                        $classe_valor = 'credito'; $sinal_valor = '+ ';
                    } elseif ($transacao['tipo'] === 'TRANSFERENCIA') {
                        $icone_classe .= $isCredito ? 'bi-arrow-left-circle-fill' : 'bi-arrow-right-circle-fill';
                    } else {
                        $icone_classe .= 'bi-credit-card-2-front-fill';
                    }
                ?>
                <div class="transacao-item">
                    <div class="transacao-icone <?php echo $classe_valor; ?>"><i class="<?php echo $icone_classe; ?>"></i></div>
                    <div class="transacao-detalhes">
                        <span class="transacao-tipo"><?php echo htmlspecialchars(ucfirst(strtolower($transacao['tipo']))); ?></span>
                        <span class="transacao-data"><?php echo date('d/m/Y \à\s H:i', strtotime($transacao['data_transacao'])); ?></span>
                    </div>
                    <div class="transacao-valor <?php echo $classe_valor; ?>"><?php echo $sinal_valor; ?>R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="transacao-item"><p>Nenhuma transação encontrada.</p></div>
        <?php endif; ?>
    </div>
  </section>

  <div id="modalTransferir" class="modal">
    <div class="menu">
      <div class="modal-conteudo">
        <span id="fecharTransferir" class="fechar">&times;</span>
        <h2>Transferir dinheiro</h2>
        <form>
            <label for="destinatario">CPF do Destinatário:</label>
            <input type="text" id="destinatario" name="cpf_destinatario" placeholder="Digite o CPF (só números)" required>
            <label for="valorTransferir">Valor:</label>
            <input type="number" id="valorTransferir" name="valor" class="modal_txt" placeholder="R$ 0,00" min="0.01" step="0.01" required>
            <button type="submit">Confirmar transferência</button>
        </form>
      </div>
    </div>
  </div>

  <div id="modalDeposito" class="modal-dep">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Depósito</h2>
      <form>
          <p>Insira o valor para depositar:</p>
          <input type="number" id="valorDeposito" name="valor" placeholder="Valor" min="0.01" step="0.01" required />
          <button type="submit">Confirmar</button>
      </form>
    </div>
  </div>

  <script src="js/pag3.js"></script>
</body>
</html>