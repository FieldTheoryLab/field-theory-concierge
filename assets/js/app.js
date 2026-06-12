(function(){
  function ready(fn){
    if(document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

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
    const typeTarget = app.querySelector('[data-ftc-typewriter]') || app.querySelector('.ftc-intro-copy');

    let visitor = localStorage.getItem('ftcVisitorName') || '';
    let menuLoaded = false;
    let introTimer = null;

    const introText = [
      'Hi.',
      '',
      'Welcome to our website.',
      '',
      "We're Field Theory Lab.",
      '',
      'We help businesses with website technology, analytics, AI automation, and digital marketing.',
      '',
      "What's your name?"
    ].join('\n');

    const storedTheme = localStorage.getItem('ftcTheme');
    setTheme(storedTheme || 'dark');

    if(visitor){
      intro.classList.remove('is-visible');
      app.classList.add('is-chat');
      chat.classList.add('is-visible');
      addAssistantMessage(welcomeHTML(false));
      setTimeout(function(){ chatInput.focus(); }, 250);
    }else{
      runIntroTyping(introText, function(){
        nameInput.focus();
      });
    }

    nameForm.addEventListener('submit', function(e){
      e.preventDefault();

      const name = nameInput.value.trim();

      if(!name) return;

      visitor = name;
      localStorage.setItem('ftcVisitorName', name);
      nameInput.blur();

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

    clearBtn.addEventListener('click', function(e){
      e.preventDefault();
      stream.innerHTML = '';
      chatInput.value = '';
      addAssistantMessage(welcomeHTML(false));
    });

    resetBtn.addEventListener('click', function(e){
      e.preventDefault();
      localStorage.removeItem('ftcVisitorName');

      visitor = '';

      clearTimeout(introTimer);

      app.classList.remove('is-chat');
      chat.classList.remove('is-visible');
      intro.classList.add('is-visible');

      stream.innerHTML = '';
      nameInput.value = '';

      setTimeout(function(){
        runIntroTyping(introText, function(){
          nameInput.focus();
        });
      }, 250);
    });

    themeBtn.addEventListener('click', function(e){
      e.preventDefault();
      setTheme(app.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });

    menuBtn.addEventListener('click', function(e){
      e.preventDefault();
      openMenu();
    });

    modal.querySelectorAll('[data-ftc-close]').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        closeMenu();
      });
    });

    function runIntroTyping(text, done){
      if(!typeTarget){
        if(typeof done === 'function') done();
        return;
      }

      clearTimeout(introTimer);

      let i = 0;
      let output = '';

      typeTarget.innerHTML = '<div class="ftc-typewriter"><span class="ftc-cursor">█</span></div>';

      const typeBox = typeTarget.querySelector('.ftc-typewriter');

      function tick(){
        if(i >= text.length){
          typeBox.innerHTML = formatTypedText(output) + '<span class="ftc-cursor">█</span>';
          if(typeof done === 'function') done();
          return;
        }

        output += text.charAt(i);

        typeBox.innerHTML = formatTypedText(output) + '<span class="ftc-cursor">█</span>';

        const char = text.charAt(i);
        i++;

        let delay = 42;

        if(char === '.') delay = 430;
        if(char === ',') delay = 130;
        if(char === '\n') delay = 240;

        introTimer = setTimeout(tick, delay);
      }

      tick();
    }

    function formatTypedText(text){
      return escapeHTML(text).replace(/\n/g, '<br>');
    }

    function setTheme(theme){
      app.setAttribute('data-theme', theme);
      localStorage.setItem('ftcTheme', theme);

      if(themeBtn){
        themeBtn.textContent = theme === 'dark' ? '☾' : '☀';
      }
    }

    function beginChat(withIntro){
      clearTimeout(introTimer);

      intro.classList.remove('is-visible');

      setTimeout(function(){
        app.classList.add('is-chat');
        chat.classList.add('is-visible');

        if(!stream.children.length){
          addAssistantMessage(welcomeHTML(withIntro));
        }

        setTimeout(function(){
          chatInput.focus();
        }, 250);
      }, 360);
    }

    function welcomeHTML(first){
      const name = escapeHTML(visitor || 'there');
      const video = settings.demo_video_url || '';

      if(first){
        return '' +
          '<div class="ftc-answer-heading">Nice to meet you, ' + name + '.</div>' +
          '<div class="ftc-answer-body">' +
            '<p><strong>Field Theory Lab helps organizations turn messy digital systems into clearer websites, smarter marketing, and better decisions.</strong></p>' +
            '<p>Most clients come to us when their website is underperforming, analytics are unclear, marketing feels fragmented, or AI feels useful but hard to implement. We bring strategy, design, development, data, and automation together so the digital experience actually supports growth.</p>' +
            '<div class="ftc-intro-grid">' +
              '<div class="ftc-intro-tile"><strong>Web Technology</strong><span>UX, WordPress, Drupal, performance, accessibility, and integrations.</span></div>' +
              '<div class="ftc-intro-tile"><strong>Digital Marketing</strong><span>SEO, content, campaigns, conversion strategy, and visibility planning.</span></div>' +
              '<div class="ftc-intro-tile"><strong>Analytics</strong><span>GA4, dashboards, reporting, tracking, and decision-ready summaries.</span></div>' +
              '<div class="ftc-intro-tile"><strong>AI Automation</strong><span>Assistants, workflows, knowledge tools, and practical adoption planning.</span></div>' +
            '</div>' +
            (video ? '<div class="ftc-hero-video"><video src="' + escapeAttr(video) + '" controls playsinline preload="metadata"></video></div>' : '') +
            '<p>Choose a topic below or ask Field Theory anything about our work, services, analytics, AI, marketing, or how we can help.</p>' +
          '</div>' +
          promptHTML(['Show me your work','What services do you offer?','Tell me about Field Theory','How can I work with Field Theory?']);
      }

      return '' +
        '<div class="ftc-answer-heading">Welcome back, ' + name + '.</div>' +
        '<div class="ftc-answer-body">' +
          '<p>What would you like to explore today?</p>' +
        '</div>' +
        promptHTML(['Show me your work','What services do you offer?','Analytics','AI Automation','Contact']);
    }

    function submitPrompt(raw){
      const term = (raw || '').trim();

      if(!term) return;

      if(!app.classList.contains('is-chat')){
        beginChat(false);
      }

      addUserMessage(term);

      chatInput.value = '';

      const thinking = addThinking();

      fetch(ajaxUrl, {
        method:'POST',
        headers:{
          'Content-Type':'application/x-www-form-urlencoded'
        },
        body:new URLSearchParams({
          action:'ftc_answer',
          nonce:nonce,
          term:term
        }).toString()
      })

      .then(function(r){
        return r.json();
      })

      .then(function(data){
        setTimeout(function(){
          thinking.remove();

          if(data && data.success){
            addAssistantMessage(data.data.html);
          }else{
            addAssistantMessage(fallbackHTML());
          }

        }, 520);
      })

      .catch(function(){
        thinking.remove();
        addAssistantMessage(fallbackHTML());
      });
    }

    function addUserMessage(text){
      const el = document.createElement('div');

      el.className = 'ftc-message ftc-user';

      el.innerHTML =
        '<div class="ftc-bubble">' +
          escapeHTML(text) +
        '</div>';

      stream.appendChild(el);

      scrollToBottom();
    }

    function addAssistantMessage(html){
      const icon = settings.icon_logo || '';

      const el = document.createElement('div');

      el.className = 'ftc-message ftc-assistant';

      el.innerHTML =
        '<div class="ftc-avatar">' +
          (
            icon
            ? '<img src="' + escapeAttr(icon) + '" alt="FT">'
            : 'FT'
          ) +
        '</div>' +
        '<div class="ftc-card">' +
          html +
        '</div>';

      stream.appendChild(el);

      scrollToBottom();
    }

    function addThinking(){
      const icon = settings.icon_logo || '';

      const el = document.createElement('div');

      el.className = 'ftc-message ftc-assistant';

      el.innerHTML =
        '<div class="ftc-avatar">' +
          (
            icon
            ? '<img src="' + escapeAttr(icon) + '" alt="FT">'
            : 'FT'
          ) +
        '</div>' +
        '<div class="ftc-card">' +
          '<div class="ftc-thinking">' +
            '<span>Field Theory is thinking</span>' +
            '<i></i><i></i><i></i>' +
          '</div>' +
        '</div>';

      stream.appendChild(el);

      scrollToBottom();

      return el;
    }

    function fallbackHTML(){
      return '' +
        '<div class="ftc-answer-heading">Good question.</div>' +
        '<div class="ftc-answer-body">' +
          '<p>I can help with Field Theory services, portfolio, analytics, AI automation, web technology, marketing, and contact information.</p>' +
        '</div>' +
        promptHTML(['Show me your work','What services do you offer?','Contact']);
    }

    function promptHTML(items){
      return '' +
        '<div class="ftc-followups">' +
          '<div class="ftc-section-label">Try asking</div>' +
          '<div class="ftc-followup-row">' +
            items.map(function(item){
              return '' +
                '<button type="button" class="ftc-followup" data-prompt="' + escapeAttr(item) + '">' +
                  escapeHTML(item) +
                '</button>';
            }).join('') +
          '</div>' +
        '</div>';
    }

    function openMenu(){
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden','false');

      if(menuLoaded) return;

      fetch(ajaxUrl,{
        method:'POST',
        headers:{
          'Content-Type':'application/x-www-form-urlencoded'
        },
        body:new URLSearchParams({
          action:'ftc_menu',
          nonce:nonce
        }).toString()
      })

      .then(function(r){
        return r.json();
      })

      .then(function(data){
        menuLoaded = true;
        menuContent.innerHTML =
          data.success
          ? data.data.html
          : '<p>Menu unavailable.</p>';
      })

      .catch(function(){
        menuContent.innerHTML =
          '<p>Menu unavailable.</p>';
      });
    }

    function closeMenu(){
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden','true');
    }

    function scrollToBottom(){
      requestAnimationFrame(function(){
        stream.scrollTop = stream.scrollHeight;
      });
    }

    function escapeHTML(str){
      return String(str).replace(/[&<>"']/g, function(s){
        return {
          '&':'&amp;',
          '<':'&lt;',
          '>':'&gt;',
          '"':'&quot;',
          "'":'&#039;'
        }[s];
      });
    }

    function escapeAttr(str){
      return escapeHTML(str).replace(/`/g,'&#096;');
    }
  }
})();
