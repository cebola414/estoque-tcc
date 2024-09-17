<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

// Funções auxiliares
function getCategories($pdo)
{
    $stmt = $pdo->query("SELECT * FROM categories");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUser($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getProducts($pdo, $userId, $searchQuery = '')
{
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.user_id = ?";
    $params = [$userId];

    if (!empty($searchQuery)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addProduct($pdo, $name, $description, $quantity, $supplier, $categoryId, $userId)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, quantity, supplier, category_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $quantity, $supplier, $categoryId, $userId]);
        return ['success' => true, 'message' => 'Produto adicionado com sucesso.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao adicionar produto: ' . $e->getMessage()];
    }
}

function deleteProduct($pdo, $id, $userId)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return ['success' => true, 'message' => 'Produto excluído com sucesso.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao excluir produto: ' . $e->getMessage()];
    }
}

function deleteSelectedProducts($pdo, $ids, $userId)
{
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute(array_merge($ids, [$userId]));
        return ['success' => true, 'message' => 'Produtos selecionados excluídos com sucesso.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao excluir produtos selecionados: ' . $e->getMessage()];
    }
}

// Função para excluir uma categoria
function deleteCategory($pdo, $id)
{
    try {
        // Verifica se a categoria está associada a produtos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return ['success' => false, 'message' => 'Não é possível excluir a categoria, pois ela está associada a produtos.'];
        }

        // Exclui a categoria
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Categoria excluída com sucesso.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao excluir categoria: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_category') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $result = deleteCategory($pdo, $id);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
}
// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    $userId = $_SESSION['user_id'];

    $result = ['success' => false, 'message' => 'Ação não reconhecida.'];

    switch ($action) {
        case 'add':
            $result = addProduct($pdo, $name, $description, $quantity, $supplier, $categoryId, $userId);
            break;
        case 'update':
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, quantity = ?, supplier = ?, category_id = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$name, $description, $quantity, $supplier, $categoryId, $id, $userId]);
                $result = ['success' => true, 'message' => 'Produto atualizado com sucesso.'];
            }
            break;
        case 'delete':
            if ($id) {
                $result = deleteProduct($pdo, $id, $userId);
            }
            break;
        case 'delete_selected':
            $ids = explode(',', $_POST['ids']);
            if (is_array($ids) && count($ids) > 0) {
                $result = deleteSelectedProducts($pdo, $ids, $userId);
            }
            break;
        case 'add_category':
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $result = ['success' => true, 'message' => 'Categoria adicionada com sucesso.'];
            break;
        case 'update_category':
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $result = ['success' => true, 'message' => 'Categoria atualizada com sucesso.'];
            }
            break;
        case 'delete_category':
            if ($id) {
                $result = deleteCategory($pdo, $id);
            }
            break;
    }

    // Enviar resposta JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}
// Carregar produtos e categorias
$searchQuery = $_GET['search'] ?? '';
$userId = $_SESSION['user_id'];
$products = getProducts($pdo, $userId, $searchQuery);
$user = getUser($pdo, $userId);
$categories = getCategories($pdo);

