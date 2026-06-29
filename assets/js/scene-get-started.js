/* ─────────────────────────────────────────────────────────────────────────────
   FTL Get-Started — 3D Service Grid  v5.48.17  ·  Three.js r128
   6 service category cards · one Three.js scene per card · hover float/rotate
   Build: 2026-06-23T09:00Z
─────────────────────────────────────────────────────────────────────────────*/
(function () {
  'use strict';

  var ASSET_BASE = (window.ftcData && window.ftcData.pluginUrl) ? window.ftcData.pluginUrl : '';
  /* Real screenshots mapped to each service card's device screens.
     fit: fill stretches to the bezel; contain shows the full capture;
     top-width scales to screen width, top-aligned, crops overflow at bottom. */
  var SERVICE_SCREEN_IMGS = {
    websites: {
      desktop: { path: 'assets/images/portfolio/LetsPlant_featured.png', fit: 'contain' },
      tablet:  { path: 'assets/images/portfolio/LynnScholarship_mobile.png', fit: 'top-width' },
      phone:   { path: 'assets/images/portfolio/TEP_mobile_home.png', fit: 'contain' }
    },
    ecommerce: {
      landscape: { path: 'assets/images/portfolio/MountainWestSales_home.png', fit: 'fill' }
    },
    seo: {
      desktop: { path: 'assets/images/portfolio/TheEducaationPlan_desktop.png', fit: 'contain' },
      tablet:  { path: 'assets/images/portfolio/TEP_mobile_menu.png', fit: 'fill' },
      phone:   { path: 'assets/images/portfolio/TEP_mobile_content.png', fit: 'contain' }
    },
    marketing: {
      landscape: { path: 'assets/images/dashboard-design/linkedin-company-pages.png', fit: 'fill' },
      phone:     { path: 'assets/images/portfolio/EnhancedWellness_mobile_home.png', fit: 'contain' }
    }
  };
  var PORTFOLIO_IMGS = SERVICE_SCREEN_IMGS.websites;

  /* ── Service data ─────────────────────────────────────────────────────── */
  var CARDS = [
    { id: 'websites',
      prompt: 'Tell me about Website Development services',
      url: '/services/website-development/',
      label: 'Website Development & Core Tech',
      tagline: 'Performance-first websites built to convert.',
      device: 'multidevice', screen: 'website',
      hex: 0x10b981, css: '#10b981' },
    { id: 'ecommerce',
      prompt: 'Tell me about Ecommerce & Conversion Rate Optimization (CRO) services',
      url: '/services/ecommerce-cro/',
      label: 'Ecommerce & CRO',
      tagline: 'Turn browsers into buyers.',
      device: 'ipad-landscape', screen: 'ecommerce',
      hex: 0x06b6d4, css: '#06b6d4' },
    { id: 'seo',
      prompt: 'Tell me about Search & Discovery Optimization (SEO / AEO) services',
      url: '/services/seo-aeo/',
      label: 'Search & SEO / AEO',
      tagline: 'Rank higher, answer smarter.',
      device: 'multidevice-search', screen: 'search',
      hex: 0x06b6d4, css: '#06b6d4' },
    { id: 'marketing',
      prompt: 'Tell me about Digital Marketing services',
      url: '/services/digital-marketing/',
      label: 'Digital Marketing & Growth',
      tagline: 'Campaigns that compound over time.',
      device: 'multidevice-marketing', screen: 'marketing',
      hex: 0x3b82f6, css: '#3b82f6' },
    { id: 'ai',
      prompt: 'Tell me about AI and Innovation services',
      url: '/services/ai-innovation/',
      label: 'Technology, Innovation and A.I.',
      tagline: "Build tomorrow's edge today.",
      device: 'glb-model', modelPath: 'assets/models/ai-neural-network.glb', screen: 'ai',
      hex: 0xff4b32, css: '#ff4b32' },
    { id: 'data',
      prompt: 'Tell me about Data, Analysis and Visualization services',
      url: '/services/data-analysis-visualization/',
      label: 'Data, Analysis & Visualization',
      tagline: 'Turn metrics into decisions.',
      device: 'dataviz', screen: 'analytics',
      hex: 0x8b5cf6, css: '#8b5cf6' },
  ];

  /* ── Light device materials — visible on dark stage backgrounds ─────── */
  function devFrameMat() {
    return new THREE.MeshPhysicalMaterial({
      color: 0xecedee, metalness: 0.78, roughness: 0.24,
      clearcoat: 0.85, envMapIntensity: 1.5
    });
  }
  function devStandMat() {
    return new THREE.MeshPhysicalMaterial({
      color: 0xd6d8dc, metalness: 0.88, roughness: 0.20,
      clearcoat: 0.8, envMapIntensity: 1.3
    });
  }
  function devTabletMat() {
    return new THREE.MeshPhysicalMaterial({
      color: 0xf2f3f5, metalness: 0.82, roughness: 0.18,
      clearcoat: 0.92, envMapIntensity: 1.7
    });
  }
  function devPhoneMat() {
    return new THREE.MeshPhysicalMaterial({
      color: 0xeaeaec, metalness: 0.72, roughness: 0.22,
      clearcoat: 0.85, envMapIntensity: 1.2
    });
  }
  function devAccentMat() {
    return new THREE.MeshPhysicalMaterial({
      color: 0xb8bcc4, metalness: 0.85, roughness: 0.25
    });
  }
  function devBezelMat() {
    return new THREE.MeshStandardMaterial({ color: 0x9a9ea6 });
  }

  /* Field Theory brand palette (matches app.css :root) */
  var FTC_BRAND = {
    yellow: 0xffd94d,
    blue:   0x397cf6,
    green:  0x35db4f,
    red:    0xff4b32,
    sky:    0x5ec8ff,
    black:  0x101010,
    panel:  0x202020,
    white:  0xf5f2ea
  };

  var FTC_BRAND_CSS = {
    yellow: '#ffd94d',
    blue:   '#397cf6',
    green:  '#35db4f',
    red:    '#ff4b32',
    sky:    '#5ec8ff',
    black:  '#101010',
    panel:  '#202020',
    white:  '#f5f2ea'
  };

  function hexToRgba(hex, alpha) {
    if (!hex || typeof hex !== 'string') return 'rgba(57,124,246,' + alpha + ')';
    var h = hex.replace('#', '');
    if (h.length === 3) h = h.split('').map(function (ch) { return ch + ch; }).join('');
    var num = parseInt(h, 16);
    if (!isFinite(num)) return 'rgba(57,124,246,' + alpha + ')';
    var r = (num >> 16) & 255;
    var g = (num >> 8) & 255;
    var b = num & 255;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function applyGlbBrandColors(root) {
    if (!root || !window.THREE) return;
    var nameMap = {
      Orange:            FTC_BRAND.yellow,
      Blue:              FTC_BRAND.blue,
      'Blue Light':      FTC_BRAND.blue,
      'Blue Glossy Light': 0x5a94f8,
      Purple:            FTC_BRAND.red,
      'Purple Glossy':   0xff6b56,
      Metal:             FTC_BRAND.white
    };
    root.traverse(function (ch) {
      if (!ch.isMesh || !ch.material) return;
      var mats = Array.isArray(ch.material) ? ch.material : [ch.material];
      mats.forEach(function (mat) {
        var hex = nameMap[mat.name || ''];
        if (hex === undefined) return;
        if (mat.color && mat.color.setHex) mat.color.setHex(hex);
        if (mat.emissive && mat.emissive.isColor) {
          mat.emissive.setHex(hex);
          mat.emissiveIntensity = mat.emissiveIntensity || 0.06;
        }
        mat.needsUpdate = true;
      });
    });
  }

  function makeNeuralContactShadow() {
    var cv = document.createElement('canvas');
    cv.width = 256;
    cv.height = 256;
    var ctx = cv.getContext('2d');
    var g = ctx.createRadialGradient(128, 128, 6, 128, 128, 122);
    g.addColorStop(0, 'rgba(0,0,0,0.58)');
    g.addColorStop(0.48, 'rgba(0,0,0,0.24)');
    g.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, 256, 256);
    var tex = new THREE.CanvasTexture(cv);
    var mesh = new THREE.Mesh(
      new THREE.PlaneGeometry(1, 1),
      new THREE.MeshBasicMaterial({ map: tex, transparent: true, depthWrite: false, opacity: 0.42 })
    );
    mesh.rotation.x = -Math.PI / 2;
    mesh.renderOrder = -1;
    return mesh;
  }

  /* ── Navigate ─────────────────────────────────────────────────────────── */
  function navigateToCard(data) {
    if (data.url) {
      window.location.href = data.url;
      return;
    }
    var inp  = document.querySelector('[data-ftc-chat-input]');
    var form = document.querySelector('[data-ftc-chat-form]');
    if (!inp || !form) return;
    inp.value = data.prompt;
    inp.dispatchEvent(new Event('input',  { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
    setTimeout(function () {
      form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }, 80);
  }

  /* ── Script loader ────────────────────────────────────────────────────── */
  function loadScript(url, cb) {
    var s = document.createElement('script');
    s.src = url;
    s.onload = cb;
    s.onerror = function () { console.warn('FTL scene: failed', url); cb(); };
    document.head.appendChild(s);
  }

  function loadDeps(cb) {
    var T  = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    var RL = 'https://unpkg.com/three@0.128.0/examples/js/loaders/RGBELoader.js';
    if (window.THREE) {
      if (window.THREE.RGBELoader) { cb(); return; }
      loadScript(RL, cb);
    } else {
      loadScript(T, function () { loadScript(RL, cb); });
    }
  }

  function ensureGltfLoader(cb) {
    var GL = 'https://unpkg.com/three@0.128.0/examples/js/loaders/GLTFLoader.js';
    if (window.THREE && window.THREE.GLTFLoader) { cb(true); return; }
    loadScript(GL, function () { cb(!!(window.THREE && window.THREE.GLTFLoader)); });
  }

  function ftcAssetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return ASSET_BASE + path.replace(/^\//, '');
  }

  function loadImage(url, cb) {
    if (!url) { cb(null); return; }
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function () { cb(img); };
    img.onerror = function () { console.warn('FTL scene: image failed', url); cb(null); };
    img.src = url;
  }

  function normalizeScreenSlot(raw) {
    if (!raw) return null;
    if (typeof raw === 'string') return { path: raw, fit: 'contain' };
    return {
      path: raw.path || '',
      fit: raw.fit || 'contain'
    };
  }

  function screenSlotPath(raw) {
    var slot = normalizeScreenSlot(raw);
    return slot ? slot.path : '';
  }

  function makeBrightScreenTex(planeW, planeH) {
    var planeAspect = planeW / planeH;
    var maxDim = 512;
    var W, H;
    if (planeAspect >= 1) {
      W = maxDim;
      H = Math.max(2, Math.round(maxDim / planeAspect));
    } else {
      H = maxDim;
      W = Math.max(2, Math.round(maxDim * planeAspect));
    }
    var cv = document.createElement('canvas');
    cv.width = W;
    cv.height = H;
    var ctx = cv.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);
    var tex = new THREE.CanvasTexture(cv);
    if (THREE.sRGBEncoding) tex.encoding = THREE.sRGBEncoding;
    tex.needsUpdate = true;
    return tex;
  }

  /* Bake screenshot at up to 2048px — fill (stretch), contain, or top-width (width-fit + top crop). */
  function makePortfolioScreenTex(img, planeW, planeH, opts) {
    opts = opts || {};
    var fitMode = opts.fit || 'contain';

    var planeAspect = planeW / planeH;
    var iw = img.width;
    var ih = img.height;

    var maxDim = 2048;
    var W, H;
    if (planeAspect >= 1) {
      W = maxDim;
      H = Math.max(2, Math.round(maxDim / planeAspect));
    } else {
      H = maxDim;
      W = Math.max(2, Math.round(maxDim * planeAspect));
    }

    var cv = document.createElement('canvas');
    cv.width = W;
    cv.height = H;
    var ctx = cv.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    if (fitMode === 'fill') {
      ctx.drawImage(img, 0, 0, iw, ih, 0, 0, W, H);
    } else if (fitMode === 'top-width') {
      var twScale = W / iw;
      ctx.drawImage(img, 0, 0, iw, ih, 0, 0, W, ih * twScale);
    } else {
      var scale = Math.min(W / iw, H / ih);
      var dw = iw * scale;
      var dh = ih * scale;
      var dx = (W - dw) / 2;
      var dy = (H - dh) / 2;
      ctx.drawImage(img, 0, 0, iw, ih, dx, dy, dw, dh);
    }

    var tex = new THREE.CanvasTexture(cv);
    tex.minFilter = THREE.LinearFilter;
    tex.magFilter = THREE.LinearFilter;
    tex.generateMipmaps = false;
    if (THREE.sRGBEncoding) tex.encoding = THREE.sRGBEncoding;
    if (typeof tex.anisotropy !== 'undefined') tex.anisotropy = 8;
    tex.needsUpdate = true;
    return tex;
  }

  function applyPortfolioScreenMat(mesh) {
    if (!mesh || !mesh.material) return;
    applyGoTimeScreenMat(mesh, 1.0);
  }

  function getServiceScreenImgs(cardId) {
    return SERVICE_SCREEN_IMGS[cardId] || null;
  }

  function bindPhotoScreen(mesh, slot, planeW, planeH) {
    if (!mesh || !window.THREE) return;
    var cfg = normalizeScreenSlot(slot);
    if (!cfg || !cfg.path) return;
    mesh.userData.isPortfolioScreen = true;
    if (mesh.material) {
      var placeholder = makeBrightScreenTex(planeW, planeH);
      var prevPlaceholder = mesh.material.map;
      mesh.material.map = placeholder;
      if (mesh.material.color) mesh.material.color.setRGB(1, 1, 1);
      if (mesh.material.emissive) mesh.material.emissive.setRGB(0, 0, 0);
      if ('emissiveIntensity' in mesh.material) mesh.material.emissiveIntensity = 0;
      mesh.material.needsUpdate = true;
      if (prevPlaceholder && prevPlaceholder !== placeholder && prevPlaceholder.dispose) prevPlaceholder.dispose();
    }
    loadImage(ftcAssetUrl(cfg.path), function (img) {
      if (!img || !mesh.parent) return;
      var tex = makePortfolioScreenTex(img, planeW, planeH, cfg);
      var prev = mesh.material && mesh.material.map;
      mesh.material.map = tex;
      if (mesh.material.color) mesh.material.color.setRGB(1, 1, 1);
      mesh.material.needsUpdate = true;
      if (prev && prev !== tex && prev.dispose) prev.dispose();
    });
  }

  function preloadServiceScreenImages() {
    var seen = {};
    Object.keys(SERVICE_SCREEN_IMGS).forEach(function (id) {
      var slots = SERVICE_SCREEN_IMGS[id];
      Object.keys(slots).forEach(function (key) {
        var path = screenSlotPath(slots[key]);
        if (!path || seen[path]) return;
        seen[path] = true;
        loadImage(ftcAssetUrl(path), function () {});
      });
    });
  }

  function applyGoTimeScreenMat(mesh, intensity) {
    if (!mesh || !mesh.material) return;
    var k = intensity != null ? intensity : 1.0;
    if (mesh.material.map && THREE.sRGBEncoding) {
      mesh.material.map.encoding = THREE.sRGBEncoding;
      mesh.material.map.minFilter = THREE.LinearFilter;
      mesh.material.map.magFilter = THREE.LinearFilter;
      mesh.material.map.generateMipmaps = false;
      mesh.material.map.needsUpdate = true;
    }
    if (mesh.material.color) mesh.material.color.setRGB(k, k, k);
    mesh.material.needsUpdate = true;
  }

  function applyGoTimeEmissiveScreen(mesh, tex) {
    if (!mesh) return;
    if (mesh.material && mesh.material.dispose) mesh.material.dispose();
    mesh.material = new THREE.MeshStandardMaterial({
      map: tex,
      emissive: 0xffffff,
      emissiveMap: tex,
      emissiveIntensity: 0.62,
      roughness: 1,
      metalness: 0,
      envMapIntensity: 0
    });
    mesh.userData.isGoTimeScreen = true;
    if (tex && THREE.sRGBEncoding) {
      tex.encoding = THREE.sRGBEncoding;
      tex.minFilter = THREE.LinearFilter;
      tex.magFilter = THREE.LinearFilter;
      tex.generateMipmaps = false;
    }
    mesh.material.needsUpdate = true;
  }

  function tuneGoTimeBezelMaterials(body, cardId, envMap) {
    body.traverse(function (ch) {
      if (!ch.isMesh || !ch.material || ch.userData.isGoTimeScreen) return;
      if (ch.material.map && ch.material.isMeshBasicMaterial) return;
      if (ch.material.isMeshStandardMaterial || ch.material.isMeshPhysicalMaterial) {
        if (envMap) {
          ch.material.envMap = envMap;
          ch.material.envMapIntensity = ch.material.envMapIntensity || 1.1;
        }
        ch.castShadow = true;
        ch.receiveShadow = true;
        ch.material.needsUpdate = true;
      }
    });
  }

  function tuneGoTimeScreenMaterials(body, cardId) {
    var screenK = 0.85;
    body.traverse(function (ch) {
      if (!ch.isMesh || !ch.material) return;
      if (ch.userData.isPortfolioScreen) return;
      if (ch.material.map && ch.material.isMeshBasicMaterial) {
        applyGoTimeScreenMat(ch, screenK);
        if (!ch.material.emissive) ch.material.emissive = new THREE.Color(0xffffff);
        ch.material.emissiveIntensity = 0.02;
      }
      if (ch.material.isMeshStandardMaterial || ch.material.isMeshPhysicalMaterial) {
        if (ch.material.map && THREE.sRGBEncoding) {
          ch.material.map.encoding = THREE.sRGBEncoding;
          ch.material.map.needsUpdate = true;
        }
      }
    });
  }

  function tuneGoTimeDeviceBody(body, cardId) {
    tuneGoTimeScreenMaterials(body, cardId);
  }

  /* ══════════════════════════════════════════════════════════════════════
     CANVAS TEXTURE UTILITIES
  ══════════════════════════════════════════════════════════════════════ */

  function rrPath(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y,     x + w, y + r,     r);
    ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
    ctx.lineTo(x + r, y + h);
    ctx.arcTo(x,     y + h, x,     y + h - r, r);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x,     y,     x + r, y,         r);
    ctx.closePath();
  }

  function makeScreenTex(type, css) {
    var W = (type === 'laptop') ? 640 : 360;
    var H = (type === 'laptop') ? 400 : 520;
    var cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    var ctx = cv.getContext('2d');

    /* ── Laptop ── detailed browser + website wireframe */
    if (type === 'laptop') {
      ctx.fillStyle = '#0a1220';
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = '#0f1a2c';
      ctx.fillRect(0, 0, W, 36);
      ['#ff5f57', '#ffbd2e', '#28c840'].forEach(function (c, i) {
        ctx.beginPath(); ctx.arc(16 + i * 22, 18, 7, 0, Math.PI * 2);
        ctx.fillStyle = c; ctx.fill();
      });
      rrPath(ctx, 70, 8, W - 140, 20, 5);
      ctx.fillStyle = 'rgba(255,255,255,0.08)'; ctx.fill();
      ctx.fillStyle = 'rgba(255,255,255,0.5)';
      ctx.font = '10px monospace'; ctx.textAlign = 'center';
      ctx.fillText('fieldtheory.co', W / 2, 22);
      ctx.fillStyle = 'rgba(255,255,255,0.04)';
      ctx.fillRect(0, 36, W, 26);
      ['Home', 'Services', 'About', 'Contact'].forEach(function (item, i) {
        ctx.fillStyle = i === 1 ? css : 'rgba(255,255,255,0.4)';
        ctx.font = '9px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(item, 20 + i * 80, 53);
      });
      ctx.fillStyle = 'rgba(255,255,255,0.025)';
      ctx.fillRect(0, 62, W, 96);
      ctx.fillStyle = css;
      ctx.fillRect(20, 74, Math.round(W * 0.54), 20);
      ctx.fillStyle = css + '55';
      ctx.fillRect(20, 100, Math.round(W * 0.38), 13);
      ctx.fillStyle = css + '33';
      ctx.fillRect(20, 118, Math.round(W * 0.46), 13);
      rrPath(ctx, 20, 136, 88, 24, 6);
      ctx.fillStyle = css; ctx.fill();
      ctx.fillStyle = '#000'; ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Get Started', 64, 152);
      var col2Y = 172;
      ctx.fillStyle = css + '88';
      ctx.fillRect(20, col2Y, Math.round(W * 0.33), 9);
      [14, 26, 38].forEach(function (dy) {
        ctx.fillStyle = 'rgba(255,255,255,0.13)';
        ctx.fillRect(20, col2Y + dy, Math.round(W * (0.33 - dy * 0.003)), 7);
      });
      rrPath(ctx, Math.round(W * 0.55), col2Y - 4, Math.round(W * 0.41), 66, 5);
      ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1; ctx.stroke();
      ctx.beginPath();
      var px0 = Math.round(W * 0.555), py0 = col2Y + 54;
      ctx.moveTo(px0, py0); ctx.lineTo(px0 + 20, py0 - 20); ctx.lineTo(px0 + 40, py0 - 8);
      ctx.lineTo(px0 + 60, py0 - 30); ctx.lineTo(px0 + 80, py0);
      ctx.closePath();
      ctx.fillStyle = 'rgba(255,255,255,0.1)'; ctx.fill();
      var cardY = H - 78;
      var cardW = Math.floor((W - 40) / 3) - 4;
      [0, 1, 2].forEach(function (i) {
        var cx2 = 20 + i * (cardW + 6);
        rrPath(ctx, cx2, cardY, cardW, 66, 5);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.strokeStyle = css + '33'; ctx.lineWidth = 1; ctx.stroke();
        ctx.beginPath(); ctx.arc(cx2 + 14, cardY + 14, 8, 0, Math.PI * 2);
        ctx.fillStyle = css + '44'; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.fillRect(cx2 + 8, cardY + 28, cardW - 20, 7);
        ctx.fillStyle = 'rgba(255,255,255,0.22)';
        ctx.fillRect(cx2 + 8, cardY + 40, Math.round((cardW - 20) * 0.65), 6);
        ctx.fillRect(cx2 + 8, cardY + 52, Math.round((cardW - 20) * 0.45), 5);
      });
    }

    /* ── Phone — faux Instagram timeline (marketing) ── */
    if (type === 'instagram') {
      ctx.fillStyle = '#000'; ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = '#111'; ctx.fillRect(0, 0, W, 44);
      ctx.fillStyle = '#fff'; ctx.font = 'bold 15px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Instagram', W / 2, 28);
      ctx.strokeStyle = 'rgba(255,255,255,0.12)'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(0, 44); ctx.lineTo(W, 44); ctx.stroke();

      var storyY = 52;
      for (var si = 0; si < 5; si++) {
        var sxc = 14 + si * 58;
        ctx.beginPath(); ctx.arc(sxc + 22, storyY + 22, 22, 0, Math.PI * 2);
        ctx.strokeStyle = si === 0 ? css : 'rgba(255,255,255,0.25)'; ctx.lineWidth = 2.5; ctx.stroke();
        ctx.beginPath(); ctx.arc(sxc + 22, storyY + 22, 18, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(255,255,255,0.08)'; ctx.fill();
      }

      var feedY = 108;
      var posts = [
        { accent: '#E1306C', user: 'brand_official' },
        { accent: '#1877F2', user: 'growth_team' },
        { accent: '#10b981', user: 'campaigns' }
      ];
      posts.forEach(function (post, pi) {
        var py = feedY + pi * 148;
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fillRect(0, py, W, 140);
        ctx.beginPath(); ctx.arc(22, py + 22, 12, 0, Math.PI * 2);
        ctx.fillStyle = post.accent; ctx.fill();
        ctx.fillStyle = '#fff'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(post.user, 40, py + 26);
        rrPath(ctx, 14, py + 40, W - 28, 72, 6);
        ctx.fillStyle = post.accent + '33'; ctx.fill();
        ctx.strokeStyle = post.accent + '66'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = 'rgba(255,255,255,0.55)'; ctx.font = '10px sans-serif';
        ctx.fillText('Sponsored · Learn more', 14, py + 128);
      });
    }

    /* ── Phone — marketing analytics mobile app */
    if (type === 'phone') {
      ctx.fillStyle = '#080e1a'; ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = 'rgba(255,255,255,0.06)'; ctx.fillRect(0, 0, W, 52);
      ctx.fillStyle = '#fff'; ctx.font = 'bold 16px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Campaigns', W / 2, 32);
      ctx.beginPath(); ctx.arc(W - 20, 26, 3, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,0.4)'; ctx.fill();
      ctx.beginPath(); ctx.arc(W - 32, 26, 3, 0, Math.PI * 2); ctx.fill();
      ctx.beginPath(); ctx.arc(W - 44, 26, 3, 0, Math.PI * 2); ctx.fill();
      [['CTR: 4.2%', 65], ['ROAS: 3.8x', 195]].forEach(function (p) {
        rrPath(ctx, p[1], 60, 90, 26, 13);
        ctx.fillStyle = css + '2a'; ctx.fill();
        ctx.strokeStyle = css + '88'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = css; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(p[0], p[1] + 45, 77);
      });
      var chartY = 98, chartH = 130, chartW = W - 36;
      ctx.fillStyle = 'rgba(255,255,255,0.03)'; ctx.fillRect(18, chartY, chartW, chartH);
      [0.33, 0.66].forEach(function (t) {
        ctx.strokeStyle = 'rgba(255,255,255,0.05)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(18, chartY + t * chartH); ctx.lineTo(18 + chartW, chartY + t * chartH);
        ctx.stroke();
      });
      var linePts = [[0, 0.82], [0.13, 0.70], [0.26, 0.72], [0.40, 0.52], [0.53, 0.48], [0.67, 0.30], [0.80, 0.20], [1.0, 0.06]];
      ctx.beginPath();
      linePts.forEach(function (p, i) {
        var px = 18 + p[0] * chartW;
        var py = chartY + p[1] * chartH;
        i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
      });
      ctx.strokeStyle = css; ctx.lineWidth = 2.5; ctx.stroke();
      ctx.lineTo(18 + chartW, chartY + chartH);
      ctx.lineTo(18, chartY + chartH);
      ctx.closePath();
      ctx.fillStyle = css + '18'; ctx.fill();
      ctx.beginPath(); ctx.arc(18 + chartW, chartY + 0.06 * chartH, 4, 0, Math.PI * 2);
      ctx.fillStyle = css; ctx.fill();
      var barVals = [0.40, 0.58, 0.46, 0.74, 0.64, 0.84, 1.0];
      var barAreaY = 242, barAreaH = 220;
      barVals.forEach(function (v, i) {
        var bw = 36, gap = 7;
        var bx = 16 + i * (bw + gap);
        var bh = v * (barAreaH - 20);
        var by = barAreaY + (barAreaH - 20) - bh;
        rrPath(ctx, bx, by, bw, bh, 4);
        ctx.fillStyle = i === 6 ? css : css + '50'; ctx.fill();
      });
      ['M', 'T', 'W', 'T', 'F', 'S', 'S'].forEach(function (d, i) {
        ctx.fillStyle = 'rgba(255,255,255,0.38)';
        ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(d, 16 + 18 + i * 43, H - 6);
      });
    }

    /* ── Phone (website) ── mobile browser wireframe */
    if (type === 'website') {
      ctx.fillStyle = '#0a1220';
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = '#0f1a2c';
      ctx.fillRect(0, 0, W, 40);
      ['#ff5f57', '#ffbd2e', '#28c840'].forEach(function (c, i) {
        ctx.beginPath(); ctx.arc(14 + i * 18, 20, 6, 0, Math.PI * 2);
        ctx.fillStyle = c; ctx.fill();
      });
      rrPath(ctx, 52, 10, W - 64, 20, 5);
      ctx.fillStyle = 'rgba(255,255,255,0.08)'; ctx.fill();
      ctx.fillStyle = 'rgba(255,255,255,0.5)';
      ctx.font = '9px monospace'; ctx.textAlign = 'center';
      ctx.fillText('yoursite.com', W / 2, 24);
      ctx.fillStyle = 'rgba(255,255,255,0.04)';
      ctx.fillRect(0, 40, W, 22);
      ['Home', 'Work', 'About', 'Contact'].forEach(function (item, i) {
        ctx.fillStyle = i === 0 ? css : 'rgba(255,255,255,0.4)';
        ctx.font = '8px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(item, 14 + i * 58, 55);
      });
      ctx.fillStyle = 'rgba(255,255,255,0.025)';
      ctx.fillRect(0, 62, W, 88);
      ctx.fillStyle = css;
      ctx.fillRect(16, 74, Math.round(W * 0.62), 16);
      ctx.fillStyle = css + '55';
      ctx.fillRect(16, 96, Math.round(W * 0.48), 10);
      ctx.fillStyle = css + '33';
      ctx.fillRect(16, 112, Math.round(W * 0.54), 10);
      rrPath(ctx, 16, 128, 72, 22, 6);
      ctx.fillStyle = css; ctx.fill();
      ctx.fillStyle = '#000'; ctx.font = 'bold 9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Get Started', 52, 142);
      var cardY = 168;
      [0, 1, 2].forEach(function (i) {
        var cy = cardY + i * 72;
        rrPath(ctx, 16, cy, W - 32, 62, 6);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.strokeStyle = css + '33'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = css + '88';
        ctx.fillRect(24, cy + 12, W - 80, 8);
        ctx.fillStyle = 'rgba(255,255,255,0.22)';
        ctx.fillRect(24, cy + 26, Math.round((W - 80) * 0.7), 6);
        ctx.fillRect(24, cy + 38, Math.round((W - 80) * 0.5), 5);
      });
      ctx.fillStyle = 'rgba(255,255,255,0.04)';
      ctx.fillRect(0, H - 52, W, 52);
      ctx.fillStyle = css; ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Built for performance & conversion', W / 2, H - 28);
    }

    /* ── Tablet ── analytics dashboard */
    if (type === 'tablet') {
      ctx.fillStyle = '#060e1a'; ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fillRect(0, 0, W, 44);
      ctx.fillStyle = '#fff'; ctx.font = 'bold 14px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Analytics Dashboard', W / 2, 27);
      ctx.fillStyle = css;
      ctx.fillRect(W / 2 - 50, 38, 100, 2);
      var kpis = [['12.4K', 'Sessions'], ['3.7%', 'Conversion'], ['+38%', 'Revenue']];
      var kpiW = Math.floor((W - 32) / 3);
      kpis.forEach(function (k, i) {
        var kx = 8 + i * (kpiW + 8);
        rrPath(ctx, kx, 52, kpiW, 60, 6);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.strokeStyle = css + '44'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = css; ctx.font = 'bold 20px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(k[0], kx + kpiW / 2, 80);
        ctx.fillStyle = 'rgba(255,255,255,0.42)'; ctx.font = '10px sans-serif';
        ctx.fillText(k[1], kx + kpiW / 2, 98);
      });
      var cx3 = W / 2, cy3 = 245, ro3 = 92, ri3 = 53;
      var segs3 = [
        { v: 0.62, c: css },
        { v: 0.18, c: css + 'aa' },
        { v: 0.12, c: css + '66' },
        { v: 0.08, c: css + '33' }
      ];
      var st3 = -Math.PI / 2;
      segs3.forEach(function (sg) {
        ctx.beginPath();
        ctx.moveTo(cx3, cy3);
        ctx.arc(cx3, cy3, ro3, st3, st3 + sg.v * Math.PI * 2);
        ctx.closePath();
        ctx.fillStyle = sg.c; ctx.fill();
        st3 += sg.v * Math.PI * 2;
      });
      ctx.beginPath(); ctx.arc(cx3, cy3, ri3, 0, Math.PI * 2);
      ctx.fillStyle = '#060e1a'; ctx.fill();
      ctx.fillStyle = '#fff'; ctx.font = 'bold 26px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('74%', cx3, cy3 + 9);
      ctx.fillStyle = 'rgba(255,255,255,0.42)'; ctx.font = '11px sans-serif';
      ctx.fillText('Conv Rate', cx3, cy3 + 26);
      var legends = ['Primary', 'Organic', 'Social', 'Other'];
      var segColors = [css, css + 'aa', css + '66', css + '33'];
      legends.forEach(function (l, i) {
        var lx = 16, ly = 348 + i * 18;
        ctx.beginPath(); ctx.arc(lx + 5, ly, 5, 0, Math.PI * 2);
        ctx.fillStyle = segColors[i]; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.45)'; ctx.font = '10px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(l, lx + 14, ly + 4);
      });
      ctx.beginPath();
      var pts3 = [[0, 0.8], [0.15, 0.60], [0.3, 0.68], [0.45, 0.40], [0.6, 0.48], [0.75, 0.20], [0.9, 0.10], [1.0, 0.04]];
      pts3.forEach(function (p, i) {
        var px3 = 18 + p[0] * (W - 36);
        var py3 = H - 18 - p[1] * 38;
        i === 0 ? ctx.moveTo(px3, py3) : ctx.lineTo(px3, py3);
      });
      ctx.strokeStyle = css; ctx.lineWidth = 2; ctx.stroke();
      ctx.lineTo(W - 18, H - 18);
      ctx.lineTo(18, H - 18);
      ctx.closePath();
      ctx.fillStyle = css + '18'; ctx.fill();
    }

    return new THREE.CanvasTexture(cv);
  }

  /* ══════════════════════════════════════════════════════════════════════
     SHARED GEOMETRY UTILITIES
  ══════════════════════════════════════════════════════════════════════ */

  function roundedShape(w, h, r) {
    var s = new THREE.Shape();
    var x = -w / 2, y = -h / 2;
    s.moveTo(x + r, y);
    s.lineTo(x + w - r, y);
    s.quadraticCurveTo(x + w, y,     x + w, y + r);
    s.lineTo(x + w, y + h - r);
    s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    s.lineTo(x + r, y + h);
    s.quadraticCurveTo(x,     y + h, x,     y + h - r);
    s.lineTo(x, y + r);
    s.quadraticCurveTo(x,     y,     x + r, y);
    return s;
  }

  var SLIM_EXT = { depth: 0.028, bevelEnabled: true, bevelThickness: 0.006, bevelSize: 0.006, bevelSegments: 2 };

  /* ─── Phone ────────────────────────────────────────────────────────── */
  function buildPhone(data) {
    var grp = new THREE.Group();
    var bodyMat = devPhoneMat();
    var bodyGeo = new THREE.ExtrudeGeometry(roundedShape(1.85, 3.8, 0.32), SLIM_EXT);
    var body = new THREE.Mesh(bodyGeo, bodyMat);
    body.position.z = -SLIM_EXT.depth / 2;
    grp.add(body);

    var screenKey = (data.screen === 'website') ? 'website' : 'phone';
    var scrTex = makeScreenTex(screenKey, data.css);
    var scr = new THREE.Mesh(
      new THREE.PlaneGeometry(1.62, 3.36),
      new THREE.MeshBasicMaterial({ map: scrTex })
    );
    scr.position.z = SLIM_EXT.depth / 2 + 0.012;
    grp.add(scr);

    var notch = new THREE.Mesh(
      new THREE.CylinderGeometry(0.072, 0.072, 0.04, 16),
      devBezelMat()
    );
    notch.rotation.z = Math.PI / 2;
    notch.position.set(0, 1.72, SLIM_EXT.depth / 2 + 0.02);
    grp.add(notch);

    var cam = new THREE.Mesh(
      new THREE.CylinderGeometry(0.065, 0.065, 0.04, 16),
      devAccentMat()
    );
    cam.rotation.z = Math.PI / 2;
    cam.position.set(0.52, 1.72, SLIM_EXT.depth / 2 + 0.018);
    grp.add(cam);

    var btn = new THREE.Mesh(
      new THREE.BoxGeometry(0.025, 0.42, 0.04),
      devAccentMat()
    );
    btn.position.set(0.98, 0.6, 0.0);
    grp.add(btn);

    return grp;
  }

  /* ─── iPad portrait ────────────────────────────────────────────────── */
  function _makeIPadTex(screen, css) {
    var W = 560, H = 740;
    var cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    var ctx = cv.getContext('2d');

    /* ── Analytics (Data card) — bold colorful data visualization ── */
    if (screen === 'analytics') {
      /* Gradient background */
      var bgG = ctx.createLinearGradient(0, 0, W, H);
      bgG.addColorStop(0, '#0a0618');
      bgG.addColorStop(0.5, '#0c0e1d');
      bgG.addColorStop(1, '#0a0618');
      ctx.fillStyle = bgG;
      ctx.fillRect(0, 0, W, H);

      /* Header bar */
      ctx.fillStyle = 'rgba(255,255,255,0.05)';
      ctx.fillRect(0, 0, W, 46);
      ctx.fillStyle = css;
      ctx.font = 'bold 14px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Data & Visualization', 16, 28);
      ctx.fillStyle = '#06b6d4';
      ctx.font = '11px sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('Live · Real-time', W - 16, 28);

      /* KPI tiles — each a different brand color */
      var kpiColors = ['#06b6d4', css, '#ec4899'];
      var kpiVals = [['12.4K', 'Sessions'], ['3.7%', 'Conv Rate'], ['+38%', 'Revenue']];
      var kpiW2 = Math.floor((W - 32) / 3);
      kpiVals.forEach(function (k, i) {
        var kx2 = 8 + i * (kpiW2 + 8);
        rrPath(ctx, kx2, 54, kpiW2, 64, 8);
        ctx.fillStyle = kpiColors[i] + '1a'; ctx.fill();
        ctx.strokeStyle = kpiColors[i] + '66'; ctx.lineWidth = 1.5; ctx.stroke();
        ctx.fillStyle = kpiColors[i];
        ctx.font = 'bold 24px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(k[0], kx2 + kpiW2 / 2, 88);
        ctx.fillStyle = 'rgba(255,255,255,0.42)';
        ctx.font = '10px sans-serif';
        ctx.fillText(k[1], kx2 + kpiW2 / 2, 106);
      });

      /* Colorful concentric arc rings */
      var cx2 = W / 2, cy2 = 264, outerR = 116;
      var ringDefs = [
        { r: outerR,        color: css,       arc: 0.86, start: -Math.PI / 2 },
        { r: outerR * 0.76, color: '#06b6d4', arc: 0.72, start: -Math.PI / 2 + 0.15 },
        { r: outerR * 0.54, color: '#ec4899', arc: 0.60, start: -Math.PI / 2 + 0.28 },
        { r: outerR * 0.33, color: '#f59e0b', arc: 0.45, start: -Math.PI / 2 + 0.40 },
      ];
      ctx.lineCap = 'round';
      ringDefs.forEach(function (rd) {
        ctx.shadowColor = rd.color;
        ctx.shadowBlur = 14;
        ctx.beginPath();
        ctx.arc(cx2, cy2, rd.r, rd.start, rd.start + rd.arc * Math.PI * 2);
        ctx.strokeStyle = rd.color;
        ctx.lineWidth = 13;
        ctx.stroke();
        ctx.shadowBlur = 0;
        /* Bright end-cap dot */
        var ea = rd.start + rd.arc * Math.PI * 2;
        ctx.beginPath();
        ctx.arc(cx2 + Math.cos(ea) * rd.r, cy2 + Math.sin(ea) * rd.r, 7, 0, Math.PI * 2);
        ctx.fillStyle = rd.color; ctx.fill();
      });
      ctx.lineCap = 'butt';

      /* Center label */
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 36px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('74%', cx2, cy2 + 13);
      ctx.fillStyle = 'rgba(255,255,255,0.45)';
      ctx.font = '12px sans-serif';
      ctx.fillText('Conv Rate', cx2, cy2 + 32);

      /* Colorful bar chart */
      var barSectionY = 432;
      ctx.fillStyle = css + '88';
      ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Weekly Performance', 16, barSectionY + 14);

      var barPalette = ['#8b5cf6', '#06b6d4', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
      var bValsA = [0.52, 0.68, 0.61, 0.79, 0.84, 0.73, 1.0];
      var bDaysA = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
      var baY = barSectionY + 24, baH = 130;
      var bwA = Math.floor((W - 28) / 7) - 5;
      bValsA.forEach(function (v, i) {
        var bxA = 12 + i * (bwA + 5);
        var bhA = v * (baH - 14);
        var byA = baY + (baH - 14) - bhA;
        rrPath(ctx, bxA, byA, bwA, bhA, 4);
        var bGr = ctx.createLinearGradient(0, byA, 0, byA + bhA);
        bGr.addColorStop(0, barPalette[i]);
        bGr.addColorStop(1, barPalette[i] + '44');
        ctx.fillStyle = bGr; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.35)';
        ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(bDaysA[i], bxA + bwA / 2, baY + baH + 2);
      });

      /* Trend line */
      var lineSecY = baY + baH + 16;
      ctx.fillStyle = '#06b6d4'; ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Revenue Trend', 16, lineSecY + 12);
      var lineH = H - lineSecY - 34;
      ctx.beginPath();
      var ptsA = [[0, 0.82], [0.15, 0.62], [0.30, 0.68], [0.45, 0.40], [0.60, 0.48], [0.75, 0.20], [0.90, 0.10], [1.0, 0.04]];
      ptsA.forEach(function (p, i) {
        var pxA = 16 + p[0] * (W - 32);
        var pyA = lineSecY + 20 + (1 - p[1]) * lineH * 0.85;
        i === 0 ? ctx.moveTo(pxA, pyA) : ctx.lineTo(pxA, pyA);
      });
      ctx.strokeStyle = '#06b6d4'; ctx.lineWidth = 2.5; ctx.stroke();
      ctx.lineTo(W - 16, lineSecY + 20 + lineH * 0.85);
      ctx.lineTo(16,     lineSecY + 20 + lineH * 0.85);
      ctx.closePath();
      ctx.fillStyle = '#06b6d411'; ctx.fill();

      return new THREE.CanvasTexture(cv);
    }

    /* ── Ecommerce portrait — dark product page mockup ── */
    if (screen === 'ecommerce') {
      _drawEcommercePageMockup(ctx, W, H, css);
      return new THREE.CanvasTexture(cv);
    }

    /* ── SEO / AEO — portrait tablet (restored dark UI) ── */
    if (screen === 'search') {
      _drawSearchPageMockup(ctx, W, H, css, 'tablet');
      return new THREE.CanvasTexture(cv);
    }

    ctx.fillStyle = '#0a1220';
    ctx.fillRect(0, 0, W, H);
    return new THREE.CanvasTexture(cv);
  }

  function _countUp(time, target, speed) {
    var n = parseFloat(String(target).replace(/[^0-9.]/g, '')) || 0;
    var v = Math.min(n, n * (1 - Math.exp(-time * (speed || 1.2))));
    if (String(target).indexOf('%') >= 0) return v.toFixed(1) + '%';
    if (String(target).indexOf('x') >= 0) return v.toFixed(1) + 'x';
    if (String(target).indexOf('$') >= 0) return '$' + v.toFixed(2);
    if (String(target).indexOf('K') >= 0) return (v >= 10 ? v.toFixed(1) : v.toFixed(2)) + 'K';
    return Math.round(v).toString();
  }

  /* Animated website hero — scroll, counter tick, nav pulse */
  function _drawWebsiteHeroAnim(ctx, W, H, css, time) {
    time = time || 0;
    var isWide = W > H * 1.05;
    var scrollOff = (Math.sin(time * 0.38) * 0.5 + 0.5) * (isWide ? 48 : 36);
    var navPulse = 0.55 + 0.45 * Math.sin(time * 2.4);
    var counter = _countUp(time % 6, '2.4K', 1.4);

    ctx.save();
    ctx.fillStyle = '#0a1220';
    ctx.fillRect(0, 0, W, H);
    ctx.beginPath();
    ctx.rect(0, 0, W, H);
    ctx.clip();
    ctx.translate(0, -scrollOff);

    ctx.fillStyle = '#0f1a2c';
    ctx.fillRect(0, 0, W, isWide ? 36 : 40);
    ['#ff5f57', '#ffbd2e', '#28c840'].forEach(function (c, i) {
      ctx.beginPath(); ctx.arc((isWide ? 16 : 14) + i * (isWide ? 22 : 18), isWide ? 18 : 20, isWide ? 7 : 6, 0, Math.PI * 2);
      ctx.fillStyle = c; ctx.fill();
    });
    rrPath(ctx, isWide ? 70 : 52, isWide ? 8 : 10, W - (isWide ? 140 : 64), 20, 5);
    ctx.fillStyle = 'rgba(255,255,255,0.08)'; ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    ctx.font = (isWide ? 10 : 9) + 'px monospace'; ctx.textAlign = 'center';
    ctx.fillText('fieldtheory.co', W / 2, isWide ? 22 : 24);

    var navY = isWide ? 53 : 55;
    var navItems = isWide ? ['Home', 'Services', 'About', 'Contact'] : ['Home', 'Work', 'About', 'Contact'];
    ctx.fillStyle = 'rgba(255,255,255,0.04)';
    ctx.fillRect(0, isWide ? 36 : 40, W, isWide ? 26 : 22);
    navItems.forEach(function (item, i) {
      var active = i === 1;
      ctx.fillStyle = active ? css : 'rgba(255,255,255,0.4)';
      if (active) ctx.globalAlpha = navPulse;
      ctx.font = (isWide ? 9 : 8) + 'px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText(item, (isWide ? 20 : 14) + i * (isWide ? 80 : 58), navY);
      ctx.globalAlpha = 1;
    });

    var heroTop = isWide ? 62 : 62;
    ctx.fillStyle = 'rgba(255,255,255,0.025)';
    ctx.fillRect(0, heroTop, W, isWide ? 96 : 88);
    ctx.fillStyle = css;
    ctx.fillRect(isWide ? 20 : 16, heroTop + 12, Math.round(W * (isWide ? 0.54 : 0.62)), isWide ? 20 : 16);
    ctx.fillStyle = css + '55';
    ctx.fillRect(isWide ? 20 : 16, heroTop + (isWide ? 38 : 34), Math.round(W * (isWide ? 0.38 : 0.48)), isWide ? 13 : 10);
    ctx.fillStyle = css + '33';
    ctx.fillRect(isWide ? 20 : 16, heroTop + (isWide ? 56 : 50), Math.round(W * (isWide ? 0.46 : 0.54)), isWide ? 13 : 10);
    rrPath(ctx, isWide ? 20 : 16, heroTop + (isWide ? 74 : 66), isWide ? 88 : 72, isWide ? 24 : 22, 6);
    var ctaGlow = 0.55 + 0.45 * Math.sin(time * 3.2);
    ctx.shadowColor = css; ctx.shadowBlur = 12 + ctaGlow * 18;
    ctx.fillStyle = css; ctx.fill();
    ctx.shadowBlur = 0;
    ctx.fillStyle = '#000'; ctx.font = 'bold ' + (isWide ? 10 : 9) + 'px sans-serif'; ctx.textAlign = 'center';
    ctx.fillText('Get Started', (isWide ? 64 : 52), heroTop + (isWide ? 90 : 80));

    rrPath(ctx, W - (isWide ? 130 : 90), isWide ? 72 : 68, isWide ? 110 : 74, isWide ? 34 : 28, 6);
    ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fill();
    ctx.strokeStyle = css + '55'; ctx.lineWidth = 1; ctx.stroke();
    ctx.fillStyle = css; ctx.font = 'bold ' + (isWide ? 11 : 9) + 'px sans-serif'; ctx.textAlign = 'center';
    ctx.fillText(counter, W - (isWide ? 75 : 53), isWide ? 94 : 86);
    ctx.fillStyle = 'rgba(255,255,255,0.42)'; ctx.font = (isWide ? 8 : 7) + 'px sans-serif';
    ctx.fillText('visitors', W - (isWide ? 75 : 53), isWide ? 106 : 96);

    var cardY = heroTop + (isWide ? 110 : 106);
    var cardCount = isWide ? 3 : 3;
    var cardW = isWide ? Math.floor((W - 40) / 3) - 4 : W - 32;
    for (var ci = 0; ci < cardCount; ci++) {
      var cx2 = isWide ? 20 + ci * (cardW + 6) : 16;
      var cy2 = isWide ? cardY : cardY + ci * 72;
      var ch2 = isWide ? 66 : 62;
      rrPath(ctx, cx2, cy2, cardW, ch2, 5);
      ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
      ctx.strokeStyle = css + '33'; ctx.lineWidth = 1; ctx.stroke();
      ctx.fillStyle = css + '88';
      ctx.fillRect(cx2 + 8, cy2 + 12, cardW - 20, isWide ? 7 : 8);
    }
    ctx.restore();
  }

  /* Animated marketing dashboard — metric count-up, ad shimmer, chart draw-in */
  function _drawMarketingDashboardAnim(ctx, W, H, css, time) {
    time = time || 0;
    var cssDim = css + 'bb';
    var loop = time % 8;
    var drawProg = Math.min(1, loop / 2.2);
    var shimmer = (Math.sin(time * 3.4) * 0.5 + 0.5);

    var bgM = ctx.createLinearGradient(0, 0, 0, H);
    bgM.addColorStop(0, '#050810'); bgM.addColorStop(1, '#030508');
    ctx.fillStyle = bgM; ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fillRect(0, 0, W, 44);
    ctx.fillStyle = '#f4f7fc'; ctx.font = 'bold 14px sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('Paid Ad Campaigns', 16, 28);

    var adGrid = [
      { bg: '#121a28', accent: '#5b8fd4', label: 'Search' },
      { bg: '#141420', accent: '#4a7fd4', label: 'Meta' },
      { bg: '#151220', accent: '#c45a82', label: 'Story' },
      { bg: '#101a14', accent: '#3da87a', label: 'Display' },
      { bg: '#181220', accent: '#7c6bb8', label: 'Retarget' },
      { bg: '#1a1410', accent: '#c4923a', label: 'Video' },
    ];
    var sqW = Math.floor((W - 40) / 3) - 6;
    var sqH = 88;
    adGrid.forEach(function (ad, i) {
      var col = i % 3;
      var row = Math.floor(i / 3);
      var ax = 14 + col * (sqW + 8);
      var ay = 52 + row * (sqH + 10);
      rrPath(ctx, ax, ay, sqW, sqH, 10);
      ctx.fillStyle = ad.bg; ctx.fill();
      ctx.strokeStyle = ad.accent + '66'; ctx.lineWidth = 1.5; ctx.stroke();
      ctx.fillStyle = ad.accent + '33';
      ctx.fillRect(ax + 10, ay + 10, sqW - 20, sqH - 36);
      if (i === Math.floor(shimmer * adGrid.length)) {
        var sg = ctx.createLinearGradient(ax, ay, ax + sqW, ay);
        sg.addColorStop(0, 'rgba(255,255,255,0)');
        sg.addColorStop(0.5, 'rgba(255,255,255,0.14)');
        sg.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.fillStyle = sg;
        ctx.fillRect(ax + 10, ay + 10, sqW - 20, sqH - 36);
      }
      ctx.fillStyle = '#eef2f8';
      ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText(ad.label + ' Ad', ax + 14, ay + 24);
    });

    var kpisM = [['CTR', '4.2%'], ['ROAS', '3.8x'], ['CPC', '$1.24'], ['Conv', '2.9%']];
    var kpiMW = Math.floor((W - 32) / 4) - 4;
    kpisM.forEach(function (k, i) {
      var kx = 8 + i * (kpiMW + 5);
      rrPath(ctx, kx, 318, kpiMW, 40, 6);
      ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
      ctx.strokeStyle = cssDim + '33'; ctx.lineWidth = 1; ctx.stroke();
      ctx.fillStyle = 'rgba(244,247,252,0.68)'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(k[0], kx + kpiMW / 2, 332);
      ctx.fillStyle = '#f4f7fc'; ctx.font = 'bold 14px sans-serif';
      ctx.fillText(_countUp(loop, k[1], 1.6), kx + kpiMW / 2, 350);
    });

    var barMY = 378, barMH = 48;
    var bvalsM = [0.58, 0.84, 0.48, 0.72];
    var labelsM = ['Google', 'Meta', 'LinkedIn', 'Other'];
    var barCols = ['#4285F4', '#1877F2', '#0A66C2', cssDim];
    var bwM = Math.floor((W - 40) / 4) - 4;
    bvalsM.forEach(function (v, i) {
      var bx = 16 + i * (bwM + 4);
      var bh = v * drawProg * (barMH - 14);
      var by = barMY + 16 + (barMH - 14) - bh;
      rrPath(ctx, bx, by, bwM, Math.max(2, bh), 3);
      ctx.fillStyle = barCols[i]; ctx.fill();
      ctx.fillStyle = 'rgba(255,255,255,0.32)'; ctx.font = '8px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(labelsM[i], bx + bwM / 2, barMY + barMH + 2);
    });

    var linePts = [[0, 0.82], [0.13, 0.70], [0.26, 0.72], [0.40, 0.52], [0.53, 0.48], [0.67, 0.30], [0.80, 0.20], [1.0, 0.06]];
    var chartW = W - 32, chartH = 36, chartY = 430;
    ctx.beginPath();
    linePts.forEach(function (p, i) {
      var px = 16 + p[0] * chartW * drawProg;
      var py = chartY + p[1] * chartH;
      i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
    });
    ctx.strokeStyle = css; ctx.lineWidth = 2.5; ctx.stroke();
  }

  function _drawMarketingPhoneAnim(ctx, W, H, css, time) {
    time = time || 0;
    ctx.fillStyle = '#000'; ctx.fillRect(0, 0, W, H);
    ctx.fillStyle = '#111'; ctx.fillRect(0, 0, W, 44);
    ctx.fillStyle = '#fff'; ctx.font = 'bold 15px sans-serif'; ctx.textAlign = 'center';
    ctx.fillText('Instagram', W / 2, 28);

    var storyY = 52;
    for (var si = 0; si < 5; si++) {
      var sxc = 14 + si * 58;
      var pulse = si === 0 ? 0.55 + 0.45 * Math.sin(time * 3) : 1;
      ctx.beginPath(); ctx.arc(sxc + 22, storyY + 22, 22, 0, Math.PI * 2);
      ctx.strokeStyle = si === 0 ? css : 'rgba(255,255,255,0.25)';
      ctx.globalAlpha = pulse;
      ctx.lineWidth = 2.5; ctx.stroke();
      ctx.globalAlpha = 1;
      ctx.beginPath(); ctx.arc(sxc + 22, storyY + 22, 18, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,0.08)'; ctx.fill();
    }

    var feedY = 108;
    var posts = [
      { accent: '#E1306C', user: 'brand_official' },
      { accent: '#1877F2', user: 'growth_team' },
    ];
    posts.forEach(function (post, pi) {
      var py = feedY + pi * 148;
      var fade = Math.min(1, (time - pi * 0.35) * 1.4);
      if (fade <= 0) return;
      ctx.save();
      ctx.globalAlpha = fade;
      ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fillRect(0, py, W, 140);
      rrPath(ctx, 14, py + 40, W - 28, 72, 6);
      ctx.fillStyle = post.accent + '33'; ctx.fill();
      var shim = Math.sin(time * 2.5 + pi) * 0.5 + 0.5;
      ctx.fillStyle = 'rgba(255,255,255,' + (shim * 0.12) + ')';
      ctx.fillRect(14, py + 40, (W - 28) * shim, 72);
      ctx.fillStyle = '#fff'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText(post.user, 40, py + 26);
      ctx.restore();
    });
  }

  function setupGoTimeScreenAnims(body, cardId, css) {
    if (!body) return [];
    var anims = [];

    function pushAnim(mesh, w, h, drawFn) {
      if (!mesh || !mesh.material || mesh.userData.isPortfolioScreen) return;
      var cv = document.createElement('canvas');
      cv.width = w; cv.height = h;
      var ctx = cv.getContext('2d');
      var tex = new THREE.CanvasTexture(cv);
      if (THREE.sRGBEncoding) tex.encoding = THREE.sRGBEncoding;
      tex.minFilter = THREE.LinearFilter;
      tex.magFilter = THREE.LinearFilter;
      tex.generateMipmaps = false;
      if (mesh.material.map && mesh.material.map.dispose) mesh.material.map.dispose();
      applyGoTimeEmissiveScreen(mesh, tex);
      anims.push({
        ctx: ctx, tex: tex, w: w, h: h, css: css, time: 0,
        draw: function (c, cw, ch, t) { drawFn(c, cw, ch, css, t); }
      });
    }

    if (cardId === 'ai' && body._animTex) {
      anims.push({
        ctx: body._animTex.ctx,
        tex: body._animTex.tex,
        w: body._animTex.w || 640,
        h: body._animTex.h || 360,
        css: css,
        time: 0,
        draw: function (c, cw, ch, t) { _drawNeuralNet(c, cw, ch, css, t); }
      });
      return anims;
    }

    if (cardId === 'ecommerce' && body._ecomTex) {
      anims.push({
        ctx: body._ecomTex.ctx,
        tex: body._ecomTex.tex,
        w: body._ecomTex.w,
        h: body._ecomTex.h,
        css: css,
        time: 0,
        draw: function (c, cw, ch, t) {
          var scrollY = (Math.sin(t * 0.32) * 0.5 + 0.5) * 0.62;
          _drawEcommercePageMockup(c, cw, ch, css, scrollY, t);
        }
      });
      return anims;
    }

    body.traverse(function (ch) {
      if (!ch.isMesh || !ch.material || !ch.material.map) return;
      if (ch.userData.isGoTimeScreen || ch.userData.isPortfolioScreen) return;
      var geo = ch.geometry;
      if (!geo || !geo.parameters || !geo.parameters.width) return;
      var pw = geo.parameters.width || 1;
      var ph = geo.parameters.height || 1;
      var aspect = pw / ph;

      if (cardId === 'websites') {
        if (aspect > 1.15) pushAnim(ch, 640, 400, _drawWebsiteHeroAnim);
        else pushAnim(ch, 360, 520, _drawWebsiteHeroAnim);
      } else if (cardId === 'marketing') {
        if (aspect > 1.15) pushAnim(ch, 740, 500, _drawMarketingDashboardAnim);
        else pushAnim(ch, 360, 520, _drawMarketingPhoneAnim);
      } else if (cardId === 'seo') {
        var ff = aspect > 1.15 ? 'desktop' : (ph > pw * 1.2 ? 'tablet' : 'mobile');
        var sw = ff === 'desktop' ? 640 : (ff === 'tablet' ? 560 : 360);
        var sh = ff === 'desktop' ? 400 : (ff === 'tablet' ? 740 : 520);
        pushAnim(ch, sw, sh, function (c, w, h, accent, t) {
          _drawSearchPageMockup(c, w, h, accent, ff, t);
        });
      }
    });

    anims.forEach(function (s) {
      s.draw(s.ctx, s.w, s.h, 0);
      s.tex.needsUpdate = true;
    });

    return anims;
  }

  /* Search & Discovery mockup — formFactor: mobile | tablet | desktop */
  function _drawSearchPageMockup(ctx, W, H, css, formFactor, time) {
    formFactor = formFactor || 'desktop';
    time = time || 0;
    var lightInput = (formFactor === 'mobile'); /* mobile-only light search input on dark chrome */
    var isWide = W > H * 1.05;

    var bgS = ctx.createLinearGradient(0, 0, W, H);
    bgS.addColorStop(0, '#0a0618');
    bgS.addColorStop(0.5, '#0c0e1d');
    bgS.addColorStop(1, '#0a1020');
    ctx.fillStyle = bgS;
    ctx.fillRect(0, 0, W, H);

    ctx.strokeStyle = 'rgba(6,182,212,0.06)';
    ctx.lineWidth = 1;
    for (var gxS = 0; gxS < W; gxS += 48) {
      ctx.beginPath(); ctx.moveTo(gxS, 0); ctx.lineTo(gxS, H); ctx.stroke();
    }
    for (var gyS = 0; gyS < H; gyS += 48) {
      ctx.beginPath(); ctx.moveTo(0, gyS); ctx.lineTo(W, gyS); ctx.stroke();
    }

    var hdrH = isWide ? 46 : 44;
    ctx.fillStyle = 'rgba(255,255,255,0.05)';
    ctx.fillRect(0, 0, W, hdrH);
    ctx.fillStyle = css;
    ctx.font = 'bold ' + (isWide ? 14 : 13) + 'px sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('Search & Discovery', isWide ? 18 : 14, isWide ? 28 : 26);
    ctx.fillStyle = '#8b5cf6';
    ctx.font = (isWide ? 11 : 10) + 'px sans-serif'; ctx.textAlign = 'right';
    ctx.fillText('SEO · AEO · AI Answers', W - (isWide ? 18 : 14), isWide ? 28 : 26);

    var kpiCols = ['#06b6d4', '#8b5cf6', '#ec4899', '#10b981'];
    var kpiData = [['#1', 'Avg Rank'], ['94%', 'Visibility'], ['+42%', 'Traffic'], ['A+', 'Schema']];
    var kpiTop = hdrH + (isWide ? 10 : 8);
    var kpiCount = formFactor === 'mobile' ? 2 : (isWide ? 4 : 3);
    var kpiTileH = isWide ? 52 : 48;
    var kpiTileW = Math.floor((W - (isWide ? 40 : 28)) / kpiCount);
    kpiData.slice(0, kpiCount).forEach(function (k, i) {
      var kx3 = (isWide ? 10 : 8) + i * (kpiTileW + (isWide ? 6 : 5));
      rrPath(ctx, kx3, kpiTop, kpiTileW, kpiTileH, 8);
      ctx.fillStyle = kpiCols[i] + '18'; ctx.fill();
      ctx.strokeStyle = kpiCols[i] + '66'; ctx.lineWidth = 1.5; ctx.stroke();
      ctx.fillStyle = kpiCols[i];
      ctx.font = 'bold ' + (isWide ? 18 : 16) + 'px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(k[0], kx3 + kpiTileW / 2, kpiTop + (isWide ? 28 : 26));
      ctx.fillStyle = 'rgba(255,255,255,0.42)';
      ctx.font = '9px sans-serif';
      ctx.fillText(k[1], kx3 + kpiTileW / 2, kpiTop + (isWide ? 44 : 40));
    });

    var searchY = kpiTop + kpiTileH + (isWide ? 14 : 12);
    /* Match result-card horizontal inset — edge-to-edge within mockup screen */
    var contentPad = formFactor === 'mobile' ? 12 : (isWide ? 14 : 12);
    var searchW = W - contentPad * 2;
    var searchBarH = isWide ? 44 : 40;
    var searchX = contentPad;

    if (lightInput) {
      ctx.shadowColor = 'rgba(0,0,0,0.12)';
      ctx.shadowBlur = 10;
      rrPath(ctx, searchX, searchY, searchW, searchBarH, searchBarH / 2);
      ctx.fillStyle = '#ffffff'; ctx.fill();
      ctx.shadowBlur = 0;
      ctx.strokeStyle = '#dfe1e5'; ctx.lineWidth = 1.5; ctx.stroke();
      ctx.strokeStyle = '#9aa0a6'; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.arc(searchX + 28, searchY + searchBarH / 2, 9, 0, Math.PI * 2); ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(searchX + 35, searchY + searchBarH / 2 + 7);
      ctx.lineTo(searchX + 43, searchY + searchBarH / 2 + 15);
      ctx.stroke();
      ctx.fillStyle = '#3c4043';
      ctx.font = (isWide ? 13 : 12) + 'px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('seo services albuquerque', searchX + 48, searchY + searchBarH / 2 + 5);
    } else {
      ctx.shadowColor = 'rgba(6,182,212,0.35)';
      ctx.shadowBlur = 16;
      rrPath(ctx, searchX, searchY, searchW, searchBarH, searchBarH / 2);
      ctx.fillStyle = 'rgba(255,255,255,0.06)'; ctx.fill();
      ctx.shadowBlur = 0;
      ctx.strokeStyle = css + '99'; ctx.lineWidth = 2; ctx.stroke();
      ctx.strokeStyle = '#94a3b8'; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.arc(searchX + 28, searchY + searchBarH / 2, 10, 0, Math.PI * 2); ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(searchX + 35, searchY + searchBarH / 2 + 7);
      ctx.lineTo(searchX + 43, searchY + searchBarH / 2 + 15);
      ctx.stroke();
      ctx.fillStyle = 'rgba(255,255,255,0.35)';
      ctx.font = (isWide ? 13 : 12) + 'px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('seo services albuquerque · ai answers', searchX + 48, searchY + searchBarH / 2 + 5);
    }

    var featsS = ['Organic', 'AI Answers', 'Local', 'Schema'];
    var featCols = [css, '#8b5cf6', '#ec4899', '#10b981'];
    var pillHS = 24;
    var pillCount = formFactor === 'mobile' ? 3 : 4;
    var pillGap = isWide ? 8 : 6;
    var pillWS = Math.floor((W - contentPad * 2 - (pillCount - 1) * pillGap) / pillCount);
    var pillsXS = contentPad;
    var pillsY = searchY + searchBarH + (isWide ? 12 : 10);
    featsS.slice(0, pillCount).forEach(function (f, i) {
      var fxS = pillsXS + i * (pillWS + pillGap);
      rrPath(ctx, fxS, pillsY, pillWS, pillHS, 12);
      ctx.fillStyle = featCols[i] + (lightInput ? '18' : '22'); ctx.fill();
      ctx.strokeStyle = featCols[i] + '88'; ctx.lineWidth = 1; ctx.stroke();
      ctx.fillStyle = featCols[i];
      ctx.font = 'bold 9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(f, fxS + pillWS / 2, pillsY + 16);
    });

    var dividerY = pillsY + pillHS + 12;
    ctx.fillStyle = 'rgba(255,255,255,0.03)';
    ctx.fillRect(0, dividerY, W, H - dividerY);
    ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(0, dividerY); ctx.lineTo(W, dividerY); ctx.stroke();

    var sResults = [
      { dot: css,       rank: '#1', title: 'fieldtheory.co › SEO & AEO Services', desc: 'AI-powered search optimization and answer engine visibility.' },
      { dot: '#8b5cf6', rank: '#2', title: 'Rank higher across search and AI platforms', desc: 'Structure, content, and technical signals that make expertise findable.' },
      { dot: '#10b981', rank: '#3', title: 'Featured snippet · People Also Ask', desc: 'Answer-first content mapped to real buyer questions.' },
    ];
    if (isWide && formFactor !== 'mobile') {
      sResults.push({ dot: '#f59e0b', rank: '#4', title: 'Local discovery · Maps visibility', desc: 'Location signals and local content for nearby search intent.' });
    }

    var resultStep = isWide ? 72 : 78;
    var resultsY = dividerY + (isWide ? 16 : 12);
    sResults.forEach(function (r, i) {
      var fade = Math.min(1, Math.max(0, (time - i * 0.28) * 1.8));
      if (fade <= 0) return;
      var ry2 = resultsY + i * resultStep;
      var cardH = isWide ? 60 : 66;
      var rankPulse = i === 0 ? 0.6 + 0.4 * Math.sin(time * 3.5) : 1;
      ctx.save();
      ctx.globalAlpha = fade;
      rrPath(ctx, 14, ry2 - 4, W - 28, cardH, 8);
      ctx.fillStyle = 'rgba(255,255,255,0.025)'; ctx.fill();
      ctx.strokeStyle = r.dot + '33'; ctx.lineWidth = 1; ctx.stroke();
      rrPath(ctx, 22, ry2 + 2, 28, 22, 6);
      ctx.fillStyle = r.dot + '33'; ctx.fill();
      ctx.globalAlpha = fade * rankPulse;
      ctx.fillStyle = r.dot;
      ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(r.rank, 36, ry2 + 17);
      ctx.globalAlpha = fade;
      ctx.fillStyle = '#fff';
      ctx.font = 'bold ' + (isWide ? 11 : 12) + 'px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText(r.title, 58, ry2 + (isWide ? 12 : 14));
      ctx.fillStyle = 'rgba(255,255,255,0.45)';
      ctx.font = '10px sans-serif';
      ctx.fillText(r.desc.slice(0, isWide ? 58 : 42), 58, ry2 + (isWide ? 30 : 32));
      ctx.restore();
    });
  }

  function _makeSearchTex(W, H, css, formFactor) {
    var cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    _drawSearchPageMockup(cv.getContext('2d'), W, H, css, formFactor);
    return new THREE.CanvasTexture(cv);
  }

  function _drawTShirtOutline(ctx, cx, cy, w, h, css) {
    var sx = cx - w / 2;
    var sy = cy - h / 2;
    ctx.beginPath();
    ctx.moveTo(cx - w * 0.12, sy + h * 0.08);
    ctx.quadraticCurveTo(cx, sy - h * 0.02, cx + w * 0.12, sy + h * 0.08);
    ctx.lineTo(cx + w * 0.38, sy + h * 0.10);
    ctx.lineTo(cx + w * 0.48, sy + h * 0.28);
    ctx.lineTo(cx + w * 0.36, sy + h * 0.32);
    ctx.lineTo(cx + w * 0.32, sy + h * 0.92);
    ctx.lineTo(cx - w * 0.32, sy + h * 0.92);
    ctx.lineTo(cx - w * 0.36, sy + h * 0.32);
    ctx.lineTo(cx - w * 0.48, sy + h * 0.28);
    ctx.lineTo(cx - w * 0.38, sy + h * 0.10);
    ctx.closePath();
    ctx.strokeStyle = css;
    ctx.lineWidth = 3;
    ctx.stroke();
    ctx.fillStyle = css + '18';
    ctx.fill();
  }

  /* Dark-mode ecommerce product page — scrollable on hover */
  function _drawEcommercePageMockup(ctx, W, H, css, scrollY, time) {
    scrollY = scrollY || 0;
    time = time || 0;
    var isWide = W > H * 1.05;
    var totalH = isWide ? H + 200 : H + 240;
    var maxScroll = Math.max(1, totalH - H);

    ctx.save();
    ctx.beginPath();
    ctx.rect(0, 0, W, H);
    ctx.clip();
    ctx.translate(0, -scrollY * maxScroll);

    var bgE = ctx.createLinearGradient(0, 0, W, totalH);
    bgE.addColorStop(0, '#070d18');
    bgE.addColorStop(0.5, '#0a1020');
    bgE.addColorStop(1, '#081018');
    ctx.fillStyle = bgE;
    ctx.fillRect(0, 0, W, totalH);

    ctx.strokeStyle = 'rgba(6,182,212,0.05)';
    ctx.lineWidth = 1;
    for (var gxE = 0; gxE < W; gxE += 48) {
      ctx.beginPath(); ctx.moveTo(gxE, 0); ctx.lineTo(gxE, totalH); ctx.stroke();
    }
    for (var gyE = 0; gyE < totalH; gyE += 48) {
      ctx.beginPath(); ctx.moveTo(0, gyE); ctx.lineTo(W, gyE); ctx.stroke();
    }

    var hdrH = isWide ? 46 : 44;
    ctx.fillStyle = 'rgba(255,255,255,0.05)';
    ctx.fillRect(0, 0, W, hdrH);
    ctx.fillStyle = css;
    ctx.font = 'bold ' + (isWide ? 14 : 13) + 'px sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('Product Store', isWide ? 18 : 14, isWide ? 28 : 26);
    ctx.fillStyle = '#10b981';
    ctx.font = (isWide ? 11 : 10) + 'px sans-serif'; ctx.textAlign = 'right';
    ctx.fillText('CRO · Checkout · Revenue', W - (isWide ? 18 : 14), isWide ? 28 : 26);

    var kpiCols = ['#06b6d4', '#8b5cf6', '#ec4899', '#10b981'];
    var kpiData = [['$89', 'AOV'], ['4.2%', 'Conv'], ['68%', 'Cart'], ['+24%', 'Rev']];
    var kpiTop = hdrH + 10;
    var kpiH = isWide ? 52 : 48;
    var kpiCount = isWide ? 4 : 3;
    var kpiW = Math.floor((W - (isWide ? 40 : 28)) / kpiCount);
    kpiData.slice(0, kpiCount).forEach(function (k, i) {
      var kxE = (isWide ? 10 : 8) + i * (kpiW + (isWide ? 6 : 5));
      rrPath(ctx, kxE, kpiTop, kpiW, kpiH, 8);
      ctx.fillStyle = kpiCols[i] + '18'; ctx.fill();
      ctx.strokeStyle = kpiCols[i] + '66'; ctx.lineWidth = 1.5; ctx.stroke();
      ctx.fillStyle = kpiCols[i];
      ctx.font = 'bold ' + (isWide ? 18 : 16) + 'px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(k[0], kxE + kpiW / 2, kpiTop + (isWide ? 28 : 26));
      ctx.fillStyle = 'rgba(255,255,255,0.42)';
      ctx.font = '9px sans-serif';
      ctx.fillText(k[1], kxE + kpiW / 2, kpiTop + (isWide ? 44 : 40));
    });

    if (isWide) {
      var cardY = kpiTop + kpiH + 14;
      var cardH = totalH - cardY - 58;
      var imgW = Math.min(280, W * 0.38);
      rrPath(ctx, 16, cardY, imgW, cardH, 12);
      ctx.fillStyle = 'rgba(255,255,255,0.03)'; ctx.fill();
      ctx.strokeStyle = css + '44'; ctx.lineWidth = 1.5; ctx.stroke();
      _drawTShirtOutline(ctx, 16 + imgW / 2, cardY + cardH * 0.46, imgW * 0.62, cardH * 0.58, css);
      ctx.fillStyle = 'rgba(255,255,255,0.28)';
      ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Product image', 16 + imgW / 2, cardY + cardH - 18);

      var detX = 16 + imgW + 16;
      var detW = W - detX - 16;
      rrPath(ctx, detX, cardY, detW, cardH, 12);
      ctx.fillStyle = 'rgba(255,255,255,0.025)'; ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1; ctx.stroke();

      ctx.fillStyle = '#fff';
      ctx.font = 'bold 18px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Essential Tee', detX + 18, cardY + 34);
      var pricePulse = 0.88 + 0.12 * Math.sin(time * 2.6);
      ctx.fillStyle = '#ffd94d';
      ctx.globalAlpha = pricePulse;
      ctx.font = 'bold 32px sans-serif';
      ctx.fillText('$49.00', detX + 18, cardY + 72);
      ctx.globalAlpha = 1;
      ctx.fillStyle = 'rgba(255,255,255,0.38)';
      ctx.font = '11px sans-serif';
      ctx.fillText('Free shipping · 30-day returns', detX + 18, cardY + 94);

      var pillLabels = ['S', 'M', 'L', 'XL'];
      pillLabels.forEach(function (lbl, i) {
        var pxE = detX + 18 + i * 44;
        var pyE = cardY + 112;
        rrPath(ctx, pxE, pyE, 36, 24, 12);
        ctx.fillStyle = i === 1 ? css + '33' : 'rgba(255,255,255,0.05)'; ctx.fill();
        ctx.strokeStyle = i === 1 ? css : 'rgba(255,255,255,0.12)'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = i === 1 ? css : 'rgba(255,255,255,0.55)';
        ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(lbl, pxE + 18, pyE + 16);
      });

      rrPath(ctx, detX + 18, cardY + cardH - 52, detW - 36, 36, 10);
      var cartGr = ctx.createLinearGradient(detX + 18, 0, detX + detW - 18, 0);
      cartGr.addColorStop(0, css);
      cartGr.addColorStop(1, '#10b981');
      var ctaGlow = 0.55 + 0.45 * Math.sin(time * 2.8);
      ctx.shadowColor = css; ctx.shadowBlur = 6 + ctaGlow * 14;
      ctx.fillStyle = cartGr; ctx.fill();
      ctx.shadowBlur = 0;
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 13px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Add to Cart', detX + detW / 2, cardY + cardH - 28);

      var barY = totalH - 46;
      ctx.fillStyle = 'rgba(255,255,255,0.03)'; ctx.fillRect(0, barY, W, 46);
      ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(0, barY); ctx.lineTo(W, barY); ctx.stroke();
      var funnel = [['View', '100%'], ['Cart', '24%'], ['Checkout', '12%'], ['Purchase', '4.2%']];
      var fW = Math.floor((W - 40) / 4);
      funnel.forEach(function (f, i) {
        var fxE = 12 + i * (fW + 4);
        ctx.fillStyle = kpiCols[i];
        ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(f[0], fxE, barY + 18);
        ctx.fillStyle = 'rgba(255,255,255,0.42)';
        ctx.font = '9px sans-serif';
        ctx.fillText(f[1], fxE, barY + 32);
      });

      var revY = totalH + 24;
      ctx.fillStyle = css + '88'; ctx.font = 'bold 12px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Customer Reviews', 16, revY + 16);
      [0, 1].forEach(function (ri) {
        var ryE = revY + 28 + ri * 52;
        rrPath(ctx, 16, ryE, W - 32, 44, 8);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.55)'; ctx.font = '10px sans-serif';
        ctx.fillText('Great fit and quality — would buy again.', 28, ryE + 26);
      });
    } else {
      var pCardY = kpiTop + kpiH + 12;
      var imgH = Math.min(240, H * 0.32);
      rrPath(ctx, 14, pCardY, W - 28, imgH, 12);
      ctx.fillStyle = 'rgba(255,255,255,0.03)'; ctx.fill();
      ctx.strokeStyle = css + '44'; ctx.lineWidth = 1.5; ctx.stroke();
      _drawTShirtOutline(ctx, W / 2, pCardY + imgH * 0.46, (W - 28) * 0.52, imgH * 0.62, css);

      var detY = pCardY + imgH + 12;
      rrPath(ctx, 14, detY, W - 28, 118, 12);
      ctx.fillStyle = 'rgba(255,255,255,0.025)'; ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1; ctx.stroke();
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 16px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Essential Tee', 28, detY + 28);
      ctx.fillStyle = '#ffd94d';
      ctx.globalAlpha = 0.88 + 0.12 * Math.sin(time * 2.6);
      ctx.font = 'bold 28px sans-serif';
      ctx.fillText('$49.00', 28, detY + 60);
      ctx.globalAlpha = 1;
      rrPath(ctx, 28, detY + 76, W - 56, 32, 10);
      var cartGrP = ctx.createLinearGradient(28, 0, W - 28, 0);
      cartGrP.addColorStop(0, css);
      cartGrP.addColorStop(1, '#10b981');
      ctx.shadowColor = css; ctx.shadowBlur = 6 + (0.55 + 0.45 * Math.sin(time * 2.8)) * 12;
      ctx.fillStyle = cartGrP; ctx.fill();
      ctx.shadowBlur = 0;
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 12px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText('Add to Cart', W / 2, detY + 96);

      var moreY = detY + 140;
      ctx.fillStyle = css + '88'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('You may also like', 28, moreY);
      [0, 1, 2].forEach(function (mi) {
        var mxE = 14 + mi * ((W - 28) / 3 + 4);
        rrPath(ctx, mxE, moreY + 12, (W - 42) / 3, 72, 8);
        ctx.fillStyle = kpiCols[mi] + '22'; ctx.fill();
      });
    }
    ctx.restore();
  }

  /* ─── iPad landscape texture (search UI + ecommerce landscape) ─────── */
  function _makeIPadLandscapeTex(screen, css) {
    var W = 740, H = 500;
    var cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    var ctx = cv.getContext('2d');

    /* ── Search UI (SEO/AEO card) — restored dark UI on tablet/desktop ── */
    if (screen === 'search') {
      _drawSearchPageMockup(ctx, W, H, css, W > H * 1.05 ? 'desktop' : 'tablet');
      return new THREE.CanvasTexture(cv);
    }

    /* ── Marketing landscape — paid ads dashboard (toned for 3D / ACES) ── */
    if (screen === 'marketing') {
      var cssDim = css + 'bb';
      var bgM = ctx.createLinearGradient(0, 0, 0, H);
      bgM.addColorStop(0, '#050810'); bgM.addColorStop(1, '#030508');
      ctx.fillStyle = bgM; ctx.fillRect(0, 0, W, H);

      ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fillRect(0, 0, W, 44);
      ctx.fillStyle = '#f4f7fc'; ctx.font = 'bold 14px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Paid Ad Campaigns', 16, 28);
      ctx.fillStyle = 'rgba(244,247,252,0.72)'; ctx.font = '10px sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('Digital Advertising  ·  Last 30 days', W - 16, 28);

      /* Platform logo tiles — Google, Meta, Instagram, LinkedIn */
      var platforms = [
        { label: 'Google',  colors: ['#4285F4', '#EA4335', '#FBBC05', '#34A853'], x: 12 },
        { label: 'Meta',    color: '#1877F2', x: 108 },
        { label: 'Instagram', color: '#E1306C', x: 194 },
        { label: 'LinkedIn', color: '#0A66C2', x: 280 },
      ];
      platforms.forEach(function (p) {
        rrPath(ctx, p.x, 52, 88, 36, 8);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.strokeStyle = (p.color || p.colors[0]) + '66'; ctx.lineWidth = 1.5; ctx.stroke();
        if (p.colors) {
          var lx = p.x + 10;
          p.colors.forEach(function (c) {
            ctx.beginPath(); ctx.arc(lx, 70, 5, 0, Math.PI * 2);
            ctx.fillStyle = c; ctx.fill();
            lx += 14;
          });
        } else {
          ctx.beginPath(); ctx.arc(p.x + 16, 70, 7, 0, Math.PI * 2);
          ctx.fillStyle = p.color; ctx.fill();
        }
        ctx.fillStyle = 'rgba(232,237,245,0.82)';
        ctx.font = 'bold 9px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(p.label, p.x + 44, 88);
      });

      /* Grid of mocked digital ad squares */
      ctx.fillStyle = 'rgba(232,237,245,0.72)'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Active Ad Creatives', 16, 100);
      var adGrid = [
        { bg: '#121a28', accent: '#5b8fd4', label: 'Search' },
        { bg: '#141420', accent: '#4a7fd4', label: 'Meta' },
        { bg: '#151220', accent: '#c45a82', label: 'Story' },
        { bg: '#101a14', accent: '#3da87a', label: 'Display' },
        { bg: '#181220', accent: '#7c6bb8', label: 'Retarget' },
        { bg: '#1a1410', accent: '#c4923a', label: 'Video' },
      ];
      var sqW = Math.floor((W - 40) / 3) - 6;
      var sqH = 88;
      adGrid.forEach(function (ad, i) {
        var col = i % 3;
        var row = Math.floor(i / 3);
        var ax = 14 + col * (sqW + 8);
        var ay = 112 + row * (sqH + 10);
        rrPath(ctx, ax, ay, sqW, sqH, 10);
        ctx.fillStyle = ad.bg; ctx.fill();
        ctx.strokeStyle = ad.accent + '66'; ctx.lineWidth = 1.5; ctx.stroke();
        ctx.fillStyle = ad.accent + '33';
        ctx.fillRect(ax + 10, ay + 10, sqW - 20, sqH - 36);
        ctx.fillStyle = '#eef2f8';
        ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(ad.label + ' Ad', ax + 14, ay + 24);
        rrPath(ctx, ax + 10, ay + sqH - 22, 48, 14, 7);
        ctx.fillStyle = ad.accent; ctx.fill();
        ctx.fillStyle = '#000'; ctx.font = 'bold 8px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText('CTA', ax + 34, ay + sqH - 12);
        ctx.fillStyle = ad.accent + 'aa';
        ctx.font = '8px sans-serif'; ctx.textAlign = 'right';
        ctx.fillText('Sponsored', ax + sqW - 10, ay + sqH - 10);
      });

      var kpisM = [['CTR', '4.2%'], ['ROAS', '3.8x'], ['CPC', '$1.24'], ['Conv', '2.9%']];
      var kpiMW = Math.floor((W - 32) / 4) - 4;
      kpisM.forEach(function (k, i) {
        var kx = 8 + i * (kpiMW + 5);
        rrPath(ctx, kx, 318, kpiMW, 40, 6);
        ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fill();
        ctx.strokeStyle = cssDim + '33'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = 'rgba(244,247,252,0.68)'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(k[0], kx + kpiMW / 2, 332);
        ctx.fillStyle = '#f4f7fc'; ctx.font = 'bold 14px sans-serif';
        ctx.fillText(k[1], kx + kpiMW / 2, 350);
      });

      var barMY = 378, barMH = 48;
      ctx.fillStyle = 'rgba(232,237,245,0.62)'; ctx.font = 'bold 9px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText('Spend by Channel', 16, barMY + 10);
      var bvalsM = [0.58, 0.84, 0.48, 0.72];
      var labelsM = ['Google', 'Meta', 'LinkedIn', 'Other'];
      var bwM = Math.floor((W - 40) / 4) - 4;
      var barCols = ['#4285F4', '#1877F2', '#0A66C2', cssDim];
      bvalsM.forEach(function (v, i) {
        var bx = 16 + i * (bwM + 4);
        var bh = v * (barMH - 14);
        var by = barMY + 16 + (barMH - 14) - bh;
        rrPath(ctx, bx, by, bwM, bh, 3);
        ctx.fillStyle = barCols[i]; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.32)'; ctx.font = '8px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(labelsM[i], bx + bwM / 2, barMY + barMH + 2);
      });

      return new THREE.CanvasTexture(cv);
    }

    /* ── Ecommerce landscape — dark product page mockup ── */
    if (screen === 'ecommerce') {
      _drawEcommercePageMockup(ctx, W, H, css);
      return new THREE.CanvasTexture(cv);
    }

    ctx.fillStyle = '#0a1220';
    ctx.fillRect(0, 0, W, H);
    return new THREE.CanvasTexture(cv);
  }

  function buildIPad(data) {
    var grp = new THREE.Group();
    var bodyW = 2.8, bodyH = 3.8, bodyD = SLIM_EXT.depth;
    var bodyShape = roundedShape(bodyW, bodyH, 0.22);
    var bodyGeo = new THREE.ExtrudeGeometry(bodyShape,
      { depth: bodyD, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 4 });
    var alMat = devTabletMat();
    var body = new THREE.Mesh(bodyGeo, alMat);
    body.position.z = -bodyD / 2;
    grp.add(body);

    var bezel = 0.10;
    var scrW  = bodyW - bezel * 2;
    var scrH  = bodyH - bezel * 2;
    var sTex  = _makeIPadTex(data.screen || 'seo', data.css);
    var scrMesh = new THREE.Mesh(
      new THREE.PlaneGeometry(scrW, scrH),
      new THREE.MeshBasicMaterial({ map: sTex })
    );
    scrMesh.position.z = bodyD / 2 + 0.015;
    grp.add(scrMesh);
    if (!data.goTimeRail) {
      var ipadImgs = getServiceScreenImgs(data.id);
      if (ipadImgs && ipadImgs.portrait) {
        bindPhotoScreen(scrMesh, ipadImgs.portrait, scrW, scrH);
      }
    }

    var notch = new THREE.Mesh(
      new THREE.BoxGeometry(0.30, 0.055, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x9a9ea6 })
    );
    notch.position.set(0, bodyH / 2 - 0.065, bodyD / 2 + 0.004);
    grp.add(notch);

    var cam = new THREE.Mesh(
      new THREE.CylinderGeometry(0.028, 0.028, 0.012, 12),
      new THREE.MeshStandardMaterial({ color: 0x888c94 })
    );
    cam.rotation.x = Math.PI / 2;
    cam.position.set(0.09, bodyH / 2 - 0.065, bodyD / 2 + 0.006);
    grp.add(cam);

    var btn = new THREE.Mesh(
      new THREE.BoxGeometry(0.022, 0.26, 0.06),
      devAccentMat()
    );
    btn.position.set(bodyW / 2 + 0.011, 0.4, 0);
    grp.add(btn);

    return grp;
  }

  /* ─── iPad landscape (Fix 3: SEO search UI, Fix 4: ecommerce) ──────── */
  function buildIPadLandscape(data) {
    var grp = new THREE.Group();
    var bodyW = 4.0, bodyH = 2.6, bodyD = SLIM_EXT.depth;
    var bodyShape = roundedShape(bodyW, bodyH, 0.22);
    var bodyGeo = new THREE.ExtrudeGeometry(bodyShape,
      { depth: bodyD, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 4 });
    var alMat = devTabletMat();
    var body = new THREE.Mesh(bodyGeo, alMat);
    body.position.z = -bodyD / 2;
    grp.add(body);

    var bezel = 0.10;
    var scrW  = bodyW - bezel * 2;
    var scrH  = bodyH - bezel * 2;
    var sTex  = data.goTimeRail
      ? _makeIPadLandscapeTex(data.screen || 'ecommerce', data.css)
      : makeBrightScreenTex(scrW, scrH);
    var scrMesh = new THREE.Mesh(
      new THREE.PlaneGeometry(scrW, scrH),
      new THREE.MeshBasicMaterial({ map: sTex })
    );
    scrMesh.position.z = bodyD / 2 + 0.015;
    grp.add(scrMesh);
    if (!data.goTimeRail && data.id === 'ecommerce') {
      var ecomImgs = getServiceScreenImgs('ecommerce');
      if (ecomImgs && ecomImgs.landscape) {
        bindPhotoScreen(scrMesh, ecomImgs.landscape, scrW, scrH);
      }
    }

    /* Top notch (landscape: right side of tablet) */
    var notch = new THREE.Mesh(
      new THREE.BoxGeometry(0.055, 0.28, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x9a9ea6 })
    );
    notch.position.set(bodyW / 2 - 0.065, 0, bodyD / 2 + 0.004);
    grp.add(notch);

    /* Camera dot on right side */
    var cam = new THREE.Mesh(
      new THREE.CylinderGeometry(0.024, 0.024, 0.012, 12),
      new THREE.MeshStandardMaterial({ color: 0x888c94 })
    );
    cam.rotation.z = Math.PI / 2;
    cam.position.set(bodyW / 2 - 0.065, -0.08, bodyD / 2 + 0.006);
    grp.add(cam);

    /* Power button on right edge */
    var btn = new THREE.Mesh(
      new THREE.BoxGeometry(0.022, 0.20, 0.06),
      devAccentMat()
    );
    btn.position.set(bodyW / 2 + 0.011, 0.4, 0);
    grp.add(btn);

    /* Volume buttons on left edge */
    [-0.2, 0.1].forEach(function (yOff) {
      var vBtn = new THREE.Mesh(
        new THREE.BoxGeometry(0.022, 0.14, 0.05),
        devAccentMat()
      );
      vBtn.position.set(-bodyW / 2 - 0.011, yOff, 0);
      grp.add(vBtn);
    });

    return grp;
  }

  /* ─── Standalone neural network (AI / Innovation — no device mesh) ─ */
  function _drawMonitorIdleScreen(ctx, W, H, css) {
    ctx.fillStyle = '#0a1220';
    ctx.fillRect(0, 0, W, H);
    ctx.fillStyle = 'rgba(255,255,255,0.05)';
    ctx.fillRect(0, 0, W, 34);
    ['#ff5f57', '#ffbd2e', '#28c840'].forEach(function (c, i) {
      ctx.beginPath(); ctx.arc(14 + i * 18, 17, 5, 0, Math.PI * 2);
      ctx.fillStyle = c; ctx.fill();
    });
    ctx.fillStyle = css;
    ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('AI Studio', 58, 22);
    ctx.fillStyle = 'rgba(255,255,255,0.35)';
    ctx.font = '10px sans-serif'; ctx.textAlign = 'right';
    ctx.fillText('Ready', W - 14, 22);

    var tiles = [
      { label: 'Models', val: '12 active', color: css, x: 18, y: 48 },
      { label: 'Workflows', val: '8 running', color: '#8b5cf6', x: 168, y: 48 },
      { label: 'Agents', val: '3 deployed', color: '#ec4899', x: 318, y: 48 },
      { label: 'Uptime', val: '99.9%', color: '#10b981', x: 468, y: 48 },
    ];
    tiles.forEach(function (tile) {
      rrPath(ctx, tile.x, tile.y, 138, 54, 8);
      ctx.fillStyle = tile.color + '14'; ctx.fill();
      ctx.strokeStyle = tile.color + '55'; ctx.lineWidth = 1; ctx.stroke();
      ctx.fillStyle = tile.color;
      ctx.font = 'bold 10px sans-serif'; ctx.textAlign = 'left';
      ctx.fillText(tile.label, tile.x + 12, tile.y + 22);
      ctx.fillStyle = 'rgba(255,255,255,0.72)';
      ctx.font = 'bold 14px sans-serif';
      ctx.fillText(tile.val, tile.x + 12, tile.y + 42);
    });

    rrPath(ctx, 18, 118, W - 36, 118, 10);
    ctx.fillStyle = 'rgba(255,255,255,0.03)'; ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1; ctx.stroke();
    ctx.fillStyle = 'rgba(255,255,255,0.45)';
    ctx.font = '10px monospace'; ctx.textAlign = 'left';
    ['> init neural_pipeline()', '> load knowledge_base()', '> status: awaiting model graph'].forEach(function (line, i) {
      ctx.fillStyle = i === 2 ? css : 'rgba(255,255,255,0.42)';
      ctx.fillText(line, 30, 144 + i * 22);
    });

    ctx.fillStyle = 'rgba(255,255,255,0.25)';
    ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
    ctx.fillText('Monitor idle — neural network rendered separately', W / 2, H - 16);
  }

  function _drawNeuralNet(ctx, W, H, css, t) {
    t = t || 0;
    ctx.clearRect(0, 0, W, H);
    var bg = ctx.createLinearGradient(0, 0, 0, H);
    bg.addColorStop(0, 'rgba(16,16,16,0.20)');
    bg.addColorStop(1, 'rgba(32,32,32,0.16)');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    /* 4-layer perspective network — scaled to ~60% frame with padding */
    var frame = 0.60;
    var pad = (1 - frame) * 0.5;
    var mapX = function (x) { return (pad + x * frame) * W; };
    var mapY = function (y) { return (pad + y * frame) * H; };
    var layers = [
      [{ x: 0.10, y: 0.18 }, { x: 0.10, y: 0.38 }, { x: 0.10, y: 0.62 }, { x: 0.10, y: 0.82 }],
      [{ x: 0.34, y: 0.14 }, { x: 0.34, y: 0.36 }, { x: 0.34, y: 0.64 }, { x: 0.34, y: 0.86 }],
      [{ x: 0.62, y: 0.20 }, { x: 0.62, y: 0.44 }, { x: 0.62, y: 0.68 }, { x: 0.62, y: 0.88 }],
      [{ x: 0.88, y: 0.32 }, { x: 0.88, y: 0.68 }]
    ];
    var nodeColors = [
      FTC_BRAND_CSS.blue,
      FTC_BRAND_CSS.green,
      FTC_BRAND_CSS.yellow,
      FTC_BRAND_CSS.sky,
      FTC_BRAND_CSS.red,
      FTC_BRAND_CSS.white
    ];
    var nodes = [];
    layers.forEach(function (layer, li) {
      var depthScale = 0.72 + li * 0.12;
      layer.forEach(function (n) {
        nodes.push({
          x: mapX(n.x),
          y: mapY(n.y),
          layer: li,
          depth: depthScale,
          color: nodeColors[(li * 3 + nodes.length) % nodeColors.length]
        });
      });
    });

    ctx.lineCap = 'round';
    for (var li2 = 0; li2 < layers.length - 1; li2++) {
      var from = nodes.filter(function (n) { return n.layer === li2; });
      var to   = nodes.filter(function (n) { return n.layer === li2 + 1; });
      from.forEach(function (a, ai) {
        to.forEach(function (b, bi) {
          var shimmer = 0.28 + 0.22 * Math.sin(t * 2.0 + ai * 0.7 + bi * 0.5 + li2 * 0.4);
          var breath  = 0.85 + 0.15 * Math.sin(t * 1.4 + ai * 0.3);
          ctx.strokeStyle = a.color.replace(')', ', ' + shimmer + ')').replace('rgb', 'rgba').replace('#', '');
          /* hex to rgba fallback */
          var cA = a.color;
          ctx.strokeStyle = hexToRgba(cA, shimmer);
          ctx.lineWidth = 2.8 + breath * 1.4;
          ctx.shadowColor = cA;
          ctx.shadowBlur = 6 + shimmer * 10;
          ctx.beginPath();
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
          ctx.stroke();
          ctx.shadowBlur = 0;
        });
      });
    }
    ctx.lineCap = 'butt';

    nodes.forEach(function (n, ni) {
      var pulse = 0.5 + 0.5 * Math.sin(ni * 1.1 + t * 2.6);
      var breath = 0.88 + 0.12 * Math.sin(t * 1.8 + ni * 0.6);
      var nodeR = (14 + n.depth * 6) * breath + pulse * 3;
      var glowR = nodeR + 14 + pulse * 8;

      ctx.beginPath(); ctx.arc(n.x, n.y, glowR, 0, Math.PI * 2);
      ctx.fillStyle = n.color.replace('#', '');
      var grd = ctx.createRadialGradient(n.x, n.y, nodeR * 0.2, n.x, n.y, glowR);
      grd.addColorStop(0, n.color + '55');
      grd.addColorStop(1, n.color + '00');
      ctx.fillStyle = grd;
      ctx.fill();

      ctx.beginPath(); ctx.arc(n.x, n.y, nodeR, 0, Math.PI * 2);
      ctx.fillStyle = n.color;
      ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,' + (0.45 + 0.35 * pulse) + ')';
      ctx.lineWidth = 2.2;
      ctx.stroke();

      ctx.beginPath(); ctx.arc(n.x - nodeR * 0.25, n.y - nodeR * 0.25, nodeR * 0.28, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,' + (0.25 + 0.2 * pulse) + ')';
      ctx.fill();
    });
  }

  function _makeMonitorTex(css, t) {
    var W = 640, H = 360;
    var cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    _drawMonitorIdleScreen(cv.getContext('2d'), W, H, css);
    return new THREE.CanvasTexture(cv);
  }

  function buildMonitor(data) {
    var grp = new THREE.Group();
    var alMat = devFrameMat();
    var standMat = devStandMat();

    var monW = 3.6, monH = 2.2, monD = 0.08;
    var baseH = 0.08, neckH = 0.50;
    var frameY = baseH + neckH + monH / 2;

    var frame = new THREE.Mesh(new THREE.BoxGeometry(monW, monH, monD), alMat);
    frame.position.y = frameY;
    grp.add(frame);

    var scrTex = _makeMonitorTex(data.css, 0);
    var scr = new THREE.Mesh(
      new THREE.PlaneGeometry(monW - 0.12, monH - 0.12),
      new THREE.MeshBasicMaterial({ map: scrTex })
    );
    scr.position.set(0, frameY, monD / 2 + 0.004);
    grp.add(scr);

    /* Standalone neural network graphic — larger, floating above the monitor */
    var nnW = 960, nnH = 576;
    var nnCv = document.createElement('canvas');
    nnCv.width = nnW; nnCv.height = nnH;
    _drawNeuralNet(nnCv.getContext('2d'), nnW, nnH, data.css, 0);
    var nnTex = new THREE.CanvasTexture(nnCv);
    var nnPlaneW = 4.35, nnPlaneH = 2.61;
    var nnGraphic = new THREE.Mesh(
      new THREE.PlaneGeometry(nnPlaneW, nnPlaneH),
      new THREE.MeshBasicMaterial({ map: nnTex, transparent: true })
    );
    nnGraphic.position.set(0.08, frameY + monH * 0.52 + nnPlaneH * 0.42, 0.22);
    nnGraphic.rotation.y = -0.08;
    grp.add(nnGraphic);
    grp._animTex = { cv: nnCv, ctx: nnCv.getContext('2d'), tex: nnTex, css: data.css, w: nnW, h: nnH };

    var led = new THREE.Mesh(
      new THREE.BoxGeometry(monW * 0.10, 0.032, monD * 0.5),
      new THREE.MeshStandardMaterial({ color: data.hex, emissive: data.hex, emissiveIntensity: 0.5 })
    );
    led.position.set(0, frameY - monH / 2 + 0.04, monD / 2 + 0.02);
    grp.add(led);

    var neck = new THREE.Mesh(new THREE.BoxGeometry(0.15, neckH, 0.12), standMat);
    neck.position.set(0, baseH + neckH / 2, 0);
    grp.add(neck);

    var base = new THREE.Mesh(new THREE.BoxGeometry(1.2, baseH, 0.60), standMat);
    base.position.set(0, baseH / 2, 0.06);
    grp.add(base);

    return grp;
  }

  function buildNeuralNetOnly(data) {
    var grp = new THREE.Group();
    var nnW = 960, nnH = 600;
    var nnCv = document.createElement('canvas');
    nnCv.width = nnW;
    nnCv.height = nnH;
    _drawNeuralNet(nnCv.getContext('2d'), nnW, nnH, data.css, 0);
    var nnTex = new THREE.CanvasTexture(nnCv);
    var planeW = 8.8, planeH = 5.5;
    var nnGraphic = new THREE.Mesh(
      new THREE.PlaneGeometry(planeW, planeH),
      new THREE.MeshBasicMaterial({ map: nnTex, transparent: true })
    );
    grp.add(nnGraphic);
    grp._animTex = { cv: nnCv, ctx: nnCv.getContext('2d'), tex: nnTex, css: data.css, w: nnW, h: nnH };
    return grp;
  }

  function buildGlbModel(data) {
    var fallback = buildNeuralNetOnly(data);
    var modelUrl = ftcAssetUrl(data.modelPath || '');
    if (!modelUrl || !window.THREE) return fallback;

    var grp = new THREE.Group();
    var readyListeners = [];
    grp._animTex = fallback._animTex || null;
    grp.add(fallback);

    grp._onModelReady = function (cb) {
      if (typeof cb === 'function') readyListeners.push(cb);
    };

    var fireReady = function () {
      readyListeners.splice(0).forEach(function (cb) {
        try { cb(); } catch (e) { console.warn('FTL scene: model ready callback failed', e); }
      });
    };

    function removeLowerModelSection(root) {
      if (!root || !window.THREE) return;
      root.updateMatrixWorld(true);
      var rootBox = new THREE.Box3().setFromObject(root);
      if (!rootBox || !isFinite(rootBox.min.y) || !isFinite(rootBox.max.y)) return;
      var totalHeight = Math.max(0.001, rootBox.max.y - rootBox.min.y);
      var cutoffY = rootBox.min.y + totalHeight * 0.20;
      var meshNodes = [];
      root.traverse(function (ch) {
        if (ch && ch.isMesh && ch.parent) meshNodes.push(ch);
      });
      if (meshNodes.length < 2) return;
      meshNodes.forEach(function (mesh) {
        if (!mesh.geometry) return;
        if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
        if (!mesh.geometry.boundingBox) return;
        var center = mesh.geometry.boundingBox.getCenter(new THREE.Vector3());
        center.applyMatrix4(mesh.matrixWorld);
        if (center.y < cutoffY) {
          mesh.parent.remove(mesh);
        }
      });
    }

    function loadModelNow() {
      var loader = new THREE.GLTFLoader();
      loader.load(modelUrl, function (gltf) {
        var root = gltf && (gltf.scene || (gltf.scenes && gltf.scenes[0]));
        if (!root) { fireReady(); return; }
        removeLowerModelSection(root);
        applyGlbBrandColors(root);
        root.traverse(function (ch) {
          if (!ch.isMesh || !ch.material) return;
          var mat = ch.material;
          if (Array.isArray(mat)) return;
          if (mat.metalness === undefined) mat.metalness = 0.2;
          if (mat.roughness === undefined) mat.roughness = 0.7;
        });
        grp.remove(fallback);
        grp._animTex = null;
        grp.add(root);
        fireReady();
      }, undefined, function (err) {
        console.warn('FTL scene: failed to load GLB model', modelUrl, err);
        fireReady();
      });
    }

    if (window.THREE.GLTFLoader) {
      loadModelNow();
    } else {
      ensureGltfLoader(function (ok) {
        if (!ok) {
          console.warn('FTL scene: GLTFLoader unavailable, using fallback neural net');
          fireReady();
          return;
        }
        loadModelNow();
      });
    }

    return grp;
  }

  /* ─── Data viz — BufferGeometry radiating lines ────────────────────── */
  function buildDataViz(data) {
    var grp      = new THREE.Group();
    var segCount = 18000;
    var positions = new Float32Array(segCount * 6);
    var colors    = new Float32Array(segCount * 6);
    var col       = new THREE.Color();

    for (var i = 0; i < segCount; i++) {
      var phi   = Math.random() * Math.PI * 2;
      var theta = Math.acos(2 * Math.random() - 1);
      var len   = 0.22 + Math.random() * 0.78;
      var dx    = Math.sin(theta) * Math.cos(phi);
      var dy    = Math.cos(theta);
      var dz    = Math.sin(theta) * Math.sin(phi);
      var b     = i * 6;

      positions[b]     = 0; positions[b + 1] = 0; positions[b + 2] = 0;
      positions[b + 3] = dx * len; positions[b + 4] = dy * len; positions[b + 5] = dz * len;

      /* Full HSL spectrum mapped to azimuthal angle — produces the classic
         green-top / cyan-left / blue-bottom / magenta-right gradient */
      col.setHSL(phi / (Math.PI * 2), 1.0, 0.55);
      /* Center (origin) very dim so lines appear to radiate outward */
      colors[b]     = col.r * 0.08; colors[b + 1] = col.g * 0.08; colors[b + 2] = col.b * 0.08;
      colors[b + 3] = col.r;        colors[b + 4] = col.g;        colors[b + 5] = col.b;
    }

    var geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geo.setAttribute('color',    new THREE.BufferAttribute(colors, 3));

    var mat   = new THREE.LineBasicMaterial({ vertexColors: true });
    var lines = new THREE.LineSegments(geo, mat);
    lines.frustumCulled = false;
    grp.add(lines);

    grp._isDataViz = true;
    return grp;
  }

  /* ─── Multi-device responsive scene (Website Dev card) ─────────────── */
  function buildMultiDevice(data) {
    var grp = new THREE.Group();

    var alMat   = devFrameMat();
    var tabMat  = devTabletMat();
    var standMt = devStandMat();
    var phoneMt = devPhoneMat();

    /* ── Desktop monitor ─────────────────────────────────────────── */
    var monGrp = new THREE.Group();
    var monW = 3.0, monH = 1.88, monD = 0.08;
    var mBaseH = 0.08, mNeckH = 0.44;
    var mFrameY = mBaseH + mNeckH + monH / 2;

    var monFrame = new THREE.Mesh(new THREE.BoxGeometry(monW, monH, monD), alMat);
    monFrame.position.y = mFrameY;
    monGrp.add(monFrame);

    var monScrW = monW - 0.10;
    var monScrH = monH - 0.10;
    var monTex = data.goTimeRail ? makeScreenTex('laptop', data.css) : makeBrightScreenTex(monScrW, monScrH);
    var monScr = new THREE.Mesh(
      new THREE.PlaneGeometry(monScrW, monScrH),
      new THREE.MeshBasicMaterial({ map: monTex })
    );
    monScr.position.set(0, mFrameY, monD / 2 + 0.005);
    monGrp.add(monScr);
    if (!data.goTimeRail) {
      var webImgs = getServiceScreenImgs('websites');
      if (webImgs && webImgs.desktop) {
        bindPhotoScreen(monScr, webImgs.desktop, monScrW, monScrH);
      }
    }

    var monLed = new THREE.Mesh(
      new THREE.BoxGeometry(monW * 0.09, 0.030, monD * 0.5),
      new THREE.MeshStandardMaterial({ color: data.hex, emissive: data.hex, emissiveIntensity: 0.5 })
    );
    monLed.position.set(0, mFrameY - monH / 2 + 0.035, monD / 2 + 0.018);
    monGrp.add(monLed);

    var monNeck = new THREE.Mesh(new THREE.BoxGeometry(0.13, mNeckH, 0.10), standMt);
    monNeck.position.set(0, mBaseH + mNeckH / 2, 0);
    monGrp.add(monNeck);

    var monBase = new THREE.Mesh(new THREE.BoxGeometry(1.15, mBaseH, 0.55), standMt);
    monBase.position.set(0, mBaseH / 2, 0.05);
    monGrp.add(monBase);

    monGrp.position.set(0.10, 0.0, -0.50);
    grp.add(monGrp);

    /* ── Tablet — left ────────────────────────────────────────────── */
    var tabGrp = new THREE.Group();
    var tW = 2.8, tH = 3.8, tD = SLIM_EXT.depth;
    var tabBodyGeo = new THREE.ExtrudeGeometry(roundedShape(tW, tH, 0.22),
      { depth: tD, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 4 });
    var tabBody = new THREE.Mesh(tabBodyGeo, tabMat);
    tabBody.position.z = -tD / 2;
    tabGrp.add(tabBody);

    var tabScrW = tW - 0.20;
    var tabScrH = tH - 0.20;
    var tabTex = data.goTimeRail ? makeScreenTex('laptop', data.css) : makeBrightScreenTex(tabScrW, tabScrH);
    var tabScr = new THREE.Mesh(
      new THREE.PlaneGeometry(tabScrW, tabScrH),
      new THREE.MeshBasicMaterial({ map: tabTex })
    );
    tabScr.position.z = tD / 2 + 0.015;
    tabGrp.add(tabScr);
    if (!data.goTimeRail) {
      var webTabImgs = getServiceScreenImgs('websites');
      if (webTabImgs && webTabImgs.tablet) {
        bindPhotoScreen(tabScr, webTabImgs.tablet, tabScrW, tabScrH);
      }
    }

    tabGrp.add(new THREE.Mesh(
      new THREE.BoxGeometry(0.28, 0.05, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x9a9ea6 })
    ));
    tabGrp.children[tabGrp.children.length - 1].position.set(0, tH / 2 - 0.06, tD / 2 + 0.004);

    tabGrp.scale.setScalar(0.60);
    tabGrp.position.set(-1.22, 0.82, 0.18);
    tabGrp.rotation.y = 0.18;
    grp.add(tabGrp);

    /* ── Phone — right ────────────────────────────────────────────── */
    var phGrp = new THREE.Group();
    var phBodyGeo = new THREE.ExtrudeGeometry(roundedShape(1.85, 3.8, 0.32), SLIM_EXT);
    var phBody = new THREE.Mesh(phBodyGeo, phoneMt);
    phBody.position.z = -SLIM_EXT.depth / 2;
    phGrp.add(phBody);

    var phScrW = 1.62;
    var phScrH = 3.36;
    var phTex = data.goTimeRail ? makeScreenTex('website', data.css) : makeBrightScreenTex(phScrW, phScrH);
    var phScr = new THREE.Mesh(
      new THREE.PlaneGeometry(phScrW, phScrH),
      new THREE.MeshBasicMaterial({ map: phTex })
    );
    phScr.position.z = SLIM_EXT.depth / 2 + 0.012;
    phGrp.add(phScr);
    if (!data.goTimeRail) {
      var webPhoneImgs = getServiceScreenImgs('websites');
      if (webPhoneImgs && webPhoneImgs.phone) {
        bindPhotoScreen(phScr, webPhoneImgs.phone, phScrW, phScrH);
      }
    }

    var notchMd = new THREE.Mesh(
      new THREE.CylinderGeometry(0.072, 0.072, 0.04, 16),
      devBezelMat()
    );
    notchMd.rotation.z = Math.PI / 2;
    notchMd.position.set(0, 1.72, SLIM_EXT.depth / 2 + 0.02);
    phGrp.add(notchMd);

    phGrp.scale.setScalar(0.42);
    phGrp.position.set(0.92, 0.58, 0.58);
    phGrp.rotation.y = -0.16;
    grp.add(phGrp);

    /* ── Fix 2: Animate-in — slide from sides, not up/down ──────── */
    var slideX = 3.5; /* horizontal offset in group-local units */
    grp._multiDevices = [
      { mesh: monGrp,
        start: new THREE.Vector3(monGrp.position.x - 0.8, monGrp.position.y, monGrp.position.z),
        end:   monGrp.position.clone(), delay: 0.0,  phase: 0.0 },
      { mesh: tabGrp,
        start: new THREE.Vector3(tabGrp.position.x - slideX, tabGrp.position.y, tabGrp.position.z),
        end:   tabGrp.position.clone(), delay: 0.15, phase: 1.4 },
      { mesh: phGrp,
        start: new THREE.Vector3(phGrp.position.x + slideX, phGrp.position.y, phGrp.position.z),
        end:   phGrp.position.clone(),  delay: 0.30, phase: 2.8 },
    ];
    monGrp.position.copy(grp._multiDevices[0].end);
    tabGrp.position.copy(grp._multiDevices[1].end);
    phGrp.position.copy(grp._multiDevices[2].end);

    return grp;
  }

  /* ─── Search multi-device: desktop + tablet + phone (mobile-only light input) ─ */
  function buildSearchMultiDevice(data) {
    var grp = new THREE.Group();

    var alMat   = devFrameMat();
    var tabMat  = devTabletMat();
    var standMt = devStandMat();
    var phoneMt = devPhoneMat();

    /* ── Desktop monitor ─────────────────────────────────────────── */
    var monGrp = new THREE.Group();
    var monW = 3.0, monH = 1.88, monD = 0.08;
    var mBaseH = 0.08, mNeckH = 0.44;
    var mFrameY = mBaseH + mNeckH + monH / 2;

    var monFrame = new THREE.Mesh(new THREE.BoxGeometry(monW, monH, monD), alMat);
    monFrame.position.y = mFrameY;
    monGrp.add(monFrame);

    var monScrW = monW - 0.10;
    var monScrH = monH - 0.10;
    var monTex = data.goTimeRail ? _makeSearchTex(640, 400, data.css, 'desktop') : makeBrightScreenTex(monScrW, monScrH);
    var monScr = new THREE.Mesh(
      new THREE.PlaneGeometry(monScrW, monScrH),
      new THREE.MeshBasicMaterial({ map: monTex })
    );
    monScr.position.set(0, mFrameY, monD / 2 + 0.005);
    monGrp.add(monScr);
    if (!data.goTimeRail) {
      var seoImgs = getServiceScreenImgs('seo');
      if (seoImgs && seoImgs.desktop) {
        bindPhotoScreen(monScr, seoImgs.desktop, monScrW, monScrH);
      }
    }

    var monLed = new THREE.Mesh(
      new THREE.BoxGeometry(monW * 0.09, 0.030, monD * 0.5),
      new THREE.MeshStandardMaterial({ color: data.hex, emissive: data.hex, emissiveIntensity: 0.5 })
    );
    monLed.position.set(0, mFrameY - monH / 2 + 0.035, monD / 2 + 0.018);
    monGrp.add(monLed);

    var monNeck = new THREE.Mesh(new THREE.BoxGeometry(0.13, mNeckH, 0.10), standMt);
    monNeck.position.set(0, mBaseH + mNeckH / 2, 0);
    monGrp.add(monNeck);

    var monBase = new THREE.Mesh(new THREE.BoxGeometry(1.15, mBaseH, 0.55), standMt);
    monBase.position.set(0, mBaseH / 2, 0.05);
    monGrp.add(monBase);

    monGrp.position.set(0.10, 0.0, -0.50);
    grp.add(monGrp);

    /* ── Tablet — left ────────────────────────────────────────────── */
    var tabGrp = new THREE.Group();
    var tW = 2.8, tH = 3.8, tD = SLIM_EXT.depth;
    var tabBodyGeo = new THREE.ExtrudeGeometry(roundedShape(tW, tH, 0.22),
      { depth: tD, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 4 });
    var tabBody = new THREE.Mesh(tabBodyGeo, tabMat);
    tabBody.position.z = -tD / 2;
    tabGrp.add(tabBody);

    var tabScrW = tW - 0.20;
    var tabScrH = tH - 0.20;
    var tabTex = data.goTimeRail ? _makeSearchTex(560, 740, data.css, 'tablet') : makeBrightScreenTex(tabScrW, tabScrH);
    var tabScr = new THREE.Mesh(
      new THREE.PlaneGeometry(tabScrW, tabScrH),
      new THREE.MeshBasicMaterial({ map: tabTex })
    );
    tabScr.position.z = tD / 2 + 0.015;
    tabGrp.add(tabScr);
    if (!data.goTimeRail) {
      var seoTabImgs = getServiceScreenImgs('seo');
      if (seoTabImgs && seoTabImgs.tablet) {
        bindPhotoScreen(tabScr, seoTabImgs.tablet, tabScrW, tabScrH);
      }
    }

    tabGrp.add(new THREE.Mesh(
      new THREE.BoxGeometry(0.28, 0.05, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x9a9ea6 })
    ));
    tabGrp.children[tabGrp.children.length - 1].position.set(0, tH / 2 - 0.06, tD / 2 + 0.004);

    tabGrp.scale.setScalar(0.60);
    tabGrp.position.set(-1.22, 0.82, 0.18);
    tabGrp.rotation.y = 0.18;
    grp.add(tabGrp);

    /* ── Phone — right (dark UI + light search input only) ──────── */
    var phGrp = new THREE.Group();
    var phBodyGeo = new THREE.ExtrudeGeometry(roundedShape(1.85, 3.8, 0.32), SLIM_EXT);
    var phBody = new THREE.Mesh(phBodyGeo, phoneMt);
    phBody.position.z = -SLIM_EXT.depth / 2;
    phGrp.add(phBody);

    var phScrW = 1.62;
    var phScrH = 3.36;
    var phTex = data.goTimeRail ? _makeSearchTex(360, 520, data.css, 'mobile') : makeBrightScreenTex(phScrW, phScrH);
    var phScr = new THREE.Mesh(
      new THREE.PlaneGeometry(phScrW, phScrH),
      new THREE.MeshBasicMaterial({ map: phTex })
    );
    phScr.position.z = SLIM_EXT.depth / 2 + 0.012;
    phGrp.add(phScr);
    if (!data.goTimeRail) {
      var seoPhoneImgs = getServiceScreenImgs('seo');
      if (seoPhoneImgs && seoPhoneImgs.phone) {
        bindPhotoScreen(phScr, seoPhoneImgs.phone, phScrW, phScrH);
      }
    }

    var notchMd = new THREE.Mesh(
      new THREE.CylinderGeometry(0.072, 0.072, 0.04, 16),
      devBezelMat()
    );
    notchMd.rotation.z = Math.PI / 2;
    notchMd.position.set(0, 1.72, SLIM_EXT.depth / 2 + 0.02);
    phGrp.add(notchMd);

    phGrp.scale.setScalar(0.42);
    phGrp.position.set(0.92, 0.58, 0.58);
    phGrp.rotation.y = -0.16;
    grp.add(phGrp);

    var slideX = 3.5;
    grp._multiDevices = [
      { mesh: monGrp,
        start: new THREE.Vector3(monGrp.position.x - 0.8, monGrp.position.y, monGrp.position.z),
        end:   monGrp.position.clone(), delay: 0.0,  phase: 0.0 },
      { mesh: tabGrp,
        start: new THREE.Vector3(tabGrp.position.x - slideX, tabGrp.position.y, tabGrp.position.z),
        end:   tabGrp.position.clone(), delay: 0.15, phase: 1.4 },
      { mesh: phGrp,
        start: new THREE.Vector3(phGrp.position.x + slideX, phGrp.position.y, phGrp.position.z),
        end:   phGrp.position.clone(),  delay: 0.30, phase: 2.8 },
    ];
    monGrp.position.copy(grp._multiDevices[0].end);
    tabGrp.position.copy(grp._multiDevices[1].end);
    phGrp.position.copy(grp._multiDevices[2].end);

    return grp;
  }

  /* ─── Marketing multi-device: landscape tablet (left) + phone (right) ─ */
  function buildMarketingMultiDevice(data) {
    var grp    = new THREE.Group();
    var tabMat = devTabletMat();
    var phoneMt = devPhoneMat();

    /* ── Landscape tablet ─────────────────────────────────────────── */
    var tabGrp = new THREE.Group();
    var tW = 4.0, tH = 2.6, tD = SLIM_EXT.depth;
    var tabBody = new THREE.Mesh(
      new THREE.ExtrudeGeometry(roundedShape(tW, tH, 0.22),
        { depth: tD, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 4 }),
      tabMat
    );
    tabBody.position.z = -tD / 2;
    tabGrp.add(tabBody);

    var tabScrW = tW - 0.20;
    var tabScrH = tH - 0.20;
    var tabTex = data.goTimeRail ? _makeIPadLandscapeTex('marketing', data.css) : makeBrightScreenTex(tabScrW, tabScrH);
    var tabScr = new THREE.Mesh(
      new THREE.PlaneGeometry(tabScrW, tabScrH),
      new THREE.MeshBasicMaterial({ map: tabTex })
    );
    tabScr.position.z = tD / 2 + 0.015;
    tabGrp.add(tabScr);
    if (!data.goTimeRail) {
      var mktImgs = getServiceScreenImgs('marketing');
      if (mktImgs && mktImgs.landscape) {
        bindPhotoScreen(tabScr, mktImgs.landscape, tabScrW, tabScrH);
      }
    }

    /* Camera notch — right edge (landscape) */
    var tNotch = new THREE.Mesh(
      new THREE.BoxGeometry(0.055, 0.28, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x9a9ea6 })
    );
    tNotch.position.set(tW / 2 - 0.065, 0, tD / 2 + 0.004);
    tabGrp.add(tNotch);

    /* Volume buttons — left edge */
    [-0.2, 0.1].forEach(function (yOff) {
      var vBtn = new THREE.Mesh(
        new THREE.BoxGeometry(0.022, 0.14, 0.05),
        devAccentMat()
      );
      vBtn.position.set(-tW / 2 - 0.011, yOff, 0);
      tabGrp.add(vBtn);
    });

    tabGrp.scale.setScalar(0.78);
    tabGrp.position.set(-0.72, 0.02, 0);
    tabGrp.rotation.y = 0.15;
    grp.add(tabGrp);

    /* ── Phone ────────────────────────────────────────────────────── */
    var phGrp = new THREE.Group();
    var phBodyGeo = new THREE.ExtrudeGeometry(roundedShape(1.85, 3.8, 0.32), SLIM_EXT);
    var phBody = new THREE.Mesh(phBodyGeo, phoneMt);
    phBody.position.z = -SLIM_EXT.depth / 2;
    phGrp.add(phBody);

    var phScrW = 1.62;
    var phScrH = 3.36;
    var phTex = data.goTimeRail ? makeScreenTex('instagram', data.css) : makeBrightScreenTex(phScrW, phScrH);
    var phScr = new THREE.Mesh(
      new THREE.PlaneGeometry(phScrW, phScrH),
      new THREE.MeshBasicMaterial({ map: phTex })
    );
    phScr.position.z = SLIM_EXT.depth / 2 + 0.012;
    phGrp.add(phScr);
    if (!data.goTimeRail) {
      var mktPhoneImgs = getServiceScreenImgs('marketing');
      if (mktPhoneImgs && mktPhoneImgs.phone) {
        bindPhotoScreen(phScr, mktPhoneImgs.phone, phScrW, phScrH);
      }
    }

    var phNotch = new THREE.Mesh(
      new THREE.CylinderGeometry(0.072, 0.072, 0.04, 16),
      devBezelMat()
    );
    phNotch.rotation.z = Math.PI / 2;
    phNotch.position.set(0, 1.72, SLIM_EXT.depth / 2 + 0.02);
    phGrp.add(phNotch);

    var phBtn = new THREE.Mesh(
      new THREE.BoxGeometry(0.025, 0.42, 0.04),
      devAccentMat()
    );
    phBtn.position.set(0.98, 0.6, 0.0);
    phGrp.add(phBtn);

    phGrp.scale.setScalar(0.62);
    phGrp.position.set(0.62, -0.04, 0.45);
    phGrp.rotation.y = -0.14;
    grp.add(phGrp);

    /* ── Slide-in animation — matches Website Dev (smoothstep ~0.8s) ─ */
    grp._multiDevices = [
      { mesh: tabGrp,
        start: new THREE.Vector3(tabGrp.position.x - 3.0, tabGrp.position.y, tabGrp.position.z),
        end:   tabGrp.position.clone(),
        delay: 0.0,  phase: 0.0 },
      { mesh: phGrp,
        start: new THREE.Vector3(phGrp.position.x + 2.5, phGrp.position.y, phGrp.position.z),
        end:   phGrp.position.clone(),
        delay: 0.15, phase: 1.4 },
    ];
    tabGrp.position.copy(grp._multiDevices[0].end);
    phGrp.position.copy(grp._multiDevices[1].end);

    return grp;
  }

  /* ══════════════════════════════════════════════════════════════════════
     SPIN ANIMATION HELPERS
  ══════════════════════════════════════════════════════════════════════ */

  function easeInOut(t) {
    return t * t * (3.0 - 2.0 * t); /* smoothstep */
  }

  /* Vertical lift during phone click spin — peaks at mid-rotation, returns to rest */
  var SPIN_LIFT_Y = 0.14;

  function getSpinType(device) {
    if (device === 'phone')                                          return 'y-lift';
    if (device === 'ipad' || device === 'ipad-landscape')           return 'y-flip';
    if (device === 'monitor')                                        return 'y-flip';
    if (device === 'dataviz')                                        return 'y-flip';
    if (device === 'multidevice' || device === 'multidevice-marketing' || device === 'multidevice-search') return 'multi';
    return 'y-flip';
  }

  function getSpinDuration(device) {
    if (device === 'phone')                  return 0.6;
    if (device === 'ipad-landscape')         return 0.7;
    if (device === 'ipad')                   return 0.7;
    if (device === 'monitor')                return 1.0;
    if (device === 'dataviz')                return 0.8;
    if (device === 'multidevice')            return 1.1;
    if (device === 'multidevice-search')     return 1.1;
    if (device === 'multidevice-marketing')  return 0.9;
    return 0.7;
  }

  /* Shared spin-tick handler — uses delta accumulation so the device always
     rotates FORWARD regardless of the starting rotation value.  The previous
     eased value is stored in state.spinPrevT / sd.prevT and the per-frame
     delta is added to the current rotation rather than setting an absolute
     target.  At completion the rotation is snapped back to the pre-spin base
     so any remaining idle-animation frames don't cause a visual snap-back. */
  function tickSpin(state, body, animTexFrame, clockTime, renderer, scene, camera) {
    var spNow = performance.now();
    var spE, spP, spT, spPp, spEt, spAllDone;

    if (state.spinType === 'multi' && state.spinSubDevices.length) {
      spAllDone = true;
      state.spinSubDevices.forEach(function (sd) {
        if (sd.done) return;
        var sdE = (spNow - state.spinStart) / 1000 - sd.delay;
        if (sdE < 0) { spAllDone = false; return; }
        var sdP = Math.min(sdE / sd.duration, 1.0);
        var sdT = easeInOut(sdP);
        /* Delta accumulation — always forward */
        var sdPrev = (sd.prevT !== undefined) ? sd.prevT : 0;
        var sdDelta = sdT - sdPrev;
        sd.prevT = sdT;
        if (sd.type === 'y-lift') {
          sd.mesh.rotation.y += sdDelta * Math.PI * 2;
          sd.mesh.position.y = sd.basePY + Math.sin(sdP * Math.PI) * SPIN_LIFT_Y;
        } else if (sd.type === 'y-flip') {
          sd.mesh.rotation.y += sdDelta * Math.PI * 2;
        } else { /* y-peek */
          var sdPp = sdP < 0.5 ? sdP * 2 : (sdP - 0.5) * 2;
          var sdEt = easeInOut(sdPp);
          sd.mesh.rotation.y = sdP < 0.5
            ? sd.baseRY + sdEt * 0.65
            : sd.baseRY + (1 - sdEt) * 0.65;
        }
        if (sdP >= 1.0) {
          /* Snap to pre-spin rest so idle animation has no distance to travel */
          if (sd.type === 'y-flip' || sd.type === 'y-lift') sd.mesh.rotation.y = sd.baseRY;
          if (sd.type === 'y-lift') sd.mesh.position.y = sd.basePY;
          sd.done = true;
        } else {
          spAllDone = false;
        }
      });
      if (spAllDone) {
        if (state.spinBaseScale != null) body.scale.setScalar(state.spinBaseScale);
        state.spinning = false;
        if (window.location.pathname !== state.spinUrl) { window.location.href = state.spinUrl; }
      }

    } else {
      spE = (spNow - state.spinStart) / 1000;
      spP = Math.min(spE / state.spinDuration, 1.0);
      spT = easeInOut(spP);
      /* Delta accumulation — always forward */
      var spPrev = (state.spinPrevT !== undefined) ? state.spinPrevT : 0;
      var spDelta = spT - spPrev;
      state.spinPrevT = spT;
      if (state.spinType === 'y-lift') {
        body.rotation.y += spDelta * Math.PI * 2;
        body.position.y = state.spinBasePY + Math.sin(spP * Math.PI) * SPIN_LIFT_Y;
      } else if (state.spinType === 'y-flip') {
        body.rotation.y += spDelta * Math.PI * 2;
      } else if (state.spinType === 'y-peek') {
        spPp = spP < 0.5 ? spP * 2 : (spP - 0.5) * 2;
        spEt = easeInOut(spPp);
        body.rotation.y = spP < 0.5
          ? state.spinBaseRY + spEt * 0.65
          : state.spinBaseRY + (1 - spEt) * 0.65;
      } else { /* accelerate — dataviz: 3 fast full rotations */
        body.rotation.y += spDelta * Math.PI * 6;
      }
      if (spP >= 1.0) {
        /* Snap to pre-spin rest to prevent idle-animation snap-back */
        if (state.spinType === 'y-flip' || state.spinType === 'y-lift') body.rotation.y = state.spinBaseRY;
        if (state.spinType === 'y-lift') body.position.y = state.spinBasePY;
        if (state.spinBaseScale != null) body.scale.setScalar(state.spinBaseScale);
        state.spinning = false;
        if (window.location.pathname !== state.spinUrl) { window.location.href = state.spinUrl; }
      } else if (state.spinBaseScale != null && state.spinScaleMul) {
        body.scale.setScalar(state.spinBaseScale * state.spinScaleMul);
      }
    }

    if (body._animTex) {
      animTexFrame++;
      if (animTexFrame % 3 === 0) {
        _drawNeuralNet(body._animTex.ctx, body._animTex.w || 640, body._animTex.h || 360, body._animTex.css, clockTime);
        body._animTex.tex.needsUpdate = true;
      }
    }
    renderer.render(scene, camera);
  }

  function startSpin(state, data, body) {
    if (state.spinning) return;
    state.spinning    = true;
    state.spinStart   = performance.now();
    state.spinUrl     = data.url;
    state.spinType    = getSpinType(data.device);
    state.spinDuration = getSpinDuration(data.device);
    state.spinBaseRY  = body.rotation.y;
    state.spinBaseRZ  = body.rotation.z;
    state.spinBasePY  = body.position.y;
    state.spinPrevT   = 0;
    state.spinSubDevices = [];
    state.spinScaleMul = (state.spinType === 'multi') ? 1.0 : 0.86;
    if (state.spinType === 'multi' && body._multiDevices) {
      var subConfigs = (data.device === 'multidevice' || data.device === 'multidevice-search')
        ? [['y-flip', 1.0], ['y-flip', 0.7], ['y-lift', 0.6]]
        : [['y-flip', 0.7], ['y-lift', 0.6]];
      state.spinSubDevices = body._multiDevices.map(function (d, i) {
        var cfg = subConfigs[i] || ['y-flip', 0.7];
        return { mesh: d.mesh, type: cfg[0], delay: i * 0.10,
                 duration: cfg[1], baseRY: d.mesh.rotation.y,
                 baseRZ: d.mesh.rotation.z, basePY: d.mesh.position.y,
                 prevT: 0, done: false };
      });
    }
  }

  /* ══════════════════════════════════════════════════════════════════════
     PER-CARD SCENE
  ══════════════════════════════════════════════════════════════════════ */

  function initCardScene(canvasEl, cardIdx) {
    if (!window.THREE) return;
    if (!canvasEl || !document.body || !document.body.contains(canvasEl)) return;
    var data = CARDS[cardIdx];
    if (!data) return;

    var W = canvasEl.clientWidth  || 280;
    var H = canvasEl.clientHeight || 220;

    var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(W, H);
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.NoToneMapping;
    renderer.toneMappingExposure = 1;
    if (THREE.sRGBEncoding) renderer.outputEncoding = THREE.sRGBEncoding;
    canvasEl.appendChild(renderer.domElement);

    var scene = new THREE.Scene();
    /* Data-viz card uses a tighter FOV/camera so the sphere fills the frame */
    var isDataViz = (data.device === 'dataviz');
    var camFov  = isDataViz ? 60 : 40;
    var camDist = isDataViz ? 2.0 : 7;
    var camera = new THREE.PerspectiveCamera(camFov, W / H, 0.01, 100);
    camera.position.set(0, 0, camDist);

    /* Lighting */
    scene.add(new THREE.AmbientLight(0x1a1a18, 0.72));
    var key = new THREE.DirectionalLight(0xfff5ee, 2.0);
    key.position.set(4, 7, 10);
    scene.add(key);
    var fill = new THREE.DirectionalLight(0xd4d0c8, 0.5);
    fill.position.set(-6, 3, 6);
    scene.add(fill);
    var rim = new THREE.DirectionalLight(0x9090a0, 0.28);
    rim.position.set(0, 2, -7);
    scene.add(rim);

    /* Synthetic environment map */
    var pmrem = new THREE.PMREMGenerator(renderer);
    var envCv = document.createElement('canvas');
    envCv.width = 256; envCv.height = 128;
    var envCtx = envCv.getContext('2d');
    envCtx.fillStyle = '#080807';
    envCtx.fillRect(0, 0, 256, 128);
    var envTex = new THREE.CanvasTexture(envCv);
    envTex.mapping = THREE.EquirectangularReflectionMapping;
    scene.environment = pmrem.fromEquirectangular(envTex).texture;
    envTex.dispose();
    pmrem.dispose();

    /* Build device */
    var body;
    if (data.device === 'multidevice') {
      body = buildMultiDevice(data);
    } else if (data.device === 'multidevice-search') {
      body = buildSearchMultiDevice(data);
    } else if (data.device === 'multidevice-marketing') {
      body = buildMarketingMultiDevice(data);
    } else if (data.device === 'dataviz') {
      body = buildDataViz(data);
    } else if (data.device === 'phone') {
      body = buildPhone(data);
    } else if (data.device === 'ipad') {
      body = buildIPad(data);
    } else if (data.device === 'ipad-landscape') {
      body = buildIPadLandscape(data);
    } else if (data.device === 'neural-net') {
      body = buildNeuralNetOnly(data);
    } else if (data.device === 'magnifier') {
      /* magnifier kept as fallback — no cards currently use it */
      body = buildIPad(data);
    } else {
      body = buildMonitor(data);
    }
    scene.add(body);

    /* Apply environment to meshes */
    body.traverse(function (ch) {
      if (!ch.isMesh || !ch.material) return;
      if (ch.material.isMeshPhysicalMaterial || ch.material.isMeshStandardMaterial) {
        ch.material.envMap = scene.environment;
        ch.material.envMapIntensity = ch.material.envMapIntensity || 0.55;
        ch.material.needsUpdate = true;
      }
    });

    /* Scale and center device — measure at end (resting) positions */
    if (body._multiDevices) {
      body._multiDevices.forEach(function (d) { d.mesh.position.copy(d.end); });
    }
    body.updateMatrixWorld(true);
    var box = new THREE.Box3().setFromObject(body);
    var bSize = box.getSize(new THREE.Vector3());
    var bCenter = box.getCenter(new THREE.Vector3());
    if (body._multiDevices) {
      body._multiDevices.forEach(function (d) { d.mesh.position.copy(d.start); });
    }

    var visH = 2 * camDist * Math.tan((camFov / 2) * Math.PI / 180);
    var visW = visH * (W / H);
    /* Data-viz sphere has equal extent on all axes — scale by the smaller
       screen dimension so it fits inside both width and height with margin. */
    var target = isDataViz ? (Math.min(visH, visW) * 0.78) : (visH * 0.76);
    var scale  = target / bSize.y;
    body.scale.setScalar(scale);
    body.position.set(-bCenter.x * scale, -bCenter.y * scale, 0);

    /* ── Fix 1: Animation state — hover-only idle animation ─────── */
    var clockTime  = 0;
    var lastTime   = performance.now();
    var hovered    = false;
    var hoverBlend = 0;   /* 0 = completely still, 1 = fully animated  */
    var hoverClock = 0;   /* only advances while hovered               */
    var active     = true;
    var redMot     = window.matchMedia('(prefers-reduced-motion:reduce)').matches;

    /* Hover listeners on parent card */
    var cardEl = canvasEl.closest('.ftc-grid-card');
    var spinState = { spinning: false, spinStart: 0, spinUrl: '', spinType: '',
                      spinDuration: 0.7, spinBaseRY: 0, spinBaseRZ: 0, spinBasePY: 0,
                      spinSubDevices: [] };
    if (cardEl) {
      cardEl.addEventListener('mouseenter', function () { hovered = true; });
      cardEl.addEventListener('mouseleave', function () { hovered = false; });
      cardEl.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!data.url) { navigateToCard(data); return; }
        hovered = false;
        startSpin(spinState, data, body);
      });
    }

    if (isDataViz && window.FTCDataStreamCounters && window.FTCDataStreamCounters.attach) {
      window.FTCDataStreamCounters.attach(canvasEl, { isDetail: false });
    }

    /* Pause when off-screen */
    if (typeof IntersectionObserver !== 'undefined') {
      new IntersectionObserver(function (entries) {
        active = entries[0].isIntersecting;
      }, { threshold: 0.05 }).observe(canvasEl);
    }

    /* Handle resize */
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(function () {
        var nW = canvasEl.clientWidth;
        var nH = canvasEl.clientHeight;
        if (!nW || !nH) return;
        camera.aspect = nW / nH;
        camera.updateProjectionMatrix();
        renderer.setSize(nW, nH);
      }).observe(canvasEl);
    }

    var baseX = body.position.x;
    var baseY = body.position.y;
    var animTexFrame = 0;

    (function tick() {
      requestAnimationFrame(tick);
      if (!active || document.hidden) return;
      var now   = performance.now();
      var delta = Math.min((now - lastTime) / 1000, 0.1); /* cap at 100 ms */
      lastTime  = now;
      clockTime += delta;

      /* ── Click spin — runs exclusively; skips normal motion ─────── */
      if (spinState.spinning) {
        tickSpin(spinState, body, animTexFrame, clockTime, renderer, scene, camera);
        return;
      }

      /* ── Blend hover animation in/out smoothly ─────────────────── */
      if (hovered && !redMot) {
        hoverClock += delta;
        hoverBlend = Math.min(1.0, hoverBlend + delta * 3.6); /* ~0.28 s to full */
      } else {
        hoverBlend = Math.max(0.0, hoverBlend - delta * 2.4); /* ~0.42 s to idle */
      }

      /* Derive all motion from hoverBlend — zero when idle */
      var bob   = hoverBlend * Math.sin(hoverClock * 1.25) * 0.055;
      var sway  = hoverBlend * Math.sin(hoverClock * 0.48) * 0.07;
      var liftY = hoverBlend * 0.14;
      var tiltX = hoverBlend * (-0.06);

      body.position.x = baseX;
      body.position.y = baseY + bob + liftY;

      if (body._isDataViz) {
        body.rotation.y += 0.24 * delta; /* 0.24 rad/s — time-based, frame-rate independent */
        body.rotation.x += (hoverBlend * (-0.05) - body.rotation.x) * 0.06;
      } else if (body._multiDevices) {
        body.rotation.y += (0     - body.rotation.y) * 0.06;
        body.rotation.x += (tiltX - body.rotation.x) * 0.08;
        body._multiDevices.forEach(function (d) {
          /* Fix 2: slide-in tween (~0.8 s) */
          var elapsed = Math.max(0, clockTime - d.delay);
          var t = Math.min(1.0, elapsed * 1.25);
          t = t * t * (3.0 - 2.0 * t); /* smoothstep */
          d.mesh.position.lerpVectors(d.start, d.end, t);
          /* Hover rock — only while hoverBlend > 0 and after animate-in */
          var targetRock = (elapsed > 0.85 && !redMot)
            ? hoverBlend * Math.sin(hoverClock * 0.85 + d.phase) * 0.087
            : 0;
          d.mesh.rotation.y += (targetRock - d.mesh.rotation.y) * 0.05;
        });
      } else {
        body.rotation.y += (sway  - body.rotation.y) * 0.04;
        body.rotation.x += (tiltX - body.rotation.x) * 0.08;
      }

      /* AI monitor — live neural-net texture update (~20 fps) */
      if (body._animTex) {
        animTexFrame++;
        if (animTexFrame % 3 === 0) {
          _drawNeuralNet(body._animTex.ctx, body._animTex.w || 640, body._animTex.h || 360, body._animTex.css, clockTime);
          body._animTex.tex.needsUpdate = true;
        }
      }

      /* Ecommerce product page — scroll on hover */
      if (body._ecomTex) {
        var eTarget = (hovered && !redMot) ? 1.0 : 0.0;
        body._ecomTex.scroll += (eTarget - body._ecomTex.scroll) * (hovered ? 0.055 : 0.09);
        _drawEcommercePageMockup(body._ecomTex.ctx, body._ecomTex.w, body._ecomTex.h, body._ecomTex.css, body._ecomTex.scroll);
        body._ecomTex.tex.needsUpdate = true;
      }

      renderer.render(scene, camera);
    }());
  }

  /* ══════════════════════════════════════════════════════════════════════
     GRID BOOTSTRAP — class-based, supports dynamic injection (Fix 6)
  ══════════════════════════════════════════════════════════════════════ */

  var threeReady = false;
  var threeLoading = false;
  var pendingThreeCbs = [];
  var svcIo = null;

  function whenThreeReady(cb) {
    if (threeReady && window.THREE) { cb(); return; }
    pendingThreeCbs.push(cb);
    startThreeLoad();
  }

  function flushThreeCallbacks() {
    var cbs = pendingThreeCbs.splice(0);
    cbs.forEach(function (cb) {
      try { cb(); } catch (e) { console.warn('FTL scene: three callback error', e); }
    });
  }

  function startThreeLoad() {
    if (threeReady || threeLoading) return;
    threeLoading = true;
    preloadServiceScreenImages();
    loadDeps(function () {
      threeReady = true;
      threeLoading = false;
      setTimeout(function () {
        window.dispatchEvent(new CustomEvent('ftc-three-ready'));
      }, 0);
      flushThreeCallbacks();
      tryBuildGrid();
    });
  }

  function preloadThree() {
    startThreeLoad();
  }

  function ensureService3dSpinner(el) {
    if (!el || el.querySelector('.ftc-service-3d-spinner')) return;
    el.classList.add('is-loading-3d');
    var sp = document.createElement('span');
    sp.className = 'ftc-service-3d-spinner';
    sp.setAttribute('aria-hidden', 'true');
    el.appendChild(sp);
  }

  function removeService3dSpinner(el) {
    if (!el) return;
    el.classList.remove('is-loading-3d');
    var sp = el.querySelector('.ftc-service-3d-spinner');
    if (sp) sp.remove();
  }

  function ensureSvcObserver() {
    /* Reserved — service 3D is initialized explicitly via initService3dIn(). */
  }

  function initService3dIn(root) {
    root = root || document;
    var els = Array.prototype.slice.call(root.querySelectorAll('.ftc-service-3d:not([data-ftc-svc-init="1"])'));
    if (!els.length) return;
    var staggerMs = 40;
    els.forEach(function (el, i) {
      if (el.getAttribute('data-ftc-svc-init') === 'pending') return;
      el.setAttribute('data-ftc-svc-init', 'pending');
      ensureService3dSpinner(el);
      var key = el.getAttribute('data-service') || 'innovation';
      var idx = SVC_MAP.hasOwnProperty(key) ? SVC_MAP[key] : 4;
      var data = CARDS[idx];
      whenThreeReady(function () {
        requestAnimationFrame(function () {
          setTimeout(function () {
            try {
              initServiceScene(el, data);
              el.setAttribute('data-ftc-svc-init', '1');
              removeService3dSpinner(el);
            } catch (e) {
              console.warn('FTL scene: service init error svc=' + key, e);
              el.removeAttribute('data-ftc-svc-init');
              removeService3dSpinner(el);
            }
          }, i * staggerMs);
        });
      });
    });
  }

  function buildGrid() {
    /* Only initialize canvases that haven't been set up yet */
    var canvases = document.querySelectorAll('.ftc-grid-canvas:not([data-ftc-init])');
    canvases.forEach(function (el, seqIdx) {
      el.setAttribute('data-ftc-init', '1');
      /* Read card index from parent data-card-idx attribute */
      var cardEl = el.closest('[data-card-idx]');
      var idx = cardEl ? parseInt(cardEl.getAttribute('data-card-idx'), 10) : seqIdx;
      setTimeout(function () {
        try { initCardScene(el, idx); }
        catch (e) { console.warn('FTL scene: card init error card=' + idx, e); }
      }, seqIdx * 80);
    });
  }

  function tryBuildGrid() {
    try {
      if (!threeReady) return;
      var pending = document.querySelectorAll('.ftc-grid-canvas:not([data-ftc-init])');
      if (pending.length > 0) requestAnimationFrame(buildGrid);
    } catch (e) { console.warn('FTL scene: tryBuildGrid error', e); }
  }

  function tryBuildServiceGrid() {
    /* Service card 3D is booted from app.js after section text is ready. */
  }

  function bootstrapScenes() {
    try {
      if (!document.body) return;

      if (document.querySelector('.ftc-grid-canvas:not([data-ftc-init])')) {
        startThreeLoad();
      }

      var obs = new MutationObserver(function () {
        if (document.querySelector('.ftc-grid-canvas:not([data-ftc-init])')) startThreeLoad();
        tryBuildGrid();
      });
      obs.observe(document.body, { childList: true, subtree: true });
    } catch (e) {
      console.warn('FTL scene: init error', e);
    }
  }

  if (document.body) {
    bootstrapScenes();
  } else {
    document.addEventListener('DOMContentLoaded', bootstrapScenes);
  }

  /* ══════════════════════════════════════════════════════════════════════
     SERVICE CARD 3D — one scene per .ftc-service-3d container
  ══════════════════════════════════════════════════════════════════════ */

  /* Maps data-service attribute values → CARDS[] index */
  var SVC_MAP = {
    web:        0,
    commerce:   1,
    search:     2,
    marketing:  3,
    ai:         4,
    innovation: 4,
    data:       5,
  };

  function initServiceScene(canvasEl, data) {
    if (!window.THREE) return;
    if (!canvasEl || !document.body || !document.body.contains(canvasEl)) return;
    var W = canvasEl.clientWidth  || 280;
    var H = canvasEl.clientHeight || Math.round(W * 10 / 16);

    var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(W, H);
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.NoToneMapping;
    renderer.toneMappingExposure = 1;
    if (THREE.sRGBEncoding) renderer.outputEncoding = THREE.sRGBEncoding;
    canvasEl.appendChild(renderer.domElement);

    var scene  = new THREE.Scene();
    var isDetailCanvas = !!canvasEl.closest('.ftc-service-detail-webgl, .ftc-service-detail-visual');
    var isCategoryCard = !isDetailCanvas && !!canvasEl.closest('.ftc-service-category-card, .ftc-service-card');
    /* Data-viz: tighter camera so sphere fills the frame */
    var isSvcDataViz = (data.device === 'dataviz');
    var svcCamFov    = isSvcDataViz ? 60 : 40;
    var svcCamDist   = isSvcDataViz ? 2.0 : 7;
    var camera = new THREE.PerspectiveCamera(svcCamFov, W / H, 0.01, 100);
    camera.position.set(0, 0, svcCamDist);

    scene.add(new THREE.AmbientLight(0x1a1a18, 0.72));
    var key = new THREE.DirectionalLight(0xfff5ee, 2.0);
    key.position.set(4, 7, 10);
    scene.add(key);
    var fill = new THREE.DirectionalLight(0xd4d0c8, 0.5);
    fill.position.set(-6, 3, 6);
    scene.add(fill);
    var rim = new THREE.DirectionalLight(0x9090a0, 0.28);
    rim.position.set(0, 2, -7);
    scene.add(rim);

    var pmrem  = new THREE.PMREMGenerator(renderer);
    var envCv  = document.createElement('canvas');
    envCv.width = 256; envCv.height = 128;
    var envCtx = envCv.getContext('2d');
    envCtx.fillStyle = '#080807';
    envCtx.fillRect(0, 0, 256, 128);
    var envTex = new THREE.CanvasTexture(envCv);
    envTex.mapping = THREE.EquirectangularReflectionMapping;
    scene.environment = pmrem.fromEquirectangular(envTex).texture;
    envTex.dispose();
    pmrem.dispose();

    var body;
    if      (data.device === 'multidevice')          body = buildMultiDevice(data);
    else if (data.device === 'multidevice-search')   body = buildSearchMultiDevice(data);
    else if (data.device === 'multidevice-marketing') body = buildMarketingMultiDevice(data);
    else if (data.device === 'dataviz')              body = buildDataViz(data);
    else if (data.device === 'glb-model')            body = buildGlbModel(data);
    else if (data.device === 'phone')                body = buildPhone(data);
    else if (data.device === 'ipad')                 body = buildIPad(data);
    else if (data.device === 'ipad-landscape')       body = buildIPadLandscape(data);
    else if (data.device === 'neural-net')           body = buildNeuralNetOnly(data);
    else                                              body = buildMonitor(data);
    scene.add(body);

    body.traverse(function (ch) {
      if (!ch.isMesh || !ch.material) return;
      if (ch.material.isMeshPhysicalMaterial || ch.material.isMeshStandardMaterial) {
        ch.material.envMap = scene.environment;
        ch.material.envMapIntensity = ch.material.envMapIntensity || 0.55;
        ch.material.needsUpdate = true;
      }
    });

    /* Scale to fill the container at rest position */
    var isGlbModel      = (data.device === 'glb-model');
    var isNeuralNet     = (data.device === 'neural-net' || isGlbModel);
    var isLandscapeIpad = (data.device === 'ipad-landscape');
    var isPhone         = (data.device === 'phone');
    var isMonitor       = (data.device === 'monitor');
    var isMultiDev      = (data.device === 'multidevice' || data.device === 'multidevice-marketing' || data.device === 'multidevice-search');
    var isSingleDevice  = (data.device === 'phone' || data.device === 'ipad' || data.device === 'ipad-landscape' || data.device === 'monitor');
    /* ── Unified contain-fit: one padding for category cards; projected
       bounds union per sub-device for multi-device clusters. ── */
    var SVC_CARD_PAD = 0.94;
    var SVC_FIT_PAD = 0.96;
    var SVC_FIT_PAD_DATA = 0.98;
    var SVC_FIT_PAD_NEURAL_DETAIL = 0.88;
    var SVC_FIT_PAD_ANGLED = 0.86;
    var bSize  = new THREE.Vector3(1, 1, 1);
    var bCenter= new THREE.Vector3(0, 0, 0);
    var bRadius = 1;
    var baseFitScale = 1;
    var baseX = 0;
    var baseY = 0;
    var fitRetryScheduled = false;
    var isDetailNeural = isDetailCanvas && isNeuralNet;
    function getSvcFitPad() {
      if (isCategoryCard) {
        if (isSvcDataViz) return SVC_FIT_PAD_DATA;
        if (isMultiDev) return SVC_CARD_PAD * 1.03;
        if (isSingleDevice && !isNeuralNet && !isSvcDataViz) return SVC_CARD_PAD * 0.96;
        return SVC_CARD_PAD;
      }
      if (isDetailNeural) return SVC_FIT_PAD_NEURAL_DETAIL;
      if (isSvcDataViz) return SVC_FIT_PAD_DATA;
      return SVC_FIT_PAD;
    }
    function getSvcVisibleFrustum(viewW, viewH) {
      var vW = viewW || W;
      var vH = viewH || H;
      if (!vW || !vH) return null;
      var visH = 2 * svcCamDist * Math.tan((svcCamFov / 2) * Math.PI / 180);
      return { visW: visH * (vW / vH), visH: visH };
    }
    function getProjectedBounds(object, camera) {
      var box = new THREE.Box3().setFromObject(object);
      if (box.isEmpty()) return null;
      var pts = [
        new THREE.Vector3(box.min.x, box.min.y, box.min.z),
        new THREE.Vector3(box.min.x, box.min.y, box.max.z),
        new THREE.Vector3(box.min.x, box.max.y, box.min.z),
        new THREE.Vector3(box.min.x, box.max.y, box.max.z),
        new THREE.Vector3(box.max.x, box.min.y, box.min.z),
        new THREE.Vector3(box.max.x, box.min.y, box.max.z),
        new THREE.Vector3(box.max.x, box.max.y, box.min.z),
        new THREE.Vector3(box.max.x, box.max.y, box.max.z)
      ];
      var ndc = new THREE.Vector3();
      var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
      for (var pi = 0; pi < pts.length; pi++) {
        ndc.copy(pts[pi]).project(camera);
        if (ndc.x < minX) minX = ndc.x;
        if (ndc.x > maxX) maxX = ndc.x;
        if (ndc.y < minY) minY = ndc.y;
        if (ndc.y > maxY) maxY = ndc.y;
      }
      return {
        minX: minX, maxX: maxX, minY: minY, maxY: maxY,
        centerX: (minX + maxX) * 0.5,
        centerY: (minY + maxY) * 0.5,
        width: maxX - minX,
        height: maxY - minY
      };
    }
    function getMeshScreenBounds(mesh, camera) {
      if (!mesh) return null;
      var screen = null;
      var bestArea = 0;
      mesh.traverse(function (ch) {
        if (!ch.isMesh || !ch.geometry) return;
        var pw = 0;
        var ph = 0;
        if (ch.geometry.parameters) {
          pw = ch.geometry.parameters.width || 0;
          ph = ch.geometry.parameters.height || 0;
        }
        if (pw < 0.05 || ph < 0.05) return;
        /* Prefer emissive/display planes over incidental geometry */
        var isDisplay = ch.material && ch.material.map && ch.geometry.type === 'PlaneGeometry';
        var area = pw * ph * (isDisplay ? 1.25 : 1);
        if (area > bestArea) {
          bestArea = area;
          screen = ch;
        }
      });
      if (screen) {
        var scrBox = new THREE.Box3().setFromObject(screen);
        if (!scrBox.isEmpty()) {
          var padX = (scrBox.max.x - scrBox.min.x) * 0.10;
          var padY = (scrBox.max.y - scrBox.min.y) * 0.10;
          var padZ = (scrBox.max.z - scrBox.min.z) * 0.10;
          scrBox.expandByVector(new THREE.Vector3(padX, padY, padZ));
          var pts = [
            new THREE.Vector3(scrBox.min.x, scrBox.min.y, scrBox.min.z),
            new THREE.Vector3(scrBox.min.x, scrBox.min.y, scrBox.max.z),
            new THREE.Vector3(scrBox.min.x, scrBox.max.y, scrBox.min.z),
            new THREE.Vector3(scrBox.min.x, scrBox.max.y, scrBox.max.z),
            new THREE.Vector3(scrBox.max.x, scrBox.min.y, scrBox.min.z),
            new THREE.Vector3(scrBox.max.x, scrBox.min.y, scrBox.max.z),
            new THREE.Vector3(scrBox.max.x, scrBox.max.y, scrBox.min.z),
            new THREE.Vector3(scrBox.max.x, scrBox.max.y, scrBox.max.z)
          ];
          var ndc = new THREE.Vector3();
          var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
          for (var si = 0; si < pts.length; si++) {
            ndc.copy(pts[si]).project(camera);
            if (ndc.x < minX) minX = ndc.x;
            if (ndc.x > maxX) maxX = ndc.x;
            if (ndc.y < minY) minY = ndc.y;
            if (ndc.y > maxY) maxY = ndc.y;
          }
          return {
            minX: minX, maxX: maxX, minY: minY, maxY: maxY,
            centerX: (minX + maxX) * 0.5,
            centerY: (minY + maxY) * 0.5,
            width: maxX - minX,
            height: maxY - minY
          };
        }
      }
      return getProjectedBounds(mesh, camera);
    }
    /* Multi-device: union screen+bezel bounds per sub-device — avoids
       oversized group AABB (monitor stands, side buttons, gaps). */
    function getContentProjectedBounds(object, camera, opts) {
      opts = opts || {};
      if (object._multiDevices && object._multiDevices.length) {
        var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        var maxDevW = 0, maxDevH = 0;
        var any = false;
        object._multiDevices.forEach(function (d) {
          var pb = getMeshScreenBounds(d.mesh, camera);
          if (!pb) return;
          any = true;
          if (pb.width > maxDevW) maxDevW = pb.width;
          if (pb.height > maxDevH) maxDevH = pb.height;
          if (pb.minX < minX) minX = pb.minX;
          if (pb.maxX > maxX) maxX = pb.maxX;
          if (pb.minY < minY) minY = pb.minY;
          if (pb.maxY > maxY) maxY = pb.maxY;
        });
        if (any) {
          var unionW = maxX - minX;
          var unionH = maxY - minY;
          if (opts.tightCluster && object._multiDevices.length > 1 && unionW > maxDevW) {
            unionW = maxDevW + (unionW - maxDevW) * 0.35;
          }
          if (opts.tightCluster && object._multiDevices.length > 1 && unionH > maxDevH) {
            unionH = maxDevH + (unionH - maxDevH) * 0.40;
          }
          return {
            minX: minX, maxX: maxX, minY: minY, maxY: maxY,
            centerX: (minX + maxX) * 0.5,
            centerY: (minY + maxY) * 0.5,
            width: unionW,
            height: unionH
          };
        }
      }
      if (isNeuralNet || isSvcDataViz) {
        var sBox = new THREE.Box3().setFromObject(object);
        if (!sBox.isEmpty()) {
          var sp = sBox.getBoundingSphere(new THREE.Sphere());
          var ctr = sp.center.clone();
          var axes = [
            new THREE.Vector3(1, 0, 0), new THREE.Vector3(0, 1, 0), new THREE.Vector3(0, 0, 1),
            new THREE.Vector3(1, 1, 0).normalize(), new THREE.Vector3(1, -1, 0).normalize()
          ];
          var ndc = new THREE.Vector3();
          var minX2 = Infinity, maxX2 = -Infinity, minY2 = Infinity, maxY2 = -Infinity;
          axes.forEach(function (axis) {
            var p1 = ctr.clone().add(axis.clone().multiplyScalar(sp.radius));
            var p2 = ctr.clone().add(axis.clone().multiplyScalar(-sp.radius));
            [p1, p2].forEach(function (p) {
              ndc.copy(p).project(camera);
              if (ndc.x < minX2) minX2 = ndc.x;
              if (ndc.x > maxX2) maxX2 = ndc.x;
              if (ndc.y < minY2) minY2 = ndc.y;
              if (ndc.y > maxY2) maxY2 = ndc.y;
            });
          });
          return {
            minX: minX2, maxX: maxX2, minY: minY2, maxY: maxY2,
            centerX: (minX2 + maxX2) * 0.5,
            centerY: (minY2 + maxY2) * 0.5,
            width: maxX2 - minX2,
            height: maxY2 - minY2
          };
        }
      }
      return getProjectedBounds(object, camera);
    }
    function getFitBoundsOpts() {
      return { tightCluster: isCategoryCard && isMultiDev };
    }
    function applyCameraSpaceCentering(viewW, viewH) {
      var frustum = getSvcVisibleFrustum(viewW, viewH);
      if (!frustum) return;
      for (var pass = 0; pass < 12; pass++) {
        body.updateMatrixWorld(true);
        var bounds = getContentProjectedBounds(body, camera, getFitBoundsOpts());
        if (!bounds) return;
        if (Math.abs(bounds.centerX) < 0.008 && Math.abs(bounds.centerY) < 0.008) break;
        body.position.x += -bounds.centerX * frustum.visW * 0.5;
        body.position.y += -bounds.centerY * frustum.visH * 0.5;
      }
    }
    function centerBodyOnContent(viewW, viewH) {
      body.updateMatrixWorld(true);
      var pb = getContentProjectedBounds(body, camera, getFitBoundsOpts());
      var frustum = getSvcVisibleFrustum(viewW, viewH);
      if (pb && frustum) {
        body.position.x -= pb.centerX * frustum.visW * 0.5;
        body.position.y -= pb.centerY * frustum.visH * 0.5;
        body.updateMatrixWorld(true);
        return;
      }
      var box = new THREE.Box3().setFromObject(body);
      if (box.isEmpty()) return;
      var c = box.getCenter(new THREE.Vector3());
      body.position.x -= c.x;
      body.position.y -= c.y;
      body.updateMatrixWorld(true);
    }
    function setMultiDevicePose(poseMode) {
      if (!body._multiDevices) return;
      body._multiDevices.forEach(function (d) {
        d.mesh.position.copy(poseMode === 'start' ? d.start : d.end);
      });
    }
    function getMultiDeviceMeasurePose() {
      return 'end';
    }
    function applyBodyFit(viewW, viewH, opts) {
      opts = opts || {};
      body.position.set(0, 0, 0);
      body.scale.set(1, 1, 1);
      if (isMultiDev) setMultiDevicePose('end');
      body.updateMatrixWorld(true);
      var frustum = getSvcVisibleFrustum(viewW, viewH);
      if (!frustum) return;
      var pad = getSvcFitPad();
      if (!isCategoryCard && !isMultiDev && !isSvcDataViz && !isNeuralNet &&
          (Math.abs(body.rotation.y) > 0.08 || Math.abs(body.rotation.x) > 0.04)) {
        pad = Math.min(pad, SVC_FIT_PAD_ANGLED);
      }
      var targetNdc = pad * 2;

      /* Category cards: center on content, then iterative projected contain-fit */
      if (isCategoryCard) {
        centerBodyOnContent(viewW, viewH);
        var catScale = 1;
        for (var ci = 0; ci < 20; ci++) {
          body.scale.setScalar(catScale);
          body.updateMatrixWorld(true);
          var cpb = getContentProjectedBounds(body, camera, getFitBoundsOpts());
          if (!cpb || cpb.width < 0.001 || cpb.height < 0.001) break;
          var cFit = Math.min(targetNdc / cpb.width, targetNdc / cpb.height);
          if (!isFinite(cFit) || cFit <= 0) break;
          if (Math.abs(cFit - 1) < 0.006) break;
          catScale *= cFit;
        }
        if (!isFinite(catScale) || catScale <= 0) catScale = 1;
        baseFitScale = catScale;
        body.scale.setScalar(catScale);
        body.updateMatrixWorld(true);
        if (isMultiDev) {
          var fullPb = getContentProjectedBounds(body, camera);
          if (fullPb && fullPb.width > 0.001 && fullPb.height > 0.001) {
            var cropClamp = Math.min(targetNdc / fullPb.width, targetNdc / fullPb.height, 1);
            if (cropClamp < 0.995) {
              catScale *= cropClamp;
              baseFitScale = catScale;
              body.scale.setScalar(catScale);
              body.updateMatrixWorld(true);
            }
          }
        }
        applyCameraSpaceCentering(viewW, viewH);
        if (isMultiDev) setMultiDevicePose('end');
        return;
      }

      var pb = getContentProjectedBounds(body, camera, getFitBoundsOpts());
      if (!pb || pb.width < 0.001 || pb.height < 0.001) {
        var box = new THREE.Box3().setFromObject(body);
        if (box.isEmpty()) {
          if (!fitRetryScheduled) {
            fitRetryScheduled = true;
            requestAnimationFrame(function () {
              fitRetryScheduled = false;
              applyBodyFit(viewW, viewH, opts);
              syncFitOrigin();
            });
          }
          return;
        }
        bSize = box.getSize(new THREE.Vector3());
        bRadius = Math.max(0.001, box.getBoundingSphere(new THREE.Sphere()).radius || 1);
        var scaleFallback = Math.min(
          (frustum.visW * pad) / Math.max(bSize.x, 0.001),
          (frustum.visH * pad) / Math.max(bSize.y, 0.001)
        );
        if (isSvcDataViz || isNeuralNet) {
          scaleFallback = Math.min(frustum.visW, frustum.visH) * pad * 0.5 / bRadius;
        }
        if (!isFinite(scaleFallback) || scaleFallback <= 0) scaleFallback = 1;
        baseFitScale = scaleFallback;
        body.scale.setScalar(scaleFallback);
        body.position.set(0, 0, 0);
        if (isMultiDev) setMultiDevicePose('end');
        body.updateMatrixWorld(true);
        var fbCenter = box.getCenter(new THREE.Vector3());
        body.position.set(-fbCenter.x * scaleFallback, -fbCenter.y * scaleFallback, 0);
        applyCameraSpaceCentering(viewW, viewH);
      } else {
        centerBodyOnContent(viewW, viewH);
        var scale = Math.min(
          targetNdc / Math.max(pb.width, 0.001),
          targetNdc / Math.max(pb.height, 0.001)
        );
        if (!isFinite(scale) || scale <= 0) scale = 1;
        baseFitScale = scale;
        body.scale.setScalar(scale);
        body.updateMatrixWorld(true);
        applyCameraSpaceCentering(viewW, viewH);
        pb = getContentProjectedBounds(body, camera, getFitBoundsOpts());
        if (pb && pb.width > 0.001 && pb.height > 0.001) {
          var clamp = Math.min(targetNdc / pb.width, targetNdc / pb.height, 1);
          if (clamp < 0.985) {
            body.scale.multiplyScalar(clamp);
            baseFitScale = body.scale.x;
            body.updateMatrixWorld(true);
            applyCameraSpaceCentering(viewW, viewH);
          }
        }
      }
      if (isMultiDev && body._multiDevices) {
        setMultiDevicePose('end');
      }
    }
    function syncFitOrigin() {
      baseX = body.position.x;
      baseY = body.position.y;
    }

    /* ── Fix 4: Angled rest rotation — apply before fit so bbox includes tilt ── */
    var detailBaseRY = 0;
    var detailBaseRX = 0;
    if (!isMultiDev && !isSvcDataViz) {
      if (isNeuralNet) {
        detailBaseRY = 0;
        detailBaseRX = 0;
      } else if (data.id === 'seo') {
        detailBaseRY = isLandscapeIpad ? 0.45 : 0.38;
        detailBaseRX = 0;
      } else {
        detailBaseRY = isLandscapeIpad ? -0.45 : (isPhone ? -0.38 : -0.35);
        detailBaseRX = isMonitor ? -0.10 : 0;
      }
      body.rotation.y = detailBaseRY;
      body.rotation.x = detailBaseRX;
    }
    var redMot = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
    applyBodyFit(W, H);
    syncFitOrigin();
    renderer.render(scene, camera);

    var contactShadow = null;
    if (isDetailNeural) {
      contactShadow = makeNeuralContactShadow();
      scene.add(contactShadow);
    }

    var clockTime  = 0;
    var lastTime   = performance.now();
    var hovered    = false;
    var hoverBlend = 0;
    var hoverClock = 0;
    var active     = true;

    /* ── Fix 3: Animate-in — detail hero only; category cards stay centered ── */
    var slideFromLeft = (data.id === 'websites' || data.id === 'seo');
    var frInit = getSvcVisibleFrustum(W, H) || { visW: 1, visH: 1 };
    var svcVisHForAnim = frInit.visH;
    var svcVisWForAnim = frInit.visW;
    var useDetailSlideIn = false;
    var animInStartX = baseX;
    if (useDetailSlideIn) {
      animInStartX = baseX + (slideFromLeft ? -(svcVisWForAnim * 0.55) : (svcVisWForAnim * 0.55));
      if (!redMot) body.position.x = animInStartX;
    }
    var ANIM_IN_DUR = 1.0; /* seconds */

    if (typeof body._onModelReady === 'function') {
      body._onModelReady(function () {
        applyBodyFit(W, H);
        syncFitOrigin();
        if (useDetailSlideIn) {
          var frAnim = getSvcVisibleFrustum(W, H);
          svcVisWForAnim = frAnim ? frAnim.visW : svcVisWForAnim;
          animInStartX = baseX + (slideFromLeft ? -(svcVisWForAnim * 0.55) : (svcVisWForAnim * 0.55));
          if (!redMot && clockTime < ANIM_IN_DUR) body.position.x = animInStartX;
        }
      });
    }

    /* Hover on the closest service card or detail ancestor */
    var cardEl = canvasEl.closest('.ftc-service-category-card, .ftc-service-card, .ftc-service-detail-webgl, .ftc-service-detail-visual');
    var spinState = { spinning: false, spinStart: 0, spinUrl: '', spinType: '',
                      spinDuration: 0.7, spinBaseRY: 0, spinBaseRZ: 0, spinBasePY: 0,
                      spinSubDevices: [], spinBaseScale: 1, spinScaleMul: 1 };
    if (cardEl) {
      if (isDetailCanvas) {
        cardEl.addEventListener('mouseenter', function () { hovered = true;  });
        cardEl.addEventListener('mouseleave', function () { hovered = false; });
      }
      cardEl.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!data.url) { navigateToCard(data); return; }
        hovered = false;
        spinState.spinBaseScale = baseFitScale;
        startSpin(spinState, data, body);
      });
    }

    if (isSvcDataViz && window.FTCDataStreamCounters && window.FTCDataStreamCounters.attach) {
      window.FTCDataStreamCounters.attach(canvasEl, { isDetail: isDetailCanvas });
    }

    if (typeof IntersectionObserver !== 'undefined') {
      new IntersectionObserver(function (entries) {
        active = entries[0].isIntersecting;
      }, { threshold: 0.05 }).observe(canvasEl);
    }

    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(function () {
        var nW = canvasEl.clientWidth;
        var nH = canvasEl.clientHeight;
        if (!nW || !nH) return;
        camera.aspect = nW / nH;
        camera.updateProjectionMatrix();
        renderer.setSize(nW, nH);
        applyBodyFit(nW, nH);
        syncFitOrigin();
        if (useDetailSlideIn) {
          svcVisWForAnim = svcVisHForAnim * (nW / nH);
          animInStartX = baseX + (slideFromLeft ? -(svcVisWForAnim * 0.55) : (svcVisWForAnim * 0.55));
          if (!redMot && clockTime < ANIM_IN_DUR) body.position.x = animInStartX;
        }
      }).observe(canvasEl);
    }

    var animTexFrame = 0;

    (function tick() {
      requestAnimationFrame(tick);
      if (!active && !isCategoryCard) return;
      if (document.hidden) return;
      var now   = performance.now();
      var delta = Math.min((now - lastTime) / 1000, 0.1);
      lastTime  = now;
      clockTime += delta;

      /* ── Click spin — runs exclusively; skips normal motion ─────── */
      if (spinState.spinning) {
        tickSpin(spinState, body, animTexFrame, clockTime, renderer, scene, camera);
        return;
      }

      /* ── Fix 5: Hover blend ─────────────────────────────────────── */
      if (hovered && !redMot) {
        hoverClock += delta;
        hoverBlend = Math.min(1.0, hoverBlend + delta * 3.6);
      } else {
        hoverBlend = Math.max(0.0, hoverBlend - delta * 2.4);
      }

      var motionScale = (isDetailCanvas && !(isSvcDataViz && isDetailCanvas)) ? 1.0 : 0.0;
      var cardLift = (isDetailCanvas && !(isSvcDataViz && isDetailCanvas)) ? 1.0 : 0.0;
      var hoverScaleAmt = isDetailNeural ? (1.0 + hoverBlend * 0.06) : 1.0;
      var bob   = isDetailNeural ? 0 : hoverBlend * Math.sin(hoverClock * 1.25) * 0.055 * motionScale;
      var sway  = hoverBlend * Math.sin(hoverClock * 0.48) * 0.07 * motionScale;
      var liftY = isDetailNeural
        ? hoverBlend * (-0.09) * cardLift
        : hoverBlend * 0.07 * cardLift;
      var tiltX = hoverBlend * (-0.06) * motionScale;

      if (isDetailNeural) {
        body.scale.setScalar(baseFitScale * hoverScaleAmt);
      }

      /* ── Fix 3: Animate-in x position (smoothstep, ~1.0 s) — detail only ── */
      var currentBaseX = baseX;
      if (useDetailSlideIn) {
        var animT = redMot ? 1.0 : Math.min(clockTime / ANIM_IN_DUR, 1.0);
        animT = animT * animT * (3.0 - 2.0 * animT); /* smoothstep */
        currentBaseX = animInStartX + (baseX - animInStartX) * animT;
      }

      body.position.x = currentBaseX;
      body.position.y = baseY + bob + liftY;

      if (contactShadow) {
        body.updateMatrixWorld(true);
        var hoverBox = new THREE.Box3().setFromObject(body);
        var shadowSpread = bRadius * baseFitScale * hoverScaleAmt;
        contactShadow.scale.set(shadowSpread * 2.05, shadowSpread * 1.22, 1);
        contactShadow.position.set(body.position.x, hoverBox.min.y - shadowSpread * 0.05, -0.06);
        contactShadow.material.opacity = 0.34 + hoverBlend * 0.34;
      }

      if (body._isDataViz) {
        body.rotation.y += 0.24 * delta; /* 0.24 rad/s — time-based */
        if (isDetailCanvas) {
          body.rotation.x += (0 - body.rotation.x) * 0.08;
        } else {
          body.rotation.x += ((hoverBlend * (-0.05) * motionScale) - body.rotation.x) * 0.06;
        }
      } else if (isMultiDev) {
        body.rotation.y += (0     - body.rotation.y) * 0.06;
        body.rotation.x += (tiltX - body.rotation.x) * 0.08;
        body._multiDevices && body._multiDevices.forEach(function (d) {
          if (isDetailCanvas || isCategoryCard) {
            d.mesh.position.copy(d.end);
            return;
          }
          var elapsed = Math.max(0, clockTime - d.delay);
          var t = Math.min(1.0, elapsed * 1.25);
          t = t * t * (3.0 - 2.0 * t);
          d.mesh.position.lerpVectors(d.start, d.end, t);
          var targetRock = (elapsed > 0.85 && !redMot)
            ? hoverBlend * Math.sin(hoverClock * 0.85 + d.phase) * 0.087 : 0;
          d.mesh.rotation.y += (targetRock - d.mesh.rotation.y) * 0.05;
        });
      } else {
        /* Hover: straighten to face-on (Y→0); mouseleave: return to angled rest.
           hoverBlend 0→1 blends from detailBaseRY to 0. */
        var targetRY = detailBaseRY * (1.0 - hoverBlend);
        var targetRX = detailBaseRX + tiltX;
        body.rotation.y += (targetRY - body.rotation.y) * 0.04;
        body.rotation.x += (targetRX - body.rotation.x) * 0.08;
      }

      if (body._animTex) {
        animTexFrame++;
        if (animTexFrame % 3 === 0) {
          _drawNeuralNet(body._animTex.ctx, body._animTex.w || 640, body._animTex.h || 360, body._animTex.css, clockTime);
          body._animTex.tex.needsUpdate = true;
        }
      }

      if (body._ecomTex) {
        var eTargetSvc = (hovered && !redMot) ? 1.0 : 0.0;
        body._ecomTex.scroll += (eTargetSvc - body._ecomTex.scroll) * (hovered ? 0.055 : 0.09);
        _drawEcommercePageMockup(body._ecomTex.ctx, body._ecomTex.w, body._ecomTex.h, body._ecomTex.css, body._ecomTex.scroll);
        body._ecomTex.tex.needsUpdate = true;
      }

      renderer.render(scene, camera);
    }());
  }

  function buildServiceGrid() {
    initService3dIn(document);
  }

  function buildDeviceBody(data) {
    if      (data.device === 'multidevice')           return buildMultiDevice(data);
    else if (data.device === 'multidevice-search')   return buildSearchMultiDevice(data);
    else if (data.device === 'multidevice-marketing') return buildMarketingMultiDevice(data);
    else if (data.device === 'dataviz')              return buildDataViz(data);
    else if (data.device === 'phone')                return buildPhone(data);
    else if (data.device === 'ipad')                 return buildIPad(data);
    else if (data.device === 'ipad-landscape')       return buildIPadLandscape(data);
    else if (data.device === 'neural-net')           return buildNeuralNetOnly(data);
    return buildMonitor(data);
  }

  function getCardById(id) {
    for (var i = 0; i < CARDS.length; i++) {
      if (CARDS[i].id === id) return CARDS[i];
    }
    return null;
  }

  window.FTCGetStartedScene = {
    CARDS: CARDS,
    SERVICE_SCREEN_IMGS: SERVICE_SCREEN_IMGS,
    loadDeps: loadDeps,
    preloadServiceScreenImages: preloadServiceScreenImages,
    buildDeviceBody: buildDeviceBody,
    getCardById: getCardById,
    drawNeuralNet: _drawNeuralNet,
    drawEcommercePageMockup: _drawEcommercePageMockup,
    drawWebsiteHeroAnim: _drawWebsiteHeroAnim,
    drawMarketingDashboardAnim: _drawMarketingDashboardAnim,
    drawMarketingPhoneAnim: _drawMarketingPhoneAnim,
    setupGoTimeScreenAnims: setupGoTimeScreenAnims,
    applyGoTimeScreenMat: applyGoTimeScreenMat,
    applyGoTimeEmissiveScreen: applyGoTimeEmissiveScreen,
    tuneGoTimeBezelMaterials: tuneGoTimeBezelMaterials,
    tuneGoTimeDeviceBody: tuneGoTimeDeviceBody,
    tuneGoTimeScreenMaterials: tuneGoTimeScreenMaterials,
    initService3dIn: initService3dIn,
    preloadThree: preloadThree,
    buildServiceGrid: buildServiceGrid,
    getSelectiveDrawDataScene: function () {
      var svc = window.FTCServiceVisual;
      return svc && svc.buildSelectiveDrawDataScene ? svc.buildSelectiveDrawDataScene : null;
    },
    getUpdateSelectiveDrawData: function () {
      var svc = window.FTCServiceVisual;
      return svc && svc.updateSelectiveDrawData ? svc.updateSelectiveDrawData : null;
    },
  };

}());
