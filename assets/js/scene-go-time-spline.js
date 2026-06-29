/* ─────────────────────────────────────────────────────────────────────────────
   FTL Go Time — Spline runtime + screen mesh texture  v5.59.6
   Primary: self-hosted .splinecode via @splinetool/runtime
   Phone motion/entrance: Spline-authored only (setVariables + emitEvent)
   Ch0 Web Dev: native Spline video screen (restoreNativeScreen)
   Ch1 Data: selective-draw globe via FTCServiceVisual.bootGoTimeDataScene
   Two-station scroll rail — Web Dev (0–0.5) → Data (0.5–1.0)
─────────────────────────────────────────────────────────────────────────────*/
(function () {
  'use strict';

  var VERSION = '5.59.6';
  var DESKTOP_PREVIEW_SCENE =
    'https://prod.spline.design/XRICJt1eQGpipdNw/scene.splinecode';
  var SPLINE_PHONE_NAME = 'iPhone 14 Pro';
  var SPLINE_CONTAINER_ROTATION = 'Container Rotation';
  var SPLINE_SCREEN_EVENT_NAME = 'Screen';
  var WEB_DEV_CHAPTER = 0;
  var DATA_CHAPTER = 1;
  var SCREEN_OVERLAY_NAME = 'FTC-Screen-Overlay';
  var STATION_IDS = ['websites', 'data'];
  var CHAPTER_ACCENTS = ['#10b981', '#8b5cf6'];
  var CHAPTER_EVENTS = {
    websites: 'WebDev', data: 'Data'
  };

  var CLOSING_PROGRESS = 0.88;
  var ENTRANCE_SCROLL = 0.05;
  var WEB_DEV_SCROLL_END = 0.42;
  var CHAPTER_SCROLL_START = 0.48;
  var SECTION_BLEND_START = 0.35;
  var SECTION_BLEND_END = 0.65;
  var TWIST_RANGE_RAD = 0.72;
  var PHONE_PARALLAX_X_VW = 8;
  /* scrollProgress marks — chapter crossfade + Spline chapterIndex events */
  var CHAPTER_MARKS = [
    { t: 0.00, chapter: 0 },
    { t: 0.45, chapter: 0 },
    { t: 0.50, chapter: 1 },
    { t: 0.95, chapter: 1 },
    { t: 1.00, chapter: 1 }
  ];
  var SCREEN_MESH_NAMES = ['Screen', 'Body-Screen', 'ScreenX', 'Display'];
  var DEMO_UI_HIDE_NAMES = ['Group', 'Connectors', 'Logo', 'Calendar card', 'calendar.png', '19th clicked'];
  var RUNTIME_VERSION = '1.12.97';
  var LOADER_MIN_MS = 900;
  var RUNTIME_TIMEOUT_MS = 22000;
  var IFRAME_TIMEOUT_MS = 12000;
  var LOADER_FADE_MS = 450;
  var SCREEN_W = 390;
  var SCREEN_H = 844;

  var ACCENT_RGB = {
    '#10b981': [16, 185, 129],
    '#8b5cf6': [139, 92, 246]
  };
  function sectionBlendFromScroll(scrollT) {
    var t = Math.max(0, Math.min(1, scrollT));
    if (t <= SECTION_BLEND_START) return 0;
    if (t >= SECTION_BLEND_END) return 1;
    return easeInOutCubic((t - SECTION_BLEND_START) / (SECTION_BLEND_END - SECTION_BLEND_START));
  }

  function phoneTwistFromScroll(scrollT) {
    var t = easeInOutCubic(Math.max(0, Math.min(1, scrollT)));
    return (t - 0.5) * 2 * TWIST_RANGE_RAD;
  }

  function findSplineObject(app, names) {
    if (!app || typeof app.findObjectByName !== 'function') return null;
    for (var i = 0; i < names.length; i++) {
      try {
        var o = app.findObjectByName(names[i]);
        if (o) return o;
      } catch (e) { /* optional */ }
    }
    return null;
  }

  function ensureDataVisualHost(viewportEl) {
    if (!viewportEl) return null;
    var host = viewportEl.querySelector('.ftc-go-time-spline-data');
    if (!host) {
      host = document.createElement('div');
      host.className = 'ftc-go-time-spline-data ftc-go-time-data-visual';
      host.setAttribute('aria-hidden', 'true');
      var canvasHost = document.createElement('div');
      canvasHost.className = 'ftc-service-webgl ftc-go-time-canvas';
      host.appendChild(canvasHost);
      viewportEl.appendChild(host);
    }
    return {
      visualEl: host,
      canvasHost: host.querySelector('.ftc-service-webgl') || host
    };
  }

  function bootDataLayer(viewportEl, scrollRoot, redMot, getScrollT) {
    var svc = window.FTCServiceVisual;
    if (!svc || !svc.bootGoTimeDataScene) return null;
    var hosts = ensureDataVisualHost(viewportEl);
    if (!hosts) return null;
    return svc.bootGoTimeDataScene(hosts.visualEl, hosts.canvasHost, {
      scrollRoot: scrollRoot,
      reducedMotion: redMot,
      getScrollProgress: function () {
        var t = getScrollT();
        return Math.max(0, Math.min(1, (t - SECTION_BLEND_START) / (1 - SECTION_BLEND_START)));
      }
    });
  }

  function applyPhoneMotion(app, canvasEl, dataHost, scrollT, sectionBlend, prevTwistBucket) {
    if (!app) return prevTwistBucket;
    var twistRad = phoneTwistFromScroll(scrollT);
    var twistNorm = Math.max(-1, Math.min(1, twistRad / TWIST_RANGE_RAD));
    var parallaxX = sectionBlend * PHONE_PARALLAX_X_VW;
    var phoneFade = 1 - smoothstep(sectionBlend);
    var scale = 1 - sectionBlend * 0.12;

    var container = findSplineObject(app, [SPLINE_CONTAINER_ROTATION, SPLINE_PHONE_NAME]);
    if (container && container.rotation) {
      container.rotation.y = twistRad;
    }

    if (canvasEl) {
      canvasEl.style.opacity = String(phoneFade);
      canvasEl.style.transform = 'translate3d(' + parallaxX + 'vw, ' + (-sectionBlend * 2) + 'vh, 0) scale(' + scale + ')';
    }

    if (dataHost) {
      dataHost.classList.toggle('is-active', sectionBlend > 0.04);
      dataHost.style.opacity = String(sectionBlend);
      dataHost.setAttribute('aria-hidden', sectionBlend < 0.12 ? 'true' : 'false');
    }

    var phone = findSplineObject(app, [SPLINE_PHONE_NAME]);
    if (phone) phone.visible = sectionBlend < 0.92;

    if (typeof app.requestRender === 'function') app.requestRender();

    var twistBucket = Math.floor((twistNorm + 1) * 10);
    if (twistBucket !== prevTwistBucket) {
      emitSplineEvent(app, 'mouseDown', SPLINE_PHONE_NAME);
    }
    return twistBucket;
  }

  function easeInOutCubic(t) {
    t = Math.max(0, Math.min(1, t));
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  }

  function smoothstep(t) {
    t = Math.max(0, Math.min(1, t));
    return t * t * (3 - 2 * t);
  }

  function spring(current, target, delta, speed) {
    var k = 1 - Math.pow(0.001, delta * speed);
    return current + (target - current) * k;
  }

  function hexToRgb(hex) {
    var key = String(hex || '').toLowerCase();
    if (ACCENT_RGB[key]) return ACCENT_RGB[key].slice();
    var n = parseInt(String(hex || '#10b981').replace('#', ''), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
  }

  function rgbaFromRgb(rgb, a) {
    return 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + a + ')';
  }

  function lerpRgb(a, b, t) {
    return [
      Math.round(a[0] + (b[0] - a[0]) * t),
      Math.round(a[1] + (b[1] - a[1]) * t),
      Math.round(a[2] + (b[2] - a[2]) * t)
    ];
  }

  function sampleChapterBlend(scrollT) {
    var marks = CHAPTER_MARKS;
    var t = Math.max(0, Math.min(marks[marks.length - 1].t, scrollT));
    var i = 0;
    while (i < marks.length - 2 && t > marks[i + 1].t) i++;
    var a = marks[i];
    var b = marks[Math.min(i + 1, marks.length - 1)];
    var span = Math.max(0.0001, b.t - a.t);
    var local = easeInOutCubic((t - a.t) / span);
    return { chapterA: a.chapter, chapterB: b.chapter, chapterBlend: local };
  }

  function chapterIndexFromScroll(scrollT) {
    var marks = CHAPTER_MARKS;
    var t = Math.max(0, Math.min(marks[marks.length - 1].t, scrollT));
    var idx = 0;
    for (var i = 0; i < marks.length; i++) {
      if (t >= marks[i].t) idx = marks[i].chapter;
    }
    return idx;
  }

  function findScrollRoot(el) {
    var node = el;
    while (node && node !== document.body) {
      var style = window.getComputedStyle(node);
      if ((style.overflowY === 'auto' || style.overflowY === 'scroll') && node.scrollHeight > node.clientHeight + 8) {
        return node;
      }
      node = node.parentElement;
    }
    return window;
  }

  function scrollProgress(rail) {
    var spacer = rail.querySelector('.ftc-go-time-spline-scroll');
    if (!spacer) return 0;
    var clientH = window.innerHeight;
    var travel = Math.max(1, spacer.offsetHeight - clientH * 0.48);
    var scrolled = Math.max(0, -spacer.getBoundingClientRect().top);
    return Math.max(0, Math.min(1, scrolled / travel));
  }

  function ioRoot(scrollRoot) {
    return (scrollRoot && scrollRoot !== window) ? scrollRoot : null;
  }

  function stationIndexFromProgress(scaled, count) {
    var idx = Math.min(count - 2, Math.max(0, Math.floor(scaled)));
    return smoothstep(scaled - idx) >= 0.5 ? Math.min(count - 1, idx + 1) : idx;
  }

  function syncPortalLayoutVars(el) {
    var app = document.querySelector('[data-ftc-app]');
    if (!app || !el) return;
    var cs = getComputedStyle(app);
    ['--ftc-header-h', '--ftc-dock-h', '--ftc-gutter'].forEach(function (key) {
      var val = cs.getPropertyValue(key);
      if (val) el.style.setProperty(key, val.trim());
    });
  }

  function portalToBody(el) {
    if (!el || el.parentNode === document.body) return;
    document.body.appendChild(el);
    syncPortalLayoutVars(el);
  }

  function updateCopyPanels(activeIndex, copyRoot, inClosing) {
    if (!copyRoot) return;
    var panels = copyRoot.querySelectorAll('.ftc-go-time-spline-panel[data-station]');
    if (inClosing) {
      copyRoot.classList.add('is-closing-hidden', 'is-rail-hidden');
      panels.forEach(function (panel) {
        panel.classList.remove('is-active');
        panel.setAttribute('aria-hidden', 'true');
        panel.style.cssText = 'display:none;opacity:0;visibility:hidden;pointer-events:none';
      });
      return;
    }
    copyRoot.classList.remove('is-closing-hidden', 'is-rail-hidden');
    panels.forEach(function (panel) {
      var stationIdx = parseInt(panel.getAttribute('data-station'), 10) || 0;
      var isActive = stationIdx === activeIndex;
      panel.classList.toggle('is-active', isActive);
      panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      if (isActive) {
        panel.style.cssText = 'display:block;opacity:1;visibility:visible;pointer-events:auto';
      } else {
        panel.style.cssText = 'display:none;opacity:0;visibility:hidden;pointer-events:none';
      }
    });
  }

  function updateRailLayers(rail, progress, closingRatio, layers) {
    var fadeFromProgress = progress >= CLOSING_PROGRESS
      ? smoothstep((progress - CLOSING_PROGRESS) / (1 - CLOSING_PROGRESS)) : 0;
    var fadeFromClosing = closingRatio > 0 ? smoothstep(closingRatio) : 0;
    var fadeT = Math.max(fadeFromProgress, fadeFromClosing);
    var hidden = fadeT >= 0.98 || closingRatio >= 0.12 || progress >= 0.995;
    rail.classList.toggle('is-closing', hidden || fadeT > 0.08);
    rail.classList.toggle('is-closing-active', hidden);
    layers.forEach(function (layer) {
      if (!layer) return;
      if (hidden) {
        layer.classList.add('is-rail-hidden');
        layer.style.opacity = '0';
        layer.style.visibility = 'hidden';
      } else if (fadeT > 0.02) {
        layer.classList.remove('is-rail-hidden');
        layer.style.visibility = 'visible';
        layer.style.opacity = String(1 - fadeT);
      } else {
        layer.classList.remove('is-rail-hidden');
        layer.style.visibility = 'visible';
        layer.style.opacity = '1';
      }
    });
  }

  function splinePreviewUrls() {
    var data = window.ftcData || {};
    var urls = [];
    if (data.goTimeSplinePreviewUrl) urls.push(data.goTimeSplinePreviewUrl);
    urls.push(
      'https://app.spline.design/file/240a5bb3-0f6f-4231-b9f7-533d50207489?view=preview',
      'https://app.spline.design/community/file/7e14c4da-a727-45f6-8fbb-43a4039673ac?view=preview'
    );
    var seen = {};
    return urls.filter(function (u) { if (seen[u]) return false; seen[u] = true; return true; });
  }

  function isDesktopViewport() {
    return window.matchMedia('(min-width: 901px)').matches;
  }

  function sceneUrlCandidates() {
    var data = window.ftcData || {};
    var urls = [];
    var preview = data.goTimeSplineDesktopPreviewUrl || DESKTOP_PREVIEW_SCENE;
    if (isDesktopViewport() && preview) urls.push(preview);
    if (data.pluginUrl) urls.push(data.pluginUrl + 'assets/spline/go-time.splinecode');
    if (data.goTimeSplineUrl && urls.indexOf(data.goTimeSplineUrl) < 0) urls.push(data.goTimeSplineUrl);
    return urls;
  }

  function runtimeModuleUrl() {
    var data = window.ftcData || {};
    return data.splineRuntimeUrl || ('https://cdn.jsdelivr.net/npm/@splinetool/runtime@' + RUNTIME_VERSION + '/build/runtime.js');
  }

  function loadRuntime() {
    if (window.__ftcSplineRuntimePromise) return window.__ftcSplineRuntimePromise;
    var link = document.querySelector('link[data-ftc-spline-preload]');
    if (!link) {
      link = document.createElement('link');
      link.rel = 'modulepreload';
      link.href = runtimeModuleUrl();
      link.setAttribute('data-ftc-spline-preload', '1');
      document.head.appendChild(link);
    }
    window.__ftcSplineRuntimePromise = import(runtimeModuleUrl()).then(function (mod) {
      if (!mod || !mod.Application) throw new Error('Spline runtime missing Application');
      return mod;
    });
    return window.__ftcSplineRuntimePromise;
  }

  function fetchSceneBuffer(url) {
    return fetch(url, { cache: 'no-store' }).then(function (res) {
      if (!res.ok) throw new Error('Scene fetch failed: ' + res.status);
      return res.arrayBuffer();
    }).then(function (buf) {
      var bytes = new Uint8Array(buf);
      if (bytes.length > 3 && bytes[0] === 0xEF && bytes[1] === 0xBB && bytes[2] === 0xBF) {
        bytes = bytes.slice(3);
      }
      if (bytes.length > 14 && bytes[0] === 0x3C && bytes[1] === 0x21) {
        throw new Error('Scene URL returned HTML');
      }
      return bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength);
    });
  }

  function loadSceneFromBuffer(app, buffer) {
    var blob = new Blob([buffer], { type: 'application/octet-stream' });
    var blobUrl = URL.createObjectURL(blob);
    return app.load(blobUrl).finally(function () { URL.revokeObjectURL(blobUrl); });
  }

  function tryLoadScene(app, urls, index) {
    if (index >= urls.length) return Promise.reject(new Error('All scene URLs failed'));
    return fetchSceneBuffer(urls[index]).then(function (buffer) {
      return loadSceneFromBuffer(app, buffer);
    }).catch(function () { return tryLoadScene(app, urls, index + 1); });
  }

  function hideDemoUIMeshes(app) {
    if (!app) return;
    DEMO_UI_HIDE_NAMES.forEach(function (name) {
      try {
        var o = app.findObjectByName(name);
        if (o) o.visible = false;
      } catch (e) { /* optional */ }
    });
    if (app._scene && typeof app._scene.traverse === 'function') {
      app._scene.traverse(function (node) {
        if (DEMO_UI_HIDE_NAMES.indexOf(node.name) >= 0) node.visible = false;
      });
    }
  }

  function findScreenMesh(app) {
    var mesh = null;
    if (!app || !app._scene || typeof app._scene.traverse !== 'function') return null;
    app._scene.traverse(function (node) {
      if (node.isMesh && SCREEN_MESH_NAMES.indexOf(node.name) >= 0) mesh = node;
    });
    return mesh;
  }

  function getScreenProxy(app, meshName) {
    if (!app || typeof app.findObjectByName !== 'function') return null;
    try { return app.findObjectByName(meshName); } catch (e) { return null; }
  }

  function findScreenTextureLayer(proxy) {
    if (!proxy || !proxy.material || !proxy.material.layers) return null;
    var texLayer = null;
    proxy.material.layers.forEach(function (layer) {
      if (layer.type === 'texture' && typeof layer.updateTexture === 'function') {
        texLayer = layer;
      }
    });
    return texLayer;
  }

  function captureNativeScreenLayers(proxy) {
    if (!proxy || !proxy.material || !proxy.material.layers) return null;
    return proxy.material.layers.map(function (layer) {
      return {
        type: layer.type,
        visible: layer.visible,
        crop: layer.crop
      };
    });
  }

  function markScreenMaterialDirty(proxy) {
    if (proxy && proxy.material && typeof proxy.material.needsUpdate !== 'undefined') {
      proxy.material.needsUpdate = true;
    }
  }

  function restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot) {
    if (!proxy || !proxy.material || !proxy.material.layers) return;
    removeExistingOverlay(app);
    if (screenMesh) screenMesh.visible = true;
    if (nativeSnapshot && nativeSnapshot.length) {
      proxy.material.layers.forEach(function (layer, i) {
        var saved = nativeSnapshot[i];
        if (!saved) return;
        layer.visible = saved.visible;
        if (typeof saved.crop !== 'undefined') layer.crop = saved.crop;
      });
    } else {
      proxy.material.layers.forEach(function (layer) {
        layer.visible = true;
        if (layer.type === 'texture') layer.crop = false;
      });
    }
    markScreenMaterialDirty(proxy);
    if (app && typeof app.requestRender === 'function') app.requestRender();
  }

  function suppressBlueLayer(proxy) {
    if (!proxy || !proxy.material || !proxy.material.layers) return;
    proxy.material.layers.forEach(function (layer) {
      if (layer.type === 'color') {
        layer.visible = false;
      } else if (layer.type === 'texture') {
        layer.visible = true;
        layer.crop = false;
      } else if (layer.type === 'light') {
        layer.visible = true;
      }
    });
    markScreenMaterialDirty(proxy);
  }

  function activateCanvasScreenLayers(proxy) {
    suppressBlueLayer(proxy);
  }

  function removeExistingOverlay(app) {
    if (!app || !app._scene) return;
    var stale = null;
    app._scene.traverse(function (node) {
      if (node.name === SCREEN_OVERLAY_NAME) stale = node;
    });
    if (stale && stale.parent) stale.parent.remove(stale);
    if (stale && stale.geometry && typeof stale.geometry.dispose === 'function') {
      stale.geometry.dispose();
    }
  }

  function sampleCanvasPixel(ctx, cv) {
    var d = ctx.getImageData(Math.floor(cv.width / 2), Math.floor(cv.height / 2), 1, 1).data;
    return [d[0], d[1], d[2], d[3]];
  }

  function resolveGpuTexture(app, texLayer) {
    if (!app || !app._renderer || !texLayer || !texLayer.texture) return null;
    var tex = texLayer.texture;
    if (tex.isTexture) return tex;
    var props = app._renderer.properties;
    if (props && typeof props.get === 'function') {
      var entry = props.get(tex);
      if (entry && entry.isTexture) return entry;
    }
    return null;
  }

  function applyScreenCanvas(texLayer, cv) {
    if (!texLayer || !cv) return;
    try {
      if (texLayer.texture) {
        texLayer.texture.image = cv;
        texLayer.texture.needsUpdate = true;
        return;
      }
    } catch (e) { /* texture proxy may throw */ }
    try { texLayer.updateTexture(cv); } catch (e2) { /* optional */ }
  }

  function primeScreenCanvas(texLayer, cv) {
    applyScreenCanvas(texLayer, cv);
    if (!texLayer || typeof texLayer.updateTexture !== 'function') return;
    try {
      var pending = texLayer.updateTexture(cv);
      if (pending && typeof pending.then === 'function') {
        pending.catch(function () { applyScreenCanvas(texLayer, cv); });
      }
    } catch (e) { /* optional */ }
  }

  function publishCanvasTexture(app, texLayer, cv, gpuTex) {
    if (gpuTex && gpuTex.isTexture) {
      if (gpuTex.image !== cv) gpuTex.image = cv;
      gpuTex.needsUpdate = true;
      return gpuTex;
    }
    applyScreenCanvas(texLayer, cv);
    return resolveGpuTexture(app, texLayer) || gpuTex;
  }

  function promiseWithTimeout(promise, ms) {
    return Promise.race([
      Promise.resolve(promise),
      new Promise(function (resolve) {
        setTimeout(function () { resolve({ __ftcTimedOut: true }); }, ms);
      })
    ]);
  }

  function primeTextureLayer(texLayer, cv) {
    return promiseWithTimeout(texLayer.updateTexture(cv.toDataURL('image/png')), 5000);
  }

  function webDevScrollPhase(scrollT) {
    if (scrollT < ENTRANCE_SCROLL) return 0;
    if (scrollT >= WEB_DEV_SCROLL_END) return 1;
    return easeInOutCubic((scrollT - ENTRANCE_SCROLL) / (WEB_DEV_SCROLL_END - ENTRANCE_SCROLL));
  }

  function makeScreenDrawer(screens) {
    var cv = document.createElement('canvas');
    cv.width = Math.round(SCREEN_W * 2);
    cv.height = Math.round(SCREEN_H * 2);
    var ctx = cv.getContext('2d');
    ctx.scale(2, 2);
    var animTime = 0;
    var lastChapterA = -1;
    var dataEntranceT = 1;

    function drawFrame(scrollT, delta) {
      animTime += delta;
      var sample = sampleChapterBlend(scrollT);
      var idA = STATION_IDS[sample.chapterA] || STATION_IDS[0];
      var idB = STATION_IDS[sample.chapterB] || idA;
      var accentA = CHAPTER_ACCENTS[sample.chapterA] || CHAPTER_ACCENTS[0];
      var accentB = CHAPTER_ACCENTS[sample.chapterB] || accentA;
      var chapterBlend = sample.chapterBlend;
      if (sample.chapterA !== lastChapterA) {
        if (idA === 'data') dataEntranceT = 0;
        lastChapterA = sample.chapterA;
      }
      if (idA === 'data') {
        dataEntranceT = Math.min(1, dataEntranceT + delta * 0.55);
      } else {
        dataEntranceT = 1;
      }
      var webScroll = (idA === 'websites' || (chapterBlend > 0 && idB === 'websites' && sample.chapterA === 0))
        ? webDevScrollPhase(scrollT) : 0;
      var entrance = idA === 'data' ? dataEntranceT : 1;

      ctx.fillStyle = '#08080b';
      ctx.fillRect(0, 0, SCREEN_W, SCREEN_H);
      if (screens.blend) {
        screens.blend(ctx, SCREEN_W, SCREEN_H, idA, idB, chapterBlend, animTime, accentA, accentB, webScroll, entrance);
      } else {
        screens.draw(ctx, SCREEN_W, SCREEN_H, idA, animTime, accentA, webScroll, entrance);
      }
    }

    return { cv: cv, ctx: ctx, drawFrame: drawFrame };
  }

  function primeOverlayTexture(app, texLayer, cv) {
    applyScreenCanvas(texLayer, cv);
    var gpuTex = resolveGpuTexture(app, texLayer);
    if (gpuTex) {
      if (gpuTex.image !== cv) gpuTex.image = cv;
      gpuTex.needsUpdate = true;
      return Promise.resolve(gpuTex);
    }
    return promiseWithTimeout(texLayer.updateTexture(cv.toDataURL('image/png')), 5000).then(function () {
      return resolveGpuTexture(app, texLayer);
    });
  }

  function bindScreenOverlayPlane(app, screenMesh, texLayer, drawer) {
    removeExistingOverlay(app);

    drawer.drawFrame(0, 0);
    var primedPixel = sampleCanvasPixel(drawer.ctx, drawer.cv);
    var gpuTex = null;

    return primeOverlayTexture(app, texLayer, drawer.cv).then(function (primeGpuTex) {
      gpuTex = primeGpuTex || resolveGpuTexture(app, texLayer);
      if (!gpuTex) {
        gpuTex = publishCanvasTexture(app, texLayer, drawer.cv, gpuTex);
      }

      var MeshCtor = screenMesh.constructor;
      var overlay = new MeshCtor(screenMesh.geometry.clone(), screenMesh.material);
      overlay.material = screenMesh.material;
      overlay.name = SCREEN_OVERLAY_NAME;
      overlay.renderOrder = (screenMesh.renderOrder || 0) + 2;
      overlay.frustumCulled = false;

      function syncOverlayTransform() {
        overlay.position.copy(screenMesh.position);
        overlay.quaternion.copy(screenMesh.quaternion);
        overlay.scale.copy(screenMesh.scale);
        overlay.position.z += 0.0002;
      }

      syncOverlayTransform();
      if (screenMesh.parent) screenMesh.parent.add(overlay);
      else app._scene.add(overlay);

      screenMesh.visible = false;
      overlay.visible = true;

      if (typeof app.requestRender === 'function') app.requestRender();

      console.info('[FTCGoTimeSpline] Attempt C overlay plane bound', {
        bindPath: gpuTex ? 'overlay.NodeMaterial.gpuTex.needsUpdate' : 'overlay.NodeMaterial.updateTexture(dataURL-once)',
        mesh: screenMesh.name,
        primedPixel: primedPixel,
        overlayMat: overlay.material && overlay.material.type,
        sameMaterial: overlay.material === screenMesh.material
      });

      window.__ftcGoTimeScreenMesh = screenMesh.name;
      window.__ftcGoTimeScreenPlane = overlay;
      window.__ftcGoTimeScreenBind = 'overlay-plane-C';

      return {
        meshName: screenMesh.name,
        bindAttempt: 'C',
        tick: function (delta, scrollT) {
          syncOverlayTransform();
          drawer.drawFrame(scrollT, delta);
          gpuTex = publishCanvasTexture(app, texLayer, drawer.cv, gpuTex) || gpuTex;
          if (typeof app.requestRender === 'function') app.requestRender();
        }
      };
    }).catch(function (err) {
      console.error('[FTCGoTimeSpline] overlay plane bind failed:', err);
      screenMesh.visible = true;
      return null;
    });
  }

  function bindScreenDualMode(app, screenMesh, proxy, texLayer, drawer, nativeSnapshot) {
    screenMesh.visible = true;
    removeExistingOverlay(app);
    restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);

    var canvasMode = false;
    var canvasPrimed = null;
    var gpuTex = null;
    var lastChapter = WEB_DEV_CHAPTER;

    function publishCanvas(scrollT, delta) {
      drawer.drawFrame(scrollT, delta);
      gpuTex = publishCanvasTexture(app, texLayer, drawer.cv, gpuTex) || gpuTex;
      if (typeof app.requestRender === 'function') app.requestRender();
    }

    function ensureCanvasMode(scrollT, delta) {
      if (!canvasMode) {
        activateCanvasScreenLayers(proxy);
        canvasMode = true;
        window.__ftcGoTimeScreenBind = 'screen-node-material';
      }
      if (!canvasPrimed) {
        drawer.drawFrame(scrollT, delta);
        canvasPrimed = primeTextureLayer(texLayer, drawer.cv).then(function () {
          gpuTex = resolveGpuTexture(app, texLayer);
          applyScreenCanvas(texLayer, drawer.cv);
          publishCanvas(scrollT, delta);
        }).catch(function (err) {
          console.warn('[FTCGoTimeSpline] canvas prime failed, using fallback:', err);
          primeScreenCanvas(texLayer, drawer.cv);
          gpuTex = resolveGpuTexture(app, texLayer);
          publishCanvas(scrollT, delta);
        });
        return;
      }
      publishCanvas(scrollT, delta);
    }

    console.info('[FTCGoTimeSpline] Screen dual-mode bound', {
      mesh: screenMesh.name,
      webDev: 'native-video',
      chapters1Plus: 'canvas-texture'
    });

    window.__ftcGoTimeScreenMesh = screenMesh.name;
    window.__ftcGoTimeScreenPlane = null;
    window.__ftcGoTimeScreenBind = 'native-video-ch0';

    return {
      meshName: screenMesh.name,
      bindAttempt: 'screen-dual-mode',
      tick: function (delta, scrollT) {
        var chapterIdx = chapterIndexFromScroll(scrollT);
        var blend = sectionBlendFromScroll(scrollT);
        if (chapterIdx === WEB_DEV_CHAPTER && blend < 0.08) {
          if (canvasMode || lastChapter !== WEB_DEV_CHAPTER) {
            restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);
            canvasMode = false;
            canvasPrimed = null;
            gpuTex = null;
            window.__ftcGoTimeScreenBind = 'native-video-ch0';
          }
          lastChapter = chapterIdx;
          return;
        }
        if (canvasMode) {
          restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);
          canvasMode = false;
          canvasPrimed = null;
          gpuTex = null;
        }
        lastChapter = chapterIdx;
      }
    };
  }

  function bootScreenTexture(app) {
    var screens = window.FTCGoTimeScreens;
    if (!app) return Promise.resolve(null);

    var screenMesh = findScreenMesh(app);
    if (!screenMesh) {
      console.warn('[FTCGoTimeSpline] Screen mesh not found');
      return Promise.resolve(null);
    }

    hideDemoUIMeshes(app);

    var proxy = getScreenProxy(app, screenMesh.name) || screenMesh;
    var nativeSnapshot = captureNativeScreenLayers(proxy);
    restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);

    if (!screens || !screens.draw) {
      console.warn('[FTCGoTimeSpline] FTCGoTimeScreens unavailable — Web Dev native only');
      window.__ftcGoTimeScreenMesh = screenMesh.name;
      window.__ftcGoTimeScreenPlane = null;
      window.__ftcGoTimeScreenBind = 'native-video-ch0';
      return Promise.resolve({
        meshName: screenMesh.name,
        bindAttempt: 'native-only',
        tick: function (delta, scrollT) {
          if (chapterIndexFromScroll(scrollT) === WEB_DEV_CHAPTER) {
            restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);
          }
        }
      });
    }

    var texLayer = findScreenTextureLayer(proxy);
    var drawer = makeScreenDrawer(screens);

    if (!texLayer || typeof texLayer.updateTexture !== 'function') {
      console.warn('[FTCGoTimeSpline] Screen texture layer not found — Web Dev native only');
      window.__ftcGoTimeScreenMesh = screenMesh.name;
      window.__ftcGoTimeScreenPlane = null;
      window.__ftcGoTimeScreenBind = 'native-video-ch0';
      return Promise.resolve({
        meshName: screenMesh.name,
        bindAttempt: 'native-only',
        tick: function (delta, scrollT) {
          if (chapterIndexFromScroll(scrollT) === WEB_DEV_CHAPTER) {
            restoreNativeScreen(app, screenMesh, proxy, nativeSnapshot);
          }
        }
      });
    }

    return Promise.resolve(bindScreenDualMode(app, screenMesh, proxy, texLayer, drawer, nativeSnapshot));
  }

  function emitSplineEvent(app, type, target) {
    try { app.emitEvent(type, target); } catch (e) { /* optional */ }
  }

  function applyScrollSpline(app, scrollT, prevChapter, prevEntrance, sectionBlend) {
    if (!app) return;
    var chapterIdx = chapterIndexFromScroll(scrollT);
    var chapterId = STATION_IDS[chapterIdx] || STATION_IDS[0];
    var entranceActive = scrollT >= ENTRANCE_SCROLL;
    var twistNorm = phoneTwistFromScroll(scrollT) / TWIST_RANGE_RAD;
    try {
      app.setVariables({
        chapterIndex: chapterIdx,
        chapterId: chapterId,
        scrollProgress: scrollT,
        entranceActive: entranceActive ? 1 : 0,
        sectionBlend: sectionBlend,
        phoneTwist: twistNorm,
        phoneTwistRad: phoneTwistFromScroll(scrollT),
        dataVisible: sectionBlend > 0.5 ? 1 : 0
      });
    } catch (e) { /* optional */ }
    if (entranceActive && !prevEntrance) {
      emitSplineEvent(app, 'start', SPLINE_SCREEN_EVENT_NAME);
      emitSplineEvent(app, 'mouseDown', SPLINE_PHONE_NAME);
    }
    if (chapterIdx === prevChapter) return;
    if (prevChapter >= 0) {
      emitSplineEvent(app, 'mouseDown', SPLINE_PHONE_NAME);
    }
  }

  function bootSplineRuntime(canvasEl, onProgress) {
    var urls = sceneUrlCandidates();
    if (!urls.length) return Promise.reject(new Error('No scene URLs'));
    if (onProgress) onProgress(22, 'Loading 3D engine…');
    return loadRuntime().then(function (mod) {
      if (onProgress) onProgress(42, 'Building studio…');
      var app = new mod.Application(canvasEl);
      return tryLoadScene(app, urls, 0).then(function () {
        if (onProgress) onProgress(72, 'Polishing scene…');
        applyScrollSpline(app, 0, -1, false, 0);
        window.__ftcSplineApp = app;
        if (onProgress) onProgress(80, 'Preparing screen layers…');
        return bootScreenTexture(app).then(function (screenTexture) {
          if (onProgress) onProgress(86, 'Rendering…');
          return { app: app, loadedAt: performance.now(), screenTexture: screenTexture };
        });
      });
    });
  }

  function bootSplineIframe(viewportEl, onProgress, urlIndex) {
    urlIndex = urlIndex || 0;
    var candidates = splinePreviewUrls();
    if (urlIndex >= candidates.length) return Promise.reject(new Error('All iframe URLs failed'));
    var iframe = viewportEl.querySelector('.ftc-go-time-spline-iframe');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.className = 'ftc-go-time-spline-iframe';
      iframe.title = 'Go Time studio scene';
      iframe.setAttribute('loading', 'eager');
      iframe.setAttribute('tabindex', '-1');
      iframe.setAttribute('allow', 'fullscreen; autoplay; xr-spatial-tracking');
      viewportEl.appendChild(iframe);
    }
    var canvasEl = viewportEl.querySelector('.ftc-go-time-spline-canvas');
    if (canvasEl) canvasEl.style.display = 'none';
    if (onProgress) onProgress(55 + urlIndex * 8, 'Loading preview…');
    var url = candidates[urlIndex];
    return new Promise(function (resolve, reject) {
      var settled = false;
      function finish(ok) {
        if (settled) return;
        settled = true;
        if (ok) {
          viewportEl.classList.add('is-iframe-ready');
          resolve(iframe);
        } else if (urlIndex + 1 < candidates.length) {
          bootSplineIframe(viewportEl, onProgress, urlIndex + 1).then(resolve).catch(reject);
        } else {
          reject(new Error('Iframe failed'));
        }
      }
      iframe.onload = function () { finish(true); };
      iframe.onerror = function () { finish(false); };
      iframe.src = url;
      setTimeout(function () { finish(true); }, IFRAME_TIMEOUT_MS);
    });
  }

  function bootAtmosphere(rail, redMot) {
    var wrap = rail.querySelector('.ftc-go-time-spline-atmosphere');
    if (!wrap) return null;

    if (!wrap.querySelector('.ftc-go-time-atmosphere__base')) {
      wrap.innerHTML =
        '<div class="ftc-go-time-atmosphere__base"></div>' +
        '<div class="ftc-go-time-atmosphere__glow ftc-go-time-atmosphere__glow--a" data-parallax="0.05"></div>' +
        '<div class="ftc-go-time-atmosphere__glow ftc-go-time-atmosphere__glow--b" data-parallax="0.08"></div>' +
        '<div class="ftc-go-time-atmosphere__glow ftc-go-time-atmosphere__glow--c" data-parallax="0.03"></div>' +
        '<div class="ftc-go-time-atmosphere__rays"></div>' +
        '<canvas class="ftc-go-time-atmosphere__particles"></canvas>' +
        '<div class="ftc-go-time-atmosphere__horizon"></div>' +
        '<div class="ftc-go-time-atmosphere__vignette"></div>' +
        '<div class="ftc-go-time-atmosphere__grain"></div>';
    }
    wrap.classList.add('is-active');

    var canvas = wrap.querySelector('.ftc-go-time-atmosphere__particles');
    var ctx = canvas && canvas.getContext ? canvas.getContext('2d') : null;
    var glowLayers = wrap.querySelectorAll('[data-parallax]');
    var accentRgb = hexToRgb('#10b981');
    var accentTarget = accentRgb.slice();
    var scrollY = 0;
    var scrollTarget = 0;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var particleCount = redMot ? 0 : 180;
    var particles = [];

    function resize() {
      var w = Math.max(1, window.innerWidth);
      var h = Math.max(1, window.innerHeight);
      if (!ctx) return;
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      particles = [];
      for (var i = 0; i < particleCount; i++) {
        particles.push({
          x: Math.random() * w,
          y: Math.random() * h,
          z: 0.2 + Math.random() * 0.8,
          r: 0.5 + Math.random() * 1.8,
          vx: (-0.06 + Math.random() * 0.12) * 0.12,
          vy: (-0.03 + Math.random() * 0.06) * 0.12,
          tw: Math.random() * Math.PI * 2,
          alpha: 0.08 + Math.random() * 0.28
        });
      }
    }

    function applyAccent(rgb) {
      wrap.style.setProperty('--ftc-atmo-accent-a', rgbaFromRgb(rgb, 0.32));
      wrap.style.setProperty('--ftc-atmo-accent-b', rgbaFromRgb(lerpRgb(rgb, [139, 92, 246], 0.28), 0.22));
      wrap.style.setProperty('--ftc-atmo-accent-c', rgbaFromRgb(lerpRgb(rgb, [59, 130, 246], 0.22), 0.16));
    }

    function drawParticles() {
      if (!ctx || !particleCount || redMot) return;
      var w = canvas.width / dpr;
      var h = canvas.height / dpr;
      ctx.clearRect(0, 0, w, h);
      particles.forEach(function (p) {
        p.x += p.vx * p.z;
        p.y += p.vy * p.z - 0.012 * p.z;
        p.tw += 0.01 * p.z;
        if (p.x < -8) p.x = w + 8;
        if (p.x > w + 8) p.x = -8;
        if (p.y < -8) p.y = h + 8;
        if (p.y > h + 8) p.y = -8;
        var a = p.alpha * (0.5 + Math.sin(p.tw) * 0.5) * (0.35 + p.z * 0.65);
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r * p.z, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(245,242,238,' + a + ')';
        ctx.fill();
      });
    }

    resize();
    applyAccent(accentRgb);
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(resize).observe(document.documentElement);

    return {
      setAccent: function (hex) { accentTarget = hexToRgb(hex); },
      tick: function (delta, progress) {
        scrollTarget = progress * window.innerHeight * 3;
        scrollY = spring(scrollY, scrollTarget, delta, redMot ? 18 : 6);
        accentRgb = lerpRgb(accentRgb, accentTarget, redMot ? 1 : 0.06);
        applyAccent(accentRgb);
        if (!redMot) {
          var parallax = scrollY * 0.06;
          glowLayers.forEach(function (layer) {
            var factor = parseFloat(layer.getAttribute('data-parallax') || '0.05');
            layer.style.transform = 'translate3d(0,' + (-parallax * factor) + 'px,0)';
          });
          drawParticles();
        }
      }
    };
  }

  function ensureLoader(rail) {
    var loader = rail.querySelector('[data-ftc-go-time-loader]');
    if (loader) return loader;
    loader = document.createElement('div');
    loader.className = 'ftc-go-time-loader';
    loader.setAttribute('data-ftc-go-time-loader', '');
    loader.setAttribute('role', 'status');
    loader.setAttribute('aria-live', 'polite');
    loader.setAttribute('aria-busy', 'true');
    loader.setAttribute('aria-label', 'Loading Go Time experience');
    loader.innerHTML =
      '<div class="ftc-go-time-loader__inner">' +
        '<div class="ftc-go-time-loader__mark" aria-hidden="true"><span class="ftc-go-time-loader__ring"></span></div>' +
        '<p class="ftc-go-time-loader__title">Go Time</p>' +
        '<div class="ftc-go-time-loader__track" aria-hidden="true"><span class="ftc-go-time-loader__bar" data-ftc-go-time-loader-progress style="width:0%"></span></div>' +
        '<p class="ftc-go-time-loader__status" data-ftc-go-time-loader-status>Initializing…</p>' +
      '</div>';
    rail.insertBefore(loader, rail.firstChild);
    return loader;
  }

  function createLoaderController(loader, rail) {
    var startedAt = performance.now();
    var progress = 0;
    var revealed = false;
    var statusEl = loader.querySelector('[data-ftc-go-time-loader-status]');
    var barEl = loader.querySelector('[data-ftc-go-time-loader-progress]');

    function setProgress(value, label) {
      progress = Math.max(progress, Math.min(100, value));
      if (barEl) barEl.style.width = progress + '%';
      if (statusEl && label) statusEl.textContent = label;
    }

    function reveal(options) {
      options = options || {};
      if (revealed) return Promise.resolve();
      revealed = true;
      var wait = Math.max(0, LOADER_MIN_MS - (performance.now() - startedAt));
      return new Promise(function (resolve) {
        setTimeout(function () {
          setProgress(100, options.label || 'Ready');
          loader.classList.add('is-exiting');
          rail.classList.remove('is-loading', 'ftc-go-time-spline--boot');
          rail.classList.add('is-ready');
          if (options.mode === 'iframe') rail.classList.add('ftc-go-time-spline--iframe');
          if (options.mode === 'runtime' && !options.failed) rail.classList.add('is-spline-runtime');
          if (options.failed) rail.classList.add('ftc-go-time-spline--error');
          setTimeout(function () {
            loader.classList.add('is-hidden');
            loader.setAttribute('aria-busy', 'false');
            if (loader.parentNode) loader.parentNode.removeChild(loader);
            resolve();
          }, LOADER_FADE_MS);
        }, wait);
      });
    }

    return { setProgress: setProgress, reveal: reveal };
  }

  function bootRail(rail) {
    if (rail._ftcSplineBooted) return false;

    var viewportEl = rail.querySelector('.ftc-go-time-spline-viewport');
    var atmosphereEl = rail.querySelector('.ftc-go-time-spline-atmosphere');
    var copyEl = rail.querySelector('.ftc-go-time-spline-copy');
    var canvasEl = viewportEl && viewportEl.querySelector('.ftc-go-time-spline-canvas');
    if (!viewportEl || !canvasEl) return false;

    portalToBody(viewportEl);
    portalToBody(atmosphereEl);
    portalToBody(copyEl);

    var loaderEl = ensureLoader(rail);
    portalToBody(loaderEl);
    rail.classList.add('is-loading', 'ftc-go-time-spline--boot');
    var loader = createLoaderController(loaderEl, rail);

    rail._ftcSplineViewport = viewportEl;
    rail._ftcSplineCopyEl = copyEl;

    var scrollRoot = findScrollRoot(rail);
    var redMot = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
    var atmosphere = bootAtmosphere(rail, redMot);
    var closingEl = rail.querySelector('.ftc-go-time-spline-closing');
    var railLayers = [viewportEl, atmosphereEl].filter(Boolean);
    var accents = [];
    rail.querySelectorAll('.ftc-go-time-spline-copy [data-station]').forEach(function (art) {
      accents.push(art.getAttribute('data-accent') || CHAPTER_ACCENTS[accents.length] || '#10b981');
    });

    var state = {
      scrollT: 0, scrollSmooth: 0, closingRatio: 0, activeIdx: 0, activeChapter: -1,
      entranceActive: false, ready: false, mode: 'pending', spline: null, screenTexture: null,
      loadedAt: 0, dataScene: null, sectionBlend: 0, twistBucket: -1
    };
    var dataHost = ensureDataVisualHost(viewportEl);
    updateCopyPanels(0, copyEl, false);

    if (typeof IntersectionObserver !== 'undefined' && closingEl) {
      new IntersectionObserver(function (entries) {
        var entry = entries[0];
        if (!entry) return;
        state.closingRatio = entry.isIntersecting ? Math.max(0, Math.min(1, entry.intersectionRatio)) : 0;
      }, { threshold: [0, 0.05, 0.12, 0.25, 0.5, 0.75, 1], root: ioRoot(scrollRoot) }).observe(closingEl);
    }

    loader.setProgress(10, 'Initializing…');

    function withTimeout(promise, ms) {
      return Promise.race([
        promise,
        new Promise(function (resolve) {
          setTimeout(function () { resolve({ __ftcTimedOut: true }); }, ms);
        })
      ]).then(function (res) {
        if (res && res.__ftcTimedOut) throw new Error('Timed out');
        return res;
      });
    }

    withTimeout(bootSplineRuntime(canvasEl, loader.setProgress), RUNTIME_TIMEOUT_MS).then(function (bundle) {
      state.spline = bundle.app;
      state.screenTexture = bundle.screenTexture || null;
      state.loadedAt = bundle.loadedAt || performance.now();
      state.mode = 'runtime';
      state.ready = true;
      loader.setProgress(94, 'Scene ready…');
      return new Promise(function (r) { requestAnimationFrame(function () { requestAnimationFrame(r); }); });
    }).catch(function (err) {
      console.error('[FTCGoTimeSpline] runtime failed:', err);
      state.mode = 'runtime';
      if (window.__ftcSplineApp) {
        state.spline = window.__ftcSplineApp;
        state.ready = true;
      } else {
        state.ready = false;
      }
    }).then(function () {
      return loader.reveal({ failed: !state.ready, label: state.ready ? 'Ready' : 'Scene unavailable', mode: state.mode });
    }).then(function () {
      if (state.ready && viewportEl) {
        state.dataScene = bootDataLayer(viewportEl, scrollRoot, redMot, function () {
          return state.scrollSmooth;
        });
      }
      rail._ftcSplineBooted = true;
      markGoTimeAppMode(rail);
      window.dispatchEvent(new CustomEvent('ftc-go-time-spline-ready', {
        detail: { root: rail, mode: state.mode, failed: !state.ready }
      }));
    });

    function tick(delta) {
      if (document.hidden) return;

      var target = scrollProgress(rail);
      state.scrollT = spring(state.scrollT, target, delta, redMot ? 22 : 7);
      state.scrollSmooth = spring(state.scrollSmooth, target, delta, redMot ? 18 : 5.5);

      var activeIdx = chapterIndexFromScroll(state.scrollSmooth);
      var accentHex = accents[Math.min(activeIdx, accents.length - 1)] || CHAPTER_ACCENTS[activeIdx];
      var sectionBlend = sectionBlendFromScroll(state.scrollSmooth);
      state.sectionBlend = sectionBlend;

      rail.classList.toggle('is-data-active', sectionBlend > 0.12);
      rail.style.setProperty('--ftc-section-blend', String(sectionBlend));

      if (state.spline && state.mode === 'runtime') {
        var entranceNow = state.scrollSmooth >= ENTRANCE_SCROLL;
        applyScrollSpline(state.spline, state.scrollSmooth, state.activeChapter, state.entranceActive, sectionBlend);
        state.entranceActive = entranceNow;
        if (activeIdx !== state.activeChapter) state.activeChapter = activeIdx;
        state.twistBucket = applyPhoneMotion(
          state.spline, canvasEl, dataHost && dataHost.visualEl,
          state.scrollSmooth, sectionBlend, state.twistBucket
        );
      }

      if (state.dataScene && state.ready) {
        state.dataScene.tick(delta);
      }

      if (state.screenTexture && state.ready) {
        state.screenTexture.tick(delta, state.scrollSmooth);
      }

      if (atmosphere) {
        atmosphere.setAccent(accentHex);
        atmosphere.tick(delta, state.scrollSmooth);
      }

      var inClosing = target >= CLOSING_PROGRESS || state.closingRatio >= 0.12;

      if (activeIdx !== state.activeIdx) state.activeIdx = activeIdx;
      updateCopyPanels(activeIdx, copyEl, inClosing);
      updateRailLayers(rail, target, state.closingRatio, railLayers);
    }

    rail._ftcSplineTick = tick;
    return true;
  }

  function startLoop(rail) {
    if (rail._ftcSplineLoopStarted) return;
    rail._ftcSplineLoopStarted = true;
    rail._ftcSplineActive = true;
    if (typeof IntersectionObserver !== 'undefined') {
      var visObserver = new IntersectionObserver(function (entries) {
        if (!entries || !entries.length) return;
        rail._ftcSplineActive = !!entries[0].isIntersecting;
      }, { threshold: 0.04 });
      visObserver.observe(rail);
      rail._ftcSplineVisObserver = visObserver;
    }
    var last = performance.now();
    (function loop() {
      requestAnimationFrame(loop);
      if (!rail._ftcSplineTick) return;
      if (document.hidden || rail._ftcSplineActive === false) {
        last = performance.now();
        return;
      }
      var now = performance.now();
      rail._ftcSplineTick(Math.min((now - last) / 1000, 0.05));
      last = now;
    })();
  }

  function init(rail) {
    if (!rail || rail.getAttribute('data-ftc-go-time-spline-init')) return;
    rail.setAttribute('data-ftc-go-time-spline-init', 'pending');
    if (bootRail(rail)) startLoop(rail);
    rail.setAttribute('data-ftc-go-time-spline-init', '1');
  }

  function scan(root) {
    (root || document).querySelectorAll('[data-ftc-go-time-spline]:not([data-ftc-go-time-spline-init])').forEach(init);
  }

  function markGoTimeAppMode(railEl) {
    var app = document.querySelector('[data-ftc-app]');
    if (app) app.classList.add('ftc-app-is-go-time');
    var msg = railEl && railEl.closest('.ftc-message');
    if (msg) {
      msg.classList.add('has-layout-go-time', 'ftc-go-time-fullpage');
    }
  }

  function introPreviewSceneUrl() {
    var data = window.ftcData || {};
    return data.goTimeSplineDesktopPreviewUrl || DESKTOP_PREVIEW_SCENE;
  }

  function waitForPaint(frames) {
    frames = frames || 2;
    return new Promise(function (resolve) {
      function step(n) {
        if (n <= 0) return resolve();
        requestAnimationFrame(function () { step(n - 1); });
      }
      step(frames);
    });
  }

  function waitForIntroTypingDone() {
    return new Promise(function (resolve) {
      var body = document.querySelector('[data-ftc-intro-body]');
      if (!body || body.classList.contains('is-complete')) {
        resolve();
        return;
      }
      var done = false;
      function finish() {
        if (done) return;
        done = true;
        observer.disconnect();
        clearTimeout(fallback);
        resolve();
      }
      var observer = new MutationObserver(function () {
        if (body.classList.contains('is-complete')) finish();
      });
      observer.observe(body, { attributes: true, attributeFilter: ['class'] });
      var fallback = setTimeout(finish, 7000);
    });
  }

  function scheduleIntroSplineWork(fn) {
    var run = function () {
      try { fn(); } catch (e) { console.warn('[FTCIntroSpline] boot failed:', e); }
    };
    if (window.requestIdleCallback) {
      window.requestIdleCallback(run, { timeout: 2500 });
    } else {
      setTimeout(run, 48);
    }
  }

  function pauseIntroSplineApp(app) {
    if (app && typeof app.stop === 'function') {
      try { app.stop(); } catch (e) { /* optional */ }
    }
  }

  function playIntroSplineApp(app) {
    if (app && typeof app.play === 'function') {
      try { app.play(); } catch (e) { /* optional */ }
    } else if (app && typeof app.requestRender === 'function') {
      app.requestRender();
    }
  }

  function revealIntroSpline(wrap, app) {
    playIntroSplineApp(app);
    return waitForPaint(2).then(function () {
      return new Promise(function (resolve) {
        requestAnimationFrame(function () {
          wrap.classList.remove('is-loading');
          wrap.classList.add('is-ready');
          resolve();
        });
      });
    });
  }

  function bootIntroSplineScene(wrap, canvas, sceneUrl) {
    var redMot = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
    var revealed = false;

    function finishReveal(app) {
      if (revealed) return Promise.resolve();
      revealed = true;
      if (redMot) {
        wrap.classList.remove('is-loading');
        wrap.classList.add('is-ready');
        playIntroSplineApp(app);
        wrap.setAttribute('data-ftc-intro-spline-init', '1');
        return Promise.resolve();
      }
      return revealIntroSpline(wrap, app).then(function () {
        wrap.setAttribute('data-ftc-intro-spline-init', '1');
      });
    }

    var fallbackTimer = setTimeout(function () {
      finishReveal(window.__ftcIntroSplineApp || null);
    }, 12000);

    loadRuntime().then(function (mod) {
      var app = new mod.Application(canvas);
      return tryLoadScene(app, [sceneUrl], 0).then(function () {
        window.__ftcIntroSplineApp = app;
        pauseIntroSplineApp(app);
        clearTimeout(fallbackTimer);
        return finishReveal(app);
      });
    }).catch(function (err) {
      clearTimeout(fallbackTimer);
      console.warn('[FTCIntroSpline] desktop preview failed:', err);
      wrap.classList.remove('is-loading');
      wrap.classList.add('is-error');
      wrap.setAttribute('data-ftc-intro-spline-init', 'error');
    });
  }

  function initIntroSplinePreview() {
    /* Intro Spline preview removed — static dark-blue background only. */
  }

  window.FTCGoTimeSpline = {
    init: init,
    scan: scan,
    markGoTimeAppMode: markGoTimeAppMode,
    initIntroSplinePreview: initIntroSplinePreview,
    version: VERSION
  };
})();
