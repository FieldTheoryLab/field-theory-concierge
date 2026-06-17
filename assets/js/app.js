(function(){
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  ready(function(){ document.querySelectorAll('[data-ftc-app]').forEach(initFTC); });

  function initFTC(app){
    const nonce = window.ftcData ? ftcData.nonce : '';
    const ajaxUrl = window.ftcData ? ftcData.ajaxUrl : '/wp-admin/admin-ajax.php';
    const intro = app.querySelector('[data-ftc-intro]');
    const chat = app.querySelector('[data-ftc-chat]');
    const chatForm = app.querySelector('[data-ftc-chat-form]');
    const chatInput = app.querySelector('[data-ftc-chat-input]');
    const stream = app.querySelector('[data-ftc-stream]');
    const themeBtn = app.querySelector('[data-ftc-theme]');
    const menuBtn = app.querySelector('[data-ftc-menu]');
    const modal = app.querySelector('[data-ftc-modal]');
    const menuContent = app.querySelector('[data-ftc-menu-content]');
    const resetBtn = app.querySelector('[data-ftc-reset]');
    const clearBtn = app.querySelector('[data-ftc-clear]');
    const introHeading = app.querySelector('[data-ftc-intro-heading]');
    const introBody = app.querySelector('[data-ftc-intro-body]');
    let menuLoaded = false;
    let lastPrompt = '';

    try{ localStorage.removeItem('ftcTheme2618'); }catch(e){}
    setTheme('dark', false);
    typeIntroHeading();
    setTimeout(function(){ if(chatInput) chatInput.focus(); }, 250);

    chatForm.addEventListener('submit', function(e){ e.preventDefault(); submitPrompt(chatInput.value); });
    app.addEventListener('click', function(e){
      const project = e.target.closest('[data-ftc-project]');
      if(project){ e.preventDefault(); closeMenu(); openProject(project.getAttribute('data-ftc-project')); return; }
      const prompt = e.target.closest('[data-prompt]');
      if(prompt){ e.preventDefault(); closeMenu(); submitPrompt(prompt.getAttribute('data-prompt')); return; }
      const service = e.target.closest('[data-ftc-service]');
      if(service){ e.preventDefault(); openService(service.getAttribute('data-ftc-service')); return; }
      const pNext = e.target.closest('[data-ftc-portfolio-next]');
      if(pNext){ e.preventDefault(); movePortfolioLatest(pNext, 1); return; }
      const pPrev = e.target.closest('[data-ftc-portfolio-prev]');
      if(pPrev){ e.preventDefault(); movePortfolioLatest(pPrev, -1); return; }
      const next = e.target.closest('[data-ftc-carousel-next]');
      if(next){ e.preventDefault(); moveCarousel(next, 1); return; }
      const prev = e.target.closest('[data-ftc-carousel-prev]');
      if(prev){ e.preventDefault(); moveCarousel(prev, -1); return; }
    });
    clearBtn.addEventListener('click', resetExperience);
    resetBtn.addEventListener('click', resetExperience);
    themeBtn.addEventListener('click', function(){ setTheme(app.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true); });
    menuBtn.addEventListener('click', openMenu);
    modal.querySelectorAll('[data-ftc-close]').forEach(function(btn){ btn.addEventListener('click', closeMenu); });


    function movePortfolioLatest(btn, dir){
      const wrap = btn.closest('.ftc-portfolio-latest-wrap');
      const track = wrap ? wrap.querySelector('.ftc-portfolio-latest') : null;
      if(!track) return;
      const card = track.querySelector('.ftc-work-card');
      const delta = card ? (card.getBoundingClientRect().width + 28) : track.clientWidth * .85;
      track.scrollBy({left: dir * delta, behavior:'smooth'});
    }

    function moveCarousel(btn, dir){
      const wrap = btn.closest('.ftc-service-carousel-wrap');
      const track = wrap ? wrap.querySelector('[data-ftc-carousel-track]') : null;
      if(!track) return;
      const amount = Math.max(280, Math.round(track.clientWidth * 0.82));
      track.scrollBy({left: dir * amount, behavior: 'smooth'});
    }

    function resetExperience(){
      stream.innerHTML = ''; chatInput.value = '';
      app.classList.remove('is-chat'); chat.classList.remove('is-visible'); intro.classList.add('is-visible');
      setTimeout(function(){ chatInput.focus(); }, 160);
    }
    function typeIntroHeading(){
      if(!introHeading) return;
      const headingText = introHeading.textContent.trim();
      const bodyText = introBody ? introBody.textContent.trim() : '';
      introHeading.innerHTML = '<span class="ftc-type-text"></span>';
      if(introBody) introBody.innerHTML = '<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>';
      else introHeading.innerHTML += '<span class="ftc-cursor" aria-hidden="true"></span>';
      const headingTarget = introHeading.querySelector('.ftc-type-text');
      const bodyTarget = introBody ? introBody.querySelector('.ftc-type-text') : null;
      const cursor = introBody ? introBody.querySelector('.ftc-cursor') : introHeading.querySelector('.ftc-cursor');
      function typeText(text, target, done){
        let i = 0;
        function tick(){
          target.textContent += text.charAt(i);
          const ch = text.charAt(i);
          const next = text.charAt(i+1);
          i++;
          if(i < text.length){
            let delay = 14 + Math.floor(Math.random()*32);
            if(ch === ',' || ch === '.' || ch === '—') delay += 55 + Math.floor(Math.random()*70);
            if(next === ' ') delay += 8 + Math.floor(Math.random()*14);
            if(Math.random() > .94) delay += 45;
            setTimeout(tick, delay);
          } else if(done) {
            setTimeout(done, 280);
          }
        }
        setTimeout(tick, 380);
      }
      typeText(headingText, headingTarget, function(){
        if(bodyTarget) typeText(bodyText, bodyTarget, function(){ introHeading.classList.add('is-complete'); if(introBody) introBody.classList.add('is-complete'); });
        else introHeading.classList.add('is-complete');
      });
    }
    function setTheme(theme, remember){
      app.setAttribute('data-theme', theme);
      /* Dark mode is the default every load. Theme changes are session-only. */
      themeBtn.textContent = theme === 'dark' ? '☾' : '☀';
      themeBtn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    }
    function beginChat(){
      intro.classList.remove('is-visible');
      chat.classList.add('is-visible');
      app.classList.add('is-chat');
    }
    function beginResponseTransition(){
      app.classList.add('is-transitioning-response');
      const last = stream.querySelector('.ftc-message:last-child');
      if(last) last.classList.add('is-soft-focus');
      clearTimeout(app._ftcTransitionTimer);
      app._ftcTransitionTimer = setTimeout(function(){
        app.classList.remove('is-transitioning-response');
        stream.querySelectorAll('.is-soft-focus').forEach(function(el){ el.classList.remove('is-soft-focus'); });
      }, 760);
    }
    function endResponseTransition(){
      clearTimeout(app._ftcTransitionTimer);
      app._ftcTransitionTimer = setTimeout(function(){
        app.classList.remove('is-transitioning-response');
        stream.querySelectorAll('.is-soft-focus').forEach(function(el){ el.classList.remove('is-soft-focus'); });
      }, 260);
    }
    function submitPrompt(raw){
      const term = (raw || '').trim(); if(!term) return;
      beginChat(); beginResponseTransition(); addUserMessage(term); chatInput.value = '';
      if(isJokePrompt(term)){ setTimeout(function(){ addAssistantMessage(jokeHTML()); }, 140); return; }
      const thinking = addThinking();
      fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_answer',nonce:nonce,term:term}).toString()})
        .then(function(r){return r.json();}).then(function(data){ setTimeout(function(){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); },220); })
        .catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); });
    }
    function openProject(id){ beginChat(); beginResponseTransition(); const thinking = addThinking(); fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_portfolio_detail',nonce:nonce,post_id:id}).toString()}).then(function(r){return r.json();}).then(function(data){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); }).catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); }); }
    function openService(id){ beginChat(); beginResponseTransition(); const thinking = addThinking(); fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_service_detail',nonce:nonce,service_id:id}).toString()}).then(function(r){return r.json();}).then(function(data){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); }).catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); }); }
    function jokeHTML(){
      const jokes = [
        'Why did the website go to therapy? Too many unresolved issues.',
        'I told my analytics dashboard a joke. It said the punchline had excellent engagement.',
        'Why did the marketer break up with the spreadsheet? It had too many rows and not enough feelings.',
        'A UX designer walks into a bar. Then quietly moves the door somewhere more intuitive.',
        'Why did the AI assistant get promoted? It had prompt attendance.',
        'I asked SEO for a joke. It said, “You will find the answer in position one.”',
        'Why was the landing page so confident? It had a clear call to action.',
        'A developer, designer, and strategist walk into a meeting. Somehow the button still needs to be bigger.',
        'Why did the data scientist bring a ladder? To reach higher confidence intervals.',
        'Field Theory joke: we do not chase trends. We A/B test them until they confess.'
      ];
      const joke = jokes[Math.floor(Math.random()*jokes.length)];
      return '<div class="ftc-response-shell ftc-response-layout-none"><header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="Okay, I have one.">Okay, I have one.</h2><div class="ftc-answer-description">'+escapeHTML(joke)+'</div></header><footer class="ftc-followups"><div class="ftc-followup-row"><button type="button" class="ftc-followup" data-prompt="Tell me another joke">Tell me another joke</button><button type="button" class="ftc-followup" data-prompt="Get Started">Back to business</button></div></footer></div>';
    }
    function isJokePrompt(term){ return /\b(joke|funny|make me laugh|tell me another joke)\b/i.test(term); }

    function addUserMessage(text){ lastPrompt = text || ''; }
    function addAssistantMessage(html){
      const el=document.createElement('div');
      el.className='ftc-message ftc-assistant';
      el.innerHTML='<div class="ftc-warp-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div><div class="ftc-card">'+html+'</div>';
      const header = el.querySelector('.ftc-response-header');
      if(header && lastPrompt){
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ftc-question-chip';
        chip.setAttribute('data-prompt', lastPrompt);
        chip.textContent = lastPrompt;
        header.appendChild(chip);
      }
      stream.appendChild(el);
      requestAnimationFrame(function(){ el.classList.add('is-arriving'); });
      el.querySelectorAll('.ftc-typewriter').forEach(typewriterElement);
      scrollTo(el, 18);
      setTimeout(function(){ el.classList.add('has-arrived'); endResponseTransition(); }, 820);
    }
    function typewriterElement(el){ if(el.dataset.initialized) return; el.dataset.initialized='true'; const text=el.dataset.text || el.textContent.trim(); el.innerHTML='<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>'; const target=el.querySelector('.ftc-type-text'); let i=0; function tick(){ target.textContent+=text.charAt(i); const ch=text.charAt(i); i++; if(i<text.length){ let delay=24+Math.floor(Math.random()*45); if(ch===','||ch==='.'||ch==='—') delay+=120; setTimeout(tick,delay); } else { el.classList.add('is-complete'); } } setTimeout(tick,80); }
    function addThinking(){ const el=document.createElement('div'); el.className='ftc-message ftc-assistant ftc-thinking-message'; el.innerHTML='<div class="ftc-card"><div class="ftc-thinking"><span>Field Theory is thinking</span><i></i><i></i><i></i></div></div>'; stream.appendChild(el); scrollTo(el,16); return el; }
    function scrollTo(el, offset){
      requestAnimationFrame(function(){
        const top = Math.max(0, el.offsetTop - (offset||0) - (window.innerWidth < 760 ? 52 : 88));
        if('scrollTo' in stream) stream.scrollTo({top: top, behavior:'smooth'});
        else stream.scrollTop = top;
      });
    }
    function fallbackHTML(){ return '<div class="ftc-response-shell"><header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="Good question.">Good question.</h2><div class="ftc-answer-description">Try asking about our services, portfolio, FAQ, privacy, analytics, AI, SEO, or hiring our team.</div></header></div>'; }
    function openMenu(){ modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); if(menuLoaded) return; fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_menu',nonce:nonce}).toString()}).then(function(r){return r.json();}).then(function(data){ menuLoaded=true; menuContent.innerHTML=data.success?data.data.html:'<p>Menu unavailable.</p>'; }).catch(function(){ menuContent.innerHTML='<p>Menu unavailable.</p>'; }); }
    function closeMenu(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); }
    function escapeHTML(str){ return String(str).replace(/[&<>"']/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]; }); }
  }
})();
