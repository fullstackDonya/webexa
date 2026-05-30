(function(){
  const API_URL = 'api/chat-notifications.php';
  const POLL_MS = 15000; // 15s

  function $(sel){ return document.querySelector(sel); }

  function updateBadge(count){
    var badge = $('#crm-chat-badge');
    if(!badge){
      // Try to create on the fly next to a messages icon if present
      var anchor = document.getElementById('messagesDropdown') || document.getElementById('crm-chat-anchor');
      if (anchor) {
        badge = document.createElement('span');
        badge.id = 'crm-chat-badge';
        badge.className = 'badge badge-danger badge-counter';
        anchor.appendChild(badge);
      }
    }
    if (badge){
      if (count > 0){
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.style.display = '';
      } else {
        badge.textContent = '';
        badge.style.display = 'none';
      }
    }
  }

  async function fetchCounts(){
    try{
      const res = await fetch(API_URL, { credentials: 'same-origin' });
      if(!res.ok) throw new Error('HTTP '+res.status);
      const data = await res.json();
      if (data && data.success && data.counts){
        updateBadge(parseInt(data.counts.pending_conversations || 0));
      }
    }catch(e){
      // silent
      // console.debug('chat poll error', e);
    }
  }

  function start(){
    fetchCounts();
    setInterval(fetchCounts, POLL_MS);
    document.addEventListener('visibilitychange', () => { if(!document.hidden) fetchCounts(); });
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive'){
    start();
  } else {
    document.addEventListener('DOMContentLoaded', start);
  }
})();