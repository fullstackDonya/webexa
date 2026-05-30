
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('salesTableBody');
    const btnAdd = document.getElementById('btnAdd');
    const modal = document.getElementById('salesModal');
    const form = document.getElementById('salesForm');
    const saleId = document.getElementById('saleId');
    const selectProduct = document.getElementById('selectProduct');
    const selectEmployee = document.getElementById('selectEmployee');
    const inputQty = document.getElementById('inputQty');
    const inputTotal = document.getElementById('inputTotal');
    const btnCancel = document.getElementById('btnCancel');
    const modalTitle = document.getElementById('modalTitle');
    const generateInvoice = document.getElementById('generateInvoice');
    const vatRateRow = document.getElementById('vatRateRow');
    const vatRate = document.getElementById('vatRate');

    // élément minimum requis
    if (!tbody) {
        console.warn('sales.js: #salesTableBody introuvable — script arrêté');
        return;
    }

    function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    
    // Fonctions modal (définies en premier pour être disponibles partout)
    function showModal() {
        if (modal) {
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            if (selectProduct) selectProduct.focus();
        }
    }
    
    function closeModal() {
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    // Toggle VAT rate selector when invoice checkbox is checked
    if (generateInvoice && vatRateRow) {
        generateInvoice.addEventListener('change', function() {
            vatRateRow.style.display = this.checked ? 'flex' : 'none';
        });
    }

    function fetchSales() {
        fetch('sales.php?action=fetch')
            .then(r => r.json())
            .then(data => {
                tbody.innerHTML = '';
                
                // Si aucune vente, afficher le message empty-state
                if (!data || data.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="7" style="padding: 3rem; text-align: center;">
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>Aucune vente enregistrée</h3>
                                <p>Commencez par ajouter votre première vente</p>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                    return;
                }
                
                data.forEach(s => {
                    const invoiceIcon = s.invoice_id ? 
                        `<span class="badge" style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                            <i class="fas fa-file-invoice"></i> Facturée
                        </span>` : '';
                    
                    const tr = document.createElement('tr');
                    tr.dataset.id = s.id;
                    tr.innerHTML = `
                        <td><strong>#${escapeHtml(s.id)}</strong></td>
                        <td class="product-cell">
                            <i class="fas fa-box"></i> ${escapeHtml(s.product_name)}
                        </td>
                        <td>
                            <div class="employee-cell">
                                <div class="employee-avatar">
                                    ${(s.first_name || '').substring(0,1).toUpperCase()}${(s.last_name || '').substring(0,1).toUpperCase()}
                                </div>
                                ${escapeHtml((s.first_name||'') + ' ' + (s.last_name||''))}
                            </div>
                        </td>
                        <td>
                            <span class="quantity-badge">
                                <i class="fas fa-layer-group"></i>
                                ${escapeHtml(s.quantity)}
                            </span>
                        </td>
                        <td class="price-cell">${parseFloat(s.total_price).toFixed(2).replace('.', ',')} €</td>
                        <td class="date-cell">
                            <i class="far fa-calendar"></i>
                            ${new Date(s.created_at).toLocaleString('fr-FR')}
                        </td>
                        <td>
                            <div class="action-buttons">
                                ${invoiceIcon}
                                ${s.invoice_id ? 
                                    `<a href="invoices.php?id=${s.invoice_id}" class="btn-icon" title="Voir facture" style="background: linear-gradient(135deg, #dbeafe, #e0e7ff); color: #1e40af;">
                                        <i class="fas fa-eye"></i>
                                    </a>` : ''}
                                <button class="btn-icon delete" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>`;
                    tbody.appendChild(tr);
                });
            })
            .catch(()=> console.error('Erreur fetch sales'));
    }

    // délégation d'événements sur le tbody (toujours présent)
    tbody.addEventListener('click', function(e){
        const btn = e.target.closest('button');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;
        if (btn.classList.contains('delete')) {
            if (!confirm('Supprimer cette vente ?')) return;
            const fd = new URLSearchParams(); fd.append('id', id);
            fetch('sales.php?action=delete', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(()=> fetchSales())
                .catch(()=> alert('Erreur suppression'));
        }
    });

    // Le reste des interactions ne nécessite pas tbody; vérifier existence avant enregistrement d'écouteurs
    if (btnAdd) {
        btnAdd.addEventListener('click', function(){
            if (saleId) saleId.value = '';
            if (selectProduct) selectProduct.value = '';
            if (selectEmployee) selectEmployee.value = '';
            if (inputQty) inputQty.value = 1;
            if (inputTotal) inputTotal.value = '0.00';
            if (generateInvoice) generateInvoice.checked = false;
            if (vatRateRow) vatRateRow.style.display = 'none';
            if (modalTitle) modalTitle.textContent = 'Ajouter une vente';
            showModal();
        });
    } else {
        console.error('❌ #btnAdd introuvable — ajout désactivé');
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    if (form) {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const url = 'sales.php?action=create';
            const fd = new URLSearchParams();
            fd.append('product_id', selectProduct ? selectProduct.value : '');
            fd.append('employee_id', selectEmployee ? selectEmployee.value : '');
            fd.append('quantity', parseInt(inputQty ? inputQty.value : 0) || 0);
            fd.append('total_price', parseFloat(inputTotal ? inputTotal.value : 0) || 0);
            
            // Désactiver le bouton pour éviter double-click
            const btnSave = document.getElementById('btnSave');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            }
            
            fetch(url, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.error) { 
                        alert(res.error);
                        if (btnSave) {
                            btnSave.disabled = false;
                            btnSave.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                        }
                        return;
                    }
                    
                    // Vérifier si on doit générer une facture
                    if (generateInvoice && generateInvoice.checked && res.id) {
                        // Créer la facture
                        const fdInvoice = new URLSearchParams();
                        fdInvoice.append('sale_id', res.id);
                        if (vatRate) {
                            fdInvoice.append('vat_rate', vatRate.value);
                        }
                        
                        fetch('api/invoices.php?action=from-sale', { 
                            method: 'POST', 
                            body: fdInvoice 
                        })
                        .then(r => r.json())
                        .then(invRes => {
                            if (invRes.success) {
                                alert('✅ Vente et facture créées avec succès !\nNuméro de facture généré.');
                            } else {
                                alert('⚠️ Vente créée mais erreur facture: ' + (invRes.error || 'Erreur inconnue'));
                            }
                            closeModal();
                            fetchSales();
                            if (btnSave) {
                                btnSave.disabled = false;
                                btnSave.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                            }
                        })
                        .catch(() => {
                            alert('⚠️ Vente créée mais erreur réseau lors de la création de la facture');
                            closeModal();
                            fetchSales();
                            if (btnSave) {
                                btnSave.disabled = false;
                                btnSave.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                            }
                        });
                    } else {
                        // Pas de facture, juste fermer le modal
                        closeModal();
                        fetchSales();
                        if (btnSave) {
                            btnSave.disabled = false;
                            btnSave.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                        }
                    }
                })
                .catch(() => {
                    alert('Erreur réseau');
                    if (btnSave) {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                    }
                });
        });
    } else {
        console.info('sales.js: #salesForm introuvable — soumission désactivée');
    }

    // ============================================
    // Gestion des modals d'ajout rapide
    // ============================================
    
    const btnAddProduct = document.getElementById('btnAddProduct');
    const btnAddEmployee = document.getElementById('btnAddEmployee');
    const productModal = document.getElementById('productModal');
    const employeeModal = document.getElementById('employeeModal');
    const productForm = document.getElementById('productForm');
    const employeeForm = document.getElementById('employeeForm');
    const btnCancelProduct = document.getElementById('btnCancelProduct');
    const btnCancelEmployee = document.getElementById('btnCancelEmployee');

    function showProductModal() {
        if (productModal) {
            productModal.classList.add('show');
            productModal.setAttribute('aria-hidden', 'false');
            document.getElementById('productName')?.focus();
        }
    }

    function closeProductModal() {
        if (productModal) {
            productModal.classList.remove('show');
            productModal.setAttribute('aria-hidden', 'true');
            if (productForm) productForm.reset();
        }
    }

    function showEmployeeModal() {
        if (employeeModal) {
            employeeModal.classList.add('show');
            employeeModal.setAttribute('aria-hidden', 'false');
            document.getElementById('employeeFirstName')?.focus();
        }
    }

    function closeEmployeeModal() {
        if (employeeModal) {
            employeeModal.classList.remove('show');
            employeeModal.setAttribute('aria-hidden', 'true');
            if (employeeForm) employeeForm.reset();
        }
    }

    // Ouvrir modal produit
    if (btnAddProduct) {
        btnAddProduct.addEventListener('click', function(e) {
            e.preventDefault();
            showProductModal();
        });
    }

    // Ouvrir modal employé
    if (btnAddEmployee) {
        btnAddEmployee.addEventListener('click', function(e) {
            e.preventDefault();
            showEmployeeModal();
        });
    }

    // Fermer modals
    if (btnCancelProduct) {
        btnCancelProduct.addEventListener('click', closeProductModal);
    }
    if (btnCancelEmployee) {
        btnCancelEmployee.addEventListener('click', closeEmployeeModal);
    }

    // Créer un nouveau produit
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btnSave = document.getElementById('btnSaveProduct');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
            }

            const fd = new FormData(productForm);
            
            fetch('api/products.php?action=create', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.error) {
                    alert('Erreur: ' + res.error);
                    return;
                }
                
                // Rafraîchir la liste des produits
                fetch('api/products.php?action=fetch')
                    .then(r => r.json())
                    .then(products => {
                        const select = document.getElementById('selectProduct');
                        if (select) {
                            // Sauvegarder l'option vide
                            const firstOption = select.options[0];
                            select.innerHTML = '';
                            select.appendChild(firstOption);
                            
                            // Ajouter tous les produits
                            products.forEach(p => {
                                const opt = document.createElement('option');
                                opt.value = p.id;
                                opt.textContent = p.product_name || p.name;
                                if (p.id == res.id) {
                                    opt.selected = true;
                                }
                                select.appendChild(opt);
                            });
                        }
                        
                        closeProductModal();
                        alert('✅ Produit créé et sélectionné !');
                    })
                    .catch(() => {
                        closeProductModal();
                        alert('⚠️ Produit créé mais erreur lors du rafraîchissement');
                    });
            })
            .catch(() => {
                alert('Erreur réseau');
            })
            .finally(() => {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save"></i> Créer';
                }
            });
        });
    }

    // Créer un nouvel employé
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btnSave = document.getElementById('btnSaveEmployee');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
            }

            const fd = new FormData(employeeForm);
            
            fetch('api/employees.php?action=create', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.error) {
                    alert('Erreur: ' + res.error);
                    return;
                }
                
                // Rafraîchir la liste des employés
                fetch('api/employees.php?action=fetch')
                    .then(r => r.json())
                    .then(employees => {
                        const select = document.getElementById('selectEmployee');
                        if (select) {
                            // Sauvegarder l'option vide
                            const firstOption = select.options[0];
                            select.innerHTML = '';
                            select.appendChild(firstOption);
                            
                            // Ajouter tous les employés
                            employees.forEach(e => {
                                const opt = document.createElement('option');
                                opt.value = e.id;
                                opt.textContent = (e.last_name || '') + ' ' + (e.first_name || '');
                                if (e.id == res.id) {
                                    opt.selected = true;
                                }
                                select.appendChild(opt);
                            });
                        }
                        
                        closeEmployeeModal();
                        alert('✅ Employé créé et sélectionné !');
                    })
                    .catch(() => {
                        closeEmployeeModal();
                        alert('⚠️ Employé créé mais erreur lors du rafraîchissement');
                    });
            })
            .catch(() => {
                alert('Erreur réseau');
            })
            .finally(() => {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save"></i> Créer';
                }
            });
        });
    }

    fetchSales();
});
