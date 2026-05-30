<?php
// sale_row.php - Modèle pour afficher une ligne de vente dans l'interface utilisateur

function renderSaleRow($sale) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($sale['id']) . '</td>';
    echo '<td>' . htmlspecialchars($sale['product_name']) . '</td>';
    echo '<td>' . htmlspecialchars($sale['quantity']) . '</td>';
    echo '<td>' . htmlspecialchars($sale['price']) . '</td>';
    echo '<td>' . htmlspecialchars($sale['total']) . '</td>';
    echo '<td>' . htmlspecialchars($sale['date']) . '</td>';
    echo '<td><button class="btn btn-edit" data-id="' . htmlspecialchars($sale['id']) . '">Modifier</button></td>';
    echo '<td><button class="btn btn-delete" data-id="' . htmlspecialchars($sale['id']) . '">Supprimer</button></td>';
    echo '</tr>';
}
?>