// filepath: /webitech-erp/webitech-erp/erp/assets/js/shifts.js
document.addEventListener('DOMContentLoaded', function() {
    const shiftForm = document.getElementById('shiftForm');
    const shiftsTable = document.getElementById('shiftsTable');

    function fetchShifts() {
        fetch('shifts.php?action=fetch')
            .then(response => response.json())
            .then(data => {
                renderShifts(data);
            })
            .catch(error => console.error('Error fetching shifts:', error));
    }

    function renderShifts(shifts) {
        shiftsTable.innerHTML = '';
        shifts.forEach(shift => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${shift.employee_name}</td>
                <td>${shift.start_datetime}</td>
                <td>${shift.end_datetime}</td>
                <td>${shift.role}</td>
                <td>
                    <button class="edit-btn" data-id="${shift.id}">Edit</button>
                    <button class="delete-btn" data-id="${shift.id}">Delete</button>
                </td>
            `;
            shiftsTable.appendChild(row);
        });
    }

    shiftForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(shiftForm);
        const action = formData.get('id') ? 'update' : 'create';

        fetch(`shifts.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                fetchShifts();
                shiftForm.reset();
            }
        })
        .catch(error => console.error('Error saving shift:', error));
    });

    shiftsTable.addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-btn')) {
            const id = event.target.dataset.id;
            fetch(`shifts.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(shift => {
                    shiftForm.elements['id'].value = shift.id;
                    shiftForm.elements['employee_id'].value = shift.employee_id;
                    shiftForm.elements['start_datetime'].value = shift.start_datetime;
                    shiftForm.elements['end_datetime'].value = shift.end_datetime;
                    shiftForm.elements['role'].value = shift.role;
                })
                .catch(error => console.error('Error fetching shift:', error));
        }

        if (event.target.classList.contains('delete-btn')) {
            const id = event.target.dataset.id;
            if (confirm('Are you sure you want to delete this shift?')) {
                fetch(`shifts.php?action=delete`, {
                    method: 'POST',
                    body: JSON.stringify({ id }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        fetchShifts();
                    }
                })
                .catch(error => console.error('Error deleting shift:', error));
            }
        }
    });

    fetchShifts();
});