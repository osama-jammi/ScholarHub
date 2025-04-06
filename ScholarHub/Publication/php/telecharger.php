<?php
include"../../Base-donnee/index.php";

$id = intval($_GET['id']);

$sql = "SELECT name,file FROM support WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nom, $contenu);
$stmt->fetch();
$stmt->close();
$conn->close();

// Type forcé
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$nom\"");
header("Content-Length: " . strlen($contenu));
echo $contenu;
?>