// Retornar produtos para pesquisa via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $searchQuery = $_GET['search'] ?? '';
    $userId = $_SESSION['user_id'];
    $products = getProducts($pdo, $userId, $searchQuery);

    if (count($products) > 0) {
        foreach ($products as $product) {
            echo '<tr>
                <td>' . htmlspecialchars($product['id']) . '</td>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td>' . htmlspecialchars($product['description']) . '</td>
                <td>' . htmlspecialchars($product['quantity']) . '</td>
                <td>' . htmlspecialchars($product['supplier']) . '</td>
                <td>' . htmlspecialchars($product['category_name']) . '</td>
                <td>
                    <button class="editButton" data-id="' . htmlspecialchars($product['id']) . '">Editar</button>
                    <button class="deleteButton" data-id="' . htmlspecialchars($product['id']) . '">Excluir</button>
                </td>
            </tr>';
        }
    } else {
        echo 'no-results';
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

        /* Estilos do cabeçalho */
        header {
            background-color: #1e1e1e;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            color: #a6a6a6;
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

        .buttonLogout:hover {
            background-color: #6b0000;
        }

        .imglogout {
            max-width: 19px;
            max-height: 19px;
            margin-left: 37px;
            margin-right: 8px;
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
        .buttonAdd,
        .buttonSave,
        .buttonSearch,
        .buttonRed {
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

        .buttonAdd,
        .buttonSave {
            background-color: #00ac28;
            color: #fff;
        }

        .buttonAdd:hover,
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

        .buttonRed {
            background-color: #ce0000;
            color: #fff;
        }

        .buttonRed:hover {
            background-color: #6b0000;
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
            background-color: rgba(0, 0, 0, 0.4);
            overflow-y: auto;
            /* Permite rolagem no modal */
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }


        .modal-content .close {
            color: #888;
            float: right;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-content .close:hover,
        .modal-content .close:focus {
            color: #000;
            text-decoration: none;
        }

        /* Estilo do formulário dentro do modal */
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content label {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .modal-content button {
            background-color: #007BFF;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        .modal-content button:hover {
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
            <button id="openCategoryModal" class="buttonAdd">Gerenciar Categorias</button>
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
                    <th>Categoria</th>
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
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
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
            <span class="close">&times;</span>
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
                <label for="category">Categoria:</label>
                <select id="category" name="category_id" required>
                    <option value="" disabled selected>Selecione uma categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Produto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
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
                <label for="editCategory">Categoria:</label>
                <select id="editCategory" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <div id="searchModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Pesquisar Produtos</h2>
            <form id="searchForm">
                <label for="searchQuery">Nome ou Descrição:</label>
                <input type="text" id="searchQuery" name="search" required>
                <button type="submit">Pesquisar</button>
            </form>
            <div id="noResults" class="no-products" style="display: none; background-color: #f8d7da;">Nenhum produto encontrado.</div>
            <table id="searchResults">
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
                <tbody></tbody>
            </table>
        </div>
    </div>


    <!-- Modal de Gerenciamento de Categorias -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Gerenciar Categorias</h2>
            <button id="openAddCategoryModal" class="buttonAdd">Adicionar Categoria</button>
            <table id="categoryTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td>
                                <button class="editCategoryButton" data-id="<?php echo htmlspecialchars($category['id']); ?>">Editar</button>
                                <button class="deleteCategoryButton" data-id="<?php echo htmlspecialchars($category['id']); ?>">Excluir</button>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Adicionar Categoria -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Adicionar Categoria</h2>
            <form id="addCategoryForm" method="post">
                <input type="hidden" name="action" value="add_category">
                <label for="categoryName">Nome da Categoria:</label>
                <input type="text" id="categoryName" name="name" required><br>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Categoria -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Categoria</h2>
            <form id="editCategoryForm" method="post">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" id="editCategoryId" name="id">
                <label for="editCategoryName">Nome da Categoria:</label>
                <input type="text" id="editCategoryName" name="name" required><br>
                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(modal) {
            modal.style.display = 'none';
        }

        function setupModalEvents() {
            var modals = document.querySelectorAll('.modal');
            var closes = document.querySelectorAll('.modal .close');

            closes.forEach(function(close) {
                close.addEventListener('click', function() {
                    closeModal(this.closest('.modal'));
                });
            });

            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    closeModal(event.target);
                }
            });
        }

        document.getElementById('openAddModal').addEventListener('click', function() {
            openModal('addModal');
        });

        document.getElementById('openSearchModal').addEventListener('click', function() {
            openModal('searchModal');
        });

        document.getElementById('openCategoryModal').addEventListener('click', function() {
            openModal('categoryModal');
        });

        document.getElementById('openAddCategoryModal').addEventListener('click', function() {
            openModal('addCategoryModal');
        });

        setupModalEvents();

        // Lógica de edição de produtos
        document.querySelectorAll('.editButton').forEach(function(button) {
            button.addEventListener('click', function() {
                var productId = this.getAttribute('data-id');
                document.getElementById('editId').value = productId;
                openModal('editModal');
            });
        });

// Lógica de exclusão de produtos via AJAX
function handleAction(action, data) {
    fetch('product_register.php', {
            method: 'POST',
            body: data,
        })
        .then(response => response.json()) // Espera resposta JSON
        .then(data => {
            if (data.success) {
                alert(data.message); // Mensagem de sucesso
            } else {
                alert(data.message); // Mensagem de erro
            }
        })
        .catch(error => {
            alert('Erro ao processar a ação.');
            console.error('Erro:', error);
        });
}

// Função para excluir produto
function deleteProduct(productId) {
    if (confirm("Tem certeza que deseja excluir este produto?")) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', productId);
        handleAction('delete', formData);
    }
}

// Função para adicionar produto
function addProduct(name, description, quantity, supplier, categoryId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', name);
    formData.append('description', description);
    formData.append('quantity', quantity);
    formData.append('supplier', supplier);
    formData.append('category_id', categoryId);
    handleAction('add', formData);
}

// Vincular eventos de clique para os botões de exclusão e adição
document.querySelectorAll('.deleteButton').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        deleteProduct(productId);
    });
});

