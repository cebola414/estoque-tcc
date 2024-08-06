<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

// Funções para obter produtos e informações do usuário
function getProducts($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUser($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Adicionar, atualizar ou excluir um produto
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
            $ids = json_decode($_POST['ids'], true);
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

$products = getProducts($pdo, $_SESSION['user_id']);
$user = getUser($pdo, $_SESSION['user_id']);
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
            flex: 1; /* Faz o título ocupar o espaço disponível */
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

        nav {
            background-color: #333;
            overflow: hidden;
        }

        nav a {
            display: block;
            color: #fff;
            text-align: center;
            padding: 14px;
            text-decoration: none;
        }

        nav a:hover {
            background-color: #ddd;
            color: black;
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
        .buttonSave {
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

        .buttonSave {
            background-color: #00ac28;
            color: #fff;
        }

        button:hover {
            background-color: #2a2a2a;
        }

        .buttonRed:hover {
            background-color: #6b0000;
        }

        .buttonSave:hover {
            background-color: #006b19;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        .options-container {
            width: 100%;
            padding: 10px;
            background-color: #fff;
            display: grid;
            flex-direction: column;
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
            background-color: rgba(0, 0, 0, 0.6); /* Fundo semi-transparente para melhor visibilidade */
            padding: 20px; /* Espaçamento ao redor do modal */
            box-sizing: border-box;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 30px; /* Padding interno do modal */
            border: 1px solid #ddd;
            width: 100%;
            max-width: 600px; /* Largura máxima ajustada */
            border-radius: 8px; /* Bordas arredondadas */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Sombra mais pronunciada */
            box-sizing: border-box;
        }

        .close {
            color: #000;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px; /* Espaço abaixo do botão de fechar */
        }

        .close:hover {
            color: #777; /* Cor de hover mais clara */
        }

        .modal-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-footer {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 20px;
            text-align: right;
        }

        .modal-footer button {
            margin-left: 10px;
        }

        /* Estilos para o formulário dentro do modal */
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Espaço entre os campos do formulário */
        }

        .modal-content label {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .modal-content input[type="number"] {
            -moz-appearance: textfield;
        }

        .modal-content input[type="number"]::-webkit-inner-spin-button,
        .modal-content input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body>
    <header>
        <img class="logo" src="../Imagens/box.png" alt="Logo Box" />
        <h1>Estoque</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <form action="../logout.php" method="POST" style="margin: 0;">
                <button type="submit" class="buttonLogout">Sair</button>
            </form>
        </div>
    </header>

    <div class="main-container">
        <div class="container">
            <div class="button-container">
                <button id="openAddModalButton">Adicionar Produto</button>
                <button id="deleteSelectedButton" class="buttonRed">Excluir Selecionados</button>
                <button id="openSearchModalButton">Buscar Produtos</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" /></th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Fornecedor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                    <tr>
                        <td><input type="checkbox" class="productCheckbox" value="<?php echo htmlspecialchars($product['id']); ?>" /></td>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier']); ?></td>
                        <td>
                            <button onclick='editProduct(<?php echo json_encode($product); ?>)'>Editar</button>
                            <form action="product_register.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <button type="submit" class="buttonRed">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Adicionar Produto -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddModal">&times;</span>
            <h2>Adicionar Novo Produto</h2>
            <form action="product_register.php" method="POST">
                <input type="hidden" name="action" value="add" />
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" required />
                <label for="description">Descrição:</label>
                <textarea id="description" name="description" required></textarea>
                <label for="quantity">Quantidade:</label>
                <input type="number" id="quantity" name="quantity" required />
                <label for="supplier">Fornecedor:</label>
                <input type="text" id="supplier" name="supplier" required />
                <button type="submit" class="buttonSave">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar Produto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Editar Produto</h2>
            <form action="product_register.php" method="POST">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" id="editId" name="id" />
                <label for="editName">Nome:</label>
                <input type="text" id="editName" name="name" required />
                <label for="editDescription">Descrição:</label>
                <textarea id="editDescription" name="description" required></textarea>
                <label for="editQuantity">Quantidade:</label>
                <input type="number" id="editQuantity" name="quantity" required />
                <label for="editSupplier">Fornecedor:</label>
                <input type="text" id="editSupplier" name="supplier" required />
                <button type="submit" class="buttonSave">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal Buscar Produto -->
    <div id="searchModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeSearchModal">&times;</span>
            <h2>Buscar Produtos</h2>
            <form id="searchForm" action="product_register.php" method="GET">
                <label for="search">Nome do Produto:</label>
                <input type="text" id="search" name="search" />
                <button type="submit" class="buttonSave">Buscar</button>
            </form>
        </div>
    </div>

    <script>
        // Função para abrir o modal de adicionar produto
        document.getElementById('openAddModalButton').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'block';
        });

        // Função para abrir o modal de edição com os dados do produto
        function editProduct(product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editQuantity').value = product.quantity;
            document.getElementById('editSupplier').value = product.supplier;
            document.getElementById('editModal').style.display = 'block';
        }

        // Função para selecionar/deselecionar todos os checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            let checked = this.checked;
            document.querySelectorAll('.productCheckbox').forEach(cb => cb.checked = checked);
        });

        // Função para abrir e fechar os modais
        document.getElementById('closeAddModal').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'none';
        });
        document.getElementById('openSearchModalButton').addEventListener('click', function() {
            document.getElementById('searchModal').style.display = 'block';
        });
        document.getElementById('closeSearchModal').addEventListener('click', function() {
            document.getElementById('searchModal').style.display = 'none';
        });
        document.getElementById('closeEditModal').addEventListener('click', function() {
            document.getElementById('editModal').style.display = 'none';
        });

        // Função para excluir produtos selecionados
        document.getElementById('deleteSelectedButton').addEventListener('click', function() {
            let selectedIds = Array.from(document.querySelectorAll('.productCheckbox:checked')).map(cb => cb.value);
            if (selectedIds.length > 0) {
                if (confirm('Tem certeza de que deseja excluir os produtos selecionados?')) {
                    fetch('product_register.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'delete_selected',
                            ids: JSON.stringify(selectedIds)
                        })
                    }).then(response => response.text()).then(data => {
                        location.reload();
                    });
                }
            } else {
                alert('Nenhum produto selecionado.');
            }
        });
    </script>
</body>

</html>
