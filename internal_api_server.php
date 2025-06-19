<?php

// internal_api_server.php (VERSÃO CORRIGIDA PARA ACEITAR JSON)

header('Content-Type: application/json');

$usersDatabase = [
    1 => ['id' => 1, 'name' => 'Alice', 'username' => 'alice', 'password' => 'senha_fraca_123'],
    2 => ['id' => 2, 'name' => 'Bob', 'username' => 'bob', 'password' => 'bob1992'],
    3 => ['id' => 3, 'name' => 'Admin', 'username' => 'admin_user', 'password' => 'PasswordS3cret!'],
];

$requestUri = $_SERVER['REQUEST_URI'];

// ... (o endpoint /api/get-user-credentials permanece o mesmo) ...
if (preg_match('/^\/api\/get-user-credentials/', $requestUri)) {
    // ...
    $userId = $_GET['user_id'] ?? null;
    if (!$userId || !isset($usersDatabase[$userId])) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado.']);
        exit;
    }
    $user_data = $usersDatabase[$userId];
    $credentials = [ 'username' => $user_data['username'], 'password' => $user_data['password'] ];
    echo json_encode($credentials);
    exit;
}


if (preg_match('/^\/api\/admin-login/', $requestUri)) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido. Use POST.']);
        exit;
    }

    // ===== CORREÇÃO APLICADA AQUI vvvvvvvv =====
    // Em vez de ler de $_POST, lemos o corpo bruto da requisição e o decodificamos como JSON
    $json_body = json_decode(file_get_contents('php://input'), true);

    $username_sent = $json_body['username'] ?? null;
    $password_sent = $json_body['password'] ?? null;
    // ===== FIM DA CORREÇÃO ^^^^^^^^^^ =====

    $admin_credentials = $usersDatabase[3];

    if ($username_sent === $admin_credentials['username'] && $password_sent === $admin_credentials['password']) {
        http_response_code(200);
        echo json_encode(['status' => 'sucesso', 'message' => 'Sistema Liberado! Acesso administrativo concedido.']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'falha', 'message' => 'Acesso Negado. Credenciais inválidas.']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint da API interna não encontrado.']);
