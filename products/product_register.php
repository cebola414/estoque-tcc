<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

// Função para obter todos os produtos
function getProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM products");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Adicionar, atualizar ou excluir um produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $supplier = $_POST['supplier'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, quantity, supplier) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $quantity, $supplier]);
    } elseif ($action === 'update') {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, quantity = ?, supplier = ? WHERE id = ?");
            $stmt->execute([$name, $description, $quantity, $supplier, $id]);
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete_all') {
        $stmt = $pdo->query("DELETE FROM products");
    }

    // Redirecionar após a operação
    header('Location: product_register.php');
    exit();
}

// Obter produtos para exibir na tabela
$products = getProducts($pdo);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>
    <style>
        /* Reset básico e fontes */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Estilo do cabeçalho */
        header {
            background-color: #1e1e1e;
            display: flex;
            color: #a6a6a6;
            padding: 5px;
            font-family: sans-serif;
            align-items: center;
        }

        header img.logo {
            max-width: 45px;
            max-height: 45px;
            width: auto;
            height: auto;
        }

        header h1 {
            padding-left: 20px;
            color: #a6a6a6;
        }

        /* Navegação */
        nav {
            background-color: #333;
            overflow: hidden;
        }

        nav a {
            float: left;
            display: block;
            color: #a6a6a6;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
        }

        nav a:hover {
            background-color: #ddd;
            color: black;
        }

        /* Container principal */
        .main-container {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }

        .container {
            flex: 1;
            padding: 20px;
            background-color: #fff;
        }

        /* Formulário */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
            color: #555;
        }

        input, textarea {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        textarea {
            font-weight: bold;
            color: #555;
        }

        /* Botões */
        button, .buttonRed, .buttonSave {
            width: 150px;
            height: 45px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-start;
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

        /* Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        /* Botões de ação na tabela */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-button, .delete-button {
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            color: #fff;
        }

        .edit-button {
            background-color: #007bff;
        }

        .edit-button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        /* Container de opções */
        .options-container {
            width: 200px;
            padding: 20px;
            background-color: #fff;
            display: grid;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <header>
        <img class="logo" src="Imagens\box.png" alt="Logo Box" />
        <h1>Estoque</h1>
    </header>
    <nav>
        <a href="../Index.html">Inicio</a>
    </nav>
    <div class="main-container">
        <div class="container">
            <form id="productForm" method="POST">
                <input type="hidden" id="productId" name="id">
                <label for="name">Nome do Produto:</label>
                <input type="text" id="name" name="name" required>
                <label for="description">Descrição:</label>
                <textarea id="description" name="description" rows="3"></textarea>
                <label for="quantity">Quantidade:</label>
                <input type="number" id="quantity" name="quantity" required>
                <label for="supplier">Fornecedor:</label>
                <input type="text" id="supplier" name="supplier" required>
                <div class="button-container">
                    <button type="button" id="searchButton">Pesquisar Produto</button>
                    <button class="buttonRed" type="button" id="deleteAllButton">Excluir Tudo</button>
                    <button class="buttonSave" type="submit" id="saveButton" name="action" value="add">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="container">
        <table id="productTable">
            <thead>
                <tr>
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
                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($product['supplier']); ?></td>
                    <td class="action-buttons">
                        <button class="edit-button" onclick="editProduct(<?php echo htmlspecialchars($product['id']); ?>)">Editar</button>
                        <form action="product_register.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="delete-button" type="submit">Deletar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        function editProduct(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('productId').value = product.id;
                    document.getElementById('name').value = product.name;
                    document.getElementById('description').value = product.description;
                    document.getElementById('quantity').value = product.quantity;
                    document.getElementById('supplier').value = product.supplier;
                    document.getElementById('saveButton').textContent = 'Atualizar';
                    document.getElementById('saveButton').setAttribute('value', 'update');
                    document.getElementById('productForm').setAttribute('action', 'product_register.php');
                });
        }

        document.getElementById('searchButton').addEventListener('click', function() {
            const nomeProduto = prompt('Digite o nome do produto:');
            if (nomeProduto === null) return;
            fetch(`search_product.php?name=${nomeProduto}`)
                .then(response => response.json())
                .then(product => {
                    if (product) {
                        alert(`Produto encontrado:\nID: ${product.id}\nNome: ${product.name}\nDescrição: ${product.description}\nQuantidade: ${product.quantity}\nFornecedor: ${product.supplier}`);
                    } else {
                        alert('Produto não encontrado.');
                    }
                });
        });

        document.getElementById('deleteAllButton').addEventListener('click', function() {
            if (confirm('Tem certeza que deseja excluir todos os produtos?')) {
                fetch('product_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_all'
                }).then(() => location.reload());
            }
        });
    </script>
</body>
</html>
