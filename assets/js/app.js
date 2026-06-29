(function(){
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  function attachDataStreamCounters(el, options){
    if(!el) return function(){};
    options = options || {};
    var appRoot = options.appRoot || (el.closest && el.closest('[data-ftc-app]')) || document.body;
    var isDetail = !!options.isDetail;
    var metricPool = [
      '1,247','98.3%','4.2ms','$12.4K','0.847',
      '3,891','72.1%','8.7ms','$4.2M','0.923',
      '642','99.1%','1.3ms','$89.5K','0.654',
      '12,040','84.7%','6.1ms','$2.1M','0.781',
      '0.912','31.4ms','$7.8K','2,468','95.4%',
      '8.3K/s','0.038','14ms','3.7σ','99.97%',
      '$340','1.02ms','77.6%','4,096','0.991',
      'Δ−2.1%','≈0.003','152Hz','7.4ms','0.502'
    ];
    var glowColors = ['#72f6ff','#ffe169','#8fb5ff','#69d85b','#c4ff47'];
    var overlay = document.createElement('div');
    overlay.className = 'ftc-data-counters' + (isDetail ? ' ftc-data-counters--detail' : '');
    overlay.setAttribute('aria-hidden','true');
    appRoot.appendChild(overlay);
    var MAX_LIVE = isDetail ? 5 : 3;
    var liveLabels = [];
    var lastSpawn = 0;
    var spawnInterval = isDetail ? 640 : 950;
    var orbitDrift = Math.random() * Math.PI * 2;
    var counterDead = false;
    var counterFrame = null;
    var metricIdx = Math.floor(Math.random() * metricPool.length);
    function spawnLabel(now){
      var alive = 0;
      for(var k=0; k<liveLabels.length; k++) if(!liveLabels[k].dead) alive++;
      if(alive >= MAX_LIVE) return;
      var text = metricPool[metricIdx % metricPool.length];
      metricIdx++;
      var glow = glowColors[Math.floor(Math.random() * glowColors.length)];
      var angle = Math.random() * Math.PI * 2;
      for(var tries=0; tries<10; tries++){
        var ok = true;
        for(var li=0; li<liveLabels.length; li++){
          if(liveLabels[li].dead) continue;
          var diff = Math.abs(liveLabels[li].angle - angle);
          if(diff > Math.PI) diff = Math.PI * 2 - diff;
          if(diff < 0.72){ ok = false; break; }
        }
        if(ok) break;
        angle = Math.random() * Math.PI * 2;
      }
      var div = document.createElement('div');
      div.className = 'ftc-data-counter';
      div.setAttribute('aria-hidden','true');
      div.textContent = text;
      div.style.color = glow;
      div.style.setProperty('--ftc-cg', glow);
      overlay.appendChild(div);
      liveLabels.push({
        el: div,
        angle: angle,
        drift: (Math.random() - 0.5) * 0.16,
        born: now,
        life: 1900 + Math.random() * 1300,
        dead: false
      });
    }
    function stop(){
      counterDead = true;
      if(counterFrame){ cancelAnimationFrame(counterFrame); counterFrame = null; }
      if(overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }
    function tickCounters(now){
      if(counterDead) return;
      if(!el.isConnected){ stop(); return; }
      orbitDrift += 0.00088;
      if(now - lastSpawn > spawnInterval){
        spawnLabel(now);
        lastSpawn = now;
      }
      var rect = el.getBoundingClientRect();
      var w = rect.width, h = rect.height;
      if(w < 4 || h < 4){ counterFrame = requestAnimationFrame(tickCounters); return; }
      var cx = rect.left + w * 0.5, cy = rect.top + h * 0.5;
      var orbitR = Math.min(w, h) * (isDetail ? 0.44 : 0.56);
      for(var i = liveLabels.length - 1; i >= 0; i--){
        var lbl = liveLabels[i];
        if(lbl.dead){ liveLabels.splice(i, 1); continue; }
        var age = now - lbl.born;
        var tNorm = age / lbl.life;
        if(tNorm >= 1){ lbl.el.remove(); lbl.dead = true; continue; }
        var opacity;
        if(tNorm < 0.12) opacity = tNorm / 0.12;
        else if(tNorm < 0.80) opacity = 1;
        else opacity = 1 - (tNorm - 0.80) / 0.20;
        opacity = Math.max(0, Math.min(1, opacity)) * 0.86;
        var ang = lbl.angle + orbitDrift + lbl.drift * (age * 0.001);
        var x = cx + Math.cos(ang) * orbitR;
        var y = cy + Math.sin(ang) * orbitR;
        lbl.el.style.opacity = opacity;
        lbl.el.style.left = x + 'px';
        lbl.el.style.top = y + 'px';
      }
      counterFrame = requestAnimationFrame(tickCounters);
    }
    counterFrame = requestAnimationFrame(tickCounters);
    return stop;
  }
  window.FTCDataStreamCounters = { attach: attachDataStreamCounters };

  ready(function(){ document.querySelectorAll('[data-ftc-app]').forEach(initFTC); });

  function initFTC(app){
    const nonce = window.ftcData ? ftcData.nonce : '';
    const ajaxUrl = window.ftcData ? ftcData.ajaxUrl : '/wp-admin/admin-ajax.php';
    const intro = app.querySelector('[data-ftc-intro]');
    const chat = app.querySelector('[data-ftc-chat]');
    const chatForm = app.querySelector('[data-ftc-chat-form]');
    const chatInput = app.querySelector('[data-ftc-chat-input]');
    const stream = app.querySelector('[data-ftc-stream]');
    const menuBtn = app.querySelector('header button[data-ftc-menu]');
    const modal = app.querySelector('#ftc-main-menu');
    const helpBtn = app.querySelector('[data-ftc-help-menu]');
    const helpModal = app.querySelector('[data-ftc-help-modal]');
    const searchToggle = app.querySelector('[data-ftc-search-toggle]');
    const searchCloseBtn = app.querySelector('[data-ftc-search-close]');
    const mobileSearchMq = window.matchMedia('(max-width: 760px)');
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
    let scrollAdvanceCooldownUntil = 0;
    let awaitingScrollAwayFromBottom = false;
    let activeModal = null;
    let lastModalTrigger = null;
    const focusableSelector = 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
    const messageMap = createMessageMap();
    let messageMapFrame = 0;
    let serviceVisuals = [];
    let serviceVisualFrame = 0;
    let aiScrollMotionTargets = [];
    let aiScrollMotionBound = false;
    let aiScrollMotionRaf = 0;
    let threeLoadPromise = null;
    let pageHidden = typeof document !== 'undefined' && document.hidden;
    if(typeof document !== 'undefined'){
      document.addEventListener('visibilitychange', function(){
        pageHidden = document.hidden;
        if(pageHidden && serviceVisualFrame){
          cancelAnimationFrame(serviceVisualFrame);
          serviceVisualFrame = 0;
        } else {
          ensureServiceVisualLoop();
        }
      });
    }
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
          revealServiceCardsAnd3d(entry.target);
        }
      });
    }, {root: stream, rootMargin: '0px 0px -14% 0px', threshold: 0.16}) : null;

    const serviceCardObserver = ('IntersectionObserver' in window) ? new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          entry.target.classList.add('ftc-card-visible');
          serviceCardObserver.unobserve(entry.target);
        }
      });
    }, {root: stream || null, rootMargin: '0px 0px -6% 0px', threshold: 0.10}) : null;

    function initServiceCardAnimations(root){
      var scope = root || app;
      if(!scope || !scope.querySelectorAll) return;
      var cards = scope.querySelectorAll(
        '.ftc-service-grid .ftc-service-card:not(.ftc-card-visible),' +
        '.ftc-services-category-grid .ftc-service-category-card:not(.ftc-card-visible)'
      );
      if(!serviceCardObserver){
        cards.forEach(function(c){ c.classList.add('ftc-card-visible'); });
        return;
      }
      cards.forEach(function(c){ serviceCardObserver.observe(c); });
    }
    function revealServiceCardsAnd3d(el){
      if(!el) return;
      var servicesRoot = el.querySelector('.ftc-response-sequence-services') || el.querySelector('.ftc-services-section-one') || el;
      if(!servicesRoot.querySelector('.ftc-service-3d')) return;
      var grid = servicesRoot.querySelector('.ftc-services-category-grid, .ftc-service-grid');
      if(grid){
        grid.querySelectorAll('.ftc-service-category-card, .ftc-service-card').forEach(function(card){
          card.classList.add('ftc-card-visible');
          if(serviceCardObserver) serviceCardObserver.unobserve(card);
        });
      }
      var isGetStartedRoute = app.getAttribute('data-ftc-route') === 'get-started' || !!el.querySelector('.ftc-response-sequence-start');
      function bootAllService3d(){
        loadThree().catch(function(){});
        if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadThree){
          window.FTCGetStartedScene.preloadThree();
        }
        if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadServiceScreenImages){
          window.FTCGetStartedScene.preloadServiceScreenImages();
        }
        if(window.FTCGetStartedScene && window.FTCGetStartedScene.initService3dIn){
          window.FTCGetStartedScene.initService3dIn(servicesRoot);
        }
      }
      var headingTw = servicesRoot.querySelector('.ftc-response-header .ftc-typewriter');
      function scheduleLazy3d(){
        setTimeout(bootAllService3d, isGetStartedRoute ? 0 : 120);
      }
      if(isGetStartedRoute || !headingTw || headingTw.classList.contains('is-complete')){
        scheduleLazy3d();
        return;
      }
      var done = false;
      function finish(){
        if(done) return;
        done = true;
        if(twObserver) twObserver.disconnect();
        clearTimeout(fallback);
        scheduleLazy3d();
      }
      var twObserver = new MutationObserver(function(){
        if(headingTw.classList.contains('is-complete')) finish();
      });
      twObserver.observe(headingTw, {attributes:true, attributeFilter:['class']});
      var fallback = setTimeout(finish, 9000);
    }

    const servicesParticleHosts = new WeakSet();

    function initGoTimeSpline(root){
      if(!window.FTCGoTimeSpline || !window.FTCGoTimeSpline.scan) return;
      window.FTCGoTimeSpline.scan(root || document);
      var scope = root || document;
      var pending = scope.querySelector('[data-ftc-go-time-spline]:not(.is-ready)');
      if(pending) app.classList.add('ftc-app-go-time-loading');
      scope.querySelectorAll('[data-ftc-go-time-spline].is-ready').forEach(function(rail){
        if(window.FTCGoTimeSpline.markGoTimeAppMode) window.FTCGoTimeSpline.markGoTimeAppMode(rail);
        initGoTimeSplineTypewriters(rail);
      });
    }

    function isInsideGoTimeSpline(el){
      if(!el || !el.closest) return false;
      if(el.closest('[data-ftc-go-time-spline]')) return true;
      if(el.closest('.ftc-go-time-spline-copy')) return true;
      if(el.closest('.ftc-go-time-spline-panel')) return true;
      return false;
    }

    function initGoTimeSplineTypewriters(rail){
      if(!rail) return;
      var roots = [rail];
      if(rail._ftcSplineCopyEl) roots.push(rail._ftcSplineCopyEl);
      roots.forEach(function(scope){
        scope.querySelectorAll('.ftc-typewriter').forEach(function(el){
          if(typeObserver) typeObserver.unobserve(el);
          var text = el.dataset.pendingText || el.dataset.text || el.textContent.trim();
          el.classList.remove('ftc-typewriter');
          el.textContent = text;
          el.classList.add('is-complete');
          el.dataset.initialized = 'true';
          el.dataset.typed = 'true';
        });
      });
    }

    window.addEventListener('ftc-go-time-spline-ready', function(e){
      app.classList.remove('ftc-app-go-time-loading');
      if(e.detail && e.detail.root) initGoTimeSplineTypewriters(e.detail.root);
    });

    function scrollToGoTimeSpline(messageEl){
      if(!messageEl || !stream) return;
      var rail = messageEl.querySelector('[data-ftc-go-time-spline]');
      var target = rail || messageEl;
      var offset = window.innerWidth < 760 ? 52 : 88;
      requestAnimationFrame(function(){
        var streamRect = stream.getBoundingClientRect();
        var targetRect = target.getBoundingClientRect();
        var top = Math.max(0, stream.scrollTop + (targetRect.top - streamRect.top) - offset);
        if('scrollTo' in stream) stream.scrollTo({ top: top, behavior: 'auto' });
        else stream.scrollTop = top;
        requestAnimationFrame(function(){
          if(window.FTCGoTimeSpline && window.FTCGoTimeSpline.scan) window.FTCGoTimeSpline.scan(messageEl);
        });
      });
    }

    function initServicesHeroParticles(root){
      var scope = root || app;
      if(!scope || !scope.querySelectorAll) return;
      if(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      scope.querySelectorAll(
        '.ftc-response-sequence-services > .ftc-response-header,' +
        '.ftc-response-layout-services > .ftc-response-header'
      ).forEach(function(header){
        if(servicesParticleHosts.has(header)) return;
        servicesParticleHosts.add(header);

        var wrap = document.createElement('div');
        wrap.className = 'ftc-services-hero-particles';
        wrap.setAttribute('aria-hidden', 'true');
        var canvas = document.createElement('canvas');
        wrap.appendChild(canvas);
        header.insertBefore(wrap, header.firstChild);

        var ctx = canvas.getContext('2d');
        var particles = [];
        var count = 28;
        var running = true;
        var rafId = 0;

        function resize(){
          var rect = header.getBoundingClientRect();
          var w = Math.max(1, Math.round(rect.width));
          var h = Math.max(1, Math.round(rect.height + rect.height * 0.35));
          canvas.width = w;
          canvas.height = h;
          canvas.style.width = w + 'px';
          canvas.style.height = h + 'px';
          particles = [];
          for(var i = 0; i < count; i++){
            particles.push({
              x: Math.random() * w,
              y: h * (0.35 + Math.random() * 0.75),
              r: 0.6 + Math.random() * 1.4,
              speed: 0.08 + Math.random() * 0.22,
              drift: -0.12 + Math.random() * 0.24,
              alpha: 0.08 + Math.random() * 0.22
            });
          }
        }

        function tick(){
          if(!running) return;
          var w = canvas.width;
          var h = canvas.height;
          ctx.clearRect(0, 0, w, h);
          particles.forEach(function(p){
            p.y -= p.speed;
            p.x += p.drift;
            if(p.y < -4){
              p.y = h + Math.random() * 24;
              p.x = Math.random() * w;
            }
            if(p.x < -4) p.x = w + 2;
            if(p.x > w + 4) p.x = -2;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(245,242,234,' + p.alpha + ')';
            ctx.fill();
          });
          rafId = requestAnimationFrame(tick);
        }

        resize();
        tick();

        if(typeof ResizeObserver !== 'undefined'){
          new ResizeObserver(resize).observe(header);
        } else {
          window.addEventListener('resize', resize);
        }

        if(typeof IntersectionObserver !== 'undefined'){
          new IntersectionObserver(function(entries){
            running = entries[0].isIntersecting;
            if(running && !rafId) tick();
            else if(!running && rafId){
              cancelAnimationFrame(rafId);
              rafId = 0;
            }
          }, { threshold: 0.05 }).observe(header);
        }
      });
    }

    function initServiceDetailAnimations(root){
      var scope = root || app;
      if(!scope || !scope.querySelector) return;
      if(!scope.querySelector('.ftc-response-layout-service-detail')) return;
      scope.querySelectorAll(
        '.ftc-service-detail-visual [data-ftc-service-visual],' +
        '.ftc-service-detail-webgl [data-ftc-service-visual]'
      ).forEach(function(el){
        requestAnimationFrame(function(){
          requestAnimationFrame(function(){ el.classList.add('is-in-view'); });
        });
      });
      var detailHost = scope.querySelector('.ftc-service-detail-webgl .ftc-service-3d, .ftc-service-detail-visual .ftc-service-3d');
      if(detailHost && window.FTCGetStartedScene && window.FTCGetStartedScene.initService3dIn){
        var bootRoot = detailHost.closest('.ftc-service-detail') || scope;
        setTimeout(function(){
          if(detailHost.isConnected){
            window.FTCGetStartedScene.initService3dIn(bootRoot);
          }
        }, 320);
      }
      refreshAiDetailScrollMotion(scope);
    }

    function bindAiDetailScrollMotion(){
      if(aiScrollMotionBound) return;
      aiScrollMotionBound = true;
      var onScrollOrResize = function(){
        if(aiScrollMotionRaf) return;
        aiScrollMotionRaf = requestAnimationFrame(function(){
          aiScrollMotionRaf = 0;
          updateAiDetailScrollMotion();
        });
      };
      window.addEventListener('scroll', onScrollOrResize, { passive: true });
      window.addEventListener('resize', onScrollOrResize);
    }

    function refreshAiDetailScrollMotion(root){
      var scope = root || app;
      if(!scope || !scope.querySelectorAll) return;
      bindAiDetailScrollMotion();
      var targets = scope.querySelectorAll(
        '.ftc-service-detail-webgl .ftc-service-3d[data-service="innovation"], ' +
        '.ftc-service-detail-visual .ftc-service-3d[data-service="innovation"], ' +
        '.ftc-service-detail-webgl .ftc-service-3d[data-service="ai"], ' +
        '.ftc-service-detail-visual .ftc-service-3d[data-service="ai"]'
      );
      targets.forEach(function(motionEl){
        if(motionEl.dataset.ftcAiScrollBound === '1') return;
        var host = motionEl.closest('.ftc-service-detail-webgl, .ftc-service-detail-visual') || motionEl.parentElement;
        if(!host) return;
        motionEl.dataset.ftcAiScrollBound = '1';
        motionEl.classList.add('ftc-ai-scroll-motion');
        aiScrollMotionTargets.push({ host: host, motionEl: motionEl });
      });
      updateAiDetailScrollMotion();
    }

    function updateAiDetailScrollMotion(){
      var reduced = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
      aiScrollMotionTargets = aiScrollMotionTargets.filter(function(item){
        if(!item || !item.host || !item.motionEl) return false;
        if(!item.host.isConnected || !item.motionEl.isConnected) return false;
        if(reduced){
          item.motionEl.style.transform = '';
          return true;
        }
        var rect = item.host.getBoundingClientRect();
        var vh = window.innerHeight || document.documentElement.clientHeight || 1;
        var raw = (vh - rect.top) / (vh + Math.max(rect.height, 1));
        var p = Math.max(0, Math.min(1, raw));
        var slideY = p * 78;
        var rotX = p * 4.5;
        var rotY = -p * 3.5;
        item.motionEl.style.transform = 'translate3d(0,' + slideY.toFixed(2) + 'px,0) rotateX(' + rotX.toFixed(2) + 'deg) rotateY(' + rotY.toFixed(2) + 'deg)';
        return true;
      });
    }

    window.FTCServiceVisual = window.FTCServiceVisual || {};
    window.FTCServiceVisual.bootGoTimeDataScene = bootGoTimeDataScene;
    window.FTCServiceVisual.buildSelectiveDrawDataScene = buildSelectiveDrawDataScene;
    window.FTCServiceVisual.updateSelectiveDrawData = updateSelectiveDrawData;
    window.dispatchEvent(new CustomEvent('ftc-service-visual-ready'));

    try{ localStorage.removeItem('ftcTheme2618'); }catch(e){}
    app.setAttribute('data-theme', 'dark');
    const hasServerRenderedMessages = hydrateServerRenderedMessages();
    initServiceVisuals(app);
    initServiceCardAnimations(app);
    initServicesHeroParticles(app);
    if(!hasServerRenderedMessages) typeIntroHeading();
    if(chatForm) chatForm.setAttribute('aria-hidden', mobileSearchMq.matches ? 'true' : 'false');
    if(chatInput && !app.classList.contains('ftc-route-app') && (!mobileSearchMq.matches || app.classList.contains('is-mobile-search-open'))){
      setTimeout(function(){ chatInput.focus(); }, 250);
    }
    setTimeout(loadMenuContent, 700);

    if(chatForm) chatForm.addEventListener('submit', function(e){ e.preventDefault(); submitPrompt(chatInput.value); });
    document.addEventListener('click', function(e){
      if(!modal || !modal.classList.contains('is-open')) return;
      const menuClose = e.target.closest('#ftc-main-menu [data-ftc-close]');
      if(menuClose){
        e.preventDefault();
        closeMenu();
        return;
      }
      const menuPrompt = e.target.closest('#ftc-main-menu .ftc-menu-prompt');
      if(menuPrompt){
        e.preventDefault();
        closeMenu(false);
        const menuRedirect = menuPrompt.getAttribute('data-redirect');
        if(menuRedirect){ window.location.href = menuRedirect; return; }
        const menuPromptText = menuPrompt.getAttribute('data-prompt');
        if(menuPromptText) submitPrompt(menuPromptText);
      }
    });
    app.addEventListener('click', function(e){
      const menuTrigger = e.target.closest('button[data-ftc-menu]');
      if(menuTrigger){
        e.preventDefault();
        openMenu();
        return;
      }
      const resetHome = e.target.closest('[data-ftc-reset]');
      if(resetHome){ e.preventDefault(); window.location.href = '/'; return; }
      const revertAction = e.target.closest('[data-ftc-revert-action]');
      if(revertAction){ e.preventDefault(); closeAllMenus(); revertLastAction(revertAction); return; }
      const resetPrompt = e.target.closest('[data-ftc-reset-to-prompt]');
      if(resetPrompt){ e.preventDefault(); closeAllMenus(); resetToPrompt(resetPrompt.getAttribute('data-ftc-reset-to-prompt') || resetPrompt.getAttribute('data-prompt')); return; }
      const project = e.target.closest('[data-ftc-project]');
      if(project){ e.preventDefault(); closeAllMenus(); openProject(project.getAttribute('data-ftc-project')); return; }
      const service = e.target.closest('[data-ftc-service]');
      if(service){
        if(e.target.closest('.ftc-rubik-visual')){ e.preventDefault(); e.stopPropagation(); return; }
        e.preventDefault(); closeAllMenus(); openService(service.getAttribute('data-ftc-service'), service.getAttribute('data-ftc-service-label')); return;
      }
      const redirect = e.target.closest('[data-redirect]');
      if(redirect){ e.preventDefault(); closeAllMenus(); window.location.href = redirect.getAttribute('data-redirect'); return; }
      const prompt = e.target.closest('[data-prompt]');
      if(prompt){ e.preventDefault(); closeAllMenus(); submitPrompt(prompt.getAttribute('data-prompt')); return; }
      const next = e.target.closest('[data-ftc-carousel-next]');
      if(next){ e.preventDefault(); moveCarousel(next, 1); return; }
      const prev = e.target.closest('[data-ftc-carousel-prev]');
      if(prev){ e.preventDefault(); moveCarousel(prev, -1); return; }
    });
    if(clearBtn) clearBtn.addEventListener('click', resetExperience);
    if(resetBtn) resetBtn.addEventListener('click', function(e){ e.preventDefault(); window.location.href = '/'; });
    if(helpBtn) helpBtn.addEventListener('click', openHelpMenu);
    if(searchToggle) searchToggle.addEventListener('click', toggleMobileSearch);
    if(searchCloseBtn) searchCloseBtn.addEventListener('click', closeMobileSearch);
    if(mobileSearchMq.addEventListener){
      mobileSearchMq.addEventListener('change', function(e){
        if(!e.matches) closeMobileSearch(false);
      });
    } else if(mobileSearchMq.addListener){
      mobileSearchMq.addListener(function(e){
        if(!e.matches) closeMobileSearch(false);
      });
    }
    if(helpModal) helpModal.querySelectorAll('[data-ftc-help-close]').forEach(function(btn){ btn.addEventListener('click', closeHelpMenu); });
    app.addEventListener('keydown', handleModalKeydown);
    if(stream) stream.addEventListener('scroll', function(){
      releaseAllServiceVisualDrags();
      if(messageMap && messageMap.querySelector('.ftc-message-map-dot')) scheduleMessageMapUpdate();
      scheduleBgToneUpdate();
      if(awaitingScrollAwayFromBottom && !isNearStreamBottom()) awaitingScrollAwayFromBottom = false;
      if(Date.now() - lastUserScrollAt < 900) maybeAppendQueuedFragment(false);
    }, {passive:true});
    if(stream) stream.addEventListener('wheel', function(e){
      if(e.deltaY > 0){
        lastUserScrollAt = Date.now();
        if(!streamHasScrollRoom()) maybeAppendQueuedFragment(true);
        else maybeAppendQueuedFragment(false);
      }
    }, {passive:true});
    if(stream) stream.addEventListener('touchstart', function(){ lastUserScrollAt = Date.now(); }, {passive:true});
    if(stream) stream.addEventListener('touchmove', function(){
      lastUserScrollAt = Date.now();
      maybeAppendQueuedFragment(false);
    }, {passive:true});
    window.addEventListener('resize', scheduleMessageMapUpdate);
    window.addEventListener('resize', function(){ serviceVisuals.forEach(resizeServiceVisual); });

    let bgToneFrame = 0;
    function bgToneForPromptKey(key){
      if(!key) return 'default';
      if(key === 'get-started') return 'home';
      if(key === 'services' || key === 'services-all' || key.indexOf('service-') === 0) return 'services';
      if(key === 'portfolio' || key === 'portfolio-all') return 'portfolio';
      if(key === 'testimonials') return 'testimonials';
      if(key === 'go-time') return 'go-time';
      if(key === 'contact' || key === 'about' || key === 'faq') return 'neutral';
      return 'default';
    }
    function updateBgTone(){
      bgToneFrame = 0;
      if(intro && intro.classList.contains('is-visible') && !app.classList.contains('is-chat')){
        app.setAttribute('data-ftc-bg-tone', 'intro');
        return;
      }
      if(!stream) return;
      const streamRect = stream.getBoundingClientRect();
      const focusY = streamRect.top + streamRect.height * 0.42;
      let best = null;
      let bestDist = Infinity;
      stream.querySelectorAll('.ftc-message.ftc-assistant').forEach(function(message){
        if(message.classList.contains('ftc-thinking-message')) return;
        const rect = message.getBoundingClientRect();
        if(rect.bottom < streamRect.top + 40 || rect.top > streamRect.bottom - 40) return;
        const center = rect.top + rect.height * 0.5;
        const dist = Math.abs(center - focusY);
        if(dist < bestDist){ bestDist = dist; best = message; }
      });
      const tone = best ? bgToneForPromptKey(best.dataset.ftcPromptKey || '') : 'default';
      app.setAttribute('data-ftc-bg-tone', tone);
    }
    function scheduleBgToneUpdate(){
      if(bgToneFrame) return;
      bgToneFrame = requestAnimationFrame(updateBgTone);
    }
    scheduleBgToneUpdate();

    function moveCarousel(btn, dir){
      const wrap = btn.closest('.ftc-service-carousel-wrap');
      const track = wrap ? wrap.querySelector('[data-ftc-carousel-track]') : null;
      if(!track) return;
      const amount = Math.max(280, Math.round(track.clientWidth * 0.82));
      track.scrollBy({left: dir * amount, behavior: 'smooth'});
    }

    function scrollToNextResponse(btn){
      scrollAdvanceCooldownUntil = Date.now() + 1200;
      const message = btn.closest('.ftc-message');
      const sectionOne = btn.closest('[data-ftc-section-one]');
      if(sectionOne){
        const parent = sectionOne.parentElement;
        const sections = parent ? Array.prototype.slice.call(parent.children).filter(function(el){
          return el !== sectionOne && (el.matches('section') || el.hasAttribute('data-ftc-section-one'));
        }) : [];
        const idx = parent ? Array.prototype.indexOf.call(parent.children, sectionOne) : -1;
        if(idx >= 0){
          for(let i = idx + 1; i < parent.children.length; i++){
            const candidate = parent.children[i];
            if(candidate && candidate.matches('section,.ftc-services-all-content,.ftc-portfolio-all-content,[data-ftc-section-one]')){
              scrollTo(candidate, 18);
              return;
            }
          }
        }
        if(sections[0]){ scrollTo(sections[0], 18); return; }
      }
      const currentShell = btn.closest('.ftc-response-shell');
      if(currentShell){
        let sibling = currentShell.nextElementSibling;
        while(sibling){
          if(sibling.classList && sibling.classList.contains('ftc-response-shell')){
            scrollTo(sibling, 18);
            return;
          }
          sibling = sibling.nextElementSibling;
        }
      }
      if(message && !pendingFragments.length) enqueueDeferredFragments(message);
      if(appendNextQueuedFragment({shouldScroll:true})) return;
      const next = message ? message.nextElementSibling : null;
      if(next && next.classList && next.classList.contains('ftc-message')){
        revealAssistantMessage(next);
        scrollTo(next, 18);
        return;
      }
      if(appendNextQueuedFragment({shouldScroll:true})) return;
      if(message){
        const content = message.querySelector('.ftc-response-content') || message.querySelector('.ftc-card');
        if(content){ scrollTo(content, 18); return; }
      }
      if('scrollBy' in stream) stream.scrollBy({top: Math.round(stream.clientHeight * 0.78), behavior:'smooth'});
      else stream.scrollTop += Math.round(stream.clientHeight * 0.78);
    }

    function resetExperience(){
      disposeAllServiceVisuals(null);
      stream.innerHTML = ''; chatInput.value = '';
      clearPendingFragments();
      if(messageMap) messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){ dot.remove(); });
      scheduleMessageMapUpdate();
      app.classList.remove('is-chat'); chat.classList.remove('is-visible'); intro.classList.add('is-visible');
      scheduleBgToneUpdate();
      setTimeout(function(){ chatInput.focus(); }, 160);
    }
    function resetToPrompt(prompt){
      const term = (prompt || '').trim();
      if(!term) return;
      disposeAllServiceVisuals(null);
      stream.innerHTML = '';
      chatInput.value = '';
      lastPrompt = '';
      clearPendingFragments();
      if(messageMap) messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){ dot.remove(); });
      scheduleMessageMapUpdate();
      beginChat();
      submitPrompt(term);
    }
    function typeIntroHeading(){
      if(!introBody) {
        if(introHeading) introHeading.classList.add('is-complete');
        return;
      }
      const bodyText = introBody.textContent.trim();
      introBody.innerHTML = '<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>';
      const bodyTarget = introBody.querySelector('.ftc-type-text');
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
        setTimeout(tick, prefersReducedMotion ? 0 : 100);
      }
      function startBodyTyping(){
        typeText(bodyText, bodyTarget, function(){
          if(introHeading) introHeading.classList.add('is-complete');
          introBody.classList.add('is-complete');
        });
      }
      setTimeout(startBodyTyping, prefersReducedMotion ? 0 : 260);
    }
    function beginChat(){
      intro.classList.remove('is-visible');
      chat.classList.add('is-visible');
      app.classList.add('is-chat');
      scheduleBgToneUpdate();
    }
    function beginResponseTransition(){
      disposeAllServiceVisuals(null);
      clearPendingFragments();
      if(stream) stream.setAttribute('aria-busy','true');
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
      if(stream) stream.setAttribute('aria-busy','false');
      app._ftcTransitionTimer = setTimeout(function(){
        app.classList.remove('is-transitioning-response');
        stream.querySelectorAll('.is-soft-focus').forEach(function(el){ el.classList.remove('is-soft-focus'); });
      }, 260);
    }
    function submitPrompt(raw){
      const term = (raw || '').trim(); if(!term) return;
      closeMobileSearch(false);
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
    function isGoTimePrompt(label){
      const t = normalizePromptLabel(label);
      return /^(?:it s |its )?go(?:\s|-)?time$/.test(t);
    }
    function promptKey(label){
      const t = normalizePromptLabel(label);
      if(!t) return '';
      if(isGoTimePrompt(label)) return 'go-time';
      if(/^(get started|start|home|overview)$/.test(t)) return 'get-started';
      if(/(show me )?all portfolio|all project/.test(t)) return 'portfolio-all';
      if(/(show me )?all service/.test(t)) return 'services-all';
      if(/hire|contact|proposal|consultation|work together|call|inquiry|get started with a project/.test(t)) return 'contact';
      if(/show me your work|portfolio|project|case stud|examples/.test(t)) return 'portfolio';
      if(/^(our )?services$|^services$|^help my company$|^help my business$|^how can you help my company$|^what do you do$/.test(t)) return 'services';
      if(/service|current site|better website|ux web|web development|seo|aeo|schema|local seo|marketing|campaign|google ads|paid media|analytics|dashboard|ga4|looker|ai|automation|assistant|prototype|ecommerce|checkout|cro|shopify|woocommerce|hosting|maintenance|accessibility|ada/.test(t)) return 'service-' + t;
      if(/testimonial|review|client/.test(t)) return 'testimonials';
      if(/faq|frequently asked|question|how long|how much|budget|timeline|support|maintenance/.test(t)) return 'faq';
      if(/about|company|team|people|who are you|field theory/.test(t)) return 'about';
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
      if(messageMap) messageMap.querySelectorAll('.ftc-message-map-dot').forEach(function(dot){
        if(dot._ftcTarget === message) dot.remove();
      });
      disposeAllServiceVisuals(null);
      message.remove();
      scheduleMessageMapUpdate();
    }
    function getAssistantMessages(){
      if(!stream) return [];
      return Array.prototype.slice.call(stream.querySelectorAll('.ftc-message.ftc-assistant')).filter(function(message){
        return !message.classList.contains('ftc-thinking-message');
      });
    }
    function getRouteRevertHref(){
      if(!app.classList.contains('ftc-route-app')) return '';
      const parts = window.location.pathname.replace(/^\/+|\/+$/g, '').split('/').filter(Boolean);
      if(parts[0] === 'services' && parts.length >= 3) return '/services/' + parts[1] + '/';
      if(parts[0] === 'services' && parts.length === 2) return '/services/';
      if(parts[0] === 'portfolio' && parts.length >= 2) return '/portfolio/';
      return '';
    }
    function syncRevertActionButtons(root){
      const scope = root || app;
      if(!scope || !scope.querySelectorAll) return;
      const messages = getAssistantMessages();
      const canRevert = messages.length > 1 || !!getRouteRevertHref();
      scope.querySelectorAll('[data-ftc-revert-action]').forEach(function(btn){
        btn.disabled = !canRevert;
        btn.setAttribute('aria-disabled', canRevert ? 'false' : 'true');
        btn.classList.toggle('is-disabled', !canRevert);
      });
    }
    function ensureRevertActionButtons(root){
      const scope = root || app;
      if(!scope || !scope.querySelectorAll) return;
      scope.querySelectorAll('.ftc-response-actions-left').forEach(function(left){
        if(left.querySelector('[data-ftc-revert-action]')) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ftc-revert-action-btn';
        btn.setAttribute('data-ftc-revert-action', '');
        btn.setAttribute('aria-label', 'Go back');
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>';
        left.insertBefore(btn, left.firstChild);
      });
      syncRevertActionButtons(scope);
    }
    function revertLastAction(triggerEl){
      closeMobileSearch(false);
      const messages = getAssistantMessages();
      if(!messages.length) return;
      const current = triggerEl ? triggerEl.closest('.ftc-message.ftc-assistant') : null;
      const targetMessage = current && messages.indexOf(current) >= 0 ? current : messages[messages.length - 1];
      const targetIndex = messages.indexOf(targetMessage);
      if(messages.length <= 1){
        const routeHref = getRouteRevertHref();
        if(routeHref){
          window.location.href = routeHref;
          return;
        }
        const actionsLeft = triggerEl && triggerEl.closest('.ftc-response-actions-left');
        const resetBtn = actionsLeft && actionsLeft.querySelector('[data-ftc-reset-to-prompt]');
        const resetPrompt = resetBtn && (resetBtn.getAttribute('data-ftc-reset-to-prompt') || resetBtn.getAttribute('data-prompt'));
        if(resetPrompt){
          resetToPrompt(resetPrompt);
          return;
        }
        if(window.history.length > 1){
          window.history.back();
          return;
        }
        resetExperience();
        return;
      }
      clearPendingFragments();
      removeAssistantMessage(targetMessage);
      const remaining = getAssistantMessages();
      if(!remaining.length){
        resetExperience();
        syncRevertActionButtons(app);
        return;
      }
      const previous = remaining[Math.max(0, targetIndex - 1)];
      lastPrompt = promptLabelForMessage(previous, '');
      endResponseTransition();
      scrollTo(previous, 18);
      scheduleBgToneUpdate();
      syncRevertActionButtons(app);
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
    function streamHasScrollRoom(){
      if(!stream) return false;
      return stream.scrollHeight > (stream.clientHeight + 12);
    }
    function kickDeferredFragmentQueue(){
      if(!pendingFragments.length || pendingRemoteLoading) return;
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          if(!pendingFragments.length || pendingRemoteLoading) return;
          if(!streamHasScrollRoom() || isNearStreamBottom()) maybeAppendQueuedFragment(true);
        });
      });
    }
    function appendNextQueuedFragment(opts){
      opts = opts || {};
      const shouldScroll = opts.shouldScroll !== false;
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
            addAssistantMessage(data && data.success ? data.data.html : fallbackHTML(), shouldScroll);
            markFragmentAppended({skipScrollGate: opts.skipScrollGate});
          })
          .catch(function(){
            thinking.remove();
            if(generation !== pendingGeneration) return;
            pendingRemoteLoading = false;
            addAssistantMessage(fallbackHTML(), shouldScroll);
            markFragmentAppended({skipScrollGate: opts.skipScrollGate});
          });
        return true;
      }
      appendAssistantMessage(next.html, next.prompt, shouldScroll, 0);
      markFragmentAppended({skipScrollGate: opts.skipScrollGate});
      return true;
    }
    function maybeAppendQueuedFragment(force){
      if(!pendingFragments.length || pendingRemoteLoading) return false;
      if(Date.now() < scrollAdvanceCooldownUntil) return false;
      const noScrollRoom = !streamHasScrollRoom();
      if(awaitingScrollAwayFromBottom && isNearStreamBottom() && !noScrollRoom) return false;
      if(!force && !noScrollRoom && !isNearStreamBottom()) return false;
      if(pendingAppendFrame) return true;
      const shouldForce = !!force;
      pendingAppendFrame = requestAnimationFrame(function(){
        pendingAppendFrame = 0;
        if(shouldForce || isNearStreamBottom()) appendNextQueuedFragment({shouldScroll:false});
      });
      return true;
    }
    function markFragmentAppended(opts){
      opts = opts || {};
      if(opts.skipScrollGate) return;
      awaitingScrollAwayFromBottom = true;
      scrollAdvanceCooldownUntil = Date.now() + 1400;
    }
    function drainGetStartedFragmentQueue(){
      function step(){
        if(!pendingFragments.length){
          document.querySelectorAll('.ftc-message.ftc-assistant').forEach(revealServiceCardsAnd3d);
          return;
        }
        if(pendingRemoteLoading){
          setTimeout(step, 120);
          return;
        }
        awaitingScrollAwayFromBottom = false;
        scrollAdvanceCooldownUntil = 0;
        appendNextQueuedFragment({shouldScroll:false, skipScrollGate:true});
        setTimeout(step, pendingRemoteLoading ? 120 : 280);
      }
      step();
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
      if(sequence === 'get-started'){
        loadThree().catch(function(){});
        if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadThree){
          window.FTCGetStartedScene.preloadThree();
        }
        if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadServiceScreenImages){
          window.FTCGetStartedScene.preloadServiceScreenImages();
        }
        setTimeout(drainGetStartedFragmentQueue, 400);
      }
      kickDeferredFragmentQueue();
    }
    function addAssistantMessage(html, shouldScroll){
      const scroll = shouldScroll !== false;
      const fragments = getResponseFragments(html);
      if(fragments.length > 1){
        const queued = [];
        fragments.forEach(function(fragment, index){
          const prompt = fragment.getAttribute('data-ftc-response-prompt') || fragment.getAttribute('data-response-title') || (index === 0 ? (lastPrompt || 'Response') : 'Continue');
          removeExistingPromptInstances(prompt);
          if(index === 0) appendAssistantMessage(fragment.outerHTML, prompt, scroll, 0);
          else queued.push({html: fragment.outerHTML, prompt: prompt});
        });
        pendingFragments = queued;
        kickDeferredFragmentQueue();
        return;
      }
      const prompt = getSingleResponsePrompt(html) || lastPrompt || 'Response';
      removeExistingPromptInstances(prompt);
      appendAssistantMessage(html, prompt, scroll, 0);
    }
    function applyMessageLayoutClasses(el){
      const shell = el ? el.querySelector('.ftc-response-shell') : null;
      if(!shell) return;
      if(shell.classList.contains('ftc-response-go-time')){
        el.classList.add('has-layout-go-time');
        if(window.FTCGoTimeSpline && window.FTCGoTimeSpline.markGoTimeAppMode){
          var rail = el.querySelector('[data-ftc-go-time-spline]');
          if(rail) window.FTCGoTimeSpline.markGoTimeAppMode(rail);
        }
      }
      ['service-detail','child-service','about','project'].forEach(function(layout){
        if(shell.classList.contains('ftc-response-layout-'+layout)) el.classList.add('has-layout-'+layout);
      });
    }
    function promptLabelForMessage(el, fallback){
      const shell = el ? el.querySelector('.ftc-response-shell') : null;
      return (fallback || '') || (el ? (el.getAttribute('data-ftc-prompt-label') || '') : '') || (shell ? (shell.getAttribute('data-ftc-response-prompt') || shell.getAttribute('data-response-title')) : '') || lastPrompt || 'Response';
    }
    function animatePromptChip(chip){
      if(!chip) return;
      chip.classList.remove('ftc-question-chip-pop');
      void chip.offsetWidth;
      requestAnimationFrame(function(){
        chip.classList.add('ftc-question-chip-pop');
        if(chip._ftcPopTimer) clearTimeout(chip._ftcPopTimer);
        chip._ftcPopTimer = setTimeout(function(){ chip.classList.remove('ftc-question-chip-pop'); }, 980);
      });
    }
    function ensurePromptChip(el, label){
      if(!el || !label) return;
      if(el.classList.contains('has-layout-go-time')) return;
      const header = el.querySelector('.ftc-response-header');
      if(!header) return;
      let chip = header.querySelector('.ftc-question-chip');
      if(!chip){
        chip = document.createElement('span');
        chip.className = 'ftc-question-chip';
        header.appendChild(chip);
      }
      chip.textContent = label;
      chip.setAttribute('aria-hidden','true');
      chip.removeAttribute('data-prompt');
      chip.removeAttribute('type');
      animatePromptChip(chip);
    }
    function hydrateServerRenderedMessages(){
      const messages = Array.prototype.slice.call(stream.querySelectorAll('.ftc-message.ftc-assistant'));
      if(!messages.length) return false;
      messages.forEach(function(el){
        applyMessageLayoutClasses(el);
        const promptLabel = promptLabelForMessage(el, '');
        el.dataset.ftcPromptKey = promptKey(promptLabel);
        el.dataset.revealed = 'true';
        el.classList.add('has-arrived');
        ensurePromptChip(el, promptLabel);
        if(!el.dataset.ftcMapAdded){
          addMessageMapPoint(el, promptLabel);
          el.dataset.ftcMapAdded = 'true';
        }
        el.querySelectorAll('.ftc-typewriter').forEach(function(tw){
          if(tw.closest('[data-ftc-go-time-spline]')) return;
          lazyTypewriterElement(tw);
        });
        el.querySelectorAll('[data-ftc-contact-quiz]').forEach(initContactQuiz);
        el.querySelectorAll('[data-ft-ai-assessment]').forEach(initAiAssessment);
        initServiceVisuals(el);
        initServiceCardAnimations(el);
        initServicesHeroParticles(el);
        initGoTimeSpline(el);
        initServiceDetailAnimations(el);
        enqueueDeferredFragments(el);
        splitInlineGetStartedShells(el);
        initGetStartedHeroVideo(el);
        if(el.querySelector('.ftc-get-started-video-only') && stream && !app.getAttribute('data-ftc-route')){
          stream.scrollTop = 0;
        }
        revealServiceCardsAnd3d(el);
      });
      ensureRevertActionButtons(app);
      syncRevertActionButtons(app);
      scheduleMessageMapUpdate();
      return true;
    }
    function appendAssistantMessage(html, promptLabel, shouldScroll, sequenceIndex){
      const el=document.createElement('div');
      el.className='ftc-message ftc-assistant' + (sequenceIndex > 0 ? ' ftc-staged-response' : '');
      el.innerHTML='<div class="ftc-warp-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div><div class="ftc-card">'+html+'</div>';
      applyMessageLayoutClasses(el);
      const resolvedPromptLabel = promptLabelForMessage(el, promptLabel);
      el.dataset.ftcPromptKey = promptKey(resolvedPromptLabel);
      stream.appendChild(el);
      ensurePromptChip(el, resolvedPromptLabel);
      ensureRevertActionButtons(el);
      syncRevertActionButtons(el);
      enqueueDeferredFragments(el);
      addMessageMapPoint(el, resolvedPromptLabel || 'Response');
      el.querySelectorAll('.ftc-typewriter').forEach(function(tw){
        if(tw.closest('[data-ftc-go-time-spline]')) return;
        lazyTypewriterElement(tw);
      });
      el.querySelectorAll('[data-ftc-contact-quiz]').forEach(initContactQuiz);
      el.querySelectorAll('[data-ft-ai-assessment]').forEach(initAiAssessment);
      initServiceVisuals(el);
      initServiceCardAnimations(el);
      initServicesHeroParticles(el);
      initGoTimeSpline(el);
      initServiceDetailAnimations(el);
      requestAnimationFrame(function(){
        initServiceVisuals(el);
        initServiceCardAnimations(el);
        initServicesHeroParticles(el);
        initGoTimeSpline(el);
        initServiceDetailAnimations(el);
        initGetStartedHeroVideo(el);
        serviceVisuals.forEach(resizeServiceVisual);
        ensureServiceVisualLoop();
      });
      if(sequenceIndex > 0 && messageRevealObserver){
        el.classList.add('is-waiting-scroll');
        messageRevealObserver.observe(el);
      } else {
        revealAssistantMessage(el);
        revealServiceCardsAnd3d(el);
      }
      if(stream) stream.setAttribute('aria-busy','false');
      if(shouldScroll){
        if(el.querySelector('[data-ftc-go-time-spline]')) scrollToGoTimeSpline(el);
        else scrollTo(el, 18);
      }
      scheduleBgToneUpdate();
    }
    function disposeServiceVisual(state){
      if(!state || state.disposed) return;
      state.disposed = true;
      if(state.resizeObserver){
        state.resizeObserver.disconnect();
        state.resizeObserver = null;
      }
      if(state.scene){
        state.scene.traverse(function(obj){
          if(obj.geometry) obj.geometry.dispose();
          if(!obj.material) return;
          if(Array.isArray(obj.material)) obj.material.forEach(function(m){ if(m && m.dispose) m.dispose(); });
          else if(obj.material.dispose) obj.material.dispose();
        });
      }
      if(state.renderer){
        state.renderer.dispose();
        try {
          var gl = state.renderer.getContext();
          var ext = gl && gl.getExtension('WEBGL_lose_context');
          if(ext) ext.loseContext();
        } catch(err) {}
      }
      if(state.io){
        state.io.disconnect();
        state.io = null;
      }
      if(state.el && state.el._ftcVisualBootObserver){
        state.el._ftcVisualBootObserver.disconnect();
        state.el._ftcVisualBootObserver = null;
      }
      if(state._stopDataCounters){ try{ state._stopDataCounters(); }catch(e){} state._stopDataCounters = null; }
      if(state.el){
        delete state.el.dataset.ftcVisualInitialized;
        delete state.el.dataset.ftcVisualPending;
      }
    }
    function disposeAllServiceVisuals(exceptEl){
      var kept = [];
      serviceVisuals.forEach(function(state){
        if(exceptEl && state.el === exceptEl){ kept.push(state); return; }
        disposeServiceVisual(state);
      });
      serviceVisuals = kept;
      if(!serviceVisuals.length && serviceVisualFrame){
        cancelAnimationFrame(serviceVisualFrame);
        serviceVisualFrame = 0;
      }
    }
    function attachServiceVisualObserver(state){
      if(!state || !state.el) return;
      const isDetail = !!state.el.closest('.ftc-service-detail-webgl');
      state.isDetailVisual = isDetail;
      if(isDetail){
        state.renderPaused = false;
        return;
      }
      if(!('IntersectionObserver' in window)) return;
      state.renderPaused = false;
      state.io = new IntersectionObserver(function(entries){
        var entry = entries[0];
        if(!entry) return;
        state.renderPaused = !entry.isIntersecting;
        if(!state.renderPaused && !serviceVisualFrame){
          serviceVisualFrame = requestAnimationFrame(renderServiceVisuals);
        }
      }, {root: stream || null, rootMargin: '100px 0px', threshold: 0.04});
      state.io.observe(state.el);
    }
    function ensureServiceVisualLoop(){
      if(serviceVisualFrame || !serviceVisuals.length) return;
      var active = serviceVisuals.some(function(s){ return s && !s.disposed && !s.renderPaused; });
      if(active) serviceVisualFrame = requestAnimationFrame(renderServiceVisuals);
    }
    function releaseAllServiceVisualDrags(){
      serviceVisuals.forEach(function(state){
        if(!state || !state.el) return;
        if(!state.dragging && state.capturedPointerId == null) return;
        state.dragging = false;
        state.el.classList.remove('is-dragging');
        if(state.el.releasePointerCapture && state.capturedPointerId != null){
          try { state.el.releasePointerCapture(state.capturedPointerId); } catch(err) {}
        }
        state.capturedPointerId = null;
      });
    }
    function initServiceVisuals(root){
      const scope = root || app;
      const visuals = Array.prototype.slice.call(scope.querySelectorAll ? scope.querySelectorAll('[data-ftc-service-visual]') : []);
      visuals.forEach(initServiceVisual);
      ensureServiceVisualLoop();
    }
    function serviceVisualUsesWebGL(key, el){
      if(!el || !el.closest('.ftc-service-detail-webgl')) return false;
      return key === 'data';
    }
    function loadThree(){
      if(window.THREE){
        threeLoadPromise = null;
        return Promise.resolve(window.THREE);
      }
      if(threeLoadPromise){
        return threeLoadPromise.then(function(){
          return window.THREE || Promise.reject(new Error('three unavailable'));
        });
      }
      const url = window.ftcData && ftcData.threeUrl ? ftcData.threeUrl : '';
      if(!url) return Promise.reject(new Error('three unavailable'));
      threeLoadPromise = new Promise(function(resolve, reject){
        function finish(){
          if(window.THREE) resolve(window.THREE);
          else reject(new Error('three load failed'));
        }
        const existing = document.querySelector('script[src="' + url + '"]');
        if(existing){
          if(window.THREE){ finish(); return; }
          existing.addEventListener('load', finish, {once: true});
          existing.addEventListener('error', function(){
            threeLoadPromise = null;
            reject(new Error('three load failed'));
          }, {once: true});
          return;
        }
        const script = document.createElement('script');
        script.src = url;
        script.crossOrigin = 'anonymous';
        script.onload = finish;
        script.onerror = function(){
          threeLoadPromise = null;
          reject(new Error('three load failed'));
        };
        document.head.appendChild(script);
      });
      return threeLoadPromise;
    }
    function retryStuckServiceVisuals(){
      if(window.THREE) threeLoadPromise = null;
      var stuck = document.querySelectorAll('[data-ftc-visual-pending]');
      if(!stuck.length) return;
      stuck.forEach(function(el){ delete el.dataset.ftcVisualPending; });
      initServiceVisuals(app);
    }
    window.addEventListener('ftc-three-ready', retryStuckServiceVisuals);
    function initServiceVisual(el){
      if(!el || el.dataset.ftcVisualInitialized || el.dataset.ftcVisualPending) return;
      const key = el.getAttribute('data-ftc-service-visual') || 'innovation';
      if(!serviceVisualUsesWebGL(key, el)){
        el.dataset.ftcVisualInitialized = 'true';
        el.classList.add('has-static-fallback');
        return;
      }
      el.dataset.ftcVisualPending = 'true';
      function bootServiceVisual(){
        if(!el.isConnected || el.dataset.ftcVisualInitialized){
          delete el.dataset.ftcVisualPending;
          return;
        }
        function runBoot(){
          if(!el.isConnected || el.dataset.ftcVisualInitialized){
            delete el.dataset.ftcVisualPending;
            return;
          }
          loadThree().then(function(){
          if(!el.isConnected || el.dataset.ftcVisualInitialized){
            delete el.dataset.ftcVisualPending;
            return;
          }
          delete el.dataset.ftcVisualPending;
          if(el._ftcVisualBootObserver){
            el._ftcVisualBootObserver.disconnect();
            el._ftcVisualBootObserver = null;
          }
          el.dataset.ftcVisualInitialized = 'true';
          el.classList.remove('has-static-fallback');
          disposeAllServiceVisuals(el);
      const canvas = el.querySelector('canvas') || document.createElement('canvas');
      if(!canvas.parentNode) el.appendChild(canvas);
      if(!window.THREE){
        el.classList.add('has-static-fallback');
        return;
      }
      const THREE = window.THREE;
      const isPreview = !el.closest('.ftc-service-detail-webgl');
      const isDetailDataLike = !isPreview && key === 'data';
      function createServiceRenderer(antialias){
        return new THREE.WebGLRenderer({
          canvas: canvas,
          alpha: true,
          antialias: antialias,
          powerPreference: 'high-performance'
        });
      }
      var renderer = createServiceRenderer(isDetailDataLike);
      if(!renderer.getContext()){
        renderer.dispose();
        renderer = createServiceRenderer(false);
      }
      if(!renderer.getContext()){
        el.classList.add('has-static-fallback');
        return;
      }
      renderer.setClearColor(0x000000, 0);
      var pixelCap = isPreview ? 0.85 : (key === 'data' ? 1.05 : (key === 'innovation' ? 1.08 : 1.1));
      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, pixelCap));
      if(renderer.shadowMap) renderer.shadowMap.enabled = false;
      if('outputEncoding' in renderer && THREE.sRGBEncoding) renderer.outputEncoding = THREE.sRGBEncoding;
      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 80);
      camera.position.set(0, 0, 8);
      const group = new THREE.Group();
      scene.add(group);
      if(isPreview){
        scene.add(new THREE.AmbientLight(0xffffff, 0.72));
        const fill = new THREE.PointLight(0x8fb5ff, 0.55, 24);
        fill.position.set(2, 3, 6);
        scene.add(fill);
      } else {
        scene.add(new THREE.AmbientLight(0xffffff, 0.68));
        const lightA = new THREE.PointLight(0x8fb5ff, 0.95, 28);
        lightA.position.set(-3, 3, 7);
        scene.add(lightA);
        const lightB = new THREE.PointLight(0xffe169, 0.5, 24);
        lightB.position.set(4, -2, 6);
        scene.add(lightB);
      }
      const floor = new THREE.Mesh(new THREE.PlaneGeometry(7, 4.5), new THREE.ShadowMaterial({opacity: 0.14}));
      floor.position.set(0, -1.65, -0.82);
      floor.rotation.x = -Math.PI / 2;
      floor.receiveShadow = true;
      floor.visible = false;
      scene.add(floor);
      const state = {
        el: el,
        renderer: renderer,
        scene: scene,
        camera: camera,
        group: group,
        key: key,
        isPreview: isPreview,
        frameTick: 0,
        mouseX: 0,
        mouseY: 0,
        t: Math.random() * 100,
        visible: true,
        enter: 0,
        springX: 0,
        springY: 0,
        springVX: 0,
        springVY: 0,
        dragX: 0,
        dragY: 0,
        dragging: false,
        capturedPointerId: null,
        lastPointerX: 0,
        lastPointerY: 0,
        lastScrollTop: stream ? stream.scrollTop : (window.pageYOffset || document.documentElement.scrollTop || 0),
        scrollLift: 0,
        scrollLiftTarget: 0,
        scrollLiftHold: 0
      };
      buildServiceScene(state, THREE);
      resizeServiceVisual(state);
      if(!isPreview){
        state._lastVisible = true;
        state.renderer.render(state.scene, state.camera);
      }
      const host = el.closest('button') || el;
      host.addEventListener('mousemove', function(e){
        var now = Date.now();
        if(now - (state._mouseAt || 0) < 48) return;
        state._mouseAt = now;
        const rect = el.getBoundingClientRect();
        if(!rect.width || !rect.height) return;
        state.mouseX = ((e.clientX - rect.left) / rect.width - 0.5) * 0.9;
        state.mouseY = ((e.clientY - rect.top) / rect.height - 0.5) * -0.7;
      }, {passive: true});
      host.addEventListener('mouseleave', function(){ state.mouseX = 0; state.mouseY = 0; });
      var coarsePointer = window.matchMedia('(pointer: coarse)').matches;
      el.addEventListener('pointerdown', function(e){
        if(state.marketingRubik) return;
        if(!el.closest('.ftc-service-detail-webgl')) return;
        if(e.pointerType === 'mouse' && e.button !== 0) return;
        state.dragging = true;
        state.capturedPointerId = e.pointerId;
        state.lastPointerX = e.clientX;
        state.lastPointerY = e.clientY;
        el.classList.add('is-dragging');
        if(!coarsePointer && el.setPointerCapture){
          try { el.setPointerCapture(e.pointerId); } catch(err) {}
        }
      });
      el.addEventListener('pointermove', function(e){
        if(!state.dragging) return;
        e.preventDefault();
        const dx = e.clientX - state.lastPointerX;
        const dy = e.clientY - state.lastPointerY;
        state.lastPointerX = e.clientX;
        state.lastPointerY = e.clientY;
        state.dragX = Math.max(-0.75, Math.min(0.75, state.dragX + dx * 0.0045));
        state.dragY = Math.max(-0.55, Math.min(0.55, state.dragY + dy * 0.004));
      });
      function releaseDrag(e){
        if(!state.dragging && state.capturedPointerId == null) return;
        state.dragging = false;
        el.classList.remove('is-dragging');
        var pointerId = e && e.pointerId != null ? e.pointerId : state.capturedPointerId;
        if(el.releasePointerCapture && pointerId != null){
          try { el.releasePointerCapture(pointerId); } catch(err) {}
        }
        state.capturedPointerId = null;
      }
      el.addEventListener('pointerup', releaseDrag);
      el.addEventListener('pointercancel', releaseDrag);
      el.addEventListener('lostpointercapture', releaseDrag);
      if('ResizeObserver' in window){
        const ro = new ResizeObserver(function(){ resizeServiceVisual(state); });
        ro.observe(el);
        state.resizeObserver = ro;
      }
      if(!isPreview) el.classList.add('is-in-view');
      serviceVisuals.push(state);
      attachServiceVisualObserver(state);
      ensureServiceVisualLoop();
      }).catch(function(){
        delete el.dataset.ftcVisualPending;
        el.classList.add('has-static-fallback');
      });
        }
        if(window.THREE) runBoot();
        else {
          window.addEventListener('ftc-three-ready', runBoot, {once: true});
          var bootPoll = 0;
          var bootPollId = setInterval(function(){
            if(window.THREE){
              clearInterval(bootPollId);
              runBoot();
            } else if(++bootPoll > 120){
              clearInterval(bootPollId);
              delete el.dataset.ftcVisualPending;
              el.classList.add('has-static-fallback');
            }
          }, 50);
        }
      }
      if('IntersectionObserver' in window){
        var isDetailHost = !!el.closest('.ftc-service-detail-webgl');
        var rect = el.getBoundingClientRect();
        var inView = rect.bottom > -40 && rect.top < (window.innerHeight + 40) && rect.width > 20;
        if(inView || isDetailHost) bootServiceVisual();
        else {
          el._ftcVisualBootObserver = new IntersectionObserver(function(entries){
            if(entries[0] && entries[0].isIntersecting){
              bootServiceVisual();
            }
          }, {root: stream || null, rootMargin: '80px 0px', threshold: 0.02});
          el._ftcVisualBootObserver.observe(el);
        }
      } else {
        bootServiceVisual();
      }
    }
    function resizeServiceVisual(state){
      if(!state || !state.el || !state.renderer) return;
      const rect = state.el.getBoundingClientRect();
      const width = Math.max(1, Math.round(rect.width));
      const height = Math.max(1, Math.round(rect.height || rect.width * 0.5625));
      state.renderer.setSize(width, height, false);
      state.camera.aspect = width / height;
      state.camera.updateProjectionMatrix();
      if(state.marketingRubik && state.rubik && window.FTCRubikCube && window.FTCRubikCube.fitCamera){
        window.FTCRubikCube.fitCamera(state, state.rubik);
      }
    }
    function serviceMaterial(THREE, color, opacity, wireframe){
      const Material = THREE.MeshPhysicalMaterial || THREE.MeshStandardMaterial;
      return new Material({
        color: color,
        emissive: color,
        emissiveIntensity: 0.05,
        roughness: 0.26,
        metalness: 0.22,
        clearcoat: 0.35,
        clearcoatRoughness: 0.38,
        transparent: opacity < 1,
        opacity: opacity,
        wireframe: !!wireframe,
        side: THREE.DoubleSide || undefined
      });
    }
    function serviceLine(THREE, points, color, opacity){
      const geo = new THREE.BufferGeometry().setFromPoints(points);
      const mat = new THREE.LineBasicMaterial({color: color, transparent: opacity < 1, opacity: opacity});
      return new THREE.Line(geo, mat);
    }
    function serviceRect(THREE, w, h, x, y, z, color){
      return serviceLine(THREE, [
        new THREE.Vector3(-w/2 + x, -h/2 + y, z),
        new THREE.Vector3(w/2 + x, -h/2 + y, z),
        new THREE.Vector3(w/2 + x, h/2 + y, z),
        new THREE.Vector3(-w/2 + x, h/2 + y, z),
        new THREE.Vector3(-w/2 + x, -h/2 + y, z)
      ], color, 0.92);
    }
    function serviceTube(THREE, points, color, radius, opacity){
      const curve = new THREE.CatmullRomCurve3(points);
      const mesh = new THREE.Mesh(new THREE.TubeGeometry(curve, 72, radius || 0.025, 10, false), serviceMaterial(THREE, color, opacity || 0.92, false));
      return mesh;
    }
    function serviceEdges(THREE, mesh, color, opacity){
      const edges = new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry), new THREE.LineBasicMaterial({color: color, transparent: opacity < 1, opacity: opacity || 0.9}));
      edges.position.copy(mesh.position);
      edges.rotation.copy(mesh.rotation);
      edges.scale.copy(mesh.scale);
      return edges;
    }
    function serviceDevice(THREE, w, h, d, x, y, z, color){
      const wrap = new THREE.Group();
      const frame = new THREE.Mesh(new THREE.BoxGeometry(w,h,d), serviceMaterial(THREE, color, 0.24, false));
      frame.position.set(x,y,z);
      frame.castShadow = true;
      frame.receiveShadow = true;
      wrap.add(frame);
      const edges = serviceEdges(THREE, frame, color, 0.96);
      wrap.add(edges);
      const screen = new THREE.Mesh(new THREE.PlaneGeometry(w*0.78,h*0.7), serviceMaterial(THREE, 0x10141f, 0.5, false));
      screen.position.set(x,y,z + d/2 + 0.012);
      wrap.add(screen);
      for(let i=0;i<3;i++){
        const bar = new THREE.Mesh(new THREE.BoxGeometry(w*(0.18 + i*.1),0.025,0.018), serviceMaterial(THREE, i === 1 ? 0xffd94d : 0x8fb5ff, 0.8, false));
        bar.position.set(x - w*.22 + i*w*.12, y + h*(0.16 - i*.13), z + d/2 + 0.03);
        wrap.add(bar);
      }
      return wrap;
    }
    function buildServiceScenePrototype(state, THREE){
      const blue = 0x397cf6, sky = 0x8fb5ff, yellow = 0xffd94d, red = 0xff5b45, green = 0x69d85b;
      const group = state.group;
      const key = state.key;
      if(key === 'web'){
        const desktop = serviceDevice(THREE, 3.25, 1.9, 0.12, -0.38, 0.08, 0, blue);
        const tablet = serviceDevice(THREE, 1.15, 1.82, 0.13, 1.42, -0.05, 0.72, yellow);
        const phone = serviceDevice(THREE, 0.68, 1.32, 0.14, -1.88, -0.18, 0.9, red);
        tablet.rotation.y = -0.22;
        phone.rotation.y = 0.34;
        group.add(desktop, tablet, phone);
        group.add(serviceTube(THREE, [new THREE.Vector3(-1.3,-1.08,0), new THREE.Vector3(-.4,-1.34,.2), new THREE.Vector3(.55,-1.12,.4), new THREE.Vector3(1.5,-1.32,.6)], sky, .018, .72));
      } else if(key === 'commerce'){
        for(let i=0;i<5;i++){
          const box = new THREE.Mesh(new THREE.BoxGeometry(0.72,0.72,0.72,2,2,2), serviceMaterial(THREE, [blue,yellow,red,sky,green][i], 0.78, false));
          box.position.set((i-2)*0.58, Math.sin(i)*0.34, (i%2)*0.55);
          box.userData.spin = 0.4 + i * 0.09;
          box.castShadow = true;
          box.receiveShadow = true;
          group.add(box);
          group.add(serviceEdges(THREE, box, 0xffffff, .2));
        }
        const ring = new THREE.Mesh(new THREE.TorusGeometry(1.82,0.035,14,96), serviceMaterial(THREE, yellow, 0.86, false));
        ring.rotation.x = Math.PI / 2.8;
        group.add(ring);
        group.add(serviceTube(THREE, [new THREE.Vector3(-1.9,-1.05,.1), new THREE.Vector3(-.8,-1.35,.3), new THREE.Vector3(.7,-1.22,.4), new THREE.Vector3(1.86,-.9,.5)], green, .025, .8));
      } else if(key === 'data'){
        const pts = [];
        for(let i=0;i<8;i++){
          const h = 0.45 + ((i * 17) % 7) * 0.2;
          const bar = new THREE.Mesh(new THREE.BoxGeometry(0.22,h,0.22), serviceMaterial(THREE, i%2 ? sky : yellow, 0.86, false));
          bar.position.set((i-3.5)*0.46, -0.75 + h/2, 0);
          bar.userData.baseY = bar.position.y;
          bar.castShadow = true;
          bar.receiveShadow = true;
          group.add(bar);
          pts.push(new THREE.Vector3((i-3.5)*0.46, -0.15 + Math.sin(i*.8)*0.55, 0.45));
        }
        group.add(serviceTube(THREE, pts, blue, .022, .95));
        pts.forEach(function(pt, i){
          const node = new THREE.Mesh(new THREE.SphereGeometry(0.07,18,18), serviceMaterial(THREE, i%2 ? red : sky, .92, false));
          node.position.copy(pt);
          node.castShadow = true;
          group.add(node);
        });
      } else if(key === 'search'){
        for(let i=0;i<4;i++){
          const ring = new THREE.Mesh(new THREE.TorusGeometry(0.78 + i*0.34,0.022,10,96), serviceMaterial(THREE, i%2 ? sky : blue, 0.68, false));
          ring.rotation.x = Math.PI / 2 + i*.16;
          ring.rotation.y = i*.33;
          ring.userData.spin = 0.24 + i*.07;
          group.add(ring);
        }
        for(let i=0;i<11;i++){
          const node = new THREE.Mesh(new THREE.SphereGeometry(0.055,16,16), serviceMaterial(THREE, i%3 ? yellow : red, 0.9, false));
          node.position.set(Math.cos(i*1.7)*1.55, Math.sin(i*1.2)*0.88, Math.sin(i)*0.75);
          node.userData.float = i * 0.4;
          node.castShadow = true;
          group.add(node);
        }
      } else if(key === 'marketing'){
        for(let i=0;i<5;i++){
          const ring = new THREE.Mesh(new THREE.TorusGeometry(0.48 + i*0.32,0.032,12,96), serviceMaterial(THREE, [red,yellow,blue,sky,green][i], 0.68, false));
          ring.rotation.x = Math.PI / 2.8;
          ring.position.y = (i-2)*0.1;
          ring.userData.spin = (i%2 ? -1 : 1) * (0.12 + i*.035);
          group.add(ring);
        }
        const cone = new THREE.Mesh(new THREE.ConeGeometry(0.55,1.18,5,1,true), serviceMaterial(THREE, yellow, 0.28, true));
        cone.position.set(0,-0.1,0.5);
        cone.castShadow = true;
        group.add(cone);
        group.add(serviceTube(THREE, [new THREE.Vector3(-1.7,.75,.2), new THREE.Vector3(-.6,.2,.7), new THREE.Vector3(.2,.55,.35), new THREE.Vector3(1.55,-.28,.65)], red, .026, .78));
      } else {
        const core = new THREE.Mesh(new THREE.IcosahedronGeometry(1.32,1), serviceMaterial(THREE, blue, 0.2, false));
        core.castShadow = true;
        core.receiveShadow = true;
        group.add(core);
        const wire = new THREE.LineSegments(new THREE.WireframeGeometry(new THREE.IcosahedronGeometry(1.55,2)), new THREE.LineBasicMaterial({color: sky, transparent:true, opacity:.86}));
        wire.userData.spin = 0.18;
        group.add(wire);
        const knot = new THREE.Mesh(new THREE.TorusKnotGeometry(0.78,0.045,140,10), serviceMaterial(THREE, yellow, 0.86, false));
        knot.userData.spin = -0.23;
        knot.castShadow = true;
        group.add(knot);
        for(let i=0;i<18;i++){
          const p = new THREE.Mesh(new THREE.SphereGeometry(0.028 + (i%3)*.01,10,10), serviceMaterial(THREE, i%2 ? sky : red, .74, false));
          p.position.set(Math.cos(i*2.11)*2.05, Math.sin(i*1.47)*1.12, Math.sin(i*.82)*1.18);
          p.userData.float = i*.37;
          group.add(p);
        }
      }
      group.traverse(function(obj){
        if(obj.isMesh){
          obj.castShadow = obj.castShadow || key !== 'search';
          obj.receiveShadow = obj.receiveShadow || false;
        }
      });
    }
    function blueprintLineMaterial(THREE, color, opacity){
      return new THREE.LineBasicMaterial({
        color: color,
        transparent: true,
        opacity: opacity == null ? 0.78 : opacity,
        depthWrite: false,
        blending: THREE.AdditiveBlending || THREE.NormalBlending
      });
    }
    function blueprintMeshMaterial(THREE, color, opacity){
      return new THREE.MeshBasicMaterial({
        color: color,
        transparent: true,
        opacity: opacity == null ? 0.035 : opacity,
        depthWrite: false,
        side: THREE.DoubleSide || undefined,
        blending: THREE.AdditiveBlending || THREE.NormalBlending
      });
    }
    function blueprintLine(THREE, group, points, color, opacity){
      const geo = new THREE.BufferGeometry().setFromPoints(points);
      const line = new THREE.Line(geo, blueprintLineMaterial(THREE, color, opacity));
      group.add(line);
      return line;
    }
    function blueprintSegments(THREE, group, segments, color, opacity){
      const points = [];
      segments.forEach(function(pair){
        points.push(pair[0], pair[1]);
      });
      const geo = new THREE.BufferGeometry().setFromPoints(points);
      const line = new THREE.LineSegments(geo, blueprintLineMaterial(THREE, color, opacity));
      group.add(line);
      return line;
    }
    function blueprintLoop(THREE, group, points, color, opacity){
      const geo = new THREE.BufferGeometry().setFromPoints(points);
      const line = new THREE.LineLoop(geo, blueprintLineMaterial(THREE, color, opacity));
      group.add(line);
      return line;
    }
    function blueprintCircle(THREE, group, radius, color, opacity, x, y, z, rx, ry, rz, segments){
      const pts = [];
      const total = segments || 96;
      for(let i=0;i<total;i++){
        const a = (i / total) * Math.PI * 2;
        pts.push(new THREE.Vector3(Math.cos(a) * radius, Math.sin(a) * radius, 0));
      }
      const line = blueprintLoop(THREE, group, pts, color, opacity);
      line.position.set(x || 0, y || 0, z || 0);
      line.rotation.set(rx || 0, ry || 0, rz || 0);
      return line;
    }
    function blueprintBox(THREE, group, w, h, d, x, y, z, color, opacity, fillOpacity){
      const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), blueprintMeshMaterial(THREE, color, fillOpacity == null ? 0.03 : fillOpacity));
      mesh.position.set(x || 0, y || 0, z || 0);
      group.add(mesh);
      const edges = new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry), blueprintLineMaterial(THREE, color, opacity == null ? 0.86 : opacity));
      edges.position.copy(mesh.position);
      group.add(edges);
      return {mesh: mesh, edges: edges};
    }
    function blueprintGrid(THREE, group, width, height, cols, rows, z, color, opacity){
      const segs = [];
      for(let i=0;i<=cols;i++){
        const x = -width/2 + (width * i / cols);
        segs.push([new THREE.Vector3(x,-height/2,z), new THREE.Vector3(x,height/2,z)]);
      }
      for(let j=0;j<=rows;j++){
        const y = -height/2 + (height * j / rows);
        segs.push([new THREE.Vector3(-width/2,y,z), new THREE.Vector3(width/2,y,z)]);
      }
      const grid = blueprintSegments(THREE, group, segs, color, opacity);
      grid.userData.breath = 0.15;
      return grid;
    }
    function blueprintGear(THREE, group, radius, teeth, x, y, z, color, opacity){
      const pts = [];
      const total = teeth * 2;
      for(let i=0;i<total;i++){
        const a = (i / total) * Math.PI * 2;
        const r = radius * (i % 2 ? 0.86 : 1.08);
        pts.push(new THREE.Vector3(Math.cos(a) * r, Math.sin(a) * r, 0));
      }
      const outer = blueprintLoop(THREE, group, pts, color, opacity);
      outer.position.set(x,y,z);
      outer.userData.spin = 0.16;
      blueprintCircle(THREE, outer, radius * 0.52, color, opacity * 0.78, 0, 0, 0, 0, 0, 0, 72);
      blueprintCircle(THREE, outer, radius * 0.18, 0xffffff, opacity * 0.52, 0, 0, 0, 0, 0, 0, 48);
      for(let i=0;i<6;i++){
        const a = i / 6 * Math.PI * 2;
        blueprintLine(THREE, outer, [
          new THREE.Vector3(Math.cos(a) * radius * .25, Math.sin(a) * radius * .25, 0),
          new THREE.Vector3(Math.cos(a) * radius * .74, Math.sin(a) * radius * .74, 0)
        ], color, opacity * .46);
      }
      return outer;
    }
    function blueprintDimension(THREE, group, a, b, color, opacity){
      const line = blueprintLine(THREE, group, [a,b], color, opacity);
      const tick = 0.08;
      blueprintSegments(THREE, group, [
        [new THREE.Vector3(a.x, a.y - tick, a.z), new THREE.Vector3(a.x, a.y + tick, a.z)],
        [new THREE.Vector3(b.x, b.y - tick, b.z), new THREE.Vector3(b.x, b.y + tick, b.z)]
      ], color, opacity * 0.75);
      return line;
    }
    function blueprintNodeField(THREE, group, count, radius, colorA, colorB){
      for(let i=0;i<count;i++){
        const a = i * 2.399963;
        const r = radius * Math.sqrt((i + .5) / count);
        const dot = new THREE.Mesh(new THREE.SphereGeometry(0.022 + (i % 4) * 0.004, 8, 8), blueprintMeshMaterial(THREE, i % 3 ? colorA : colorB, 0.62));
        dot.position.set(Math.cos(a) * r, Math.sin(a) * r * .56, Math.sin(i * .73) * .58);
        dot.userData.float = i * .23;
        group.add(dot);
      }
    }
    function blueprintWireText(THREE, group, x, y, z, width, rows, color, opacity, gap){
      const rowGap = gap || 0.1;
      for(let i=0;i<rows;i++){
        const len = width * (0.52 + (((i * 37) % 38) / 100));
        blueprintLine(THREE, group, [
          new THREE.Vector3(x, y - i * rowGap, z),
          new THREE.Vector3(x + len, y - i * rowGap, z)
        ], color, opacity || 0.38);
      }
    }
    function blueprintUiCard(THREE, group, x, y, z, w, h, color, opacity){
      const card = blueprintBox(THREE, group, w, h, 0.006, x, y, z, color, opacity || 0.38, 0.004);
      card.mesh.userData.float = Math.abs(x * 0.31 + y * 0.27);
      card.edges.userData.float = card.mesh.userData.float;
      return card;
    }
    function blueprintMiniChart(THREE, group, x, y, z, w, h, color, accent){
      blueprintUiCard(THREE, group, x, y, z, w, h, color, 0.34);
      for(let i=0;i<6;i++){
        const bh = h * (0.18 + (((i * 19) % 7) / 10));
        blueprintBox(THREE, group, w * 0.08, bh, 0.006, x - w * 0.34 + i * w * 0.13, y - h * 0.36 + bh / 2, z + 0.01, i % 2 ? color : accent, 0.5, 0.006);
      }
      blueprintLine(THREE, group, [
        new THREE.Vector3(x - w * 0.4, y - h * 0.1, z + 0.018),
        new THREE.Vector3(x - w * 0.22, y + h * 0.18, z + 0.018),
        new THREE.Vector3(x, y - h * 0.02, z + 0.018),
        new THREE.Vector3(x + w * 0.2, y + h * 0.24, z + 0.018),
        new THREE.Vector3(x + w * 0.42, y + h * 0.06, z + 0.018)
      ], accent, 0.54);
    }
    function blueprintDocumentIcon(THREE, group, x, y, z, color, accent){
      const doc = blueprintBox(THREE, group, 0.23, 0.32, 0.006, x, y, z, color, 0.52, 0.006);
      blueprintSegments(THREE, group, [
        [new THREE.Vector3(x + 0.055, y + 0.16, z + 0.014), new THREE.Vector3(x + 0.115, y + 0.1, z + 0.014)],
        [new THREE.Vector3(x + 0.115, y + 0.1, z + 0.014), new THREE.Vector3(x + 0.115, y + 0.16, z + 0.014)]
      ], accent || color, 0.58);
      blueprintWireText(THREE, group, x - 0.075, y + 0.035, z + 0.018, 0.13, 3, color, 0.48, 0.055);
      doc.mesh.userData.float = Math.abs(x + y);
      doc.edges.userData.float = doc.mesh.userData.float;
    }
    function blueprintArrowChevron(THREE, group, point, angle, color, opacity, size){
      const s = size || 0.14;
      const left = angle + Math.PI * 0.78;
      const right = angle - Math.PI * 0.78;
      blueprintSegments(THREE, group, [
        [point, new THREE.Vector3(point.x + Math.cos(left) * s, point.y + Math.sin(left) * s, point.z)],
        [point, new THREE.Vector3(point.x + Math.cos(right) * s, point.y + Math.sin(right) * s, point.z)]
      ], color, opacity || 0.62);
    }
    function blueprintFlowPath(THREE, group, points, color, opacity, radius){
      const tube = serviceTube(THREE, points, color, radius || 0.008, opacity || 0.56);
      tube.userData.float = 0.42;
      group.add(tube);
      for(let i=1;i<points.length;i++){
        const p = points[i];
        const prev = points[i - 1];
        const angle = Math.atan2(p.y - prev.y, p.x - prev.x);
        if(i === points.length - 1 || i % 2 === 0) blueprintArrowChevron(THREE, group, p, angle, color, (opacity || 0.56) + 0.08, 0.11);
      }
      return tube;
    }
    function blueprintKeyboard(THREE, parent, x, y, z, color, accent){
      const keys = new THREE.Group();
      keys.position.set(x, y, z);
      keys.rotation.x = -0.16;
      keys.rotation.z = -0.02;
      parent.add(keys);
      blueprintBox(THREE, keys, 1.38, 0.38, 0.035, 0, 0, 0, color, 0.42, 0.006);
      for(let row=0; row<4; row++){
        const count = row === 3 ? 9 : 11;
        const rowWidth = count * 0.096;
        for(let col=0; col<count; col++){
          const w = row === 3 && col === 4 ? 0.22 : 0.074;
          blueprintBox(THREE, keys, w, 0.048, 0.012, -rowWidth / 2 + col * 0.096 + 0.048, 0.12 - row * 0.075, 0.035, (row + col) % 5 === 0 ? accent : color, 0.38, 0.005);
        }
      }
      keys.userData.float = 0.74;
      return keys;
    }
    function blueprintScreenDevice(THREE, parent, opts){
      const wrap = new THREE.Group();
      wrap.position.set(opts.x || 0, opts.y || 0, opts.z || 0);
      wrap.rotation.set(opts.rx || 0, opts.ry || 0, opts.rz || 0);
      wrap.scale.setScalar(opts.scale || 1);
      wrap.userData.float = opts.float || 0;
      parent.add(wrap);

      const w = opts.w;
      const h = opts.h;
      const d = opts.d || 0.07;
      const color = opts.color;
      const accent = opts.accent;
      const front = d / 2 + 0.018;
      blueprintBox(THREE, wrap, w, h, d, 0, 0, 0, color, 0.72, 0.012);
      blueprintUiCard(THREE, wrap, 0, -h * 0.02, front, w * 0.84, h * 0.75, 0xe9f1ff, 0.2);
      blueprintLine(THREE, wrap, [new THREE.Vector3(-w * 0.42, h * 0.33, front + 0.018), new THREE.Vector3(w * 0.42, h * 0.33, front + 0.018)], color, 0.34);
      for(let i=0;i<3;i++) blueprintCircle(THREE, wrap, 0.025, i === 1 ? accent : color, 0.48, -w * 0.36 + i * 0.07, h * 0.38, front + 0.02, 0, 0, 0, 24);

      if(opts.variant === 'dashboard'){
        blueprintMiniChart(THREE, wrap, -w * 0.18, h * 0.1, front + 0.035, w * 0.34, h * 0.32, color, accent);
        blueprintMiniChart(THREE, wrap, w * 0.2, -h * 0.14, front + 0.035, w * 0.36, h * 0.28, accent, color);
        blueprintUiCard(THREE, wrap, w * 0.25, h * 0.14, front + 0.035, w * 0.3, h * 0.28, color, 0.3);
        blueprintCircle(THREE, wrap, h * 0.08, accent, 0.48, w * 0.26, h * 0.16, front + 0.048, 0, 0, 0, 48);
        blueprintWireText(THREE, wrap, w * 0.09, h * 0.03, front + 0.05, w * 0.28, 5, 0xe9f1ff, 0.42, h * 0.05);
        blueprintUiCard(THREE, wrap, -w * 0.32, -h * 0.22, front + 0.035, w * 0.22, h * 0.22, accent, 0.26);
        blueprintWireText(THREE, wrap, -w * 0.38, -h * 0.18, front + 0.05, w * 0.16, 4, color, 0.48, h * 0.045);
      } else if(opts.variant === 'project'){
        for(let col=0; col<3; col++){
          blueprintUiCard(THREE, wrap, -w * 0.24 + col * w * 0.22, h * 0.03, front + 0.035, w * 0.18, h * 0.48, col === 1 ? accent : color, 0.28);
          for(let row=0; row<4; row++){
            blueprintBox(THREE, wrap, w * 0.13, h * 0.035, 0.006, -w * 0.24 + col * w * 0.22, h * 0.2 - row * h * 0.1, front + 0.05, row % 2 ? 0xe9f1ff : color, 0.36, 0.004);
          }
        }
        blueprintWireText(THREE, wrap, -w * 0.38, -h * 0.3, front + 0.046, w * 0.72, 3, 0xe9f1ff, 0.42, h * 0.06);
      } else {
        blueprintCircle(THREE, wrap, w * 0.08, color, 0.56, 0, h * 0.28, front + 0.04, 0, 0, 0, 40);
        blueprintWireText(THREE, wrap, -w * 0.24, h * 0.1, front + 0.046, w * 0.48, 4, accent, 0.48, h * 0.075);
        for(let i=0;i<3;i++) blueprintUiCard(THREE, wrap, 0, -h * 0.09 - i * h * 0.11, front + 0.035, w * 0.54, h * 0.07, i === 1 ? color : 0xe9f1ff, 0.3);
        blueprintCircle(THREE, wrap, w * 0.035, accent, 0.5, 0, -h * 0.4, front + 0.04, 0, 0, 0, 32);
      }
      return wrap;
    }
    function selectiveDataPoint(radius, theta, phi){
      return [
        radius * Math.sin(theta) * Math.cos(phi),
        radius * Math.cos(theta),
        radius * Math.sin(theta) * Math.sin(phi)
      ];
    }
    function pushSelectiveDataLine(THREE, positions, colors, visible, a, b, hue, sat, light){
      const color = new THREE.Color();
      color.setHSL(hue % 1, sat == null ? 1 : sat, light == null ? 0.58 : light);
      positions.push(a[0], a[1], a[2], b[0], b[1], b[2]);
      colors.push(color.r, color.g, color.b, color.r, color.g, color.b);
      visible.push(1, 1);
    }
    function createDataSchematicLabel(THREE, text, color){
      const canvas = document.createElement('canvas');
      canvas.width = 256;
      canvas.height = 128;
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.font = '700 44px ui-monospace, SFMono-Regular, Consolas, monospace';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = 'rgba(0,0,0,0.42)';
      ctx.fillRect(26, 30, 132, 56);
      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.globalAlpha = 0.82;
      ctx.beginPath();
      ctx.moveTo(14, 64);
      ctx.lineTo(36, 64);
      ctx.moveTo(156, 64);
      ctx.lineTo(226, 64);
      ctx.stroke();
      ctx.globalAlpha = 1;
      ctx.fillStyle = color;
      ctx.fillText(text, 40, 62);
      ctx.fillStyle = 'rgba(255,255,255,0.62)';
      ctx.fillRect(168, 60, 8, 8);
      const texture = new THREE.CanvasTexture(canvas);
      texture.minFilter = THREE.LinearFilter;
      texture.magFilter = THREE.LinearFilter;
      texture.generateMipmaps = false;
      texture.needsUpdate = true;
      const material = new THREE.SpriteMaterial({
        map: texture,
        transparent: true,
        opacity: 0,
        depthTest: true,
        depthWrite: false
      });
      const sprite = new THREE.Sprite(material);
      sprite.scale.set(0.3, 0.15, 1);
      sprite.renderOrder = 6;
      return sprite;
    }
    function addSelectiveDataLabels(state, THREE, radius){
      const labelGroup = new THREE.Group();
      labelGroup.userData.selectiveDataLabels = true;
      const specs = [
        ['017', .16, .08, '#ffe169'],
        ['042', .29, .74, '#72f6ff'],
        ['089', .41, 1.38, '#8fb5ff'],
        ['144', .54, 2.14, '#ff72d9'],
        ['233', .68, 2.86, '#d7ff63'],
        ['377', .78, 3.58, '#72f6ff'],
        ['610', .35, 4.35, '#ffe169'],
        ['987', .22, 5.16, '#ff72d9']
      ];
      specs.forEach(function(spec, index){
        const theta = Math.PI * spec[1];
        const phi = Math.PI * 2 * spec[2];
        const pos = selectiveDataPoint(radius * 1.06, theta, phi);
        const sprite = createDataSchematicLabel(THREE, spec[0], spec[3]);
        sprite.position.set(pos[0], pos[1], pos[2]);
        sprite.userData.labelIndex = index;
        sprite.userData.baseScaleX = 0.3;
        sprite.userData.baseScaleY = 0.15;
        labelGroup.add(sprite);
      });
      state.dataLabels = {
        group: labelGroup,
        sprites: labelGroup.children,
        cycle: 3.6
      };
      state.group.add(labelGroup);
    }
    function updateSelectiveDataLabels(state, t){
      const labels = state.dataLabels;
      if(!labels || !labels.sprites || !labels.sprites.length) return;
      const cycle = labels.cycle || 3.6;
      const slot = Math.floor(t / cycle) % labels.sprites.length;
      const phase = (t % cycle) / cycle;
      const fadeIn = Math.min(1, phase * 4.8);
      const fadeOut = Math.min(1, (1 - phase) * 4.2);
      const fadeA = Math.max(0, Math.min(fadeIn, fadeOut));
      labels.sprites.forEach(function(sprite, index){
        const opacity = index === slot ? fadeA * 0.82 : 0;
        sprite.material.opacity = opacity;
        const scale = 0.88 + opacity * 0.14;
        sprite.scale.set(sprite.userData.baseScaleX * scale, sprite.userData.baseScaleY * scale, 1);
      });
    }
    function buildSelectiveDrawDataScene(state, THREE){
      const group = state.group;
      const isPreview = state.el && !state.el.closest('.ftc-service-detail-webgl');
      const isMobileDetail = !isPreview && window.matchMedia && window.matchMedia('(max-width: 760px)').matches;
      const radius = 1.0;
      const numLat = isPreview ? 40 : (isMobileDetail ? 40 : 52);
      const numLng = isPreview ? 72 : (isMobileDetail ? 72 : 96);
      const lineCount = numLat * numLng;
      const linePositions = new Float32Array(lineCount * 3 * 2);
      const lineColors = new Float32Array(lineCount * 3 * 2);
      const visible = new Float32Array(lineCount * 2);
      const color = new THREE.Color(0xffffff);

      for(let i=0; i<numLat; i++){
        for(let j=0; j<numLng; j++){
          const lat = (Math.random() * Math.PI) / 50.0 + i / numLat * Math.PI;
          const lng = (Math.random() * Math.PI) / 50.0 + j / numLng * 2 * Math.PI;
          const index = i * numLng + j;

          linePositions[index * 6 + 0] = 0;
          linePositions[index * 6 + 1] = 0;
          linePositions[index * 6 + 2] = 0;
          linePositions[index * 6 + 3] = radius * Math.sin(lat) * Math.cos(lng);
          linePositions[index * 6 + 4] = radius * Math.cos(lat);
          linePositions[index * 6 + 5] = radius * Math.sin(lat) * Math.sin(lng);

          color.setHSL(lat / Math.PI, 1.0, 0.2);
          lineColors[index * 6 + 0] = color.r;
          lineColors[index * 6 + 1] = color.g;
          lineColors[index * 6 + 2] = color.b;

          color.setHSL(lat / Math.PI, 1.0, 0.7);
          lineColors[index * 6 + 3] = color.r;
          lineColors[index * 6 + 4] = color.g;
          lineColors[index * 6 + 5] = color.b;

          visible[index * 2 + 0] = 1.0;
          visible[index * 2 + 1] = 1.0;
        }
      }

      const geometry = new THREE.BufferGeometry();
      geometry.setAttribute('position', new THREE.BufferAttribute(linePositions, 3));
      geometry.setAttribute('vertColor', new THREE.BufferAttribute(lineColors, 3));
      const visibleAttribute = new THREE.BufferAttribute(visible, 1);
      geometry.setAttribute('visible', visibleAttribute);
      geometry.computeBoundingSphere();

      const material = new THREE.ShaderMaterial({
        vertexShader: [
          'attribute float visible;',
          'attribute vec3 vertColor;',
          'varying vec3 vColor;',
          'varying float vVisible;',
          'void main() {',
          '  vColor = vertColor;',
          '  vVisible = visible;',
          '  gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );',
          '}'
        ].join('\n'),
        fragmentShader: [
          'varying vec3 vColor;',
          'varying float vVisible;',
          'void main() {',
          '  if ( vVisible <= 0.0 ) discard;',
          '  gl_FragColor = vec4( vColor, 1.0 );',
          '}'
        ].join('\n')
      });

      const lineSegments = new THREE.LineSegments(geometry, material);
      lineSegments.userData.selectiveDrawData = true;
      lineSegments.userData.noOpacityPulse = true;
      lineSegments.frustumCulled = false;
      group.add(lineSegments);
      if(isPreview) addSelectiveDataLabels(state, THREE, radius);

      state.visualScale = state.el && state.el.closest('.ftc-service-detail-webgl') ? 2.35 : 1.78;
      state.dataSelective = {
        visible: visible,
        attribute: visibleAttribute,
        lastFrame: -1,
        lastMode: '',
        lineCount: lineCount
      };
      group.userData.schematic = false;
      initDataStreamCounters(state);
    }
    function initDataStreamCounters(state){
      if(state._dataCountersInit) return;
      state._dataCountersInit = true;
      state._stopDataCounters = attachDataStreamCounters(state.el, {
        appRoot: app,
        isDetail: !state.isPreview
      });
    }
    function updateSelectiveDrawData(state, t, scrollNorm){
      const data = state.dataSelective;
      if(!data || !data.visible || !data.attribute) return;
      const activity = Math.min(1, Math.abs(state.mouseX) + Math.abs(state.mouseY) + Math.abs(state.dragX || 0) * 0.35 + Math.abs(state.dragY || 0) * 0.35);
      const frame = Math.floor(t * 16);
      if(activity < 0.055){
        if(data.lastMode === 'all') return;
        data.visible.fill(1);
        data.attribute.needsUpdate = true;
        data.lastMode = 'all';
        return;
      }
      if(frame === data.lastFrame) return;
      const keepEvery = 2 + Math.floor(activity * 7);
      const phase = Math.floor((t * 9 + scrollNorm * 18) % keepEvery);
      for(let i=0; i<data.visible.length; i+=2){
        const lineIndex = i / 2;
        const shown = ((lineIndex + phase) % keepEvery) !== 0 ? 1 : 0;
        data.visible[i] = shown;
        data.visible[i + 1] = shown;
      }
      data.attribute.needsUpdate = true;
      data.lastFrame = frame;
      data.lastMode = 'cull-' + keepEvery + '-' + phase;
    }
    function abstractLineMaterial(THREE, opacity){
      return new THREE.ShaderMaterial({
        uniforms: { uOpacity: { value: opacity == null ? 0.86 : opacity } },
        vertexShader: [
          'attribute vec3 vertColor;',
          'varying vec3 vColor;',
          'void main() {',
          '  vColor = vertColor;',
          '  gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );',
          '}'
        ].join('\n'),
        fragmentShader: [
          'uniform float uOpacity;',
          'varying vec3 vColor;',
          'void main() {',
          '  gl_FragColor = vec4( vColor, uOpacity );',
          '}'
        ].join('\n'),
        transparent: true,
        depthWrite: false,
        blending: THREE.NormalBlending
      });
    }
    function semanticPushSegment(THREE, positions, colors, a, b, hue, light, hueB, lightB){
      const color = new THREE.Color();
      color.setHSL(hue % 1, 0.88, light == null ? 0.54 : light);
      positions.push(a[0], a[1], a[2]);
      colors.push(color.r, color.g, color.b);
      color.setHSL((hueB == null ? hue : hueB) % 1, 0.92, lightB == null ? 0.64 : lightB);
      positions.push(b[0], b[1], b[2]);
      colors.push(color.r, color.g, color.b);
    }
    function semanticPolyline(THREE, positions, colors, points, hue, light, closed){
      for(let i=0;i<points.length - 1;i++){
        semanticPushSegment(THREE, positions, colors, points[i], points[i+1], hue + i * 0.006, light, hue + i * 0.006, light + 0.08);
      }
      if(closed && points.length > 2){
        semanticPushSegment(THREE, positions, colors, points[points.length - 1], points[0], hue, light, hue, light + 0.08);
      }
    }
    function semanticRect(THREE, positions, colors, cx, cy, cz, w, h, depth, hue, light){
      const z = cz || 0;
      const d = depth || 0;
      const p = [
        [cx - w/2, cy - h/2, z - d/2],
        [cx + w/2, cy - h/2, z - d/2],
        [cx + w/2, cy + h/2, z - d/2],
        [cx - w/2, cy + h/2, z - d/2]
      ];
      semanticPolyline(THREE, positions, colors, p, hue, light, true);
      if(d){
        const q = p.map(function(pt){ return [pt[0] + d * 0.18, pt[1] + d * 0.14, z + d/2]; });
        semanticPolyline(THREE, positions, colors, q, hue + 0.04, light - 0.03, true);
        for(let i=0;i<4;i++) semanticPushSegment(THREE, positions, colors, p[i], q[i], hue + 0.02, light - 0.02, hue + 0.04, light + 0.04);
      }
    }
    function semanticEllipse(THREE, positions, colors, cx, cy, cz, rx, ry, tilt, hue, light, segments, start, end){
      const total = segments || 96;
      const a0 = start == null ? 0 : start;
      const a1 = end == null ? Math.PI * 2 : end;
      const pts = [];
      for(let i=0;i<=total;i++){
        const a = a0 + (a1 - a0) * i / total;
        pts.push([cx + Math.cos(a) * rx, cy + Math.sin(a) * ry, cz + Math.sin(a) * (tilt || 0)]);
      }
      semanticPolyline(THREE, positions, colors, pts, hue, light, false);
    }
    function semanticLerp3(a, b, t){
      return [
        a[0] + (b[0] - a[0]) * t,
        a[1] + (b[1] - a[1]) * t,
        a[2] + (b[2] - a[2]) * t
      ];
    }
    function semanticBilerp3(a, b, c, d, u, v){
      const top = semanticLerp3(a, b, u);
      const bottom = semanticLerp3(d, c, u);
      return semanticLerp3(top, bottom, v);
    }
    function semanticFaceGrid(THREE, positions, colors, corners, palette){
      const a = corners[0], b = corners[1], c = corners[2], d = corners[3];
      semanticPolyline(THREE, positions, colors, [a,b,c,d], palette[0][0], 0.62, true);
      for(let i=1;i<3;i++){
        const u = i / 3;
        semanticPushSegment(THREE, positions, colors, semanticLerp3(a,d,u), semanticLerp3(b,c,u), palette[i % palette.length][0], 0.42, palette[i % palette.length][0], 0.58);
        semanticPushSegment(THREE, positions, colors, semanticLerp3(a,b,u), semanticLerp3(d,c,u), palette[(i+1) % palette.length][0], 0.42, palette[(i+1) % palette.length][0], 0.58);
      }
      for(let y=0;y<3;y++){
        for(let x=0;x<3;x++){
          const hue = palette[(x + y * 2) % palette.length][0];
          const v0 = y / 3, v1 = (y + 1) / 3;
          const u0 = x / 3, u1 = (x + 1) / 3;
          const p0 = semanticBilerp3(a,b,c,d,u0,v0);
          const p1 = semanticBilerp3(a,b,c,d,u1,v0);
          const p2 = semanticBilerp3(a,b,c,d,u1,v1);
          const p3 = semanticBilerp3(a,b,c,d,u0,v1);
          semanticPolyline(THREE, positions, colors, [p0,p1,p2,p3], hue, 0.52, true);
          if((x + y) % 2 === 0){
            semanticPushSegment(THREE, positions, colors, semanticLerp3(p0,p2,.16), semanticLerp3(p0,p2,.84), hue, 0.36, hue, 0.5);
          }
        }
      }
    }
    function semanticWebScene(THREE, positions, colors){
      semanticRect(THREE, positions, colors, -0.2, 0.12, 0.04, 2.05, 1.1, 0.18, 0.58, 0.52);
      semanticRect(THREE, positions, colors, -0.82, 0.02, 0.24, 0.82, 1.22, 0.08, 0.53, 0.56);
      semanticRect(THREE, positions, colors, 0.84, -0.06, 0.32, 0.48, 0.94, 0.08, 0.1, 0.58);
      for(let i=0;i<6;i++){
        const y = 0.48 - i * 0.14;
        semanticPushSegment(THREE, positions, colors, [-0.92,y,0.18], [-0.36 + (i%3)*0.12,y,0.18], i%2 ? 0.14 : 0.58, 0.48, 0.58, 0.64);
      }
      for(let i=0;i<5;i++){
        const x = -0.02 + i * 0.22;
        semanticRect(THREE, positions, colors, x, -0.3 + (i%2)*0.06, 0.2, 0.14, 0.16 + i*0.035, 0, i%2 ? 0.1 : 0.58, 0.5);
      }
      semanticPolyline(THREE, positions, colors, [[-1.28,-0.74,0.02],[-0.48,-0.94,0.08],[0.38,-0.82,0.2],[1.08,-0.96,0.3]], 0.56, 0.56, false);
    }
    function semanticCommerceScene(THREE, positions, colors){
      const rings = [
        [0,0.78,0,1.24,0.32,0.18],
        [0,0.42,0.08,0.95,0.25,0.14],
        [0,0.08,0.14,0.66,0.18,0.1],
        [0,-0.26,0.2,0.38,0.11,0.06]
      ];
      rings.forEach(function(r,i){
        semanticEllipse(THREE, positions, colors, r[0],r[1],r[2],r[3],r[4],r[5], 0.14 + i*.04, 0.55 + i*.04, 88);
      });
      for(let i=0;i<12;i++){
        const a = Math.PI * 2 * i / 12;
        semanticPushSegment(THREE, positions, colors,
          [Math.cos(a)*1.24,0.78 + Math.sin(a)*0.32,Math.sin(a)*0.18],
          [Math.cos(a)*0.38,-0.26 + Math.sin(a)*0.11,0.2 + Math.sin(a)*0.06],
          0.13,0.42,0.2,0.64);
      }
      semanticPolyline(THREE, positions, colors, [[-0.68,-0.72,0.2],[-0.38,-0.92,0.2],[0.55,-0.92,0.2],[0.76,-0.58,0.2],[0.94,-0.58,0.2]], 0.56, 0.52, false);
      semanticEllipse(THREE, positions, colors, -0.18, -1.04, 0.22, 0.09, 0.09, 0, 0.13, 0.62, 34);
      semanticEllipse(THREE, positions, colors, 0.5, -1.04, 0.22, 0.09, 0.09, 0, 0.13, 0.62, 34);
      semanticPolyline(THREE, positions, colors, [[-0.12,-0.44,0.24],[0.06,-0.58,0.24],[0.4,-0.2,0.24]], 0.3, 0.62, false);
    }
    function semanticSearchScene(THREE, positions, colors){
      semanticEllipse(THREE, positions, colors, -0.18, 0.1, 0.02, 0.84, 0.84, 0.16, 0.57, 0.58, 128);
      semanticEllipse(THREE, positions, colors, -0.18, 0.1, 0.08, 0.55, 0.55, 0.1, 0.61, 0.48, 96);
      semanticPushSegment(THREE, positions, colors, [0.46,-0.52,0.08], [1.16,-1.12,0.24], 0.12, 0.56, 0.58, 0.64);
      semanticPushSegment(THREE, positions, colors, [0.37,-0.63,0.04], [1.05,-1.22,0.16], 0.58, 0.48, 0.58, 0.6);
      for(let i=0;i<8;i++){
        const a = Math.PI * 2 * i / 8;
        const x = -0.18 + Math.cos(a)*0.36;
        const y = 0.1 + Math.sin(a)*0.36;
        semanticPushSegment(THREE, positions, colors, [x,y,0.18], [x + Math.cos(a)*0.16,y + Math.sin(a)*0.16,0.28], i%2?0.14:0.58, 0.48, 0.16, 0.64);
      }
      semanticPolyline(THREE, positions, colors, [[-0.74,0.08,0.2],[-0.42,0.34,0.26],[-0.08,0.0,0.28],[0.24,0.28,0.2]], 0.57, 0.55, false);
    }
    function semanticMarketingScene(THREE, positions, colors){
      const palette = [
        [0.61, 0.58],
        [0.13, 0.62],
        [0.03, 0.58],
        [0.31, 0.56],
        [0.52, 0.6]
      ];
      const front = [[-0.78,0.46,0.42],[0.32,0.28,0.68],[0.4,-0.78,0.32],[-0.82,-0.58,0.1]];
      const right = [[0.32,0.28,0.68],[1.08,0.56,0.02],[1.16,-0.46,-0.28],[0.4,-0.78,0.32]];
      const top = [[-0.78,0.46,0.42],[0.32,0.28,0.68],[1.08,0.56,0.02],[-0.14,0.84,-0.22]];
      semanticFaceGrid(THREE, positions, colors, front, [palette[0],palette[1],palette[2],palette[3]]);
      semanticFaceGrid(THREE, positions, colors, right, [palette[1],palette[3],palette[4],palette[0]]);
      semanticFaceGrid(THREE, positions, colors, top, [palette[2],palette[4],palette[1],palette[0]]);
      semanticPushSegment(THREE, positions, colors, [-1.04,-0.92,-0.1], [1.26,-0.66,-0.36], 0.13, 0.38, 0.61, 0.48);
      semanticPushSegment(THREE, positions, colors, [-.82,-.58,.1], [-1.04,-.92,-.1], 0.31, 0.42, 0.13, 0.54);
      semanticPushSegment(THREE, positions, colors, [1.16,-.46,-.28], [1.26,-.66,-.36], 0.61, 0.42, 0.13, 0.54);
    }
    function semanticInnovationScene(THREE, positions, colors){
      semanticRect(THREE, positions, colors, 0, 0, 0.18, 1.28, 1.0, 0.16, 0.58, 0.52);
      semanticRect(THREE, positions, colors, 0, 0, 0.34, 0.72, 0.54, 0.08, 0.13, 0.56);
      for(let i=0;i<7;i++){
        const x = -0.78 + i * 0.26;
        semanticPushSegment(THREE, positions, colors, [x,0.62,0.18], [x,0.86,0.18], i%2?0.58:0.13, 0.42, i%2?0.58:0.13, 0.62);
        semanticPushSegment(THREE, positions, colors, [x,-0.62,0.18], [x,-0.86,0.18], i%2?0.31:0.03, 0.42, i%2?0.31:0.03, 0.62);
      }
      for(let i=0;i<5;i++){
        const y = -0.42 + i * 0.21;
        semanticPushSegment(THREE, positions, colors, [-0.78,y,0.18], [-1.1,y + (i%2?0.12:-0.1),0.1], i%2?0.58:0.13, 0.42, i%2?0.31:0.52, 0.6);
        semanticPushSegment(THREE, positions, colors, [0.78,y,0.18], [1.08,y + (i%2?-0.12:0.1),0.1], i%2?0.52:0.03, 0.42, i%2?0.13:0.58, 0.6);
      }
      const nodes = [[-0.34,0.12,0.44],[-0.12,0.28,0.46],[0.16,0.2,0.48],[0.34,-0.04,0.46],[0.04,-0.26,0.5],[-0.28,-0.18,0.46]];
      nodes.forEach(function(n,i){
        semanticEllipse(THREE, positions, colors, n[0], n[1], n[2], 0.045, 0.045, 0, i%2?0.13:0.58, 0.62, 22);
        if(i<nodes.length-1) semanticPushSegment(THREE, positions, colors, n, nodes[i+1], i%2?0.13:0.58, 0.42, i%2?0.58:0.13, 0.62);
      });
      semanticPushSegment(THREE, positions, colors, nodes[0], nodes[4], 0.31, 0.42, 0.58, 0.58);
      semanticPushSegment(THREE, positions, colors, nodes[2], nodes[5], 0.52, 0.42, 0.13, 0.58);
      semanticEllipse(THREE, positions, colors, 0, 0.02, -0.02, 1.34, 0.52, 0.12, 0.58, 0.34, 110, Math.PI * .08, Math.PI * 1.16);
    }
    function addBarycentricCoordinates(THREE, geometry){
      const geo = geometry.index ? geometry.toNonIndexed() : geometry;
      const count = geo.getAttribute('position').count;
      const barycentric = [];
      for(let i=0;i<count;i+=3){
        barycentric.push(1,0,0, 0,1,0, 0,0,1);
      }
      geo.setAttribute('barycentric', new THREE.Float32BufferAttribute(barycentric, 3));
      geo.computeVertexNormals();
      return geo;
    }
    function crystalCoreMaterial(THREE){
      return new THREE.ShaderMaterial({
        uniforms:{
          uTime:{value:0},
          uBlue:{value:new THREE.Color(0x8fb5ff)},
          uWarm:{value:new THREE.Color(0xf5f1df)}
        },
        vertexShader:[
          'uniform float uTime;',
          'varying vec3 vNormal;',
          'varying vec3 vViewPosition;',
          'varying vec3 vObjectPosition;',
          'void main(){',
          '  vec3 displaced = position;',
          '  float pulse = sin(position.y * 2.4 + uTime * 0.42) * 0.018 + sin(position.x * 2.0 - uTime * 0.28) * 0.014;',
          '  displaced += normal * pulse;',
          '  vec4 mvPosition = modelViewMatrix * vec4(displaced, 1.0);',
          '  vNormal = normalize(normalMatrix * normal);',
          '  vViewPosition = -mvPosition.xyz;',
          '  vObjectPosition = displaced;',
          '  gl_Position = projectionMatrix * mvPosition;',
          '}'
        ].join('\n'),
        fragmentShader:[
          'uniform float uTime;',
          'uniform vec3 uBlue;',
          'uniform vec3 uWarm;',
          'varying vec3 vNormal;',
          'varying vec3 vViewPosition;',
          'varying vec3 vObjectPosition;',
          'float hash(vec3 p){',
          '  p = fract(p * 0.3183099 + vec3(0.1,0.2,0.3));',
          '  p *= 17.0;',
          '  return fract(p.x * p.y * p.z * (p.x + p.y + p.z));',
          '}',
          'float noise(vec3 x){',
          '  vec3 i = floor(x);',
          '  vec3 f = fract(x);',
          '  f = f * f * (3.0 - 2.0 * f);',
          '  return mix(mix(mix(hash(i + vec3(0,0,0)), hash(i + vec3(1,0,0)), f.x), mix(hash(i + vec3(0,1,0)), hash(i + vec3(1,1,0)), f.x), f.y), mix(mix(hash(i + vec3(0,0,1)), hash(i + vec3(1,0,1)), f.x), mix(hash(i + vec3(0,1,1)), hash(i + vec3(1,1,1)), f.x), f.y), f.z);',
          '}',
          'float fbm(vec3 p){',
          '  float v = 0.0;',
          '  float a = 0.5;',
          '  for(int i=0;i<5;i++){',
          '    v += noise(p) * a;',
          '    p = p * 2.03 + vec3(3.1,1.7,2.2);',
          '    a *= 0.52;',
          '  }',
          '  return v;',
          '}',
          'float grain(vec2 p){',
          '  return fract(sin(dot(p, vec2(12.9898,78.233))) * 43758.5453123);',
          '}',
          'void main(){',
          '  vec3 n = normalize(vNormal);',
          '  vec3 viewDir = normalize(vViewPosition);',
          '  float fresnel = pow(1.0 - abs(dot(n, viewDir)), 2.65);',
          '  vec3 cloudPos = vObjectPosition * 1.7 + vec3(uTime * 0.025, -uTime * 0.018, uTime * 0.02);',
          '  float clouds = fbm(cloudPos);',
          '  float veils = smoothstep(0.38, 0.78, clouds) * 0.72 + smoothstep(0.6, 0.92, fbm(cloudPos * 1.7 + 4.0)) * 0.28;',
          '  float g = grain(gl_FragCoord.xy + uTime * 32.0);',
          '  vec3 gray = vec3(0.07 + veils * 0.46 + g * 0.08);',
          '  vec3 tint = mix(uBlue, uWarm, veils * 0.34 + fresnel * 0.42);',
          '  vec3 color = gray * tint + fresnel * vec3(0.22,0.34,0.52);',
          '  gl_FragColor = vec4(color, 0.38 + fresnel * 0.2);',
          '}'
        ].join('\n'),
        transparent:true,
        depthWrite:false,
        side:THREE.DoubleSide,
        blending:THREE.NormalBlending
      });
    }
    function crystalWireMaterial(THREE){
      return new THREE.ShaderMaterial({
        uniforms:{
          uTime:{value:0},
          uColor:{value:new THREE.Color(0xf6f1df)},
          uCyan:{value:new THREE.Color(0x72f6ff)},
          uYellow:{value:new THREE.Color(0xffe169)},
          uPink:{value:new THREE.Color(0xff72d9)},
          uLime:{value:new THREE.Color(0xd7ff63)},
          uSky:{value:new THREE.Color(0x8fb5ff)}
        },
        vertexShader:[
          'attribute vec3 barycentric;',
          'uniform float uTime;',
          'varying vec3 vBarycentric;',
          'varying vec3 vNormal;',
          'varying vec3 vViewPosition;',
          'void main(){',
          '  vBarycentric = barycentric;',
          '  vec3 displaced = position;',
          '  float pulse = sin(position.y * 2.4 + uTime * 0.42) * 0.018 + sin(position.z * 2.0 + uTime * 0.26) * 0.012;',
          '  displaced += normal * pulse;',
          '  vec4 mvPosition = modelViewMatrix * vec4(displaced, 1.0);',
          '  vNormal = normalize(normalMatrix * normal);',
          '  vViewPosition = -mvPosition.xyz;',
          '  gl_Position = projectionMatrix * mvPosition;',
          '}'
        ].join('\n'),
        fragmentShader:[
          '#ifdef GL_OES_standard_derivatives',
          '#extension GL_OES_standard_derivatives : enable',
          '#endif',
          'uniform float uTime;',
          'uniform vec3 uColor;',
          'uniform vec3 uCyan;',
          'uniform vec3 uYellow;',
          'uniform vec3 uPink;',
          'uniform vec3 uLime;',
          'uniform vec3 uSky;',
          'varying vec3 vBarycentric;',
          'varying vec3 vNormal;',
          'varying vec3 vViewPosition;',
          'float edgeFactor(){',
          '  vec3 d = fwidth(vBarycentric);',
          '  vec3 a3 = smoothstep(vec3(0.0), d * 1.65, vBarycentric);',
          '  return min(min(a3.x, a3.y), a3.z);',
          '}',
          'void main(){',
          '  float line = 1.0 - edgeFactor();',
          '  vec3 n = normalize(vNormal);',
          '  float fresnel = pow(1.0 - abs(dot(n, normalize(vViewPosition))), 2.0);',
          '  float band = fract(n.y * 0.55 + n.x * 0.42 + n.z * 0.28 + uTime * 0.04);',
          '  vec3 dataA = mix(uCyan, uSky, smoothstep(0.0, 0.35, band));',
          '  vec3 dataB = mix(uYellow, uPink, smoothstep(0.35, 0.72, band));',
          '  vec3 dataC = mix(uLime, uCyan, smoothstep(0.72, 1.0, band));',
          '  vec3 dataTint = mix(dataA, mix(dataB, dataC, step(0.35, band)), step(0.0, band));',
          '  vec3 color = mix(uColor, dataTint, 0.72 + fresnel * 0.18);',
          '  float alpha = line * (0.74 + fresnel * 0.26);',
          '  gl_FragColor = vec4(color, alpha);',
          '}'
        ].join('\n'),
        transparent:true,
        depthWrite:false,
        side:THREE.DoubleSide,
        blending:THREE.NormalBlending,
        extensions:{derivatives:true}
      });
    }
    function colorEdgesFromPalette(THREE, geometry, palette, opacity){
      var positions = geometry.attributes.position;
      var colors = new Float32Array(positions.count * 3);
      var color = new THREE.Color();
      var i, x, y, z, idx;
      for(i = 0; i < positions.count; i++){
        x = positions.getX(i);
        y = positions.getY(i);
        z = positions.getZ(i);
        idx = Math.abs(Math.floor(((Math.atan2(y, x) / Math.PI) + 1) * 2.5 + (z + 1.2) * 1.4)) % palette.length;
        color.setHex(palette[idx]);
        colors[i * 3] = color.r;
        colors[i * 3 + 1] = color.g;
        colors[i * 3 + 2] = color.b;
      }
      geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
      return new THREE.LineBasicMaterial({
        vertexColors: true,
        transparent: true,
        opacity: opacity == null ? 0.78 : opacity,
        depthWrite: false
      });
    }
    function createPaletteEdgeLines(THREE, baseEdgesGeometry, palette, opacity){
      var geometry = baseEdgesGeometry.clone();
      var material = colorEdgesFromPalette(THREE, geometry, palette, opacity);
      return new THREE.LineSegments(geometry, material);
    }
    function buildTechnologyCrystalScene(state, THREE){
      const group = state.group;
      const root = new THREE.Group();
      root.rotation.set(-0.12, -0.28, 0.08);
      group.add(root);

      const dataPalette = [0x72f6ff, 0xffe169, 0xff72d9, 0xd7ff63, 0x8fb5ff, 0x397cf6];
      const isDetail = state.el && state.el.closest('.ftc-service-detail-webgl');
      const baseGeometry = new THREE.IcosahedronGeometry(isDetail ? 1.24 : 1.34, 1);
      const coreGeometry = addBarycentricCoordinates(THREE, baseGeometry);
      const coreMaterial = crystalCoreMaterial(THREE);
      const wireMaterial = crystalWireMaterial(THREE);
      const core = new THREE.Mesh(coreGeometry.clone(), coreMaterial);
      const wire = new THREE.Mesh(coreGeometry.clone(), wireMaterial);
      core.userData.technologyCrystal = true;
      wire.userData.technologyCrystal = true;
      core.userData.noOpacityPulse = true;
      wire.userData.noOpacityPulse = true;
      core.scale.set(1.0, 0.94, 0.98);
      wire.scale.copy(core.scale);
      const edgesGeometry = new THREE.EdgesGeometry(baseGeometry, 1);
      const edgeWhite = createPaletteEdgeLines(THREE, edgesGeometry, dataPalette, 0.84);
      const edgeBlue = createPaletteEdgeLines(THREE, edgesGeometry, dataPalette, 0.52);
      const edgeRed = createPaletteEdgeLines(THREE, edgesGeometry, dataPalette, 0.38);
      edgeBlue.userData.noOpacityPulse = true;
      edgeRed.userData.noOpacityPulse = true;
      edgeWhite.userData.noOpacityPulse = true;
      edgeWhite.scale.copy(core.scale);
      edgeBlue.scale.copy(core.scale);
      edgeRed.scale.copy(core.scale);
      edgeBlue.position.set(0.018, -0.01, 0.01);
      edgeRed.position.set(-0.018, 0.012, -0.008);
      root.add(core);
      root.add(wire);
      root.add(edgeBlue);
      root.add(edgeRed);
      root.add(edgeWhite);

      state.visualScale = isDetail ? 1.32 : 1.28;
      state.technologyCrystal = {
        root:root,
        core:core,
        wire:wire,
        uniforms:[coreMaterial.uniforms, wireMaterial.uniforms]
      };
      state.group.userData.schematic = false;
      return true;
    }
    function abstractServiceEndpoint(key, i, count, THREE){
      const u = (i + 0.5) / count;
      const lane = (i % 360) / 360;
      const jitter = Math.sin(i * 12.9898) * 43758.5453;
      const rand = jitter - Math.floor(jitter);
      const a = Math.PI * 2 * (u * 38 + rand * 0.12);
      let start = [0,0,0];
      let end = [0,0,0];
      let hue = 0.58;
      let light = 0.66;

      if(key === 'web'){
        const c = Math.cos(a);
        const s = Math.sin(a);
        const layer = (i % 5) / 4;
        const w = 0.72 + layer * 0.54;
        const h = 0.42 + layer * 0.31;
        const x = Math.sign(c || 1) * Math.pow(Math.abs(c), 0.34) * w;
        const y = Math.sign(s || 1) * Math.pow(Math.abs(s), 0.42) * h;
        const z = (layer - 0.5) * 0.72 + Math.sin(a * 2.0) * 0.08;
        start = [x * 0.08, y * 0.08, -0.2 + z * 0.1];
        end = [x, y, z];
        hue = 0.56 + layer * 0.08 + (i % 17 === 0 ? 0.58 : 0);
        light = i % 23 === 0 ? 0.78 : 0.62 + layer * 0.1;
      } else if(key === 'commerce'){
        const v = lane;
        const twist = Math.PI * 2 * (u * 72 + v * 1.4);
        const y = 1.02 - v * 2.04;
        const r = 0.18 + Math.pow(v, 0.48) * 1.05 * (1 - v * 0.18) + Math.sin(twist * 2.1) * 0.035;
        start = [Math.cos(twist) * 0.05, y * 0.18, Math.sin(twist) * 0.04];
        end = [Math.cos(twist) * r, y, Math.sin(twist) * r * 0.62];
        hue = 0.12 + v * 0.25 + (i % 29 === 0 ? 0.45 : 0);
        light = 0.55 + v * 0.22;
      } else if(key === 'search'){
        const v = lane;
        const r = 0.18 + Math.sqrt(v) * 1.16;
        const theta = Math.PI * 2 * (u * 54 + rand * 0.18);
        const side = i % 2 ? -1 : 1;
        start = [0.28 * side, 0, 0.04 * side];
        end = [
          Math.cos(theta) * r,
          Math.sin(theta) * r * 0.48,
          Math.sin(theta * 1.8 + v * 5.2) * 0.62
        ];
        hue = 0.55 + v * 0.18 + (i % 31 === 0 ? 0.11 : 0);
        light = i % 19 === 0 ? 0.76 : 0.58 + v * 0.14;
      } else if(key === 'marketing'){
        const v = lane;
        const theta = Math.PI * 2 * (u * 46 + rand * 0.08);
        const rose = Math.abs(Math.sin(theta * 2.5)) * (0.36 + v * 0.82);
        start = [-0.2 + v * 0.38, -0.42 + v * 0.84, 0];
        end = [
          Math.cos(theta) * rose,
          Math.sin(theta * 1.42) * 0.62 + (v - 0.5) * 0.32,
          Math.sin(theta) * rose * 0.44
        ];
        hue = 0.02 + v * 0.16 + (i % 37 === 0 ? 0.55 : 0);
        light = 0.56 + Math.sin(v * Math.PI) * 0.22;
      } else {
        const t = Math.PI * 2 * (u * 34 + rand * 0.18);
        const v = lane;
        const knotR = 0.78 + 0.24 * Math.cos(3 * t);
        const px = knotR * Math.cos(2 * t);
        const py = knotR * Math.sin(2 * t) * 0.72;
        const pz = 0.54 * Math.sin(3 * t);
        const spread = 0.18 + v * 0.44;
        end = [
          px + Math.cos(t * 5.0) * spread,
          py + Math.sin(t * 3.0) * spread * 0.62,
          pz + Math.sin(t * 4.0) * spread * 0.78
        ];
        start = [px * 0.18, py * 0.18, pz * 0.18];
        hue = 0.54 + v * 0.24 + (i % 41 === 0 ? 0.12 : 0);
        light = i % 23 === 0 ? 0.78 : 0.58 + v * 0.12;
      }

      return {start:start, end:end, hue:hue, light:light};
    }
    function buildAbstractServiceScene(state, THREE, key){
      if(key === 'data') return false;
      if(key === 'marketing') return false;
      if(key === 'innovation') return buildTechnologyCrystalScene(state, THREE);
      const specs = {
        web: {scaleCard: 1.66, scaleDetail: 2.08, opacity: 0.64, speedX: 0.01, speedY: 0.018},
        commerce: {scaleCard: 1.68, scaleDetail: 2.12, opacity: 0.62, speedX: 0.01, speedY: -0.018},
        search: {scaleCard: 1.66, scaleDetail: 2.1, opacity: 0.64, speedX: 0.01, speedY: 0.02},
        marketing: {scaleCard: 1.72, scaleDetail: 2.16, opacity: 0.66, speedX: -0.01, speedY: 0.018}
      };
      const spec = specs[key] || specs.web;
      const linePositions = [];
      const lineColors = [];

      if(key === 'web') semanticWebScene(THREE, linePositions, lineColors);
      else if(key === 'commerce') semanticCommerceScene(THREE, linePositions, lineColors);
      else if(key === 'search') semanticSearchScene(THREE, linePositions, lineColors);
      else if(key === 'marketing') semanticMarketingScene(THREE, linePositions, lineColors);
      else semanticInnovationScene(THREE, linePositions, lineColors);

      const geometry = new THREE.BufferGeometry();
      geometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
      geometry.setAttribute('vertColor', new THREE.Float32BufferAttribute(lineColors, 3));
      geometry.computeBoundingSphere();

      const mesh = new THREE.LineSegments(geometry, abstractLineMaterial(THREE, spec.opacity));
      mesh.userData.abstractServiceObject = true;
      mesh.userData.noOpacityPulse = true;
      mesh.userData.speedX = spec.speedX;
      mesh.userData.speedY = spec.speedY;
      mesh.frustumCulled = false;
      state.group.add(mesh);
      state.visualScale = state.el && state.el.closest('.ftc-service-detail-webgl') ? spec.scaleDetail : spec.scaleCard;
      state.abstractService = true;
      state.group.userData.schematic = false;
      return true;
    }
    function buildServiceScene(state, THREE){
      const group = state.group;
      const key = state.key;
      group.userData.schematic = true;
      if(key === 'data' || key === 'ai'){
        buildSelectiveDrawDataScene(state, THREE);
        return;
      }
      if(key === 'marketing' && window.FTCRubikCube && window.FTCRubikCube.build(state, THREE)) return;
      if(buildAbstractServiceScene(state, THREE, key)) return;
      const blue = 0x397cf6, sky = 0x8fb5ff, yellow = 0xffd94d, red = 0xff5b45, green = 0x69d85b, white = 0xe9f1ff;
      blueprintGrid(THREE, group, 5.7, 3.25, 9, 5, -0.68, sky, 0.08);
      blueprintDimension(THREE, group, new THREE.Vector3(-2.6,-1.5,-.34), new THREE.Vector3(2.45,-1.5,-.34), sky, .28);

      if(key === 'web'){
        const rig = new THREE.Group();
        rig.position.set(0.06, -0.03, 0.18);
        rig.rotation.y = -0.08;
        rig.userData.float = 0.32;
        group.add(rig);

        const desktop = blueprintScreenDevice(THREE, rig, {
          w: 2.25, h: 1.38, d: 0.09, x: 0.75, y: 0.18, z: 0.18,
          ry: -0.22, rz: 0.03, color: white, accent: yellow, variant: 'dashboard', float: 0.2
        });
        const tablet = blueprintScreenDevice(THREE, rig, {
          w: 1.18, h: 1.56, d: 0.095, x: -1.12, y: -0.06, z: 0.72,
          ry: 0.34, rz: 0.11, color: sky, accent: red, variant: 'project', float: 0.54
        });
        const phone = blueprintScreenDevice(THREE, rig, {
          w: 0.62, h: 1.12, d: 0.105, x: -0.2, y: -1.06, z: 1.18,
          rx: -0.08, ry: 0.18, rz: -0.03, color: red, accent: green, variant: 'phone', float: 0.88
        });
        desktop.userData.spin = 0.012;
        tablet.userData.spin = -0.015;
        phone.userData.spin = 0.018;

        blueprintKeyboard(THREE, rig, 0.8, -1.08, 0.72, white, sky);
        blueprintBox(THREE, rig, 0.5, 0.22, 0.05, 2.15, -1.08, 0.62, white, 0.36, 0.006);
        blueprintCircle(THREE, rig, 0.19, white, 0.3, 2.15, -1.08, 0.67, -0.12, 0, 0, 48);

        blueprintFlowPath(THREE, rig, [
          new THREE.Vector3(-1.92, 0.72, 0.62),
          new THREE.Vector3(-1.5, 1.06, 0.74),
          new THREE.Vector3(-0.46, 0.98, 0.68),
          new THREE.Vector3(0.24, 0.72, 0.48)
        ], 0x66fff2, 0.58, 0.006);
        blueprintFlowPath(THREE, rig, [
          new THREE.Vector3(1.82, 0.88, 0.34),
          new THREE.Vector3(2.34, 0.72, 0.42),
          new THREE.Vector3(2.4, -0.28, 0.5),
          new THREE.Vector3(1.72, -0.7, 0.62)
        ], 0xff66d9, 0.5, 0.006);
        blueprintFlowPath(THREE, rig, [
          new THREE.Vector3(-0.64, -1.28, 1.1),
          new THREE.Vector3(0.14, -1.48, 1.0),
          new THREE.Vector3(1.0, -1.38, 0.8),
          new THREE.Vector3(1.74, -1.2, 0.64)
        ], yellow, 0.52, 0.006);

        blueprintDocumentIcon(THREE, rig, -2.05, 0.72, 0.72, 0x66fff2, sky);
        blueprintDocumentIcon(THREE, rig, 2.42, 0.48, 0.5, 0xff66d9, red);
        blueprintDocumentIcon(THREE, rig, -1.2, -1.3, 1.05, yellow, green);

        for(let i=0;i<6;i++){
          const x = -2.25 + i * 0.32;
          blueprintWireText(THREE, rig, x, 1.08 + Math.sin(i) * 0.05, 0.58, 0.17, 2, i % 2 ? sky : 0x66fff2, 0.28, 0.045);
        }
        for(let i=0;i<12;i++){
          const node = new THREE.Mesh(new THREE.SphereGeometry(0.018 + (i % 3) * 0.006, 8, 8), blueprintMeshMaterial(THREE, [sky, yellow, red, green][i % 4], 0.54));
          node.position.set(-2.1 + (i % 6) * 0.82, -1.48 + Math.floor(i / 6) * 2.72, 0.46 + Math.sin(i) * 0.28);
          node.userData.float = i * 0.29;
          rig.add(node);
        }
        blueprintDimension(THREE, rig, new THREE.Vector3(-1.82, -1.55, 0.68), new THREE.Vector3(2.24, -1.55, 0.68), white, 0.22);
      } else if(key === 'commerce'){
        const gearA = blueprintGear(THREE, group, .68, 24, -.88, .22, .2, yellow, .78);
        const gearB = blueprintGear(THREE, group, .52, 20, .18, -.18, .32, sky, .72);
        const gearC = blueprintGear(THREE, group, .44, 18, 1.06, .28, .48, red, .64);
        gearB.userData.spin = -0.22; gearC.userData.spin = .28;
        for(let i=0;i<4;i++){
          const b = blueprintBox(THREE, group, .48, .36, .32, -1.92 + i*.58, -.98 + Math.sin(i)*.08, .18 + i*.08, i%2 ? sky : white, .52, .012);
          b.mesh.rotation.z = .12; b.edges.rotation.z = .12;
        }
        blueprintLine(THREE, group, [new THREE.Vector3(-2.25,-1.22,.12), new THREE.Vector3(1.95,-.94,.48)], green, .58);
        blueprintCircle(THREE, group, 1.66, blue, .34, .1, .05, .15, Math.PI/2.7, .18, .08);
      } else if(key === 'data'){
        const base = [];
        for(let i=0;i<9;i++){
          const h = .34 + ((i * 19) % 8) * .14;
          const b = blueprintBox(THREE, group, .18, h, .18, (i-4)*.38, -.9 + h/2, .04 + (i%3)*.14, i%2 ? sky : yellow, .62, .012);
          b.mesh.userData.float = i*.2; b.edges.userData.float = i*.2;
          base.push(new THREE.Vector3((i-4)*.38, -.48 + Math.sin(i*.72)*.58, .48));
        }
        group.add(serviceTube(THREE, base, blue, .012, .86));
        base.forEach(function(pt, i){ blueprintCircle(THREE, group, .07, i%2 ? red : white, .64, pt.x, pt.y, pt.z, 0, 0, 0, 32); });
        blueprintGrid(THREE, group, 3.85, 1.6, 8, 4, -.02, white, .08).rotation.x = -0.22;
      } else if(key === 'search'){
        for(let i=0;i<5;i++){
          const ring = blueprintCircle(THREE, group, .52 + i*.28, i%2 ? sky : blue, .28 + i*.07, 0, 0, .12 + i*.02, Math.PI/2 + i*.05, i*.18, 0, 120);
          ring.userData.spin = .08 + i*.035;
        }
        blueprintNodeField(THREE, group, 28, 2.1, yellow, red);
        blueprintLine(THREE, group, [new THREE.Vector3(-2.1,-.92,.15), new THREE.Vector3(-.55,.2,.34), new THREE.Vector3(.35,-.22,.42), new THREE.Vector3(1.82,.66,.56)], white, .32);
        blueprintDimension(THREE, group, new THREE.Vector3(-.82,1.1,.3), new THREE.Vector3(.82,1.1,.3), yellow, .34);
      } else if(key === 'marketing'){
        for(let i=0;i<5;i++){
          const ring = blueprintCircle(THREE, group, .42 + i*.28, [red,yellow,blue,sky,green][i], .42, 0, -.04 + i*.03, .12, Math.PI/2.45, 0, i*.2, 96);
          ring.userData.spin = (i%2 ? -1 : 1) * (.08 + i*.02);
        }
        const funnel = [
          [new THREE.Vector3(-1.6,.78,.15), new THREE.Vector3(1.6,.78,.15)],
          [new THREE.Vector3(-1.25,.2,.2), new THREE.Vector3(1.25,.2,.2)],
          [new THREE.Vector3(-.76,-.38,.25), new THREE.Vector3(.76,-.38,.25)],
          [new THREE.Vector3(-.25,-.96,.3), new THREE.Vector3(.25,-.96,.3)],
          [new THREE.Vector3(-1.6,.78,.15), new THREE.Vector3(-.25,-.96,.3)],
          [new THREE.Vector3(1.6,.78,.15), new THREE.Vector3(.25,-.96,.3)]
        ];
        blueprintSegments(THREE, group, funnel, yellow, .55);
        blueprintNodeField(THREE, group, 18, 1.7, sky, red);
      } else {
        const g1 = blueprintGear(THREE, group, .86, 30, -.84, .08, .18, sky, .72);
        const g2 = blueprintGear(THREE, group, .62, 24, .28, -.28, .34, yellow, .72);
        const g3 = blueprintGear(THREE, group, .52, 22, 1.22, .2, .5, red, .62);
        g1.rotation.y = .28; g2.rotation.y = -.2; g3.rotation.y = .34;
        g2.userData.spin = -.25; g3.userData.spin = .32;
        blueprintCircle(THREE, group, 1.72, blue, .28, .05, -.04, .05, Math.PI/2.3, .1, .1, 128);
        blueprintLine(THREE, group, [new THREE.Vector3(-2.18,-.82,.16), new THREE.Vector3(-1.12,.34,.2), new THREE.Vector3(.08,-.52,.34), new THREE.Vector3(1.78,.48,.5)], white, .28);
        blueprintNodeField(THREE, group, 32, 2.05, sky, yellow);
      }

      let phaseIndex = 0;
      group.traverse(function(child){
        if(child === group) return;
        child.userData = child.userData || {};
        if(child.userData.phase === undefined) child.userData.phase = phaseIndex * .21;
        phaseIndex++;
      });
    }
    function renderServiceVisuals(time){
      if(pageHidden){
        serviceVisualFrame = 0;
        return;
      }
      const now = (time || 0) * 0.001;
      serviceVisuals = serviceVisuals.filter(function(state){ return state && !state.disposed && state.el && state.el.isConnected; });
      if(!serviceVisuals.length){
        serviceVisualFrame = 0;
        return;
      }
      serviceVisuals.forEach(function(state){
        if(state.disposed || state.renderPaused) return;
        if(state.isPreview && serviceVisuals.length > 1){
          var hasDetail = serviceVisuals.some(function(s){ return s && !s.disposed && !s.isPreview; });
          if(hasDetail) return;
        }
        state.frameTick = (state.frameTick || 0) + 1;
        if(state.frameTick % 8 === 0 || state._lastVisible == null){
          const rect = state.el.getBoundingClientRect();
          state._lastRect = rect;
          state._lastVisible = state.isDetailVisual || (rect.bottom > 0 && rect.top < window.innerHeight && rect.width > 20 && rect.height > 20);
          if(state.isPreview){
            state.el.classList.toggle('is-in-view', state._lastVisible);
          } else if(state._lastVisible){
            state.el.classList.add('is-in-view');
          }
        }
        if(!state._lastVisible) return;
        var skip = state.isPreview ? 5 : 4;
        /* Render data scene every 2 frames (~30fps) for smooth rotation */
        if(state.key === 'data') skip = state.isPreview ? 6 : 2;
        else if(state.technologyCrystal) skip = state.isPreview ? 6 : 4;
        if(state.frameTick % skip !== 0) return;
        const t = now + state.t;
        const rect = state._lastRect || state.el.getBoundingClientRect();
        const viewportCenter = rect.top + rect.height / 2;
        const scrollNorm = ((viewportCenter / Math.max(1, window.innerHeight)) - 0.5) * 2;
        const currentScrollTop = stream ? stream.scrollTop : (window.pageYOffset || document.documentElement.scrollTop || 0);
        const scrollDelta = currentScrollTop - (state.lastScrollTop == null ? currentScrollTop : state.lastScrollTop);
        state.lastScrollTop = currentScrollTop;
        if(state.key === 'data' && Math.abs(scrollDelta) > 0.4){
          state.scrollLiftTarget = scrollDelta > 0 ? 0.14 : -0.08;
          state.scrollLiftHold = 18;
        }
        if(state.key === 'data'){
          if(state.scrollLiftHold > 0) state.scrollLiftHold--;
          else state.scrollLiftTarget *= 0.9;
          state.scrollLift += ((state.scrollLiftTarget || 0) - state.scrollLift) * 0.08;
        }
        state.enter = Math.min(1, state.enter + 0.035);
        const ease = 1 - Math.pow(1 - state.enter, 3);
        const marketing = !!state.marketingRubik;
        if(state.isPreview){
          if(!marketing && !state.technologyCrystal){
            state.group.rotation.set(0, 0, 0);
          }
          if(!marketing){
            const floatY = Math.sin(t * 0.72) * 0.038;
            state.group.position.y += (floatY - state.group.position.y) * 0.07;
          }
        } else if(!state.dragging && !marketing){
          state.dragX *= 0.94;
          state.dragY *= 0.92;
        }
        if(!state.isPreview){
          if(!marketing){
            var motionScale = state.key === 'data' ? 0.62 : 1;
            if(state.key === 'data'){
              /* Direct time-based rotation — smooth and frame-rate independent */
              state.group.rotation.y = t * 0.3 + state.mouseX * 0.28 + state.dragX;
              state.group.rotation.x = state.mouseY * 0.18 + state.dragY * 0.6;
              state.group.rotation.z = 0;
            } else {
            const targetX = state.mouseY * 0.24 * motionScale + scrollNorm * 0.12 * motionScale + state.dragY;
            const targetY = Math.sin(t * 0.22) * 0.2 + state.mouseX * 0.38 * motionScale + scrollNorm * 0.18 * motionScale + state.dragX;
            state.springVX += (targetX - state.springX) * 0.018;
            state.springVY += (targetY - state.springY) * 0.018;
            state.springVX *= 0.88;
            state.springVY *= 0.88;
            state.springX += state.springVX;
            state.springY += state.springVY;
            state.group.rotation.x = state.springX;
            state.group.rotation.y = state.springY;
            state.group.rotation.z = Math.sin(t * 0.18) * 0.045 + scrollNorm * 0.035;
            }
          } else {
            state.group.rotation.set(0, 0, 0);
          }
          if(!marketing){
            const floatY = Math.sin(t * 0.9) * 0.055;
            const viewportDrift = state.key === 'data' ? -scrollNorm * 0.08 : -scrollNorm * 0.18;
            state.group.position.y += ((floatY + viewportDrift + (state.scrollLift || 0)) - state.group.position.y) * 0.05;
          }
        } else if(marketing){
          state.group.rotation.set(0, 0, 0);
        }
        state.group.scale.setScalar(marketing ? (state.visualScale || 1) : ((0.78 + ease * 0.22) * (state.visualScale || 1)));
        if(state.technologyCrystal && state.technologyCrystal.uniforms){
          if(!state.isPreview){
            state.technologyCrystal.uniforms.forEach(function(uniforms){
              if(uniforms && uniforms.uTime) uniforms.uTime.value = t;
            });
            if(state.technologyCrystal.root){
              state.technologyCrystal.root.rotation.x = -0.12 + Math.sin(t * 0.14) * 0.07 + state.mouseY * 0.08;
              state.technologyCrystal.root.rotation.y = -0.28 + t * 0.08 + state.mouseX * 0.12;
              state.technologyCrystal.root.rotation.z = 0.08 + Math.sin(t * 0.1) * 0.045;
            }
          } else if(state.technologyCrystal.root){
            state.technologyCrystal.root.rotation.set(-0.12, -0.28, 0.08);
            state.technologyCrystal.uniforms.forEach(function(uniforms){
              if(uniforms && uniforms.uTime) uniforms.uTime.value = 0;
            });
          }
        }
        if(state.rubik && window.FTCRubikCube) window.FTCRubikCube.tick(state.rubik, t, state.isPreview ? 0 : scrollDelta);
        state.group.traverse(function(child){
          if(child === state.group) return;
          if(child.userData && child.userData.selectiveDrawData){
            if(state.isPreview){
              child.rotation.x = t * 0.08;
              child.rotation.y = t * 0.14;
            } else {
              child.rotation.set(0, 0, 0);
            }
          }
          if(child.userData && child.userData.abstractServiceObject){
            if(state.isPreview) return;
            child.rotation.x = Math.sin(t * 0.18) * 0.08 + t * (child.userData.speedX || 0.05);
            child.rotation.y = t * (child.userData.speedY || 0.12);
            child.rotation.z = Math.sin(t * 0.14) * 0.05;
          }
          if(child.userData && child.userData.spin){
            child.rotation.x += child.userData.spin * 0.008;
            child.rotation.y += child.userData.spin * 0.011;
            child.rotation.z += child.userData.spin * 0.012;
          }
          if(child.userData && child.userData.float !== undefined){
            child.position.y += Math.sin(t * 1.6 + child.userData.float) * 0.0018;
          }
        });
        state.renderer.render(state.scene, state.camera);
      });
      if(serviceVisuals.some(function(s){ return s && !s.disposed && !s.renderPaused; })){
        serviceVisualFrame = requestAnimationFrame(renderServiceVisuals);
      } else {
        serviceVisualFrame = 0;
      }
    }
    function bootGoTimeDataScene(visualEl, canvasHost, options){
      if(!window.THREE || !visualEl || !canvasHost) return null;
      options = options || {};
      var sectionEl = options.sectionEl;
      var scrollRoot = options.scrollRoot || window;
      var redMot = !!options.reducedMotion;
      var getScrollProgress = options.getScrollProgress || function(){ return 0; };
      var side = sectionEl && parseInt(sectionEl.getAttribute('data-chapter') || '0', 10) % 2 === 1 ? 1 : -1;

      visualEl.classList.add('ftc-service-detail-webgl', 'ftc-go-time-data-visual');
      canvasHost.classList.add('ftc-service-webgl');
      canvasHost.setAttribute('data-ftc-service-visual', 'data');

      var W = canvasHost.clientWidth || visualEl.clientWidth || 640;
      var H = canvasHost.clientHeight || visualEl.clientHeight || 520;
      if(W < 40 || H < 40) return null;

      var THREE = window.THREE;
      var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, premultipliedAlpha: false });
      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      renderer.setSize(W, H, false);
      renderer.setClearColor(0x000000, 0);
      if(renderer.shadowMap) renderer.shadowMap.enabled = false;
      var canvas = renderer.domElement;
      canvas.style.display = 'block';
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      canvas.style.background = 'transparent';
      canvas.style.pointerEvents = 'none';
      canvasHost.appendChild(canvas);

      var scene = new THREE.Scene();
      var camera = new THREE.PerspectiveCamera(34, W / H, 0.1, 80);
      camera.position.set(0, 0, 7.4);

      var group = new THREE.Group();
      scene.add(group);
      scene.add(new THREE.AmbientLight(0xffffff, 0.68));
      var lightA = new THREE.PointLight(0x8fb5ff, 0.95, 28);
      lightA.position.set(-3, 3, 7);
      scene.add(lightA);
      var lightB = new THREE.PointLight(0xffe169, 0.5, 24);
      lightB.position.set(4, -2, 6);
      scene.add(lightB);

      var scrollTopNow = scrollRoot === window
        ? (window.pageYOffset || document.documentElement.scrollTop || 0)
        : scrollRoot.scrollTop;

      var state = {
        el: visualEl,
        renderer: renderer,
        scene: scene,
        camera: camera,
        group: group,
        key: 'data',
        isPreview: false,
        t: Math.random() * 100,
        mouseX: 0,
        mouseY: 0,
        enter: 0,
        scrollT: 0,
        clockTime: 0,
        active: false,
        scrollLift: 0,
        scrollLiftTarget: 0,
        scrollLiftHold: 0,
        lastScrollTop: scrollTopNow,
        dragX: 0,
        dragY: 0,
        introRotY: side * 0.72,
        introRotX: 0.22
      };

      buildServiceScene(state, THREE);
      function dataVisualScale(width) {
        if (width < 420) return 1.75;
        if (width < 760) return 2.35;
        return 3.35;
      }
      state.visualScale = dataVisualScale(W);

      var mouse = { x: 0, y: 0, tx: 0, ty: 0 };
      visualEl.addEventListener('mousemove', function(e){
        var r = visualEl.getBoundingClientRect();
        if(!r.width || !r.height) return;
        mouse.tx = ((e.clientX - r.left) / r.width - 0.5) * 0.9;
        mouse.ty = ((e.clientY - r.top) / r.height - 0.5) * -0.7;
        state.mouseX = mouse.tx;
        state.mouseY = mouse.ty;
      }, { passive: true });
      visualEl.addEventListener('mouseleave', function(){
        mouse.tx = 0;
        mouse.ty = 0;
        state.mouseX = 0;
        state.mouseY = 0;
      });

      if(typeof IntersectionObserver !== 'undefined' && sectionEl){
        var ioRootEl = (scrollRoot && scrollRoot !== window) ? scrollRoot : null;
        new IntersectionObserver(function(entries){
          state.active = entries[0].isIntersecting;
          if(entries[0].isIntersecting) sectionEl.classList.add('is-in-view');
        }, { threshold: 0.06, root: ioRootEl, rootMargin: '35% 0px' }).observe(sectionEl);
      } else {
        state.active = true;
        if(sectionEl) sectionEl.classList.add('is-in-view');
      }
      if(sectionEl && sectionEl.classList.contains('is-in-view')) state.active = true;

      if(typeof ResizeObserver !== 'undefined'){
        new ResizeObserver(function(){
          var nW = canvasHost.clientWidth || visualEl.clientWidth;
          var nH = canvasHost.clientHeight || visualEl.clientHeight;
          if(!nW || !nH) return;
          camera.aspect = nW / nH;
          camera.updateProjectionMatrix();
          renderer.setSize(nW, nH, false);
          state.visualScale = dataVisualScale(nW);
        }).observe(visualEl);
      }

      function resizeNow(){
        var nW = canvasHost.clientWidth || visualEl.clientWidth;
        var nH = canvasHost.clientHeight || visualEl.clientHeight;
        if(!nW || !nH) return;
        camera.aspect = nW / nH;
        camera.updateProjectionMatrix();
        renderer.setSize(nW, nH, false);
      }
      resizeNow();

      return {
        tick: function(delta){
          if(document.hidden) return;
          state.clockTime += delta;
          var t = state.clockTime + state.t;
          var targetScroll = getScrollProgress();
          state.scrollT += (targetScroll - state.scrollT) * (redMot ? 1 : 0.06);
          state.enter = Math.min(1, state.enter + (redMot ? 1 : delta * 1.05));
          var ease = 1 - Math.pow(1 - state.enter, 3);
          var w = state.scrollT;

          mouse.x += (mouse.tx - mouse.x) * (redMot ? 1 : 0.07);
          mouse.y += (mouse.ty - mouse.y) * (redMot ? 1 : 0.07);
          state.mouseX = mouse.x;
          state.mouseY = mouse.y;

          var currentScrollTop = scrollRoot === window
            ? (window.pageYOffset || document.documentElement.scrollTop || 0)
            : scrollRoot.scrollTop;
          var scrollDelta = currentScrollTop - state.lastScrollTop;
          state.lastScrollTop = currentScrollTop;
          if(Math.abs(scrollDelta) > 0.4){
            state.scrollLiftTarget = scrollDelta > 0 ? 0.14 : -0.08;
            state.scrollLiftHold = 18;
          }
          if(state.scrollLiftHold > 0) state.scrollLiftHold--;
          else state.scrollLiftTarget *= 0.9;
          state.scrollLift += ((state.scrollLiftTarget || 0) - state.scrollLift) * 0.08;

          var scrollNorm = (w - 0.5) * 2;
          var restRotY = t * 0.3 + state.mouseX * 0.28;
          var restRotX = state.mouseY * 0.18;
          var targetRotY = (state.introRotY || 0) * (1 - ease) + restRotY * ease;
          var targetRotX = (state.introRotX || 0) * (1 - ease) + restRotX * ease;
          state.group.rotation.y = targetRotY;
          state.group.rotation.x = targetRotX;
          state.group.rotation.z = 0;

          var floatY = redMot ? 0 : Math.sin(state.clockTime * 0.9) * 0.04 * ease;
          var viewportDrift = -scrollNorm * 0.06;
          var targetY = floatY + viewportDrift + (state.scrollLift || 0);
          state.group.position.y += (targetY - state.group.position.y) * 0.05;
          state.group.position.x += ((state.mouseX * 0.1 * w) - state.group.position.x) * 0.05;

          var padScale = 0.98;
          state.group.scale.setScalar((0.84 + ease * 0.16) * (state.visualScale || 2.68) * padScale);

          updateSelectiveDrawData(state, t, scrollNorm);

          var dolly = w * 0.42 + ease * 0.28 + state.mouseY * -0.1;
          camera.position.z += ((7.4 - dolly) - camera.position.z) * (redMot ? 1 : 0.04);
          camera.position.y += ((state.mouseY * 0.1 * w) - camera.position.y) * (redMot ? 1 : 0.04);
          camera.lookAt(state.mouseX * 0.06, targetY * 0.3, 0);

          renderer.render(scene, camera);
        },
        dispose: function(){
          if(state._stopDataCounters){ try{ state._stopDataCounters(); }catch(e){} state._stopDataCounters = null; }
          renderer.dispose();
          if(renderer.domElement.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement);
          visualEl.classList.remove('ftc-service-detail-webgl', 'ftc-go-time-data-visual');
          canvasHost.classList.remove('ftc-service-webgl');
          canvasHost.removeAttribute('data-ftc-service-visual');
        }
      };
    }
    function revealAssistantMessage(el){
      if(!el || el.dataset.revealed) return;
      el.dataset.revealed='true';
      el.classList.remove('is-waiting-scroll');
      requestAnimationFrame(function(){
        el.classList.add('is-arriving');
        initServiceDetailAnimations(el);
      });
      setTimeout(function(){ el.classList.add('has-arrived'); endResponseTransition(); }, 820);
    }
    function typewriterElement(el){ if(isInsideGoTimeSpline(el)) return; if(el.dataset.initialized) return; el.dataset.initialized='true'; const text=el.dataset.text || el.textContent.trim(); el.innerHTML='<span class="ftc-type-text"></span><span class="ftc-cursor" aria-hidden="true"></span>'; const target=el.querySelector('.ftc-type-text'); let i=0; function tick(){ target.textContent+=text.charAt(i); const ch=text.charAt(i); i++; if(i<text.length){ let delay=24+Math.floor(Math.random()*45); if(ch===','||ch==='.'||ch==='—') delay+=120; setTimeout(tick,delay); } else { el.classList.add('is-complete'); } } setTimeout(tick,80); }
    function lazyTypewriterElement(el){
      if(isInsideGoTimeSpline(el)) return;
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
    function addThinking(){ if(stream) stream.setAttribute('aria-busy','true'); const el=document.createElement('div'); el.className='ftc-message ftc-assistant ftc-thinking-message'; el.innerHTML='<div class="ftc-card"><div class="ftc-thinking" role="status" aria-live="polite"><span>Field Theory is thinking</span><i></i><i></i><i></i></div></div>'; stream.appendChild(el); scrollTo(el,16); return el; }
    function scrollTo(el, offset){
      requestAnimationFrame(function(){
        const streamRect = stream.getBoundingClientRect();
        const elRect = el.getBoundingClientRect();
        const top = Math.max(0, stream.scrollTop + (elRect.top - streamRect.top) - (offset||0) - (window.innerWidth < 760 ? 52 : 88));
        if('scrollTo' in stream) stream.scrollTo({top: top, behavior:'smooth'});
        else stream.scrollTop = top;
      });
    }
    function messageMapEnabled(){
      return window.matchMedia('(min-width: 901px)').matches;
    }
    function createMessageMap(){
      if(!messageMapEnabled()) return null;
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
      if(!messageMap || !target || !messageMapEnabled()) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ftc-message-map-dot';
      btn.setAttribute('aria-label', 'Return to ' + (label || 'response'));
      btn.title = label || 'Response';
      const bubble = document.createElement('span');
      bubble.className = 'ftc-message-map-bubble';
      bubble.textContent = label || 'Response';
      btn.appendChild(bubble);
      btn._ftcTarget = target;
      btn._ftcLabel = label || 'Response';
      btn.addEventListener('click', function(){ scrollTo(target, 12); });
      messageMap.appendChild(btn);
      requestAnimationFrame(function(){
        btn.classList.add('is-new');
        setTimeout(function(){ btn.classList.remove('is-new'); }, 900);
      });
      scheduleMessageMapUpdate();
    }
    function scheduleMessageMapUpdate(){
      if(!messageMap || !messageMapEnabled()) return;
      if(messageMapFrame) return;
      messageMapFrame = requestAnimationFrame(function(){
        messageMapFrame = 0;
        updateMessageMap();
      });
    }
    function updateMessageMap(){
      if(!messageMap || !messageMapEnabled()) return;
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
      const minY = 18;
      const maxY = Math.max(minY, trackHeight - 18);
      const fadeRange = Math.max(96, Math.min(180, trackHeight * 0.22));
      dots.forEach(function(dot, i){
        const target = dot._ftcTarget;
        if(!target || !target.parentNode) return;
        const viewportTop = target.offsetTop - stream.scrollTop + 8;
        const y = Math.max(minY, Math.min(maxY, viewportTop));
        let proximity = 1;
        if(viewportTop < minY) proximity = Math.max(0, 1 - ((minY - viewportTop) / fadeRange));
        else if(viewportTop > maxY) proximity = Math.max(0, 1 - ((viewportTop - maxY) / fadeRange));
        dot._ftcTopVisible = viewportTop >= minY && viewportTop <= maxY;
        dot.style.top = y + 'px';
        dot.style.setProperty('--ftc-map-proximity', proximity.toFixed(3));
        dot.style.setProperty('--ftc-bubble-muted-opacity', (proximity * 0.08).toFixed(3));
        dot.style.setProperty('--ftc-bubble-near-opacity', (proximity * 0.16).toFixed(3));
        dot.style.setProperty('--ftc-bubble-current-opacity', (proximity * 0.24).toFixed(3));
        dot.style.setProperty('--ftc-bubble-muted-scale', (0.42 + (proximity * 0.18)).toFixed(3));
        dot.style.setProperty('--ftc-bubble-near-scale', (0.54 + (proximity * 0.16)).toFixed(3));
        dot.style.setProperty('--ftc-bubble-current-scale', (0.62 + (proximity * 0.16)).toFixed(3));
        if(target.offsetTop <= current) activeIndex = i;
      });
      dots.forEach(function(dot, i){
        const distance = Math.min(3, Math.abs(i - activeIndex));
        const activeAtResponseTop = i === activeIndex && dot._ftcTopVisible;
        dot.style.setProperty('--ftc-map-distance', String(distance));
        dot.classList.toggle('is-current', i === activeIndex);
        dot.classList.toggle('is-active', activeAtResponseTop);
        dot.classList.toggle('is-near-active', !activeAtResponseTop && dot._ftcTopVisible && distance === 1);
        dot.classList.toggle('is-muted', !activeAtResponseTop);
      });
    }

    function initGetStartedHeroVideo(root){
      root = root || document;
      if(!root || !root.querySelectorAll) return;
      var wraps = Array.prototype.slice.call(root.querySelectorAll('[data-ftc-hero-video]:not([data-ftc-hero-video-init="1"])'));
      if(!wraps.length) return;
      wraps.forEach(function(wrap){
        wrap.setAttribute('data-ftc-hero-video-init', '1');
        var video = wrap.querySelector('[data-ftc-hero-video-el]');
        var toggle = wrap.querySelector('[data-ftc-hero-video-toggle]');
        if(!video || !toggle) return;
        var userPaused = false;
        function setPlaying(playing){
          toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
          toggle.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
        }
        function tryPlay(){
          if(userPaused) return;
          var playAttempt = video.play();
          if(playAttempt && playAttempt.catch) playAttempt.catch(function(){});
        }
        video.muted = true;
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.addEventListener('loadeddata', tryPlay);
        video.addEventListener('play', function(){ setPlaying(true); });
        video.addEventListener('pause', function(){ setPlaying(false); });
        tryPlay();
        toggle.addEventListener('click', function(){
          if(video.paused){
            userPaused = false;
            video.muted = false;
            tryPlay();
          } else {
            userPaused = true;
            video.pause();
          }
        });
        if('IntersectionObserver' in window){
          new IntersectionObserver(function(entries){
            var entry = entries[0];
            var visible = !!(entry && entry.isIntersecting && entry.intersectionRatio >= 0.35);
            if(visible && !userPaused) tryPlay();
            else video.pause();
          }, { root: stream || null, threshold: [0, 0.25, 0.35, 0.55, 0.75] }).observe(wrap);
        }
      });
    }
    function splitInlineGetStartedShells(message){
      if(!message || pendingFragments.length) return;
      if(app.classList.contains('ftc-route-app') || app.getAttribute('data-ftc-route') === 'get-started') return;
      var card = message.querySelector('.ftc-card');
      if(!card) return;
      var shells = Array.prototype.slice.call(card.children).filter(function(node){
        return node.classList && node.classList.contains('ftc-response-shell');
      });
      if(shells.length < 2) return;
      if(shells.some(function(shell){
        return shell.classList.contains('ftc-response-sequence-services')
          || shell.classList.contains('ftc-response-sequence-portfolio')
          || shell.classList.contains('ftc-response-sequence-testimonials');
      })) return;
      var keep = shells.shift();
      while(card.firstChild) card.removeChild(card.firstChild);
      card.appendChild(keep);
      pendingFragments = shells.map(function(shell){
        return {
          html: shell.outerHTML,
          prompt: shell.getAttribute('data-ftc-response-prompt') || shell.getAttribute('data-response-title') || 'Continue'
        };
      });
      kickDeferredFragmentQueue();
    }

    function initAiAssessment(root){
      if(typeof window.ftcInitAiAssessment === 'function') window.ftcInitAiAssessment(root);
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
      const totalQuestions = Math.max(1, steps.length - 1);
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
          ['Timeline', answers.timeline],
          ['Budget', answers.budget],
          ['Notes', answers.notes || 'Not provided'],
          ['Contact', contactParts.length ? contactParts.join(' / ') : 'Not provided'],
          ['Preferred Contact', answers.contactMethod]
        ].filter(function(row){ return row[1] && row[1] !== 'Not provided'; });
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
          lead_score:String(score.score)
        }).toString();
        fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body})
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
            if(status) status.textContent = 'Something did not send. Please try again or email jamie@fieldtheory.ai.';
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
      if(!menuContent) return;
      if(menuLoaded || menuLoading) return;
      menuLoading = true;
      fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'ftc_menu',nonce:nonce}).toString()})
        .then(function(r){return r.json();})
        .then(function(data){ menuLoaded=true; menuContent.innerHTML=data.success?data.data.html:'<p>Menu unavailable.</p>'; })
        .catch(function(){ menuContent.innerHTML='<p>Menu unavailable.</p>'; })
        .then(function(){ menuLoading = false; });
    }
    function handleModalKeydown(e){
      if(e.key === 'Escape' || e.key === 'Esc'){
        if(app.classList.contains('is-mobile-search-open') && mobileSearchMq.matches){
          e.preventDefault();
          closeMobileSearch();
          return;
        }
      }
      if(!activeModal) return;
      if(e.key === 'Escape' || e.key === 'Esc'){
        e.preventDefault();
        if(activeModal === helpModal) closeHelpMenu();
        else closeMenu();
        return;
      }
      if(e.key === 'Tab') trapModalFocus(e);
    }

    function getFocusable(container){
      if(!container) return [];
      return Array.prototype.slice.call(container.querySelectorAll(focusableSelector)).filter(function(el){
        if(el.classList && el.classList.contains('ftc-modal-bg')) return false;
        return !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true';
      });
    }

    function focusModal(modalEl){
      const panel = modalEl.querySelector('.ftc-modal-panel') || modalEl;
      if(!panel.hasAttribute('tabindex')) panel.setAttribute('tabindex', '-1');
      const target = getFocusable(modalEl)[0] || panel;
      setTimeout(function(){ try{ target.focus({preventScroll:true}); }catch(e){ target.focus(); } }, 0);
    }

    function trapModalFocus(e){
      const focusables = getFocusable(activeModal);
      if(!focusables.length){
        e.preventDefault();
        focusModal(activeModal);
        return;
      }
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if(e.shiftKey && document.activeElement === first){
        e.preventDefault();
        last.focus();
      } else if(!e.shiftKey && document.activeElement === last){
        e.preventDefault();
        first.focus();
      }
    }

    function rememberModalTrigger(){
      if(document.activeElement && app.contains(document.activeElement)) lastModalTrigger = document.activeElement;
    }

    function restoreModalFocus(restore){
      if(restore === false) return;
      const target = lastModalTrigger;
      lastModalTrigger = null;
      if(target && document.contains(target) && typeof target.focus === 'function'){
        setTimeout(function(){ try{ target.focus({preventScroll:true}); }catch(e){ target.focus(); } }, 0);
      }
    }

    function mountModalToBody(modalEl){
      if(!modalEl || modalEl.parentNode === document.body) return;
      modalEl._ftcHomeParent = modalEl.parentNode;
      document.body.appendChild(modalEl);
    }
    function restoreModalHome(modalEl){
      if(!modalEl || !modalEl._ftcHomeParent || modalEl.parentNode !== document.body) return;
      modalEl._ftcHomeParent.appendChild(modalEl);
    }

    function openMenu(){
      if(!modal) return;
      releaseAllServiceVisualDrags();
      rememberModalTrigger();
      closeHelpMenu(false);
      loadMenuContent();
      mountModalToBody(modal);
      document.documentElement.classList.add('ftc-menu-open');
      app.classList.add('is-menu-open');
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden','false');
      activeModal = modal;
      focusModal(modal);
    }
    function closeMenu(restore){
      if(!modal) return;
      document.documentElement.classList.remove('ftc-menu-open');
      app.classList.remove('is-menu-open');
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden','true');
      restoreModalHome(modal);
      if(activeModal === modal) activeModal = null;
      restoreModalFocus(restore);
    }
    function openHelpMenu(){
      if(!helpModal) return;
      releaseAllServiceVisualDrags();
      closeMobileSearch(false);
      rememberModalTrigger();
      closeMenu(false);
      helpModal.classList.add('is-open');
      helpModal.setAttribute('aria-hidden','false');
      activeModal = helpModal;
      focusModal(helpModal);
    }
    function closeHelpMenu(restore){
      if(!helpModal) return;
      helpModal.classList.remove('is-open');
      helpModal.setAttribute('aria-hidden','true');
      if(activeModal === helpModal) activeModal = null;
      restoreModalFocus(restore);
    }
    function closeAllMenus(){
      closeMenu(false);
      closeHelpMenu(false);
      closeMobileSearch(false);
    }

    function isMobileSearchViewport(){
      return mobileSearchMq.matches;
    }

    function openMobileSearch(){
      if(!isMobileSearchViewport()) return;
      closeHelpMenu(false);
      app.classList.add('is-mobile-search-open');
      if(searchToggle){
        searchToggle.setAttribute('aria-expanded', 'true');
        searchToggle.setAttribute('aria-label', 'Close search');
      }
      if(chatForm) chatForm.removeAttribute('aria-hidden');
      setTimeout(function(){
        if(chatInput){
          try{ chatInput.focus({preventScroll:true}); }catch(e){ chatInput.focus(); }
        }
      }, 320);
    }

    function closeMobileSearch(restoreFocus){
      app.classList.remove('is-mobile-search-open');
      if(searchToggle){
        searchToggle.setAttribute('aria-expanded', 'false');
        searchToggle.setAttribute('aria-label', 'Open search');
      }
      if(chatForm) chatForm.setAttribute('aria-hidden', 'true');
      if(restoreFocus !== false && searchToggle && isMobileSearchViewport()){
        setTimeout(function(){
          try{ searchToggle.focus({preventScroll:true}); }catch(e){ searchToggle.focus(); }
        }, 0);
      }
    }

    function toggleMobileSearch(){
      if(!isMobileSearchViewport()) return;
      if(app.classList.contains('is-mobile-search-open')) closeMobileSearch();
      else openMobileSearch();
    }
    function escapeHTML(str){ return String(str).replace(/[&<>"']/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]; }); }
    function bootGetStartedRoute(){
      if(app.getAttribute('data-ftc-route') !== 'get-started') return;
      loadThree().catch(function(){});
      if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadThree){
        window.FTCGetStartedScene.preloadThree();
      }
      if(window.FTCGetStartedScene && window.FTCGetStartedScene.preloadServiceScreenImages){
        window.FTCGetStartedScene.preloadServiceScreenImages();
      }
      document.querySelectorAll('.ftc-message.ftc-assistant').forEach(revealServiceCardsAnd3d);
      setTimeout(function(){
        document.querySelectorAll('.ftc-message.ftc-assistant').forEach(revealServiceCardsAnd3d);
      }, 350);
      window.addEventListener('ftc-three-ready', function(){
        document.querySelectorAll('.ftc-message.ftc-assistant').forEach(revealServiceCardsAnd3d);
      }, {once:true});
    }
    bootGetStartedRoute();

    if(window.THREE) retryStuckServiceVisuals();
    else {
      window.addEventListener('ftc-three-ready', retryStuckServiceVisuals, {once: true});
      var retryPoll = 0;
      var retryPollId = setInterval(function(){
        if(window.THREE){
          clearInterval(retryPollId);
          retryStuckServiceVisuals();
        } else if(++retryPoll > 120){
          clearInterval(retryPollId);
        }
      }, 50);
    }
  }
})();
