<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

function getProducts($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUser($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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
            flex: 1;
            /* Faz o título ocupar o espaço disponível */
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
    background-color: rgba(0, 0, 0, 0.5); /* Fundo semi-transparente */
}

/* Estilo específico para o conteúdo do modal */
.modal-content,
.searchModal-content {
    background-color: #ffffff;
    margin: 5% auto; /* Ajusta a margem para centralizar verticalmente */
    padding: 20px;
    border: 1px solid #ddd;
    width: 90%; /* Ajusta a largura para 90% da tela */
    max-width: 800px; /* Define uma largura máxima */
    border-radius: 8px; /* Bordas arredondadas */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra suave ao redor do modal */
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
    gap: 15px; /* Espaçamento entre os elementos do formulário */
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
.searchModal-content input[type="number"],
.modal-content textarea,
.searchModal-content textarea {
    width: calc(100% - 20px); /* Ajusta a largura considerando o padding */
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 14px;
}

.modal-content button,
.searchModal-content button {
    width: 100%;
    padding: 12px;
    background-color: #007bff; /* Cor de fundo do botão */
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra suave ao redor do botão */
}

.modal-content button:hover,
.searchModal-content button:hover {
    background-color: #0056b3; /* Cor do botão ao passar o mouse */
}

/* Estilo da tabela de resultados */
.searchModal-content table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.searchModal-content th,
.searchModal-content td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.searchModal-content th {
    background-color: #f4f4f4;
    color: #333;
    font-weight: bold;
}

.searchModal-content tr:nth-child(even) {
    background-color: #f9f9f9;
}

.searchModal-content tr:hover {
    background-color: #e2e2e2; /* Cor de fundo ao passar o mouse sobre a linha */
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
    <div class="searchModal-content">
        <span class="close" id="closeSearchModal">&times;</span>
        <h2>Buscar Produtos</h2>
        <form id="searchForm" action="product_search.php" method="GET">
            <label for="search">Nome do Produto:</label>
            <input type="text" id="search" name="search" />
            <button type="button" id="searchButton" class="buttonSave">Buscar</button>
        </form>
        <table id="searchResults">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Quantidade</th>
                    <th>Fornecedor</th>
                </tr>
            </thead>
            <tbody id="searchResultsBody">
                <!-- Resultados da busca serão exibidos aqui -->
            </tbody>
        </table>
    </div>
</div>

    <script>
        document.getElementById('openAddModalButton').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'block';
        });

        function editProduct(product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editQuantity').value = product.quantity;
            document.getElementById('editSupplier').value = product.supplier;
            document.getElementById('editModal').style.display = 'block';
        }

        document.getElementById('closeAddModal').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'none';
        });

        document.getElementById('closeEditModal').addEventListener('click', function() {
            document.getElementById('editModal').style.display = 'none';
        });

        document.getElementById('closeSearchModal').addEventListener('click', function() {
            document.getElementById('searchModal').style.display = 'none';
        });

        document.getElementById('openSearchModalButton').addEventListener('click', function() {
            document.getElementById('searchModal').style.display = 'block';
        });

        document.getElementById('searchButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('search').value;
            fetch(`product_search.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('searchResultsBody').innerHTML = data;
                });
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.productCheckbox').forEach(checkbox => {
                checkbox.checked = checked;
            });
        });

        document.getElementById('deleteSelectedButton').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.productCheckbox:checked')).map(cb => cb.value);
            if (selectedIds.length > 0) {
                fetch('product_register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete_selected',
                        ids: JSON.stringify(selectedIds)
                    })
                })
                .then(() => location.reload());
            } else {
                alert('Selecione pelo menos um produto para excluir.');
            }
        });
    </script>
</body>

</html>