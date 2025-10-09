<?php
// Função para calcular CRC16 conforme padrão PIX
function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;
    $bytes = str_split($payload);
    foreach ($bytes as $byte) {
        $resultado ^= ord($byte) << 8;
        for ($i = 0; $i < 8; $i++) {
            if (($resultado & 0x8000) !== 0) {
                $resultado = ($resultado << 1) ^ $polinomio;
            } else {
                $resultado <<= 1;
            }
            $resultado &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

// Função para formatar campos no padrão EMV (ID + Length + Value)
function emv_field($id, $value) {
    $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
    return $id . $length . $value;
}

// Função para gerar o payload completo do PIX
function gerarPayloadPix($chavePix, $descricao, $valor = null) {
    $gui = "br.gov.bcb.pix";

    // Merchant Account Information (ID 26)
    $merchantAccountInfo = emv_field("00", $gui) . emv_field("01", $chavePix);
    $merchantAccountInfo = emv_field("26", $merchantAccountInfo);

    $valorFormatado = $valor ? number_format($valor, 2, '.', '') : '';

    // Montar o payload completo
    $payload = "";
    $payload .= emv_field("00", "01");                    // Payload Format Indicator
    $payload .= $merchantAccountInfo;                     // Merchant Account Info
    $payload .= emv_field("52", "0000");                  // Merchant Category Code (default 0000)
    $payload .= emv_field("53", "986");                   // Transaction Currency (986 = BRL)
    if ($valor) {
        $payload .= emv_field("54", $valorFormatado);     // Transaction Amount (opcional)
    }
    $payload .= emv_field("58", "BR");                    // Country Code
    $payload .= emv_field("59", "Doces ML");              // Merchant Name
    $payload .= emv_field("60", "Agudos");                // Merchant City

    // Additional Data Field Template (ID 62)
    $txid = $descricao ?: "*";                             // TxID do PIX, obrigatório
    $addDataField = emv_field("05", $txid);
    $payload .= emv_field("62", $addDataField);

    // CRC placeholder
    $payloadToCalc = $payload . "6304";

    // Calcular CRC16
    $crc = calcularCRC16($payloadToCalc);

    // Final payload com CRC
    return $payloadToCalc . $crc;
}

// Quando o formulário for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST['nome']);
    $produto = htmlspecialchars($_POST['produto']);
    $quantidade = intval($_POST['quantidade']);

    // Preços simples
    $precos = [
        "paçoca" => 3.50,
        "bala" => 0.50
    ];

    if (!isset($precos[$produto])) {
        echo "<p>Produto inválido.</p>";
        exit;
    }

    $total = $precos[$produto] * $quantidade;

    // Conexão com MySQL
    $conn = new mysqli("localhost", "root", "", "lojinha");

    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    // Inserir no banco
    $stmt = $conn->prepare("INSERT INTO pedidos (nome, produto, quantidade, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssid", $nome, $produto, $quantidade, $total);

    if ($stmt->execute()) {
        echo "<h1>Pedido Realizado!</h1>";
        echo "<p>Obrigado, <strong>$nome</strong>!</p>";
        echo "<p>Produto: <strong>" . ucfirst($produto) . "</strong></p>";
        echo "<p>Quantidade: <strong>$quantidade</strong></p>";
        echo "<p>Total a pagar: <strong>R$ " . number_format($total, 2, ',', '.') . "</strong></p>";

        // Geração do QR Code PIX (payload completo e correto)
        $chave_pix = "49207078864"; // sua chave PIX (sem espaços ou caracteres)
        $descricao = "Pedido" . time(); // id ou descrição única do pedido para TXID
        $valor = null; // valor a ser cobrado (se quiser liberar o usuário a digitar, coloque null)

        $payload = gerarPayloadPix($chave_pix, $descricao, $valor);

        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($payload);

        echo "<h3>Escaneie o QR Code para pagar com PIX:</h3>";
        echo "<img src='$qrCodeUrl' alt='QR Code PIX'>";
    } else {
        echo "<p>Erro ao salvar pedido: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>Requisição inválida.</p>";
}
?>

