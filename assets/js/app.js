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
    const menuBtn = app.querySelector('[data-ftc-menu]');
    const modal = app.querySelector('[data-ftc-modal]');
    const helpBtn = app.querySelector('[data-ftc-help-menu]');
    const helpModal = app.querySelector('[data-ftc-help-modal]');
    const menuContent = app.querySelector('[data-ftc-menu-content]');
    const resetBtn = app.querySelector('[data-ftc-reset]');
    const clearBtn = app.querySelector('[data-ftc-clear]');
    const introHeading = app.querySelector('[data-ftc-intro-heading]');
    const introBody = app.querySelector('[data-ftc-intro-body]');
    let menuLoaded = false;
    let menuLoading = false;
    let lastPrompt = '';
    let pendingFragments = [];
    let pendingAppendFrame = 0;
    let pendingRemoteLoading = false;
    let pendingGeneration = 0;
    let lastUserScrollAt = 0;
    const messageMap = createMessageMap();
    let messageMapFrame = 0;
    const typeObserver = ('IntersectionObserver' in window) ? new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          typeObserver.unobserve(entry.target);
          runLazyTypewriterElement(entry.target);
        }
      });
    }, {root: stream, rootMargin: '0px 0px -10% 0px', threshold: 0.18}) : null;
    const messageRevealObserver = ('IntersectionObserver' in window) ? new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          messageRevealObserver.unobserve(entry.target);
          revealAssistantMessage(entry.target);
        }
      });
    }, {root: stream, rootMargin: '0px 0px -14% 0px', threshold: 0.16}) : null;

    try{ localStorage.removeItem('ftcTheme2618'); }catch(e){}
    app.setAttribute('data-theme', 'dark');
    typeIntroHeading();
    setTimeout(function(){ if(chatInput) chatInput.focus(); }, 250);
    setTimeout(loadMenuContent, 700);

    chatForm.addEventListener('submit', function(e){ e.preventDefault(); submitPrompt(chatInput.value); });
    app.addEventListener('click', function(e){
      const resetPrompt = e.target.closest('[data-ftc-reset-to-prompt]');
      if(resetPrompt){ e.preventDefault(); closeAllMenus(); resetToPrompt(resetPrompt.getAttribute('data-ftc-reset-to-prompt') || resetPrompt.getAttribute('data-prompt')); return; }
      const scrollMore = e.target.closest('[data-ftc-scroll-more]');
      if(scrollMore){ e.preventDefault(); scrollToNextResponse(scrollMore); return; }
      const project = e.target.closest('[data-ftc-project]');
      if(project){ e.preventDefault(); closeAllMenus(); openProject(project.getAttribute('data-ftc-project')); return; }
      const service = e.target.closest('[data-ftc-service]');
      if(service){ e.preventDefault(); closeAllMenus(); openService(service.getAttribute('data-ftc-service'), service.getAttribute('data-ftc-service-label')); return; }
      const prompt = e.target.closest('[data-prompt]');
      if(prompt){ e.preventDefault(); closeAllMenus(); submitPrompt(prompt.getAttribute('data-prompt')); return; }
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
    if(menuBtn) menuBtn.addEventListener('click', openMenu);
    if(helpBtn) helpBtn.addEventListener('click', openHelpMenu);
    if(modal) modal.querySelectorAll('[data-ftc-close]').forEach(function(btn){ btn.addEventListener('click', closeMenu); });
    if(helpModal) helpModal.querySelectorAll('[data-ftc-help-close]').forEach(function(btn){ btn.addEventListener('click', closeHelpMenu); });
    stream.addEventListener('scroll', function(){
      scheduleMessageMapUpdate();
      if(Date.now() - lastUserScrollAt < 900) maybeAppendQueuedFragment(false);
    }, {passive:true});
    stream.addEventListener('wheel', function(e){
      if(e.deltaY > 0){
        lastUserScrollAt = Date.now();
        maybeAppendQueuedFragment(false);
      }
    }, {passive:true});
    stream.addEventListener('touchstart', function(){ lastUserScrollAt = Date.now(); }, {passive:true});
    stream.addEventListener('touchmove', function(){
      lastUserScrollAt = Date.now();
      maybeAppendQueuedFragment(false);
    }, {passive:true});
    window.addEventListener('resize', scheduleMessageMapUpdate);


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

    function scrollToNextResponse(btn){
      const message = btn.closest('.ftc-message');
      const next = message ? message.nextElementSibling : null;
      if(next && next.classList && next.classList.contains('ftc-message')){
        revealAssistantMessage(next);
        scrollTo(next, 18);
        return;
      }
      if(appendNextQueuedFragment()) return;
      if('scrollBy' in stream) stream.scrollBy({top: Math.round(stream.clientHeight * 0.78), behavior:'smooth'});
      else stream.scrollTop += Math.round(stream.clientHeight * 0.78);
    }

    function resetExperience(){
      stream.innerHTML = ''; chatInput.value = '';
      clearPendingFragments();
      messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){ dot.remove(); });
      scheduleMessageMapUpdate();
      app.classList.remove('is-chat'); chat.classList.remove('is-visible'); intro.classList.add('is-visible');
      setTimeout(function(){ chatInput.focus(); }, 160);
    }
    function resetToPrompt(prompt){
      const term = (prompt || '').trim();
      if(!term) return;
      stream.innerHTML = '';
      chatInput.value = '';
      lastPrompt = '';
      clearPendingFragments();
      messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){ dot.remove(); });
      scheduleMessageMapUpdate();
      beginChat();
      submitPrompt(term);
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
    function beginChat(){
      intro.classList.remove('is-visible');
      chat.classList.add('is-visible');
      app.classList.add('is-chat');
    }
    function beginResponseTransition(){
      clearPendingFragments();
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
      beginChat();
      beginResponseTransition(); addUserMessage(term); chatInput.value = '';
      if(isJokePrompt(term)){ setTimeout(function(){ addAssistantMessage(jokeHTML()); }, 140); return; }
      const thinking = addThinking();
      fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_answer',nonce:nonce,term:term}).toString()})
        .then(function(r){return r.json();}).then(function(data){ setTimeout(function(){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); },220); })
        .catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); });
    }
    function openProject(id){ beginChat(); beginResponseTransition(); const thinking = addThinking(); fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_portfolio_detail',nonce:nonce,post_id:id}).toString()}).then(function(r){return r.json();}).then(function(data){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); }).catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); }); }
    function openService(id, label){ beginChat(); beginResponseTransition(); lastPrompt = label || ('Service ' + id); const thinking = addThinking(); fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_service_detail',nonce:nonce,service_id:id}).toString()}).then(function(r){return r.json();}).then(function(data){ thinking.remove(); addAssistantMessage(data && data.success ? data.data.html : fallbackHTML()); }).catch(function(){ thinking.remove(); addAssistantMessage(fallbackHTML()); }); }
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
    function getResponseFragments(html){
      const wrap = document.createElement('div');
      wrap.innerHTML = (html || '').trim();
      const children = Array.prototype.slice.call(wrap.children);
      if(children.length < 2) return [];
      return children.every(function(child){ return child.classList && child.classList.contains('ftc-response-shell'); }) ? children : [];
    }
    function getSingleResponsePrompt(html){
      const wrap = document.createElement('div');
      wrap.innerHTML = (html || '').trim();
      const first = wrap.firstElementChild;
      if(!first || !first.classList || !first.classList.contains('ftc-response-shell')) return '';
      return first.getAttribute('data-ftc-response-prompt') || first.getAttribute('data-response-title') || '';
    }
    function normalizePromptLabel(label){
      return (label || '').toLowerCase().replace(/&/g,' and ').replace(/[^a-z0-9]+/g,' ').trim();
    }
    function promptKey(label){
      const t = normalizePromptLabel(label);
      if(!t) return '';
      if(/(show me )?all portfolio|all project/.test(t)) return 'portfolio-all';
      if(/(show me )?all service/.test(t)) return 'services-all';
      if(/hire|contact|proposal|consultation|work together|call|inquiry|get started with a project/.test(t)) return 'contact';
      if(/show me your work|portfolio|project|case stud|examples/.test(t)) return 'portfolio';
      if(/^(our )?services$|^services$|^help my company$|^help my business$|^how can you help my company$|^what do you do$/.test(t)) return 'services';
      if(/service|current site|better website|ux web|web development|seo|aeo|schema|local seo|marketing|campaign|google ads|paid media|analytics|dashboard|ga4|looker|ai|automation|assistant|prototype|ecommerce|checkout|cro|shopify|woocommerce|hosting|maintenance|accessibility|ada/.test(t)) return 'service-' + t;
      if(/testimonial|review|client/.test(t)) return 'testimonials';
      if(/faq|frequently asked|question|how long|how much|budget|timeline|support|maintenance/.test(t)) return 'faq';
      if(/about|company|team|people|who are you|field theory/.test(t)) return 'about';
      if(/get started|start|home|overview/.test(t)) return 'get-started';
      return t;
    }
    function findPromptMessages(label){
      const key = promptKey(label);
      if(!key) return [];
      return Array.prototype.slice.call(stream.querySelectorAll('.ftc-message.ftc-assistant')).filter(function(message){
        return !message.classList.contains('ftc-thinking-message') && message.dataset.ftcPromptKey === key;
      });
    }
    function removeAssistantMessage(message){
      if(!message) return;
      if(messageRevealObserver) messageRevealObserver.unobserve(message);
      if(typeObserver) message.querySelectorAll('.ftc-typewriter').forEach(function(el){ typeObserver.unobserve(el); });
      messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){
        if(dot._ftcTarget === message) dot.remove();
      });
      message.remove();
      scheduleMessageMapUpdate();
    }
    function removeExistingPromptInstances(label){
      const matches = findPromptMessages(label);
      matches.forEach(removeAssistantMessage);
      removeQueuedPromptInstances(label);
      return matches.length;
    }
    function removeQueuedPromptInstances(label){
      const key = promptKey(label);
      if(!key || !pendingFragments.length) return;
      pendingFragments = pendingFragments.filter(function(item){ return promptKey(item.prompt) !== key; });
    }
    function clearPendingFragments(){
      pendingFragments = [];
      pendingAppendFrame = 0;
      pendingRemoteLoading = false;
      pendingGeneration++;
    }
    function isNearStreamBottom(){
      const threshold = Math.max(90, stream.clientHeight * 0.16);
      return (stream.scrollTop + stream.clientHeight) >= (stream.scrollHeight - threshold);
    }
    function appendNextQueuedFragment(){
      if(!pendingFragments.length || pendingRemoteLoading) return false;
      const next = pendingFragments.shift();
      if(next && next.remote){
        pendingRemoteLoading = true;
        const generation = pendingGeneration;
        const thinking = addThinking();
        fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_sequence_fragment',nonce:nonce,sequence:next.sequence,index:next.index}).toString()})
          .then(function(r){return r.json();})
          .then(function(data){
            thinking.remove();
            if(generation !== pendingGeneration) return;
            pendingRemoteLoading = false;
            addAssistantMessage(data && data.success ? data.data.html : fallbackHTML());
          })
          .catch(function(){
            thinking.remove();
            if(generation !== pendingGeneration) return;
            pendingRemoteLoading = false;
            addAssistantMessage(fallbackHTML());
          });
        return true;
      }
      appendAssistantMessage(next.html, next.prompt, true, 0);
      return true;
    }
    function maybeAppendQueuedFragment(force){
      if(!pendingFragments.length || pendingRemoteLoading) return false;
      if(!force && !isNearStreamBottom()) return false;
      if(pendingAppendFrame) return true;
      const shouldForce = !!force;
      pendingAppendFrame = requestAnimationFrame(function(){
        pendingAppendFrame = 0;
        if(shouldForce || isNearStreamBottom()) appendNextQueuedFragment();
      });
      return true;
    }
    function enqueueDeferredFragments(message){
      const marker = message ? message.querySelector('[data-ftc-deferred-sequence]') : null;
      if(!marker) return;
      const sequence = marker.getAttribute('data-ftc-deferred-sequence') || '';
      const nextIndex = parseInt(marker.getAttribute('data-ftc-deferred-next') || '1', 10);
      const total = parseInt(marker.getAttribute('data-ftc-deferred-total') || '0', 10);
      const prompts = (marker.getAttribute('data-ftc-deferred-prompts') || '').split('|');
      const queued = [];
      for(let index = nextIndex; index < total; index++){
        queued.push({
          remote: true,
          sequence: sequence,
          index: index,
          prompt: prompts[index - nextIndex] || 'Continue'
        });
      }
      marker.remove();
      pendingFragments = queued;
    }
    function addAssistantMessage(html){
      const fragments = getResponseFragments(html);
      if(fragments.length > 1){
        const queued = [];
        fragments.forEach(function(fragment, index){
          const prompt = fragment.getAttribute('data-ftc-response-prompt') || fragment.getAttribute('data-response-title') || (index === 0 ? (lastPrompt || 'Response') : 'Continue');
          removeExistingPromptInstances(prompt);
          if(index === 0) appendAssistantMessage(fragment.outerHTML, prompt, true, 0);
          else queued.push({html: fragment.outerHTML, prompt: prompt});
        });
        pendingFragments = queued;
        return;
      }
      const prompt = getSingleResponsePrompt(html) || lastPrompt || 'Response';
      removeExistingPromptInstances(prompt);
      appendAssistantMessage(html, prompt, true, 0);
    }
    function appendAssistantMessage(html, promptLabel, shouldScroll, sequenceIndex){
      const el=document.createElement('div');
      el.className='ftc-message ftc-assistant' + (sequenceIndex > 0 ? ' ftc-staged-response' : '');
      el.dataset.ftcPromptKey = promptKey(promptLabel);
      el.innerHTML='<div class="ftc-warp-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div><div class="ftc-card">'+html+'</div>';
      const shell = el.querySelector('.ftc-response-shell');
      if(shell){
        ['service-detail','child-service','about','project'].forEach(function(layout){
          if(shell.classList.contains('ftc-response-layout-'+layout)) el.classList.add('has-layout-'+layout);
        });
      }
      const header = el.querySelector('.ftc-response-header');
      if(header && promptLabel){
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ftc-question-chip';
        chip.setAttribute('data-prompt', promptLabel);
        chip.textContent = promptLabel;
        header.appendChild(chip);
      }
      stream.appendChild(el);
      enqueueDeferredFragments(el);
      addMessageMapPoint(el, promptLabel || 'Response');
      el.querySelectorAll('.ftc-typewriter').forEach(lazyTypewriterElement);
      el.querySelectorAll('[data-ftc-contact-quiz]').forEach(initContactQuiz);
      if(sequenceIndex > 0 && messageRevealObserver){
        el.classList.add('is-waiting-scroll');
        messageRevealObserver.observe(el);
      } else {
        revealAssistantMessage(el);
      }
      if(shouldScroll) scrollTo(el, 18);
    }
    function revealAssistantMessage(el){
      if(!el || el.dataset.revealed) return;
      el.dataset.revealed='true';
      el.classList.remove('is-waiting-scroll');
      requestAnimationFrame(function(){ el.classList.add('is-arriving'); });
      setTimeout(function(){ el.classList.add('has-arrived'); endResponseTransition(); }, 820);
    }
    function typewriterElement(el){ if(el.dataset.initialized) return; el.dataset.initialized='true'; const text=el.dataset.text || el.textContent.trim(); el.innerHTML='<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>'; const target=el.querySelector('.ftc-type-text'); let i=0; function tick(){ target.textContent+=text.charAt(i); const ch=text.charAt(i); i++; if(i<text.length){ let delay=24+Math.floor(Math.random()*45); if(ch===','||ch==='.'||ch==='—') delay+=120; setTimeout(tick,delay); } else { el.classList.add('is-complete'); } } setTimeout(tick,80); }
    function lazyTypewriterElement(el){
      if(el.dataset.initialized) return;
      el.dataset.initialized='true';
      el.dataset.pendingText = el.dataset.text || el.textContent.trim();
      el.innerHTML='<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>';
      if(typeObserver) typeObserver.observe(el);
      else runLazyTypewriterElement(el);
    }
    function runLazyTypewriterElement(el){
      if(!el || el.dataset.typed) return;
      el.dataset.typed='true';
      const text = el.dataset.pendingText || el.dataset.text || '';
      const target=el.querySelector('.ftc-type-text');
      if(!target) return;
      let i=0;
      function tick(){
        target.textContent+=text.charAt(i);
        const ch=text.charAt(i);
        i++;
        if(i<text.length){
          let delay=24+Math.floor(Math.random()*45);
          if(ch===','||ch==='.'||ch==='â€”') delay+=120;
          setTimeout(tick,delay);
        } else {
          el.classList.add('is-complete');
        }
      }
      setTimeout(tick,80);
    }
    function addThinking(){ const el=document.createElement('div'); el.className='ftc-message ftc-assistant ftc-thinking-message'; el.innerHTML='<div class="ftc-card"><div class="ftc-thinking"><span>Field Theory is thinking</span><i></i><i></i><i></i></div></div>'; stream.appendChild(el); scrollTo(el,16); return el; }
    function scrollTo(el, offset){
      requestAnimationFrame(function(){
        const top = Math.max(0, el.offsetTop - (offset||0) - (window.innerWidth < 760 ? 52 : 88));
        if('scrollTo' in stream) stream.scrollTo({top: top, behavior:'smooth'});
        else stream.scrollTop = top;
      });
    }
    function createMessageMap(){
      const nav = document.createElement('nav');
      nav.className = 'ftc-message-map';
      nav.setAttribute('aria-label', 'Conversation sections');
      const thumb = document.createElement('span');
      thumb.className = 'ftc-message-map-thumb';
      thumb.setAttribute('aria-hidden', 'true');
      nav.appendChild(thumb);
      app.appendChild(nav);
      return nav;
    }
    function addMessageMapPoint(target, label){
      if(!messageMap || !target) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ftc-message-map-dot';
      btn.setAttribute('aria-label', 'Return to ' + (label || 'response'));
      btn.title = label || 'Response';
      btn._ftcTarget = target;
      btn.addEventListener('click', function(){ scrollTo(target, 12); });
      messageMap.appendChild(btn);
      scheduleMessageMapUpdate();
    }
    function scheduleMessageMapUpdate(){
      if(messageMapFrame) return;
      messageMapFrame = requestAnimationFrame(function(){
        messageMapFrame = 0;
        updateMessageMap();
      });
    }
    function updateMessageMap(){
      const dots = Array.prototype.slice.call(messageMap.querySelectorAll('.ftc-message-map-dot'));
      const maxScroll = Math.max(1, stream.scrollHeight - stream.clientHeight);
      const trackHeight = Math.max(1, messageMap.clientHeight || stream.clientHeight);
      const thumb = messageMap.querySelector('.ftc-message-map-thumb');
      if(thumb){
        const visibleRatio = stream.scrollHeight > 0 ? Math.min(1, stream.clientHeight / stream.scrollHeight) : 1;
        const thumbHeight = Math.max(34, Math.min(trackHeight, trackHeight * visibleRatio));
        const thumbTop = maxScroll > 1 ? (stream.scrollTop / maxScroll) * Math.max(0, trackHeight - thumbHeight) : (trackHeight - thumbHeight) / 2;
        thumb.style.height = thumbHeight + 'px';
        thumb.style.top = thumbTop + 'px';
      }
      if(!dots.length) return;
      const current = stream.scrollTop + Math.min(180, stream.clientHeight * 0.28);
      let activeIndex = 0;
      dots.forEach(function(dot, i){
        const target = dot._ftcTarget;
        if(!target || !target.parentNode) return;
        const anchorTop = Math.min(maxScroll, Math.max(0, target.offsetTop));
        const pct = maxScroll <= 1
          ? (dots.length === 1 ? 50 : 12 + (i * (76 / Math.max(1, dots.length - 1))))
          : Math.max(7, Math.min(93, (anchorTop / maxScroll) * 100));
        dot.style.top = pct + '%';
        if(target.offsetTop <= current) activeIndex = i;
      });
      dots.forEach(function(dot, i){ dot.classList.toggle('is-active', i === activeIndex); });
    }
    function initContactQuiz(quiz){
      if(!quiz || quiz.dataset.initialized) return;
      quiz.dataset.initialized='true';
      const steps = Array.prototype.slice.call(quiz.querySelectorAll('[data-ftc-quiz-step]'));
      const progressText = quiz.querySelector('[data-ftc-quiz-progress-text]');
      const progressBar = quiz.querySelector('[data-ftc-quiz-progress-bar]');
      const error = quiz.querySelector('[data-ftc-quiz-error]');
      const submissionSummary = quiz.querySelector('[data-ftc-submission-summary]');
      const status = quiz.querySelector('[data-ftc-submit-status]');
      const submit = quiz.querySelector('[data-ftc-submit-inquiry]');
      const totalQuestions = 8;
      let current = 0;
      const answers = {
        services: [],
        company: '',
        website: '',
        orgType: '',
        challenge: '',
        timeline: '',
        budget: '',
        notes: '',
        name: '',
        email: '',
        phone: '',
        contactMethod: 'Email'
      };

      showStep(0);

      quiz.addEventListener('click', function(e){
        const choice = e.target.closest('[data-ftc-choice]');
        if(choice && quiz.contains(choice)){
          e.preventDefault();
          const step = choice.closest('[data-ftc-quiz-step]');
          const field = step ? step.getAttribute('data-field') : '';
          const value = choice.getAttribute('data-value') || choice.textContent.trim();
          if(step && step.hasAttribute('data-multi')){
            choice.classList.toggle('is-selected');
            choice.setAttribute('aria-pressed', choice.classList.contains('is-selected') ? 'true' : 'false');
            answers[field] = Array.prototype.slice.call(step.querySelectorAll('[data-ftc-choice].is-selected')).map(function(btn){ return btn.getAttribute('data-value') || btn.textContent.trim(); });
          } else {
            if(step) step.querySelectorAll('[data-ftc-choice]').forEach(function(btn){ btn.classList.remove('is-selected'); btn.setAttribute('aria-pressed','false'); });
            choice.classList.add('is-selected');
            choice.setAttribute('aria-pressed','true');
            answers[field] = value;
            setTimeout(function(){ goNext(step); }, 180);
          }
          return;
        }

        const next = e.target.closest('[data-ftc-next]');
        if(next && quiz.contains(next)){ e.preventDefault(); goNext(next.closest('[data-ftc-quiz-step]')); return; }

        const back = e.target.closest('[data-ftc-back]');
        if(back && quiz.contains(back)){ e.preventDefault(); showStep(Math.max(0, current - 1)); return; }

        if(submit && (e.target === submit || submit.contains(e.target))){ e.preventDefault(); submitInquiry(); return; }
      });

      quiz.addEventListener('input', function(e){
        const field = e.target.getAttribute('data-ftc-input');
        if(field) answers[field] = e.target.value.trim();
      });

      function goNext(step){
        if(!step) return;
        if(!captureStep(step)) return;
        const nextIndex = Math.min(steps.length - 1, current + 1);
        if(nextIndex === steps.length - 1) renderCompletion();
        showStep(nextIndex);
      }

      function captureStep(step){
        clearError();
        const field = step.getAttribute('data-field');
        if(field === 'services'){
          answers.services = Array.prototype.slice.call(step.querySelectorAll('[data-ftc-choice].is-selected')).map(function(btn){ return btn.getAttribute('data-value') || btn.textContent.trim(); });
          if(!answers.services.length) return fail('Choose at least one area, even if it is Not Sure Yet.');
        }
        step.querySelectorAll('[data-ftc-input]').forEach(function(input){ answers[input.getAttribute('data-ftc-input')] = input.value.trim(); });
        const required = Array.prototype.slice.call(step.querySelectorAll('[required]'));
        for(let i=0;i<required.length;i++){
          if(!required[i].value.trim()){
            required[i].focus();
            return fail('Please complete the required field.');
          }
          if(required[i].type === 'email' && required[i].validity && !required[i].validity.valid){
            required[i].focus();
            return fail('Please enter a valid email address.');
          }
        }
        if(field && !step.hasAttribute('data-multi') && step.querySelector('[data-ftc-choice]') && !answers[field]){
          return fail('Choose one option to continue.');
        }
        return true;
      }

      function showStep(index){
        current = index;
        steps.forEach(function(step, i){
          step.classList.toggle('is-active', i === index);
          step.classList.toggle('is-complete', i < index);
          step.hidden = i > index;
        });
        const visibleStep = Math.min(index + 1, totalQuestions);
        if(progressText) progressText.textContent = index >= totalQuestions ? 'Ready to send' : 'Step ' + visibleStep + ' of ' + totalQuestions;
        if(progressBar) progressBar.style.width = Math.round((Math.min(index, totalQuestions - 1) + 1) / totalQuestions * 100) + '%';
        const active = steps[index];
        if(active) setTimeout(function(){
          const focusTarget = active.querySelector('[data-ftc-choice], input, textarea, button');
          if(focusTarget) focusTarget.focus({preventScroll:true});
        }, 140);
      }

      function renderCompletion(){
        if(submissionSummary) submissionSummary.innerHTML = buildSubmissionSummary();
      }

      function buildSubmissionSummary(){
        const contactParts = [answers.name, answers.email, answers.phone].filter(Boolean);
        const rows = [
          ['Services', answers.services.length ? answers.services.join(', ') : 'Not Sure Yet'],
          ['Company', answers.company],
          ['Website', answers.website || 'Not provided'],
          ['Organization Type', answers.orgType],
          ['Challenge', answers.challenge],
          ['Timeline', answers.timeline],
          ['Budget', answers.budget],
          ['Notes', answers.notes || 'Not provided'],
          ['Contact', contactParts.length ? contactParts.join(' / ') : 'Not provided'],
          ['Preferred Contact', answers.contactMethod]
        ];
        return '<dl>' + rows.map(function(row){
          return '<div><dt>'+escapeHTML(row[0])+'</dt><dd>'+escapeHTML(row[1] || 'Not provided')+'</dd></div>';
        }).join('') + '</dl>';
      }

      function calculateLeadScore(){
        let score = 0;
        answers.services.forEach(function(service){
          if(/Website|SEO|Analytics|AI/.test(service)) score += 20;
          else if(/Ecommerce|Digital Marketing|Strategy/.test(service)) score += 15;
          else if(/Hosting|ADA|Creative/.test(service)) score += 10;
        });
        if(answers.timeline === 'Immediately') score += 25;
        else if(answers.timeline === 'Within 30 Days') score += 20;
        else if(answers.timeline === 'Within 90 Days') score += 15;
        else if(answers.timeline === 'Within 6 Months') score += 10;
        if(answers.budget === '$50,000+') score += 25;
        else if(answers.budget === '$15,000-$50,000') score += 20;
        else if(answers.budget === '$5,000-$15,000') score += 10;
        score = Math.min(100, score);
        return {score: score, priority: score > 70 ? 'High' : (score > 40 ? 'Medium' : 'Low')};
      }

      function submitInquiry(){
        if(submit.disabled) return;
        const score = calculateLeadScore();
        submit.disabled = true;
        if(status) status.textContent = 'Sending your proposal request...';
        getRecaptchaToken()
          .then(function(token){
            const body = new URLSearchParams({
              action:'ftc_submit_inquiry',
              nonce:nonce,
              services:JSON.stringify(answers.services),
              company:answers.company,
              website:answers.website,
              org_type:answers.orgType,
              challenge:answers.challenge,
              timeline:answers.timeline,
              budget:answers.budget,
              notes:answers.notes,
              name:answers.name,
              email:answers.email,
              phone:answers.phone,
              contact_method:answers.contactMethod,
              lead_score:String(score.score),
              recaptcha_token:token
            }).toString();
            return fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body});
          })
          .then(function(r){return r.json();})
          .then(function(data){
            if(data && data.success){
              if(status) status.textContent = 'Thanks. Your proposal request has been sent to Field Theory.';
              submit.textContent = 'Proposal Sent';
              quiz.classList.add('is-submitted');
            } else {
              submit.disabled = false;
              if(status) status.textContent = (data && data.data && data.data.message) ? data.data.message : 'Something did not send. Please email jamie@fieldtheory.ai.';
            }
          })
          .catch(function(){
            submit.disabled = false;
            if(status) status.textContent = 'Captcha could not load. Please refresh and try again, or email jamie@fieldtheory.ai.';
          });
      }

      function getRecaptchaToken(){
        const key = window.ftcData ? (ftcData.recaptchaSiteKey || '') : '';
        if(!key) return Promise.resolve('');
        if(!window.grecaptcha || !window.grecaptcha.ready || !window.grecaptcha.execute) return Promise.reject(new Error('recaptcha unavailable'));
        return new Promise(function(resolve, reject){
          window.grecaptcha.ready(function(){
            window.grecaptcha.execute(key, {action: (ftcData.recaptchaAction || 'ftc_submit_inquiry')}).then(resolve).catch(reject);
          });
        });
      }

      function fail(message){
        if(error) error.textContent = message;
        return false;
      }

      function clearError(){
        if(error) error.textContent = '';
      }
    }

    function fallbackHTML(){ return '<div class="ftc-response-shell"><header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="Good question.">Good question.</h2><div class="ftc-answer-description">Try asking about our services, portfolio, analytics, AI, SEO, or requesting a proposal.</div></header></div>'; }
    function loadMenuContent(){
      if(menuLoaded || menuLoading) return;
      menuLoading = true;
      fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_menu',nonce:nonce}).toString()})
        .then(function(r){return r.json();})
        .then(function(data){ menuLoaded=true; menuContent.innerHTML=data.success?data.data.html:'<p>Menu unavailable.</p>'; })
        .catch(function(){ menuContent.innerHTML='<p>Menu unavailable.</p>'; })
        .then(function(){ menuLoading = false; });
    }
    function openMenu(){
      if(!modal) return;
      closeHelpMenu();
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden','false');
      loadMenuContent();
    }
    function closeMenu(){
      if(!modal) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden','true');
    }
    function openHelpMenu(){
      if(!helpModal) return;
      closeMenu();
      helpModal.classList.add('is-open');
      helpModal.setAttribute('aria-hidden','false');
    }
    function closeHelpMenu(){
      if(!helpModal) return;
      helpModal.classList.remove('is-open');
      helpModal.setAttribute('aria-hidden','true');
    }
    function closeAllMenus(){
      closeMenu();
      closeHelpMenu();
    }
    function escapeHTML(str){ return String(str).replace(/[&<>"']/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]; }); }
  }
})();
