<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Vérification des autorisations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accès refusé');
}

// Vérification des paramètres
if (!isset($_GET['module_code']) || !isset($_GET['format'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Paramètres manquants');
}

$moduleCode = $_GET['module_code'];
$format = $_GET['format'];

// Vérifier que le professeur gère ce module
$stmt = $pdo->prepare("SELECT 1 FROM Avoir WHERE professeur_id = ? AND module_code = ?");
$stmt->execute([$_SESSION['user']['id'], $moduleCode]);

if (!$stmt->fetch()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Vous ne gérez pas ce module');
}

// Récupérer les informations du module
$stmt = $pdo->prepare("SELECT nom FROM Module WHERE code_inscription = ?");
$stmt->execute([$moduleCode]);
$module = $stmt->fetch();

// Récupérer les étudiants avec leurs moyennes
$stmt = $pdo->prepare("SELECT e.id, e.nom, e.prenom, e.adresse, 
                      (SELECT AVG(r.note) FROM Rendu r 
                       JOIN Travail t ON r.travail_id = t.id 
                       WHERE r.etudiant_id = e.id AND t.module_code = ? AND r.note IS NOT NULL) as moyenne
                      FROM Etudiant e
                      JOIN Inscrit i ON e.id = i.etudiant_id
                      WHERE i.module_code = ?
                      ORDER BY e.nom, e.prenom");
$stmt->execute([$moduleCode, $moduleCode]);
$students = $stmt->fetchAll();

// Exporter selon le format demandé
switch ($format) {
    case 'excel':
        exportToExcel($students, $module);
        break;
    case 'pdf':
        exportToPDF($students, $module);
        break;
    default:
        header('HTTP/1.1 400 Bad Request');
        exit('Format non supporté');
}

// Fonction pour exporter en Excel
function exportToExcel($students, $module) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="etudiants_' . $module['nom'] . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='4'>Étudiants - " . htmlspecialchars($module['nom']) . "</th></tr>";
    echo "<tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Moyenne</th>
          </tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student['nom']) . "</td>";
        echo "<td>" . htmlspecialchars($student['prenom']) . "</td>";
        echo "<td>" . htmlspecialchars($student['adresse']) . "</td>";
        echo "<td>" . ($student['moyenne'] ? round($student['moyenne'], 2) : 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;
}

// Fonction pour exporter en PDF (requiert TCPDF)
function exportToPDF($students, $module) {
    require_once __DIR__ . '/../../lib/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    // Dans exportToPDF(), vous pouvez ajouter :
    $pdf->SetHeaderData('', 0, 'ScholarHub', 'Export étudiants - ' . date('d/m/Y'));
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetAuthor($_SESSION['user']['nom']);
    $pdf->SetTitle('Liste des étudiants - ' . $module['nom']);
    $pdf->SetSubject('Export étudiants');
    $pdf->AddPage();
    
    // En-tête
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Liste des étudiants - ' . $module['nom'], 0, 1, 'C');
    $pdf->Ln(10);
    
    // Tableau
    $pdf->SetFont('helvetica', '', 10);
    
    // En-tête du tableau
    $pdf->SetFillColor(211, 211, 211);
    $pdf->Cell(60, 7, 'Nom', 1, 0, 'C', 1);
    $pdf->Cell(60, 7, 'Prénom', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Email', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Moyenne', 1, 1, 'C', 1);
    
    // Contenu du tableau
    $pdf->SetFillColor(245, 245, 245);
    $fill = false;
    
    foreach ($students as $student) {
        $pdf->Cell(60, 6, $student['nom'], 'LR', 0, 'L', $fill);
        $pdf->Cell(60, 6, $student['prenom'], 'LR', 0, 'L', $fill);
        $pdf->Cell(50, 6, $student['adresse'], 'LR', 0, 'L', $fill);
        $pdf->Cell(20, 6, $student['moyenne'] ? round($student['moyenne'], 2) : 'N/A', 'LR', 1, 'C', $fill);
        $fill = !$fill;
    }
    
    $pdf->Cell(190, 0, '', 'T'); // Ligne de fermeture
    
    // Pied de page
    $pdf->SetFont('helvetica', 'I', 8);

    
    $pdf->Output('etudiants_' . $module['nom'] . '.pdf', 'D');
    exit;
}
?>