/* ─────────────────────────────────────────────────────────────────────────────
   FTL Go Time — production phone screen mockups  v5.58.2
   Full-bleed 390×844 drawers — edge-to-edge, no letterboxing
─────────────────────────────────────────────────────────────────────────────*/
(function () {
  'use strict';

  var STATUS_H = 44;
  var HOME_H = 28;
  var ASSET_BASE = (window.ftcData && window.ftcData.pluginUrl) ? window.ftcData.pluginUrl : '';
  var MOBILE_SCREEN_PATHS = [
    'assets/images/portfolio/TEP_mobile_home.png',
    'assets/images/portfolio/BeWell_mobile_home.png',
    'assets/images/portfolio/EnhancedWellness_mobile_home.png',
    'assets/images/portfolio/LynnScholarship_mobile.png',
    'assets/images/portfolio/PNM_mobile_mockup.png',
    'assets/images/portfolio/TEP_mobile_splash.png'
  ];
  var mobileScreens = [];
  var mobileScreensReady = false;

  function assetUrl(path) {
    return ASSET_BASE + path;
  }

  function loadMobileScreens() {
    if (mobileScreensReady || !ASSET_BASE) return Promise.resolve(mobileScreens);
    var pending = MOBILE_SCREEN_PATHS.map(function (path) {
      return new Promise(function (resolve) {
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function () { resolve({ img: img, path: path }); };
        img.onerror = function () { resolve(null); };
        img.src = assetUrl(path);
      });
    });
    return Promise.all(pending).then(function (results) {
      mobileScreens = results.filter(Boolean);
      mobileScreensReady = true;
      return mobileScreens;
    });
  }

  loadMobileScreens();

  function siteFonts() {
    var app = document.querySelector('[data-ftc-app]') || document.documentElement;
    var cs = window.getComputedStyle(app);
    var body = (cs.getPropertyValue('--ftc-font') || "'Montserrat', Arial, sans-serif").trim();
    var heading = (cs.getPropertyValue('--ftc-brand-heading') || '"Proxima Nova", "Proxima Soft", "Montserrat", Arial, sans-serif').trim();
    if (document.fonts && document.fonts.load) {
      document.fonts.load('600 14px ' + body).catch(function () {});
      document.fonts.load('700 18px ' + heading).catch(function () {});
    }
    return { body: body, heading: heading };
  }

  var fonts = siteFonts();

  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function lerp(a, b, t) { return a + (b - a) * t; }
  function easeOut(t) { t = clamp(t, 0, 1); return 1 - Math.pow(1 - t, 3); }
  function easeInOut(t) { t = clamp(t, 0, 1); return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; }

  function roundRect(ctx, x, y, w, h, r) {
    r = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
  }

  function fillRound(ctx, x, y, w, h, r, fill) {
    roundRect(ctx, x, y, w, h, r);
    ctx.fillStyle = fill;
    ctx.fill();
  }

  function strokeRound(ctx, x, y, w, h, r, stroke, lw) {
    roundRect(ctx, x, y, w, h, r);
    ctx.strokeStyle = stroke;
    ctx.lineWidth = lw || 1;
    ctx.stroke();
  }

  function fillScreen(ctx, w, h, colorOrGradient) {
    ctx.fillStyle = colorOrGradient;
    ctx.fillRect(0, 0, w, h);
  }

  function contentBottom(h) { return h - HOME_H; }

  function drawStatusBar(ctx, w, accent, dark) {
    ctx.fillStyle = dark ? 'rgba(0,0,0,0.45)' : 'rgba(255,255,255,0.08)';
    ctx.fillRect(0, 0, w, STATUS_H);
    ctx.fillStyle = '#fff';
    ctx.font = '600 12px ' + fonts.body;
    ctx.fillText('9:41', 16, 28);
    ctx.fillStyle = accent;
    ctx.beginPath();
    ctx.arc(w - 24, 22, 4, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,0.85)';
    ctx.fillRect(w - 48, 18, 16, 8);
    ctx.fillRect(w - 64, 20, 10, 6);
  }

  function drawHomeIndicator(ctx, w, h, color) {
    fillRound(ctx, w / 2 - 56, h - HOME_H + 8, 112, 5, 3, color || 'rgba(255,255,255,0.35)');
  }

  function drawSyntaxLine(ctx, x, y, tokens) {
    var cx = x;
    tokens.forEach(function (tok) {
      ctx.fillStyle = tok.c;
      ctx.font = tok.b ? '600 11px ui-monospace,monospace' : '11px ui-monospace,monospace';
      ctx.fillText(tok.t, cx, y);
      cx += ctx.measureText(tok.t).width;
    });
  }

  function wrapText(ctx, text, x, y, maxW, lineH) {
    var words = text.split(' ');
    var line = '';
    words.forEach(function (word) {
      var test = line + word + ' ';
      if (ctx.measureText(test).width > maxW && line) {
        ctx.fillText(line.trim(), x, y);
        line = word + ' ';
        y += lineH;
      } else {
        line = test;
      }
    });
    if (line) ctx.fillText(line.trim(), x, y);
  }

  function staggerIn(index, entrance, delay) {
    return easeOut(clamp((entrance - (delay || 0) - index * 0.08) / 0.35, 0, 1));
  }

  function countUp(base, range, t, decimals) {
    var v = base + range * easeInOut(clamp(t, 0, 1));
    return decimals != null ? v.toFixed(decimals) : Math.round(v);
  }

  /* ── Web Dev — horizontal swipe through real mobile site captures ── */
  function drawWebDevScreen(ctx, w, h, t, accent, scrollPhase) {
    scrollPhase = scrollPhase || 0;
    var bottom = contentBottom(h);
    fillScreen(ctx, w, h, '#0a0a0f');
    drawStatusBar(ctx, w, accent, true);

    var screens = mobileScreens.length ? mobileScreens : null;
    var count = screens ? screens.length : 1;
    var slideW = w;
    var offsetX = scrollPhase * (count - 1) * slideW;

    ctx.save();
    ctx.beginPath();
    ctx.rect(0, STATUS_H, w, bottom - STATUS_H);
    ctx.clip();

    if (screens) {
      screens.forEach(function (entry, i) {
        var x = i * slideW - offsetX;
        if (x < -slideW || x > slideW) return;
        var img = entry.img;
        var aspect = img.width / img.height;
        var drawW = slideW - 16;
        var drawH = bottom - STATUS_H - 8;
        var fitW = drawH * aspect;
        var fitH = drawH;
        if (fitW > drawW) {
          fitW = drawW;
          fitH = drawW / aspect;
        }
        var dx = x + (slideW - fitW) / 2;
        var dy = STATUS_H + (drawH - fitH) / 2;
        fillRound(ctx, x + 6, STATUS_H + 2, slideW - 12, bottom - STATUS_H - 4, 14, '#111');
        ctx.drawImage(img, dx, dy, fitW, fitH);
        if (i < count - 1) {
          ctx.fillStyle = 'rgba(255,255,255,0.06)';
          ctx.fillRect(x + slideW - 1, STATUS_H, 2, bottom - STATUS_H);
        }
      });
    } else {
      fillRound(ctx, 8, STATUS_H + 8, w - 16, bottom - STATUS_H - 16, 12, '#181825');
      ctx.fillStyle = '#cdd6f4';
      ctx.font = '600 13px ' + fonts.body;
      ctx.fillText('Loading site previews…', 24, STATUS_H + 48);
    }
    ctx.restore();

    var dotsY = bottom - 52;
    for (var d = 0; d < count; d++) {
      var dotX = w / 2 - (count - 1) * 6 + d * 12;
      var active = Math.abs(scrollPhase * (count - 1) - d) < 0.45;
      ctx.beginPath();
      ctx.arc(dotX, dotsY, active ? 4 : 3, 0, Math.PI * 2);
      ctx.fillStyle = active ? accent : 'rgba(255,255,255,0.25)';
      ctx.fill();
    }

    ctx.fillStyle = accent;
    ctx.font = '700 11px ' + fonts.heading;
    ctx.fillText('Client sites', 16, STATUS_H + 18);
    drawHomeIndicator(ctx, w, h, 'rgba(255,255,255,0.25)');
  }

  /* ── Data — animated entrance, full bleed ── */
  function drawDataScreen(ctx, w, h, t, accent, scrollPhase, entrance) {
    entrance = entrance != null ? entrance : 1;
    var loopPulse = entrance >= 0.98 ? (0.92 + Math.sin(t * 2.2) * 0.08) : entrance;
    var bottom = contentBottom(h);
    var bg = ctx.createLinearGradient(0, 0, 0, h);
    bg.addColorStop(0, '#0f0820');
    bg.addColorStop(1, '#06040e');
    fillScreen(ctx, w, h, bg);
    drawStatusBar(ctx, w, accent, true);
    drawHomeIndicator(ctx, w, h);

    var titleIn = easeOut(clamp(entrance / 0.25, 0, 1));
    ctx.save();
    ctx.globalAlpha = titleIn;
    ctx.fillStyle = '#f8fafc';
    ctx.font = '700 18px ' + fonts.heading;
    ctx.fillText('Analytics Hub', 16, STATUS_H + 28);
    ctx.fillStyle = '#94a3b8';
    ctx.font = '11px ' + fonts.body;
    ctx.fillText('Live · Updated 2m ago', 16, STATUS_H + 46);
    ctx.restore();

    var kpis = [
      { label: 'Sessions', base: 100, range: 28.4, suffix: 'k', delta: '+12.4%', dec: 1 },
      { label: 'Conv. rate', base: 2.5, range: 1.34, suffix: '%', delta: '+0.6%', dec: 2 },
      { label: 'Revenue', base: 30, range: 12.8, prefix: '$', suffix: 'k', delta: '+8.1%', dec: 1 }
    ];
    kpis.forEach(function (k, i) {
      var inT = staggerIn(i, entrance, 0.12);
      var cardW = i === 2 ? w - 24 : (w - 36) / 2;
      var x = i === 2 ? 12 : 12 + (i % 2) * ((w - 28) / 2);
      var y = i === 2 ? STATUS_H + 128 : STATUS_H + 58;
      var slideY = lerp(24, 0, inT);
      ctx.save();
      ctx.globalAlpha = inT;
      ctx.translate(0, slideY);
      fillRound(ctx, x, y, cardW, 62, 10, 'rgba(255,255,255,0.05)');
      ctx.fillStyle = '#94a3b8';
      ctx.font = '10px ' + fonts.body;
      ctx.fillText(k.label, x + 12, y + 20);
      ctx.fillStyle = '#f8fafc';
      ctx.font = '700 20px ' + fonts.heading;
      var val = countUp(k.base, k.range, inT * loopPulse, k.dec);
      ctx.fillText((k.prefix || '') + val + (k.suffix || ''), x + 12, y + 44);
      ctx.fillStyle = accent;
      ctx.font = '600 10px ' + fonts.body;
      ctx.fillText(k.delta, x + 12, y + 56);
      ctx.restore();
    });

    var chartIn = staggerIn(0, entrance, 0.35);
    var chartY = STATUS_H + 200;
    var chartH = 130;
    ctx.save();
    ctx.globalAlpha = chartIn;
    fillRound(ctx, 12, chartY, w - 24, chartH, 12, 'rgba(255,255,255,0.04)');
    ctx.strokeStyle = accent;
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    var maxPx = Math.floor((w - 48) * chartIn);
    for (var px = 0; px <= maxPx; px++) {
      var py = chartY + chartH - 28 + Math.sin(px * 0.04 + t * 1.5) * 16 - px * 0.05;
      if (px === 0) ctx.moveTo(24 + px, py);
      else ctx.lineTo(24 + px, py);
    }
    ctx.stroke();
    ctx.restore();

    var globeIn = staggerIn(0, entrance, 0.5) * loopPulse;
    var cx = w - 64;
    var cy = chartY + chartH / 2;
    var r = 40;
    ctx.save();
    ctx.globalAlpha = globeIn;
    for (var ring = 0; ring < 3; ring++) {
      var ringT = staggerIn(ring, globeIn, ring * 0.1);
      ctx.beginPath();
      ctx.strokeStyle = 'rgba(139,92,246,' + (0.2 + ring * 0.18 * ringT) + ')';
      ctx.lineWidth = 1.5;
      ctx.arc(cx, cy, r * (0.45 + ring * 0.24) * ringT, 0, Math.PI * 2);
      ctx.stroke();
    }
    for (var n = 0; n < 10; n++) {
      var nodeT = staggerIn(n, globeIn, 0.55 + n * 0.03);
      if (nodeT <= 0) continue;
      var a = (n / 10) * Math.PI * 2 + t * 0.4;
      ctx.beginPath();
      ctx.fillStyle = accent;
      ctx.globalAlpha = nodeT * (0.5 + Math.sin(t + n) * 0.35);
      ctx.arc(cx + Math.cos(a) * r * 0.78 * nodeT, cy + Math.sin(a) * r * 0.58 * nodeT, 3 + nodeT * 2, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();

    var funnelIn = staggerIn(0, entrance, 0.65);
    var funnelY = bottom - 120;
    ctx.save();
    ctx.globalAlpha = funnelIn;
    fillRound(ctx, 12, funnelY, w - 24, 100, 12, 'rgba(255,255,255,0.04)');
    ctx.fillStyle = '#e2e8f0';
    ctx.font = '600 13px ' + fonts.body;
    ctx.fillText('Attribution funnel', 24, funnelY + 24);
    [0.78, 0.52, 0.31, 0.18].forEach(function (pct, i) {
      var barIn = staggerIn(i, funnelIn, 0.1);
      fillRound(ctx, 24, funnelY + 36 + i * 16, (w - 56) * pct * barIn, 10, 5, i === 0 ? accent : 'rgba(139,92,246,0.4)');
    });
    ctx.restore();
  }

  /* ── Marketing — full bleed dashboard + shimmer ── */
  function drawMarketingScreen(ctx, w, h, t, accent) {
    var bottom = contentBottom(h);
    var bg = ctx.createLinearGradient(0, 0, w, h);
    bg.addColorStop(0, '#0f172a');
    bg.addColorStop(1, '#1a1040');
    fillScreen(ctx, w, h, bg);
    drawStatusBar(ctx, w, accent, true);
    drawHomeIndicator(ctx, w, h);

    ctx.fillStyle = '#f8fafc';
    ctx.font = '700 18px ' + fonts.heading;
    ctx.fillText('Campaign HQ', 16, STATUS_H + 28);

    var metrics = [
      { label: 'ROAS', val: (4.2 + Math.sin(t) * 0.2).toFixed(1) + 'x' },
      { label: 'Spend', val: '$' + (18.2 + Math.cos(t * 0.8) * 0.5).toFixed(1) + 'k' },
      { label: 'Leads', val: String(Math.round(842 + Math.sin(t * 1.3) * 24)) }
    ];
    metrics.forEach(function (m, i) {
      var x = 12 + i * ((w - 32) / 3);
      var shimmer = 0.04 + Math.sin(t * 2 + i) * 0.03;
      fillRound(ctx, x, STATUS_H + 44, (w - 40) / 3, 58, 8, 'rgba(255,255,255,' + (0.05 + shimmer) + ')');
      ctx.fillStyle = '#94a3b8';
      ctx.font = '9px ' + fonts.body;
      ctx.fillText(m.label, x + 10, STATUS_H + 62);
      ctx.fillStyle = accent;
      ctx.font = '700 17px ' + fonts.heading;
      ctx.fillText(m.val, x + 10, STATUS_H + 84);
    });

    fillRound(ctx, 12, STATUS_H + 114, w - 24, bottom - STATUS_H - 240, 12, 'rgba(255,255,255,0.04)');
    ctx.strokeStyle = accent;
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    for (var i = 0; i < 48; i++) {
      var x = 24 + i * ((w - 48) / 47);
      var y = STATUS_H + 200 + Math.sin(i * 0.3 + t * 2) * 14 - i * 0.3;
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.stroke();

    var channels = [
      { name: 'Paid Search', pct: 0.82, c: '#3b82f6' },
      { name: 'Social', pct: 0.64, c: '#8b5cf6' },
      { name: 'Email', pct: 0.48, c: accent },
      { name: 'Display', pct: 0.35, c: '#06b6d4' },
      { name: 'Organic', pct: 0.28, c: '#22c55e' }
    ];
    ctx.fillStyle = '#cbd5e1';
    ctx.font = '600 13px ' + fonts.body;
    ctx.fillText('Channel mix', 16, bottom - 168);
    channels.forEach(function (ch, i) {
      var y = bottom - 152 + i * 26;
      ctx.fillStyle = '#94a3b8';
      ctx.font = '10px ' + fonts.body;
      ctx.fillText(ch.name, 20, y);
      fillRound(ctx, 110, y - 10, w - 130, 8, 4, 'rgba(255,255,255,0.08)');
      fillRound(ctx, 110, y - 10, (w - 130) * ch.pct, 8, 4, ch.c);
    });
  }

  /* ── Ecommerce — full product feed scroll ── */
  function drawEcommerceScreen(ctx, w, h, t, accent, scrollPhase) {
    scrollPhase = scrollPhase || (t * 0.08) % 1;
    var bottom = contentBottom(h);
    fillScreen(ctx, w, h, '#f4f4f5');
    drawStatusBar(ctx, w, '#111827', false);
    drawHomeIndicator(ctx, w, h, 'rgba(0,0,0,0.15)');

    ctx.fillStyle = '#111827';
    ctx.font = '700 20px ' + fonts.heading;
    ctx.fillText('Shop', 16, STATUS_H + 28);
    ctx.fillStyle = accent;
    ctx.font = '600 12px ' + fonts.body;
    ctx.fillText('Cart (2)', w - 72, STATUS_H + 28);

    var products = [
      { name: 'Studio Kit', price: '$129', color: '#dbeafe' },
      { name: 'Pro Bundle', price: '$249', color: '#fce7f3' },
      { name: 'Launch Pack', price: '$89', color: '#d1fae5' },
      { name: 'Add-on Lens', price: '$49', color: '#fef3c7' },
      { name: 'Field Kit', price: '$179', color: '#e0e7ff' }
    ];
    var scrollY = scrollPhase * 200;

    ctx.save();
    ctx.beginPath();
    ctx.rect(0, STATUS_H + 40, w, bottom - STATUS_H - 100);
    ctx.clip();
    products.forEach(function (p, i) {
      var col = i % 2;
      var row = Math.floor(i / 2);
      var x = 12 + col * ((w - 28) / 2);
      var y = STATUS_H + 48 + row * 168 - scrollY;
      if (y < STATUS_H + 36 || y > bottom - 60) return;
      fillRound(ctx, x, y, (w - 32) / 2, 156, 12, '#fff');
      strokeRound(ctx, x, y, (w - 32) / 2, 156, 12, 'rgba(0,0,0,0.06)', 1);
      fillRound(ctx, x + 8, y + 8, (w - 48) / 2, 88, 8, p.color);
      ctx.fillStyle = '#111827';
      ctx.font = '600 13px ' + fonts.body;
      ctx.fillText(p.name, x + 8, y + 112);
      ctx.fillStyle = accent;
      ctx.font = '700 15px ' + fonts.heading;
      ctx.fillText(p.price, x + 8, y + 134);
    });
    ctx.restore();

    fillRound(ctx, 12, bottom - 72, w - 24, 52, 14, accent);
    ctx.fillStyle = '#fff';
    ctx.font = '700 15px ' + fonts.heading;
    ctx.fillText('Checkout · $378', w / 2 - 52, bottom - 42);
  }

  /* ── SEO — full height SERP + chart ── */
  function drawSeoScreen(ctx, w, h, t, accent) {
    var bottom = contentBottom(h);
    fillScreen(ctx, w, h, '#030712');
    drawStatusBar(ctx, w, accent, true);
    drawHomeIndicator(ctx, w, h);

    ctx.fillStyle = '#f8fafc';
    ctx.font = '700 18px ' + fonts.heading;
    ctx.fillText('Search Console', 16, STATUS_H + 28);

    fillRound(ctx, 12, STATUS_H + 44, 96, 96, 48, 'rgba(34,211,238,0.12)');
    ctx.strokeStyle = accent;
    ctx.lineWidth = 6;
    ctx.beginPath();
    ctx.arc(60, STATUS_H + 92, 36, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * (0.78 + Math.sin(t * 0.5) * 0.04));
    ctx.stroke();
    ctx.fillStyle = accent;
    ctx.font = '700 22px ' + fonts.heading;
    ctx.fillText('78', 46, STATUS_H + 98);
    ctx.fillStyle = '#94a3b8';
    ctx.font = '10px ' + fonts.body;
    ctx.fillText('Visibility', 38, STATUS_H + 114);

    fillRound(ctx, 116, STATUS_H + 44, w - 128, 96, 10, 'rgba(255,255,255,0.04)');
    ctx.strokeStyle = accent;
    ctx.lineWidth = 2;
    ctx.beginPath();
    for (var i = 0; i < 14; i++) {
      var x = 128 + i * ((w - 156) / 13);
      var y = STATUS_H + 110 - i * 3 - Math.sin(t + i) * 4;
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.stroke();

    var results = [
      { rank: 1, title: 'Field Theory Lab — Digital Growth', url: 'fieldtheory.ai', rich: true },
      { rank: 2, title: 'Services: Web, SEO, Analytics', url: 'fieldtheory.ai/services', rich: false },
      { rank: 3, title: 'Case Study: Ecommerce CRO +22%', url: 'fieldtheory.ai/work', rich: false },
      { rank: 5, title: 'Blog: Technical SEO Playbook', url: 'fieldtheory.ai/blog', rich: false }
    ];
    results.forEach(function (r, i) {
      var y = STATUS_H + 156 + i * ((bottom - STATUS_H - 180) / 4);
      fillRound(ctx, 12, y, w - 24, (bottom - STATUS_H - 200) / 4 - 8, 10, 'rgba(255,255,255,0.03)');
      ctx.fillStyle = accent;
      ctx.font = '700 12px ' + fonts.heading;
      ctx.fillText('#' + r.rank, 24, y + 24);
      ctx.fillStyle = '#e2e8f0';
      ctx.font = '600 12px ' + fonts.body;
      wrapText(ctx, r.title, 48, y + 24, w - 72, 14);
      ctx.fillStyle = '#64748b';
      ctx.font = '10px ' + fonts.body;
      ctx.fillText(r.url, 48, y + 44);
      if (r.rich) {
        fillRound(ctx, 48, y + 52, w - 72, 12, 4, 'rgba(34,211,238,0.2)');
      }
    });
  }

  /* ── AI — full screen chat ── */
  function drawAiScreen(ctx, w, h, t, accent) {
    var bottom = contentBottom(h);
    var bg = ctx.createLinearGradient(0, 0, 0, h);
    bg.addColorStop(0, '#1a0a2e');
    bg.addColorStop(1, '#0a0412');
    fillScreen(ctx, w, h, bg);
    drawStatusBar(ctx, w, accent, true);
    drawHomeIndicator(ctx, w, h);

    fillRound(ctx, 12, STATUS_H + 4, 140, 26, 13, 'rgba(236,72,153,0.22)');
    ctx.fillStyle = accent;
    ctx.font = '600 11px ' + fonts.body;
    ctx.fillText('GPT-4o · Field Theory', 24, STATUS_H + 22);

    var msgs = [
      { user: false, text: 'Summarize Q3 funnel performance and flag anomalies.' },
      { user: true, text: 'Focus on paid + organic mix.' },
      { user: false, text: '3 insights ready. Paid ROAS up 18%. Organic conv. flat. Recommend AEO audit.' },
      { user: false, text: 'Drafted 4 action items for the growth team.' }
    ];
    var msgAreaH = bottom - STATUS_H - 130;
    var msgH = msgAreaH / msgs.length;
    msgs.forEach(function (m, i) {
      var y = STATUS_H + 36 + i * msgH;
      var mh = msgH - 10;
      var mw = w - (m.user ? 56 : 24);
      var mx = m.user ? 40 : 12;
      fillRound(ctx, mx, y, mw, mh, 14, m.user ? 'rgba(236,72,153,0.22)' : 'rgba(255,255,255,0.07)');
      ctx.fillStyle = m.user ? '#fce7f3' : '#e2e8f0';
      ctx.font = '11px ' + fonts.body;
      wrapText(ctx, m.text, mx + 12, y + 20, mw - 24, 15);
    });

    var typing = Math.floor(t * 2) % 4;
    fillRound(ctx, 12, bottom - 108, w - 24, 48, 14, 'rgba(255,255,255,0.06)');
    ctx.fillStyle = '#94a3b8';
    ctx.font = '12px ' + fonts.body;
    ctx.fillText('Ask anything…', 24, bottom - 82);
    if (typing > 0) {
      ctx.fillStyle = accent;
      ctx.font = '600 10px ' + fonts.body;
      ctx.fillText('Analyzing' + '.'.repeat(typing), 24, bottom - 64);
    }

    fillRound(ctx, 12, bottom - 52, w - 24, 40, 12, accent);
    ctx.fillStyle = '#fff';
    ctx.font = '600 13px ' + fonts.body;
    ctx.fillText('Send', w - 56, bottom - 28);
  }

  var DRAWERS = {
    websites: drawWebDevScreen,
    data: drawDataScreen,
    marketing: drawMarketingScreen,
    ecommerce: drawEcommerceScreen,
    seo: drawSeoScreen,
    ai: drawAiScreen
  };

  window.FTCGoTimeScreens = {
    version: '5.58.2',
    draw: function (ctx, w, h, chapterId, t, accent, scrollPhase, entrance) {
      var fn = DRAWERS[chapterId] || drawWebDevScreen;
      fn(ctx, w, h, t, accent || '#10b981', scrollPhase, entrance);
    },
    blend: function (ctx, w, h, idA, idB, blend, t, accentA, accentB, scrollPhase, entrance) {
      window.FTCGoTimeScreens.draw(ctx, w, h, idA, t, accentA, scrollPhase, entrance);
      if (blend > 0.02 && idB) {
        ctx.save();
        ctx.globalAlpha = clamp(blend, 0, 1);
        window.FTCGoTimeScreens.draw(ctx, w, h, idB, t, accentB, 0, entrance);
        ctx.restore();
      }
    }
  };
})();
