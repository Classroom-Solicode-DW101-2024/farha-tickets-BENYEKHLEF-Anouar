<!-- <?php
require 'config.php';

$search     = isset($_POST['search']) ? $_POST['search'] : '';
$date_debut = isset($_POST['date_debut']) ? $_POST['date_debut'] : '';
$date_fin   = isset($_POST['date_fin']) ? $_POST['date_fin'] : '';
$categorie  = isset($_POST['categorie']) ? $_POST['categorie'] : '';

$sql = "SELECT e.eventId, e.eventTitle, e.eventType, ed.dateEvent, ed.timeEvent, ed.editionId, ed.image, s.capSalle,
               IFNULL(SUM(r.qteBilletsNormal + r.qteBilletsReduit), 0) as total_billets
        FROM evenement e
        JOIN edition ed ON e.eventId = ed.eventId
        JOIN salle s ON ed.NumSalle = s.NumSalle
        LEFT JOIN reservation r ON ed.editionId = r.editionId
        WHERE ed.dateEvent >= CURDATE()";

if (!empty($search)) {
    $sql .= " AND e.eventTitle LIKE '%" . addslashes($search) . "%'";
}

if (!empty($date_debut) && !empty($date_fin)) {
    $sql .= " AND ed.dateEvent BETWEEN '" . addslashes($date_debut) . "' AND '" . addslashes($date_fin) . "'";
}

if (!empty($categorie)) {
    $sql .= " AND e.eventType = '" . addslashes($categorie) . "'";
}

$sql .= " GROUP BY ed.editionId ORDER BY ed.dateEvent ASC";

$result = $pdo->query($sql);
$events = $result->fetchAll(PDO::FETCH_ASSOC);


?> -->