// Champs dynamiques selon le type de mission
const fieldsByType = {
    vtc: `
    
        <div class="col-md-2"><input type="text" name="departure" class="form-control" placeholder="Départ" required></div>
        <div class="col-md-2"><input type="text" name="arrival" class="form-control" placeholder="Arrivée" required></div>
        <div class="col-md-2"><input type="datetime-local" name="datetime" class="form-control" required></div>
        <div class="col-md-2"><input type="text" name="driver" class="form-control" placeholder="Chauffeur" required></div>
        <div class="col-md-2"><input type="text" name="vehicle" class="form-control" placeholder="Véhicule" required></div>
    `,
    commerce: `
        <div class="col-md-3"><input type="text" name="product" class="form-control" placeholder="Produit" required></div>
        <div class="col-md-3"><input type="number" name="quantity" class="form-control" placeholder="Quantité" required></div>
        <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description" required></div>
    `,
    entreprise: `
        <div class="col-md-4"><input type="text" name="project" class="form-control" placeholder="Projet" required></div>
        <div class="col-md-4"><input type="datetime-local" name="datetime" class="form-control" required></div>

        <div class="col-md-4"><input type="text" name="description" class="form-control" placeholder="Description" required></div>
    `
};

function updateFields() {
    const type = document.getElementById('mission-type').value;
    document.getElementById('dynamic-fields').innerHTML = fieldsByType[type] || '';
}

document.getElementById('mission-type').addEventListener('change', updateFields);
document.addEventListener('DOMContentLoaded', updateFields);

// Inline edit missions
$(document).ready(function(){
    $('#missionsTable td[contenteditable=true]').on('blur', function() {
        var td = $(this);
        var value = td.text();
        var field = td.data('field');
        var id = td.data('id');
        $.ajax({
            url: 'mission_inline_update.php',
            method: 'POST',
            data: { id: id, field: field, value: value },
            success: function(response) {
                td.css('background', '#d4edda');
                setTimeout(function(){ td.css('background', ''); }, 800);
            },
            error: function() {
                td.css('background', '#f8d7da');
                setTimeout(function(){ td.css('background', ''); }, 800);
            }
        });
    });
});


// Guide pour le type de mission
document.addEventListener('DOMContentLoaded', function() {
    const guide = document.getElementById('mission-type-guide');
    if (guide) {
        guide.style.display = 'flex';
        setTimeout(() => {
            guide.style.display = 'none';
        }, 3000);
    }
});
