(function(){
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  ready(function(){
    document.querySelectorAll('[data-ftc-app]').forEach(initFTC);
  });

  function initFTC(app){
    const settings = window.ftcData && ftcData.settings ? ftcData.settings : {};
    const nonce = window.ftcData ? ftcData.nonce : '';
    const ajaxUrl = window.ftcData ? ftcData.ajaxUrl : '/wp-admin/admin-ajax.php';
    const intro = app.querySelector('[data-ftc-intro]');
    const chat = app.querySelector('[data-ftc-chat]');
    const nameForm = app.querySelector('[data-ftc-name-form]');
    const nameInput = app.querySelector('[data-ftc-name-input]');
    const chatForm = app.querySelector('[data-ftc-chat-form]');
    const chatInput = app.querySelector('[data-ftc-chat-input]');
    const stream = app.querySelector('[data-ftc-stream]');
    const themeBtn = app.querySelector('[data-ftc-theme]');
    const menuBtn = app.querySelector('[data-ftc-menu]');
    const modal = app.querySelector('[data-ftc-modal]');
    const menuContent = app.querySelector('[data-ftc-menu-content]');
    const resetBtn = app.querySelector('[data-ftc-reset]');
    const clearBtn = app.querySelector('[data-ftc-clear]');
    let visitor = localStorage.getItem('ftcVisitorName') || '';
    let menuLoaded = false;

    // Default dark unless user explicitly toggled.
    const storedTheme = localStorage.getItem('ftcTheme');
    setTheme(storedTheme || 'dark');
    if(visitor) beginChat(false);

    nameForm.addEventListener('submit', function(e){
      e.preventDefault();
      const name = nameInput.value.trim();
      if(!name) return;
      visitor = name;
      localStorage.setItem('ftcVisitorName', name);
      beginChat(true);
    });

    chatForm.addEventListener('submit', function(e){
      e.preventDefault();
      submitPrompt(chatInput.value);
    });

    app.addEventListener('click', function(e){
      const prompt = e.target.closest('[data-prompt]');
      if(prompt){
        e.preventDefault();
        closeMenu();
        submitPrompt(prompt.getAttribute('data-prompt'));
      }
    });

    clearBtn.addEventListener('click', function(){
      stream.innerHTML = '';
      chatInput.value = '';
      addAssistantMessage(welcomeHTML(false));
    });

    resetBtn.addEventListener('click', function(){
      localStorage.removeItem('ftcVisitorName');
      visitor = '';
      app.classList.remove('is-chat');
      chat.classList.remove('is-visible');
      intro.classList.add('is-visible');
      stream.innerHTML = '';
      nameInput.value = '';
      setTimeout(()=>nameInput.focus(), 250);
    });

    themeBtn.addEventListener('click', function(){
      setTheme(app.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });

    menuBtn.addEventListener('click', openMenu);
    modal.querySelectorAll('[data-ftc-close]').forEach(btn => btn.addEventListener('click', closeMenu));

    function setTheme(theme){
      app.setAttribute('data-theme', theme);
      localStorage.setItem('ftcTheme', theme);
      themeBtn.textContent = theme === 'dark' ? '☾' : '☀';
    }

    function beginChat(withIntro){
      intro.classList.remove('is-visible');
      app.classList.add('is-chat');
      setTimeout(function(){
        chat.classList.add('is-visible');
        if(!stream.children.length){ addAssistantMessage(welcomeHTML(withIntro)); }
        setTimeout(()=>chatInput.focus(), 250);
      }, 300);
    }

    function welcomeHTML(first){
      const name = escapeHTML(visitor || 'there');
      if(first){
        return '<div class="ftc-answer-heading">Nice to meet you, '+name+'.</div><div class="ftc-answer-body"><p>Field Theory Lab is a web technology and digital marketing company. We help organizations with websites, analytics, AI automation, UX, SEO, and smarter digital systems.</p><p>What would you like to explore?</p></div>' + promptHTML(['Portfolio','Services','About','Contact']);
      }
      return '<div class="ftc-answer-heading">Welcome back, '+name+'.</div><div class="ftc-answer-body"><p>What can Field Theory help you understand today?</p></div>' + promptHTML(['Portfolio','Services','Analytics','AI Automation','Contact']);
    }

    function submitPrompt(raw){
      const term = (raw || '').trim();
      if(!term) return;
      if(!app.classList.contains('is-chat')) beginChat(false);
      addUserMessage(term);
      chatInput.value = '';
      const thinking = addThinking();
      fetch(ajaxUrl, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'ftc_answer', nonce:nonce, term:term}).toString()
      })
      .then(r=>r.json())
      .then(data=>{
        setTimeout(function(){
          thinking.remove();
          if(data && data.success){ addAssistantMessage(data.data.html); }
          else addAssistantMessage(fallbackHTML());
        }, 520);
      })
      .catch(()=>{
        thinking.remove();
        addAssistantMessage(fallbackHTML());
      });
    }

    function addUserMessage(text){
      const el = document.createElement('div');
      el.className = 'ftc-message ftc-user';
      el.innerHTML = '<div class="ftc-bubble">'+escapeHTML(text)+'</div>';
      stream.appendChild(el); scrollToBottom();
    }
    function addAssistantMessage(html){
      const icon = settings.icon_logo || '';
      const el = document.createElement('div');
      el.className = 'ftc-message ftc-assistant';
      el.innerHTML = '<div class="ftc-avatar">'+(icon ? '<img src="'+escapeAttr(icon)+'" alt="FT">' : 'FT')+'</div><div class="ftc-card">'+html+'</div>';
      stream.appendChild(el); scrollToBottom();
    }
    function addThinking(){
      const el = document.createElement('div');
      el.className = 'ftc-message ftc-assistant';
      el.innerHTML = '<div class="ftc-avatar">'+(settings.icon_logo ? '<img src="'+escapeAttr(settings.icon_logo)+'" alt="FT">' : 'FT')+'</div><div class="ftc-card"><div class="ftc-thinking"><span>Field Theory is thinking</span><i></i><i></i><i></i></div></div>';
      stream.appendChild(el); scrollToBottom(); return el;
    }
    function fallbackHTML(){ return '<div class="ftc-answer-heading">Good question.</div><div class="ftc-answer-body"><p>I can help with Field Theory services, portfolio, analytics, AI automation, web technology, marketing, and contact information.</p></div>'+promptHTML(['Portfolio','Services','Contact']); }
    function promptHTML(items){ return '<div class="ftc-followups"><div class="ftc-section-label">Try asking</div><div class="ftc-followup-row">'+items.map(item=>'<button type="button" class="ftc-followup" data-prompt="'+escapeAttr(item)+'">'+escapeHTML(item)+'</button>').join('')+'</div></div>'; }
    function openMenu(){
      modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false');
      if(menuLoaded) return;
      fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_menu',nonce:nonce}).toString()})
      .then(r=>r.json()).then(data=>{ menuLoaded = true; menuContent.innerHTML = data.success ? data.data.html : '<p>Menu unavailable.</p>'; })
      .catch(()=>{ menuContent.innerHTML = '<p>Menu unavailable.</p>'; });
    }
    function closeMenu(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); }
    function scrollToBottom(){ requestAnimationFrame(()=>{ stream.scrollTop = stream.scrollHeight; }); }
    function escapeHTML(str){ return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s])); }
    function escapeAttr(str){ return escapeHTML(str).replace(/`/g,'&#096;'); }
  }
})();
