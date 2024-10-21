<?php
// Inclusão de arquivos de configuração e autenticação
require_once '../includes/auth_check.php';
require_once '../db/config.php';

// Função para obter categorias de um usuário específico
function getCategories($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter fornecedores de um usuário específico
function getSuppliers($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addSupplier($pdo, $name, $address, $contact, $userId)
{
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, address, contact, user_id) VALUES (?, ?, ?, ?)");
    return executeStatement($stmt, [$name, $address, $contact, $userId]);
}
// Função para obter o nome de usuário
function getUser($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para buscar produtos de um usuário, com suporte a pesquisa
function getProducts($pdo, $userId, $searchQuery = '')
{
    $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN suppliers s ON p.supplier = s.id 
            WHERE p.user_id = ?";
    $params = [$userId];

    // Adiciona a busca por nome ou descrição, caso o termo de pesquisa seja fornecido
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

// Função para executar uma instrução SQL com tratamento de exceções
function executeStatement($stmt, $params)
{
    try {
        $stmt->execute($params);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Função para adicionar um novo produto
function addProduct($pdo, $name, $description, $quantity, $supplier, $categoryId, $userId)
{
    $stmt = $pdo->prepare("INSERT INTO products (name, description, quantity, supplier, category_id, user_id) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    return executeStatement($stmt, [$name, $description, $quantity, $supplier, $categoryId, $userId]);
}

// Função para excluir um produto específico
function deleteProduct($pdo, $id, $userId)
{
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    return executeStatement($stmt, [$id, $userId]);
}

// Função para excluir múltiplos produtos selecionados
function deleteSelectedProducts($pdo, $ids, $userId)
{
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders) AND user_id = ?");
    return executeStatement($stmt, array_merge($ids, [$userId]));
}

// Função para adicionar uma nova categoria
function addCategory($pdo, $categoryName, $userId)
{
    $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
    return executeStatement($stmt, [$categoryName, $userId]);
}

// Função para excluir uma categoria
function deleteCategory($pdo, $id, $userId)
{
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    return executeStatement($stmt, [$id, $userId]);
}

// Função para editar um produto existente
function editProduct($pdo, $id, $name, $description, $quantity, $supplier, $categoryId, $userId)
{
    $stmt = $pdo->prepare("UPDATE products 
                           SET name = ?, description = ?, quantity = ?, supplier = ?, category_id = ? 
                           WHERE id = ? AND user_id = ?");
    return executeStatement($stmt, [$name, $description, $quantity, $supplier, $categoryId, $id, $userId]);
}

// Função para editar uma categoria existente
function editCategory($pdo, $id, $categoryName, $userId)
{
    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
    return executeStatement($stmt, [$categoryName, $id, $userId]);
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
    

    // Switch para tratar as diferentes ações de produto e categoria
    switch ($action) {
        case 'add':
            $result = addProduct($pdo, $name, $description, $quantity, $supplier, $categoryId, $userId);
            break;
        case 'update':
            if ($id) {
                $result = editProduct($pdo, $id, $name, $description, $quantity, $supplier, $categoryId, $userId);
            }
            break;
        case 'delete':
            if ($id) {
                $result = deleteProduct($pdo, $id, $userId);
                echo $result['success'] ? 'success' : 'error';
                exit();
            }
            break;
        case 'delete_selected':
            $ids = explode(',', $_POST['ids']);
            if (is_array($ids) && count($ids) > 0) {
                $result = deleteSelectedProducts($pdo, $ids, $userId);
                echo $result['success'] ? 'success' : 'error';
                exit();
            }
            break;
        case 'delete_category':
            if ($id) {
                $result = deleteCategory($pdo, $id, $userId);
                echo $result['success'] ? 'success' : 'error';
                exit();
            }
            break;
        case 'add_category':
            $result = addCategory($pdo, $name, $userId);
            echo json_encode($result); // Retorna a resposta em formato JSON
            exit();
        case 'update_category':
            if ($id) {
                $result = editCategory($pdo, $id, $name, $userId);
                echo json_encode($result); // Retorna a resposta em formato JSON
                exit();
            }
            break;
            case 'add_supplier':
                $result = addSupplier($pdo, $name, $_POST['address'], $_POST['contact'], $userId);
                echo json_encode($result); // Retorna a resposta em formato JSON
                exit();
            
    }

    // Redireciona após o processamento da ação
    header('Location: product_register.php');
    exit();
}

// Carregar produtos e categorias para exibição
$searchQuery = $_GET['search'] ?? '';
$userId = $_SESSION['user_id'];
$products = getProducts($pdo, $userId, $searchQuery);
$user = getUser($pdo, $userId);
$categories = getCategories($pdo, $userId);
$suppliers = getSuppliers($pdo, $userId); 

// Retorna os produtos encontrados para a pesquisa via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $products = getProducts($pdo, $userId, $searchQuery);

    if (count($products) > 0) {
        // Exibe cada produto encontrado na tabela de resultados
        foreach ($products as $product) {
            echo '<tr>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td>' . htmlspecialchars($product['description']) . '</td>
                <td>' . htmlspecialchars($product['quantity']) . '</td>
                <td>' . htmlspecialchars($product['supplier_name']) . '</td> 
                <td>' . htmlspecialchars($product['category_name']) . '</td>
                <td>
                    <button class="editButton" data-id="' . htmlspecialchars($product['id']) . '">Editar</button>
                    <button class="deleteButton" data-id="' . htmlspecialchars($product['id']) . '">Excluir</button>
                </td>
            </tr>';
        }
        

    } else {
        // Caso não haja resultados, retorna 'no-results'
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

        /* Informações do usuário no cabeçalho */
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

        /* Contêiner dos botões principais */
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

        .deleteButton{
            background-color: #ce0000;
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
            <button id="openManageSuppliersModal">Gerenciar Fornecedores</button>
        </div>

        <!-- Tabela de Produtos -->
        <table id="productTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
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
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
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
            <select id="supplier" name="supplier" required>
                <option value="" disabled selected>Selecione um fornecedor</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>">
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

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
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
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
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Fornecedor</th>
                        <th>Categoria</th>
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
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
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

    <div id="manageSuppliersModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Gerenciar Fornecedores</h2>
        <button id="openAddSupplierModal">Novo Fornecedor</button>
        <table id="supplierTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Endereço</th>
                    <th>Contato</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['contact']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Modal de Adicionar Fornecedor -->
<!-- Modal de Adicionar Fornecedor -->
<div id="addSupplierModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Adicionar Fornecedor</h2>
        <form id="addSupplierForm" method="post">
            <input type="hidden" name="action" value="add_supplier">
            
            <label for="supplierName">Nome do Fornecedor:</label>
            <input type="text" id="supplierName" name="name" required><br>
            
            <label for="supplierAddress">Endereço:</label>
            <input type="text" id="supplierAddress" name="address" required><br>
            
            <label for="supplierContact">Contato:</label>
            <input type="text" id="supplierContact" name="contact" required><br>
            
            <button type="submit">Adicionar</button>
        </form>
    </div>
</div>




    <script>
        // Função para abrir um modal
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'block'; // Exibe o modal
            }
        }

        // Função para fechar um modal
        function closeModal(modal) {
            if (modal) {
                modal.style.display = 'none'; // Esconde o modal
            }
        }

        // Configuração de eventos para fechar modais
        function setupModalEvents() {
            const modals = document.querySelectorAll('.modal');
            const closes = document.querySelectorAll('.modal .close'); // Seleciona todos os botões de fechar modais

            // Adiciona evento de fechamento a cada botão 'close'
            closes.forEach(close => {
                close.addEventListener('click', () => {
                    closeModal(close.closest('.modal')); // Fecha o modal mais próximo
                });
            });

            // Fecha o modal se clicar fora dele (na área do modal)
            window.addEventListener('click', event => {
                if (event.target.classList.contains('modal')) {
                    closeModal(event.target);
                }
            });
        }

        // Função genérica para excluir itens (produtos ou categorias)
        function deleteItem(type, id) {
            const confirmMessage = `Tem certeza que deseja excluir este ${type === 'product' ? 'produto' : 'categoria'}?`;

            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', type === 'product' ? 'delete' : 'delete_category');
                formData.append('id', id);

                // Requisição para excluir o item via fetch
                fetch('product_register.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            alert(`${type === 'product' ? 'Produto' : 'Categoria'} excluído com sucesso.`);
                            location.reload(); // Atualiza a página
                        } else {
                            alert(`Erro ao excluir o ${type === 'product' ? 'produto' : 'categoria'}.`);
                            console.error('Erro:', data);
                        }
                    })
                    .catch(error => {
                        alert(`Erro ao excluir o ${type === 'product' ? 'produto' : 'categoria'}.`);
                        console.error('Erro:', error);
                    });
            }
        }

 // Configura o formulário de adicionar fornecedor
document.getElementById('addSupplierForm')?.addEventListener('submit', function(e) {
    e.preventDefault(); // Impede o envio padrão do formulário

    const formData = new FormData(this);
    formData.append('action', 'add_supplier'); // Adiciona a ação

    // Requisição para adicionar um novo fornecedor
    fetch('product_register.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Fornecedor adicionado com sucesso.');
                location.reload(); // Atualiza a página
            } else {
                alert('Erro ao adicionar fornecedor: ' + (data.message || 'Erro desconhecido.'));
            }
        })
        .catch(error => {
            alert('Erro ao adicionar fornecedor: ' + error);
        });
});



        // Função para configurar ações de edição e exclusão de produtos
        function setupProductActions() {
            // Configura o botão de edição
            document.querySelectorAll('.editButton').forEach(button => {
                button.addEventListener('click', () => {
                    const productId = button.getAttribute('data-id');
                    document.getElementById('editId').value = productId;
                    openModal('editModal'); // Abre o modal de edição
                });
            });

            // Configura o botão de exclusão
            document.querySelectorAll('.deleteButton').forEach(button => {
                button.addEventListener('click', () => {
                    const productId = button.getAttribute('data-id');
                    deleteItem('product', productId); // Exclui o produto
                });
            });

            // Configura o botão de exclusão de múltiplos produtos
            const deleteSelectedButton = document.getElementById('deleteSelected');
            if (deleteSelectedButton) {
                deleteSelectedButton.addEventListener('click', () => {
                    const selectedIds = Array.from(document.querySelectorAll('input[name="select[]"]:checked')).map(checkbox => checkbox.value);

                    if (selectedIds.length > 0 && confirm('Tem certeza que deseja excluir os produtos selecionados?')) {
                        const formData = new FormData();
                        formData.append('action', 'delete_selected');
                        formData.append('ids', selectedIds.join(',')); // IDs separados por vírgula

                        fetch('product_register.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                if (data === 'success') {
                                    alert('Produtos selecionados excluídos com sucesso.');
                                    location.reload();
                                } else {
                                    alert('Erro ao excluir os produtos selecionados.');
                                    console.error('Erro:', data);
                                }
                            })
                            .catch(error => {
                                alert('Erro ao excluir os produtos selecionados.');
                                console.error('Erro:', error);
                            });
                    } else {
                        alert('Nenhum produto selecionado.');
                    }
                });
            }
        }

        // Função para configurar ações de categorias (edição e exclusão)
        function setupCategoryActions() {
            // Configura o botão de edição de categorias
            document.querySelectorAll('.editCategoryButton').forEach(button => {
                button.addEventListener('click', () => {
                    const categoryId = button.getAttribute('data-id');
                    document.getElementById('editCategoryId').value = categoryId;
                    openModal('editCategoryModal'); // Abre o modal de edição de categoria
                });
            });

            // Configura o botão de exclusão de categorias
            document.querySelectorAll('.deleteCategoryButton').forEach(button => {
                button.addEventListener('click', () => {
                    const categoryId = button.getAttribute('data-id');
                    deleteItem('category', categoryId); // Exclui a categoria
                });
            });
        }

        // Função para configurar ações dentro do modal de pesquisa
        function setupSearchActions() {
            const searchResultsTable = document.getElementById('searchResults').querySelector('tbody');

            // Configura as ações de editar e excluir dentro do modal de pesquisa
            searchResultsTable.addEventListener('click', event => {
                if (event.target.classList.contains('editButton')) {
                    const productId = event.target.getAttribute('data-id');
                    document.getElementById('editId').value = productId;

                    // Fecha o modal de pesquisa
                    closeModal(document.getElementById('searchModal'));

                    // Abre o modal de edição
                    openModal('editModal');
                } else if (event.target.classList.contains('deleteButton')) {
                    const productId = event.target.getAttribute('data-id');
                    deleteItem('product', productId); // Exclui o produto
                }
            });
        }

        // Função para pesquisa de produtos
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', e => {
                e.preventDefault();

                const searchQuery = document.getElementById('searchQuery').value;
                const resultsTable = document.getElementById('searchResults').querySelector('tbody');
                const noResultsDiv = document.getElementById('noResults');
                const tableHead = document.getElementById('searchResults').querySelector('thead');

                // Limpa os resultados anteriores
                resultsTable.innerHTML = '';

                // Requisição AJAX para buscar os produtos
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

                            // Habilita as ações nos resultados de pesquisa
                            setupSearchActions();
                        }
                    })
                    .catch(error => {
                        console.error('Erro na pesquisa:', error);
                    });
            });
        }

        // Configura o formulário de edição de categoria
        document.getElementById('editCategoryForm')?.addEventListener('submit', function(e) {
            e.preventDefault(); // Impede o envio padrão do formulário

            const formData = new FormData(this);

            // Requisição para editar a categoria
            fetch('product_register.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Categoria editada com sucesso.');
                        location.reload(); // Atualiza a página
                    } else {
                        alert('Erro ao editar categoria: ' + (data.message || 'Erro desconhecido.'));
                    }
                })
                .catch(error => {
                    alert('Erro ao editar categoria: ' + error);
                });
        });

        // Função para selecionar ou desmarcar todos os checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="select[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked; // Define o estado dos checkboxes com base no checkbox "Selecionar Todos"
            });
        });

        // Configura o formulário de adicionar categoria
        document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
            e.preventDefault(); // Impede o envio padrão do formulário

            const formData = new FormData(this);

            // Requisição para adicionar uma nova categoria
            fetch('product_register.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Categoria adicionada com sucesso.');
                        location.reload(); // Atualiza a página
                    } else {
                        alert('Erro ao adicionar categoria: ' + (data.message || 'Erro desconhecido.'));
                    }
                })
                .catch(error => {
                    alert('Erro ao adicionar categoria: ' + error);
                });
        });

        // Inicializa eventos e ações de modais e produtos
        setupModalEvents();
        setupProductActions();
        setupCategoryActions();

// Eventos de abertura de modais
document.getElementById('openAddModal')?.addEventListener('click', () => openModal('addModal'));
document.getElementById('openSearchModal')?.addEventListener('click', () => openModal('searchModal'));
document.getElementById('openCategoryModal')?.addEventListener('click', () => openModal('categoryModal'));
document.getElementById('openAddCategoryModal')?.addEventListener('click', () => openModal('addCategoryModal'));
document.getElementById('openManageSuppliersModal')?.addEventListener('click', () => openModal('manageSuppliersModal')); // Adicionado
// Evento para abrir o modal de adicionar fornecedor
document.getElementById('openAddSupplierModal')?.addEventListener('click', () => openModal('addSupplierModal'));

    </script>