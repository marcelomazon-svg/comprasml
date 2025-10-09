<?php
// Função para calcular CRC16 necessário no payload PIX
function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;
    $bytes = str_split($payload);

    foreach ($bytes as $byte) {
        $resultado ^= ord($byte) << 8;
        for ($i = 0; $i < 8; $i++) {
            if (($resultado & 0x8000) != 0) {
                $resultado = ($resultado << 1) ^ $polinomio;
            } else {
                $resultado <<= 1;
            }
            $resultado &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

// Função para montar o payload PIX oficial sem valor fixo
function gerarPayloadPix($chave, $descricao) {
    $chave = preg_replace('/[^0-9a-zA-Z@\.\-_]/', '', $chave); // limpa chave
    $merchantAccountInfo = "0014BR.GOV.BCB.PIX01" . str_pad(strlen($chave), 2, '0', STR_PAD_LEFT) . $chave;
    $merchantCategoryCode = "0000";
    $transactionCurrency = "986"; // Real (BRL)
    $txAmount = ""; // Sem valor fixo
    $countryCode = "5802BR";

    $merchantName = "Doces ML"; // nome da loja (máx 25 caracteres)
    $merchantCity = "Sao Paulo"; // cidade (máx 15 caracteres)

    $merchantName = substr($merchantName, 0, 25);
    $merchantCity = substr($merchantCity, 0, 15);

    $merchantNameField = "59" . str_pad(strlen($merchantName), 2, '0', STR_PAD_LEFT) . $merchantName;
    $merchantCityField = "60" . str_pad(strlen($merchantCity), 2, '0', STR_PAD_LEFT) . $merchantCity;

    $txid = "62" . str_pad(strlen("05" . strlen($descricao) . $descricao), 2, '0', STR_PAD_LEFT) . "05" . strlen($descricao) . $descricao;

    $payloadSemCRC = "000201" . $merchantAccountInfo . $merchantCategoryCode . $transactionCurrency . $txAmount . $countryCode .
        "52040000" . $merchantNameField . $merchantCityField . $txid . "6304";

    $crc = calcularCRC16($payloadSemCRC);
    $payload = $payloadSemCRC . $crc;

    return $payload;
}

// ----------- USO ----------- //

$chave_pix = "49207078864"; // sua chave PIX
$descricao = "Pagamento Doces ML"; // descrição opcional

$payload = gerarPayloadPix($chave_pix, $descricao);
$qr_code_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . urlencode($payload);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagamento PIX - Sem valor fixo</title>
</head>
<body>
    <h2>Escaneie o QR Code para pagar com PIX</h2>
    <p><em>Digite o valor desejado no seu app bancário.</em></p>
    <img src="<?php echo $qr_code_url; ?>" alt="QR Code PIX">
</body>
</html>
