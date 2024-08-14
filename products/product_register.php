<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

// Função para buscar produtos
function getProducts($pdo, $userId, $searchQuery = '')
{
    $sql = "SELECT * FROM products WHERE user_id = ?";
    $params = [$userId];

    if (!empty($searchQuery)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar usuário
function getUser($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';
    $userId = $_SESSION['user_id'];

    switch ($action) {
        case 'add':
            $stmt = $pdo->prepare("INSERT INTO products (name, description, quantity, supplier, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $quantity, $supplier, $userId]);
            break;
        case 'update':
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, quantity = ?, supplier = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$name, $description, $quantity, $supplier, $id, $userId]);
            }
            break;
        case 'delete':
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
            }
            break;
        case 'delete_selected':
            $ids = explode(',', $_POST['ids']);
            if (is_array($ids) && count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders) AND user_id = ?");
                $stmt->execute(array_merge($ids, [$userId]));
            }
            break;
    }

    header('Location: product_register.php');
    exit();
}

// Carregar produtos e usuário
$searchQuery = $_GET['search'] ?? '';
$userId = $_SESSION['user_id'];
$products = getProducts($pdo, $userId, $searchQuery);
$user = getUser($pdo, $userId);

// Retornar tabela de produtos via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    foreach ($products as $product) {
        echo '<tr>
            <td><input type="checkbox" name="select[]" value="' . htmlspecialchars($product['id']) . '"></td>
            <td>' . htmlspecialchars($product['id']) . '</td>
            <td>' . htmlspecialchars($product['name']) . '</td>
            <td>' . htmlspecialchars($product['description']) . '</td>
            <td>' . htmlspecialchars($product['quantity']) . '</td>
            <td>' . htmlspecialchars($product['supplier']) . '</td>
            <td>
                <button class="editButton" data-id="' . htmlspecialchars($product['id']) . '">Editar</button>
                <button class="deleteButton" data-id="' . htmlspecialchars($product['id']) . '">Excluir</button>
            </td>
        </tr>';
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos</title>
    <style>
        /* Estilos gerais */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #1e1e1e;
            display: flex;
            justify-content: space-between;
            color: #a6a6a6;
            padding: 10px 20px;
            align-items: center;
        }

        header img.logo {
            max-width: 45px;
            max-height: 45px;
        }

        header h1 {
            color: #fff;
            margin: 0;
            flex: 1;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .buttonLogout {
            background-color: #ce0000;
            color: #fff;
            border: none;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .buttonLogout:hover {
            background-color: #6b0000;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        button,
        .buttonRed,
        .buttonAdd,
        .buttonSave,
        .buttonSearch {
            width: 150px;
            height: 45px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button {
            background-color: #393939;
            color: #fff;
        }

        .buttonRed {
            background-color: #ce0000;
            color: #fff;
        }

        .buttonRed:hover {
            background-color: #6b0000;
        }

        .buttonAdd {
            background-color: #00ac28;
            color: #fff;
        }

        .buttonAdd:hover {
            background-color: #006b19;
        }

        .buttonSave {
            background-color: #00ac28;
            color: #fff;
        }

        .buttonSave:hover {
            background-color: #006b19;
        }

        .buttonSearch {
            background-color: #007BFF;
            color: #fff;
        }

        .buttonSearch:hover {
            background-color: #0056b3;
        }

        /* Estilos para o modal */
        /* Estilo geral do modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Estilo específico para o conteúdo do modal */
        .modal-content,
        .searchModal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Estilo para o botão de fechar do modal */
        .modal-content .close,
        .searchModal-content .close {
            color: #888;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-content .close:hover,
        .searchModal-content .close:hover,
        .modal-content .close:focus,
        .searchModal-content .close:focus {
            color: #000;
            text-decoration: none;
        }

        /* Estilo do formulário dentro do modal */
        .modal-content form,
        .searchModal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Estilo dos campos do formulário */
        .modal-content label,
        .searchModal-content label {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .modal-content input[type="text"],
        .searchModal-content input[type="text"],
        .modal-content input[type="number"],
        .searchModal-content input[type="number"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .modal-content button,
        .searchModal-content button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .modal-content button:hover,
        .searchModal-content button:hover {
            background-color: #0056b3;
        }

        /* Estilo para a tabela de produtos */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        /* Estilo para a tabela de resultados no modal */
        #searchResults table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        #searchResults th,
        #searchResults td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        #searchResults th {
            background-color: #f8f8f8;
        }

        /* Ocultar a tabela de produtos quando não necessário */
        #productTable {
            display: table;
        }
    </style>
</head>

<body>
    <header>
        <img src="../images/logo.png" alt="Logo" class="logo">
        <h1>Cadastro de Produtos</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <form action="../logout.php" method="post" style="margin: 0;">
                <button type="submit" class="buttonLogout">Sair</button>
            </form>
        </div>
    </header>

    <div class="main-container">
        <!-- Botão para abrir o modal de cadastro de produto -->
        <div class="button-container">
            <button id="addProductButton" class="buttonAdd">Adicionar Produto</button>
            <button id="searchButton" class="buttonSearch">Pesquisar</button>
            <button id="deleteSelectedButton" class="buttonRed">Excluir Selecionados</button>
        </div>

        <!-- Modal de Cadastro de Produto -->
        <div id="productModal" class="modal">
            <div class="modal-content">
                <span class="close" id="productModalClose">&times;</span>
                <h2>Adicionar Produto</h2>
                <form id="productForm" method="post" action="">
                    <input type="hidden" name="action" value="add">
                    <label for="name">Nome:</label>
                    <input type="text" id="name" name="name" required>
                    <label for="description">Descrição:</label>
                    <input type="text" id="description" name="description" required>
                    <label for="quantity">Quantidade:</label>
                    <input type="number" id="quantity" name="quantity" required>
                    <label for="supplier">Fornecedor:</label>
                    <input type="text" id="supplier" name="supplier" required>
                    <button type="submit" class="buttonAdd">Adicionar</button>
                </form>
            </div>
        </div>

        <!-- Modal de Pesquisa de Produto -->
        <div id="searchModal" class="modal">
            <div class="searchModal-content">
                <span class="close" id="searchModalClose">&times;</span>
                <h2>Pesquisar Produto</h2>
                <form id="searchForm">
                    <label for="search">Nome ou Descrição:</label>
                    <input type="text" id="search" name="search" required>
                    <button type="submit" class="buttonSearch">Pesquisar</button>
                </form>
                <div id="searchResults">
                    <!-- Resultados da pesquisa serão exibidos aqui -->
                </div>
            </div>
        </div>

        <!-- Tabela de Produtos -->
        <div class="container">
            <h2>Lista de Produtos</h2>
            <table id="productTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th> <!-- Caixa de seleção para selecionar todos -->
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Fornecedor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" name="select[]" value="<?php echo htmlspecialchars($product['id']); ?>"></td>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($product['supplier']); ?></td>
                            <td>
                                <button class="editButton" data-id="<?php echo htmlspecialchars($product['id']); ?>">Editar</button>
                                <button class="deleteButton" data-id="<?php echo htmlspecialchars($product['id']); ?>">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Função para abrir e fechar o modal
        var productModal = document.getElementById("productModal");
        var searchModal = document.getElementById("searchModal");
        var addProductButton = document.getElementById("addProductButton");
        var searchButton = document.getElementById("searchButton");
        var productModalClose = document.getElementById("productModalClose");
        var searchModalClose = document.getElementById("searchModalClose");

        addProductButton.onclick = function() {
            productModal.style.display = "block";
        }

        searchButton.onclick = function() {
            searchModal.style.display = "block";
        }

        productModalClose.onclick = function() {
            productModal.style.display = "none";
        }

        searchModalClose.onclick = function() {
            searchModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == productModal) {
                productModal.style.display = "none";
            }
            if (event.target == searchModal) {
                searchModal.style.display = "none";
            }
        }

        // Função para pesquisar produtos e exibir no modal
        document.getElementById('searchForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const searchQuery = document.getElementById('search').value;

            fetch('product_register.php?ajax=true&search=' + encodeURIComponent(searchQuery))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('searchResults').innerHTML = `
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Quantidade</th>
                                    <th>Fornecedor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>${data}</tbody>
                        </table>
                    `;
                })
                .catch(error => console.error('Erro na pesquisa:', error));
        });

        // Função para selecionar/deselecionar todos os produtos
        document.getElementById('selectAll').addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('input[name="select[]"]').forEach(checkbox => {
                checkbox.checked = checked;
            });
        });

        // Função para excluir produtos selecionados
        document.getElementById('deleteSelectedButton').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('input[name="select[]"]:checked'))
                .map(input => input.value);

            if (selectedIds.length === 0) {
                alert('Nenhum item selecionado.');
                return;
            }

            if (confirm('Você tem certeza que deseja excluir os itens selecionados?')) {
                const formData = new FormData();
                formData.append('action', 'delete_selected');
                formData.append('ids', selectedIds.join(','));

                fetch('product_register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => location.reload())
                .catch(error => console.error('Erro ao excluir:', error));
            }
        });
    </script>
</body>

</html>
