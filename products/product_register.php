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

// Carregar produto por ID (via AJAX)
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
        /* Estilos globais */
        /* Estilos gerais */
        /* Estilos gerais */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Estilos do cabeçalho */
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
            margin-right: 12px;
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
            display: flex;
            align-items: center;
            background-color: #ce0000;
            color: #fff;
            border: none;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .imglogout {
            max-width: 19px;
            max-height: 19px;
            margin-right: 8px;
            margin-left: 37px;
        }

        .buttonLogout:hover {
            background-color: #6b0000;
        }

        /* Container principal */
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

        /* Contêiner dos botões */
        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        /* Estilos dos botões */
        button,
        .buttonRed,
        .buttonAdd,
        .buttonSave,
        .buttonSearch,
        .buttonEditarModal {
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

        /* Estilos específicos para o botão "Editar Produto" dentro do modal */
        .buttonEditarModal {
            background-color: #f39c12;
        }

        .buttonEditarModal:hover {
            background-color: #e67e22;
        }

        /* Estilos para o modal */
        .modal,
        .searchModal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        /* Estilo específico para o conteúdo do modal */
        .modal-content,
        .searchModal-content {
            background-color: #ffffff;
            margin: 10% auto;
            /* Aumentado para mais espaço superior e inferior */
            padding: 20px;
            /* Aumentado para mais espaço interno */
            border: 1px solid #ddd;
            width: 90%;
            max-width: 800px;
            /* Pode ser ajustado conforme necessário */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Estilo para o botão de fechar do modal */
        .modal-content .close,
        .searchModal-content .close {
            color: #888;
            float: right;
            font-size: 20px;
            /* Reduzido para mais conforto */
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
            /* Aumentado para mais espaço entre os campos */
        }

        /* Estilo dos campos do formulário */
        .modal-content label,
        .searchModal-content label {
            font-size: 14px;
            /* Reduzido para mais conforto */
            margin-bottom: 8px;
            /* Aumentado para mais espaço */
        }

        .modal-content input[type="text"],
        .searchModal-content input[type="text"],
        .modal-content input[type="number"],
        .searchModal-content input[type="number"] {
            padding: 10px;
            /* Aumentado para mais conforto */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            /* Reduzido para mais conforto */
        }

        /* Estilo dos botões dentro do modal */
        .modal-content button,
        .searchModal-content button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px;
            /* Aumentado para mais conforto */
            border-radius: 5px;
            font-size: 14px;
            /* Reduzido para mais conforto */
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
            /* Aumentado para mais conforto */
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
        <img src="../Imagens/icons8-caixa-128.png" class="logo" alt="Logo">
        <h1>Cadastro de Produtos</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <form id="logoutForm" action="logout.php" method="post">
                <button type="submit" class="buttonLogout"><img src="../imagens/icons8-logout-100.png" class="imglogout">Sair</button>
            </form>
        </div>
    </header>

    <div class="main-container">
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

    <!-- Modal de Adicionar Produto -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Adicionar Produto</h2>
            <form id="addProductForm" method="post">
                <input type="hidden" name="action" value="add">
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" required><br>
                <label for="description">Descrição:</label>
                <input type="text" id="description" name="description" required><br>
                <label for="quantity">Quantidade:</label>
                <input type="number" id="quantity" name="quantity" required><br>
                <label for="supplier">Fornecedor:</label>
                <input type="text" id="supplier" name="supplier" required><br>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Produto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Editar Produto</h2>
            <form id="editProductForm" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editId" name="id">
                <label for="editName">Nome:</label>
                <input type="text" id="editName" name="name" required><br>
                <label for="editDescription">Descrição:</label>
                <input type="text" id="editDescription" name="description" required><br>
                <label for="editQuantity">Quantidade:</label>
                <input type="number" id="editQuantity" name="quantity" required><br>
                <label for="editSupplier">Fornecedor:</label>
                <input type="text" id="editSupplier" name="supplier" required><br>
                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Pesquisa -->
    <div id="searchModal" class="modal">
        <div class="modal-content">
            <h2>Pesquisar Produtos</h2>
            <form id="searchForm" method="get" action="product_register.php">
                <label for="searchQuery">Nome ou Descrição:</label>
                <input type="text" id="searchQuery" name="search" required>
                <button type="submit">Pesquisar</button>
            </form>
            <div id="noResults">Nenhum produto encontrado.</div>
            <table id="searchResults">
                <!-- Cabeçalho da tabela (inicialmente escondido) -->
                <thead style="display: none;">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Fornecedor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <!-- Resultados da pesquisa serão inseridos aqui via JavaScript -->
                <tbody></tbody>
            </table>
        </div>
    </div>
    <script>
        // Abrir modal de Adicionar Produto
        document.getElementById('openAddModal').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'block';
        });

        // Abrir modal de Pesquisa
        document.getElementById('openSearchModal').addEventListener('click', function() {
            document.getElementById('searchModal').style.display = 'block';
        });

        // Função para fechar os modais ao clicar fora do conteúdo
        window.onclick = function(event) {
            const modals = ['addModal', 'editModal', 'searchModal'];
            modals.forEach(function(modalId) {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        };

        // Função de Excluir Produtos
        document.getElementById('productTable').addEventListener('click', function(event) {
            if (event.target.classList.contains('deleteButton')) {
                const id = event.target.getAttribute('data-id');
                if (confirm('Tem certeza que deseja excluir este produto?')) {
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
            }
        });

        // Função de Editar Produtos
        document.getElementById('productTable').addEventListener('click', function(event) {
            if (event.target.classList.contains('editButton')) {
                const id = event.target.getAttribute('data-id');

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
            }
        });

        // Função de Pesquisa com Excluir/Editar no Modal
        document.getElementById('searchForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const query = document.getElementById('searchQuery').value;

            fetch('product_register.php?search=' + encodeURIComponent(query) + '&ajax=true')
                .then(response => response.text())
                .then(html => {
                    const searchResults = document.getElementById('searchResults');
                    searchResults.innerHTML = html;

                    if (html.trim() === '') {
                        document.getElementById('noResults').style.display = 'block';
                    } else {
                        document.getElementById('noResults').style.display = 'none';
                    }
                })
                .catch(error => console.error('Erro ao buscar produtos:', error));
        });

        // Função de Excluir no Modal de Pesquisa
        document.getElementById('searchResults').addEventListener('click', function(event) {
            if (event.target.classList.contains('deleteButton')) {
                const id = event.target.getAttribute('data-id');
                if (confirm('Tem certeza que deseja excluir este produto?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    fetch('product_register.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(() => document.getElementById('searchForm').submit())
                        .catch(error => console.error('Erro ao excluir produto:', error));
                }
            }
        });

        // Função de Editar no Modal de Pesquisa
        document.getElementById('searchResults').addEventListener('click', function(event) {
            if (event.target.classList.contains('editButton')) {
                const id = event.target.getAttribute('data-id');

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
            }
        });
    </script>
</body>

</html>