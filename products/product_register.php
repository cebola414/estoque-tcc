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
// Adicionar no início do arquivo PHP
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($product);
    exit();
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
            font-size: 16px;
            cursor: pointer;
        }

        .modal-content button:hover,
        .searchModal-content button:hover {
            background-color: #0056b3;
        }

        /* Estilo para a mensagem de erro */
        #noResults {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        /* Estilo da tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #e9e9e9;
        }
    </style>
</head>

<body>
    <header>
        <img src="../Imagens/box.png" class="logo" alt="Logo">
        <h1>Cadastro de Produtos</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <form action="logout.php" method="post">
                <button type="submit" class="buttonLogout">Sair</button>
            </form>
        </div>
    </header>

    <div class="main-container">
        <div class="container">
            <div class="button-container">
                <button id="openAddModal" class="buttonAdd">Adicionar Produto</button>
                <button id="openSearchModal" class="buttonSearch">Pesquisar</button>
                <button id="deleteSelected" class="buttonRed">Excluir Selecionados</button>
            </div>

            <!-- Tabela de Produtos -->
            <table id="productTable">
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
                <tbody>
                    <!-- Os produtos serão carregados aqui via PHP -->
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

    <!-- Modal de Adicionar Produto -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" id="addModalClose">&times;</span>
            <h2>Adicionar Produto</h2>
            <form id="addForm">
                <label for="addName">Nome:</label>
                <input type="text" id="addName" name="name" required>

                <label for="addDescription">Descrição:</label>
                <input type="text" id="addDescription" name="description">

                <label for="addQuantity">Quantidade:</label>
                <input type="number" id="addQuantity" name="quantity" min="0" required>

                <label for="addSupplier">Fornecedor:</label>
                <input type="text" id="addSupplier" name="supplier">

                <button type="submit" class="buttonSave">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Editar Produto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="editModalClose">&times;</span>
            <h2>Editar Produto</h2>
            <form id="editForm">
                <input type="hidden" id="editId" name="id">
                
                <label for="editName">Nome:</label>
                <input type="text" id="editName" name="name" required>

                <label for="editDescription">Descrição:</label>
                <input type="text" id="editDescription" name="description">

                <label for="editQuantity">Quantidade:</label>
                <input type="number" id="editQuantity" name="quantity" min="0" required>

                <label for="editSupplier">Fornecedor:</label>
                <input type="text" id="editSupplier" name="supplier">

                <button type="submit" class="buttonSave">Salvar</button>
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
            <div id="noResults" style="display: none; background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">
                Nenhum produto encontrado
            </div>
            <div id="searchResults">
                <!-- Resultados da pesquisa serão exibidos aqui -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Abre o modal de adicionar produto
            document.getElementById('openAddModal').addEventListener('click', function() {
                document.getElementById('addModal').style.display = 'block';
            });

            // Fecha o modal de adicionar produto
            document.getElementById('addModalClose').addEventListener('click', function() {
                document.getElementById('addModal').style.display = 'none';
            });

            // Abre o modal de editar produto
            document.querySelectorAll('.editButton').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        
        // Carregar dados do produto via AJAX e preencher o formulário
        fetch('product_register.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editId').value = data.id;
                document.getElementById('editName').value = data.name;
                document.getElementById('editDescription').value = data.description;
                document.getElementById('editQuantity').value = data.quantity;
                document.getElementById('editSupplier').value = data.supplier;
                document.getElementById('editModal').style.display = 'block';
            })
            .catch(error => console.error('Erro ao carregar dados do produto:', error));
    });
});

            // Fecha o modal de editar produto
            document.getElementById('editModalClose').addEventListener('click', function() {
                document.getElementById('editModal').style.display = 'none';
            });

            // Abre o modal de pesquisa
            document.getElementById('openSearchModal').addEventListener('click', function() {
                document.getElementById('searchModal').style.display = 'block';
            });

            // Fecha o modal de pesquisa
            document.getElementById('searchModalClose').addEventListener('click', function() {
                document.getElementById('searchModal').style.display = 'none';
            });

            // Pesquisa produtos e exibe os resultados
            document.getElementById('searchForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const searchQuery = document.getElementById('search').value;

                fetch('product_register.php?ajax=true&search=' + encodeURIComponent(searchQuery))
                    .then(response => response.text())
                    .then(data => {
                        const searchResults = document.getElementById('searchResults');
                        const noResults = document.getElementById('noResults');

                        if (data.trim() === '') {
                            noResults.style.display = 'block';
                            searchResults.innerHTML = '';
                        } else {
                            noResults.style.display = 'none';
                            searchResults.innerHTML = `
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
                        }
                    })
                    .catch(error => console.error('Erro ao buscar produtos:', error));
            });

            // Exclui produtos selecionados
            document.getElementById('deleteSelected').addEventListener('click', function() {
                const selectedIds = Array.from(document.querySelectorAll('input[name="select[]"]:checked')).map(checkbox => checkbox.value);
                if (selectedIds.length > 0) {
                    if (confirm('Tem certeza de que deseja excluir os produtos selecionados?')) {
                        const formData = new FormData();
                        formData.append('action', 'delete_selected');
                        formData.append('ids', selectedIds.join(','));

                        fetch('product_register.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(() => location.reload())
                        .catch(error => console.error('Erro ao excluir produtos:', error));
                    }
                } else {
                    alert('Selecione ao menos um produto para excluir.');
                }
            });

            // Salvar novo produto
            document.getElementById('addForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add');

                fetch('product_register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    document.getElementById('addModal').style.display = 'none';
                    location.reload();
                })
                .catch(error => console.error('Erro ao adicionar produto:', error));
            });

            // Salvar alterações no produto
            document.getElementById('editForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'update');

                fetch('product_register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    document.getElementById('editModal').style.display = 'none';
                    location.reload();
                })
                .catch(error => console.error('Erro ao atualizar produto:', error));
            });

            // Excluir produto individualmente
            document.querySelectorAll('.deleteButton').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        if (confirm('Tem certeza de que deseja excluir este produto?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('product_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => location.reload())
            .catch(error => console.error('Erro ao excluir produto:', error));
        }
    });
});
        });
    </script>
</body>
</html>