// Exemplo de uso para adicionar produto (você deve adaptar para seu caso)
document.querySelector('#addProductButton').addEventListener('click', function() {
    const name = document.querySelector('#productName').value;
    const description = document.querySelector('#productDescription').value;
    const quantity = document.querySelector('#productQuantity').value;
    const supplier = document.querySelector('#productSupplier').value;
    const categoryId = document.querySelector('#productCategory').value;
    addProduct(name, description, quantity, supplier, categoryId);
});



        // Lógica de edição de categorias
        document.querySelectorAll('.editCategoryButton').forEach(function(button) {
            button.addEventListener('click', function() {
                var categoryId = this.getAttribute('data-id');
                document.getElementById('editCategoryId').value = categoryId;
                openModal('editCategoryModal');
            });
        });

        // Lógica de exclusão de categorias
        document.querySelectorAll('.deleteCategoryButton').forEach(function(button) {
            button.addEventListener('click', function() {
                var categoryId = this.getAttribute('data-id');
                if (confirm('Tem certeza que deseja excluir esta categoria?')) {
                    fetch('product_register.php?action=delete_category&id=' + categoryId)
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message); // Exibe a mensagem recebida
                            if (data.success) {
                                location.reload(); // Atualiza a página se a exclusão for bem-sucedida
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Erro ao excluir a categoria.');
                        });
                }
            });
        });

        document.getElementById('deleteSelected').addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('input[name="select[]"]:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);

            if (selectedIds.length > 0) {
                if (confirm('Tem certeza que deseja excluir os produtos selecionados?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_selected');
                    formData.append('ids', selectedIds.join(',')); // Enviar os IDs separados por vírgula

                    fetch('product_register.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert('Produtos selecionados excluídos com sucesso.');
                            location.reload(); // Atualiza a página para refletir as mudanças
                        })
                        .catch(error => {
                            alert('Erro ao excluir os produtos selecionados.');
                            console.error(error);
                        });
                }
            } else {
                alert('Nenhum produto selecionado.');
            }
        });

        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var searchQuery = document.getElementById('searchQuery').value;
            var resultsTable = document.getElementById('searchResults').querySelector('tbody');
            var noResultsDiv = document.getElementById('noResults');
            var tableHead = document.getElementById('searchResults').querySelector('thead');

            // Limpar os resultados anteriores
            resultsTable.innerHTML = '';

            // Fazer requisição AJAX
            fetch('product_register.php?action=search&search=' + encodeURIComponent(searchQuery))
                .then(response => response.text())
                .then(data => {
                    if (data === 'no-results') {
                        noResultsDiv.style.display = 'block';
                        tableHead.style.display = 'none';
                    } else {
                        noResultsDiv.style.display = 'none';
                        tableHead.style.display = 'table-header-group';
                        resultsTable.innerHTML = data;
                    }
                })
                .catch(error => {
                    console.error('Erro na pesquisa:', error);
                });
        });
    </script>