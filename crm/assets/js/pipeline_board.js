(() => {
  console.log('DEBUG - window.PIPELINE_ENTITY:', window.PIPELINE_ENTITY);
  const entityType = window.PIPELINE_ENTITY || 'opportunity';
  console.log('DEBUG - entityType utilisé:', entityType);
  const boardEl = document.getElementById('pipeline-board');
  const refreshBtn = document.getElementById('pipeline-refresh');

  let draggedCard = null;
  let draggedData = null;

  if (!boardEl) {
    return;
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatAmount(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
      return '';
    }
    const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
    return formatter.format(Number(value));
  }

  function formatDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return escapeHtml(value);
    }
    return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function setLoading(state) {
    if (state) {
      boardEl.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin me-2"></i>Chargement du pipeline...</div>';
    }
  }

  async function loadBoard() {
    setLoading(true);
    try {
      const res = await fetch(`api/pipeline.php?action=board&entity=${encodeURIComponent(entityType)}`, {
        headers: { 'Accept': 'application/json' },
      });
      const json = await res.json();
      if (!json.success) {
        throw new Error(json.error || 'Erreur lors du chargement.');
      }
      renderBoard(json.data);
    } catch (err) {
      boardEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(err.message)}</div>`;
    }
  }

  function renderBoard(data) {
    boardEl.innerHTML = '';
    if (!data || !Array.isArray(data.stages) || !data.stages.length) {
      boardEl.innerHTML = '<div class="alert alert-info">Aucune étape n\'a encore été configurée pour ce pipeline.</div>';
      return;
    }

    data.stages.forEach(stage => {
      const column = document.createElement('div');
      column.className = 'pipeline-column';
      column.dataset.stageId = stage.id;

      const header = document.createElement('div');
      header.className = 'pipeline-column-header';
      const total = stage.total_amount ? ` · ${formatAmount(stage.total_amount)}` : '';
      header.style.background = `linear-gradient(120deg, ${stage.color_code || '#0d6efd'} 0%, rgba(13,110,253,0.35) 100%)`;
      header.innerHTML = `<h5>${escapeHtml(stage.name)}</h5><small>${stage.count} carte(s)${total}</small>`;
      column.appendChild(header);

      const list = document.createElement('div');
      list.className = 'pipeline-items';
      list.dataset.stageId = stage.id;
      list.addEventListener('dragover', onDragOver);
      list.addEventListener('drop', onDrop);

      const items = Array.isArray(stage.items) ? stage.items.slice() : [];
      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'pipeline-empty';
        empty.textContent = 'Déposez des cartes ici';
        list.appendChild(empty);
      } else {
        items.sort((a, b) => (b.position || 0) - (a.position || 0));
        items.forEach(item => list.appendChild(createCard(item)));
      }

      column.appendChild(list);
      boardEl.appendChild(column);
    });
  }

  function createCard(item) {
    const card = document.createElement('div');
    card.className = 'pipeline-card';
    card.draggable = true;
    card.dataset.entityId = item.id;
    card.dataset.stageId = item.stage_id;
    card.dataset.url = item.url || '';
    card.addEventListener('dragstart', onDragStart);
    card.addEventListener('dragend', onDragEnd);
    card.addEventListener('dblclick', () => {
      if (card.dataset.url) {
        window.location.href = card.dataset.url;
      }
    });

    const subtitle = item.subtitle ? `<div class="card-subtitle">${escapeHtml(item.subtitle)}</div>` : '';
    const metaParts = [];
    if (item.amount !== null && item.amount !== undefined) {
      metaParts.push(`<span class="badge-amount">${formatAmount(item.amount)}</span>`);
    }
    if (item.meta) {
      metaParts.push(`<span><i class="far fa-clock me-1"></i>${escapeHtml(formatDate(item.meta))}</span>`);
    }
    if (item.status) {
      metaParts.push(`<span><i class="fas fa-tag me-1"></i>${escapeHtml(item.status)}</span>`);
    }
    if (item.score !== null && item.score !== undefined) {
      metaParts.push(`<span><i class="fas fa-bolt text-warning me-1"></i>${escapeHtml(item.score)}</span>`);
    }

    card.innerHTML = `
      <h6>${escapeHtml(item.title)}</h6>
      ${subtitle}
      <div class="card-meta">${metaParts.join(' ')}</div>
    `;

    return card;
  }

  function onDragStart(event) {
    draggedCard = event.currentTarget;
    draggedData = {
      entityId: Number(draggedCard.dataset.entityId),
      fromStageId: Number(draggedCard.dataset.stageId),
    };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify(draggedData));
    setTimeout(() => draggedCard.classList.add('dragging'), 0);
  }

  function onDragEnd() {
    if (draggedCard) {
      draggedCard.classList.remove('dragging');
    }
    draggedCard = null;
    draggedData = null;
    Array.from(boardEl.querySelectorAll('.pipeline-items.drag-over')).forEach(el => el.classList.remove('drag-over'));
  }

  function onDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    const list = event.currentTarget;
    if (!list.classList.contains('drag-over')) {
      list.classList.add('drag-over');
    }
  }

  async function onDrop(event) {
    event.preventDefault();
    const list = event.currentTarget;
    list.classList.remove('drag-over');
    const targetStageId = Number(list.dataset.stageId);
    if (!draggedData || !draggedData.entityId) {
      return;
    }
    if (targetStageId === draggedData.fromStageId) {
      return;
    }

    try {
      await moveCard(draggedData.entityId, targetStageId);
    } catch (err) {
      console.error(err);
      alert(err.message || 'Impossible de déplacer la carte.');
    }
  }

  async function moveCard(entityId, stageId) {
    const payload = {
      entity: entityType,
      item_id: entityId,
      stage_id: stageId,
    };
    console.log('DEBUG - moveCard appelé avec entityType:', entityType, 'payload:', payload);
    const res = await fetch('api/pipeline.php?action=move', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    console.log('DEBUG - Réponse serveur:', json);
    if (!json.success) {
      throw new Error(json.error || 'Erreur serveur');
    }
    await loadBoard();
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => loadBoard());
  }

  document.addEventListener('DOMContentLoaded', loadBoard);
})();