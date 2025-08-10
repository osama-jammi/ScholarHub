<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'SCHOLARHUB';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonctions utilitaires
function getTableData($pdo, $table) {
    $stmt = $pdo->query("SELECT * FROM $table");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTableColumns($pdo, $table) {
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    return $columns;
}

function isBlobColumn($columnInfo) {
    return stripos($columnInfo['Type'], 'blob') !== false || 
           stripos($columnInfo['Type'], 'binary') !== false;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $table = $_POST['table'];
        $id = $_POST['id'];
        $idColumn = $_POST['id_column'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $idColumn = ?");
            $stmt->execute([$id]);
            $message = "Enregistrement supprimé avec succès!";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
    
    if (isset($_POST['add'])) {
        $table = $_POST['table'];
        $data = $_POST['data'];
        $fileData = [];
        
        // Gestion des fichiers uploadés
        foreach ($_FILES as $field => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileData[$field] = file_get_contents($file['tmp_name']);
            }
        }
        
        // Fusion des données normales et des fichiers
        $mergedData = array_merge($data, $fileData);
        
        try {
            $columns = implode(', ', array_keys($mergedData));
            $values = ':' . implode(', :', array_keys($mergedData));
            
            $stmt = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($values)");
            $stmt->execute($mergedData);
            $message = "Enregistrement ajouté avec succès!";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// Récupération des tables
$tables = [
    'Professeur', 'Etudiant', 'Module', 'Cours', 
    'Chapitre', 'Rendu', 'Travail', 'Publication',
    'Rediger', 'Contient', 'Avoir_cours', 'Contient_chapitre',
    'Publier', 'Annonce', 'Inscrit', 'Avoir', 'Contient2'
];

$currentTable = $_GET['table'] ?? 'Professeur';
$tableData = getTableData($pdo, $currentTable);
$tableColumns = getTableColumns($pdo, $currentTable);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration ScholarHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .tables-nav { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .table-btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .table-btn:hover { background: #45a049; }
        .active { background: #2E7D32; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
        .form-container { margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .submit-btn { background: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .submit-btn:hover { background: #4cae4c; }
        .delete-btn { background: #d9534f; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .delete-btn:hover { background: #c9302c; }
        .blob-preview { max-width: 100px; max-height: 100px; }
        .blob-placeholder { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administration de ScholarHub</h1>
        
        <?php if (isset($message)): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="tables-nav">
            <?php foreach ($tables as $table): ?>
                <a href="?table=<?= $table ?>" class="table-btn <?= $currentTable === $table ? 'active' : '' ?>">
                    <?= $table ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <h2>Table: <?= $currentTable ?></h2>
        
        <table>
            <thead>
                <tr>
                    <?php foreach ($tableColumns as $column => $info): ?>
                        <th><?= $column ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableData as $row): ?>
                    <tr>
                        <?php foreach ($tableColumns as $column => $info): ?>
                            <td>
                                <?php if (isBlobColumn($info) && !empty($row[$column])): ?>
                                    <?php if (strpos($info['Type'], 'image') !== false || 
                                              strpos($column, 'photo') !== false): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($row[$column]) ?>" 
                                             class="blob-preview" alt="Preview">
                                    <?php else: ?>
                                        <span class="blob-placeholder">[Fichier binaire]</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($row[$column] ?? 'NULL') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="table" value="<?= $currentTable ?>">
                                <input type="hidden" name="id" value="<?= $row[array_key_first($tableColumns)] ?>">
                                <input type="hidden" name="id_column" value="<?= array_key_first($tableColumns) ?>">
                                <button type="submit" name="delete" class="delete-btn">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-container">
            <h3>Ajouter un enregistrement</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="table" value="<?= $currentTable ?>">
                
                <?php foreach ($tableColumns as $column => $info): ?>
                    <div class="form-group">
                        <label for="<?= $column ?>"><?= $column ?> (<?= $info['Type'] ?>):</label>
                        <?php if (isBlobColumn($info)): ?>
                            <input type="file" id="<?= $column ?>" name="<?= $column ?>">
                        <?php else: ?>
                            <?php if ($info['Type'] === 'text'): ?>
                                <textarea id="<?= $column ?>" name="data[<?= $column ?>]" rows="4" style="width: 100%;"></textarea>
                            <?php else: ?>
                                <input type="text" id="<?= $column ?>" name="data[<?= $column ?>]">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" name="add" class="submit-btn">Ajouter</button>
            </form>
        </div>
    </div>
</body>
</html>