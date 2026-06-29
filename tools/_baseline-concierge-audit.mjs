import fs from "fs";
import path from "path";
import { chromium, devices } from "playwright";

const BASE_URL = process.env.FTC_BASE_URL || "http://ftl-2026.local";
const OUT_DIR = "C:/Users/jamie/Anna1/field-theory-concierge-2026/docs/optimization-baseline";
const ROUTES = [
  { key: "home", path: "/" },
  { key: "get-started", path: "/get-started/" },
  { key: "go-time", path: "/go-time/" },
  { key: "services", path: "/services/" }
];

if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

function slug(name) {
  return name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
}

async function captureRoute(browser, profile, contextOptions, route) {
  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  const url = new URL(route.path, BASE_URL).toString();
  const startedAt = Date.now();

  await page.goto(url, { waitUntil: "domcontentloaded", timeout: 180000 });
  await page.waitForTimeout(7000);

  const shotName = `${slug(route.key)}-${slug(profile)}.png`;
  const screenshotPath = path.join(OUT_DIR, shotName);
  await page.screenshot({ path: screenshotPath, fullPage: false });

  const metrics = await page.evaluate(() => {
    const nav = performance.getEntriesByType("navigation")[0];
    const paints = performance.getEntriesByType("paint");
    const resources = performance.getEntriesByType("resource");
    const scripts = resources.filter((r) => r.initiatorType === "script");
    const css = resources.filter((r) => r.initiatorType === "link" || /\.css(?:\?|$)/i.test(r.name));

    const bytes = resources.reduce((sum, r) => sum + (r.transferSize || 0), 0);
    const scriptBytes = scripts.reduce((sum, r) => sum + (r.transferSize || 0), 0);
    const cssBytes = css.reduce((sum, r) => sum + (r.transferSize || 0), 0);

    const clsEntry = performance.getEntriesByType("layout-shift")
      .filter((entry) => !entry.hadRecentInput)
      .reduce((sum, entry) => sum + entry.value, 0);

    return {
      timing: {
        domContentLoadedMs: nav ? Math.round(nav.domContentLoadedEventEnd) : null,
        loadMs: nav ? Math.round(nav.loadEventEnd) : null,
        responseStartMs: nav ? Math.round(nav.responseStart) : null
      },
      paint: {
        fcpMs: Math.round((paints.find((entry) => entry.name === "first-contentful-paint") || { startTime: 0 }).startTime),
        fpMs: Math.round((paints.find((entry) => entry.name === "first-paint") || { startTime: 0 }).startTime)
      },
      transfer: {
        totalBytes: bytes,
        scriptBytes,
        cssBytes
      },
      counts: {
        resources: resources.length,
        scripts: scripts.length,
        styles: css.length,
        images: resources.filter((r) => r.initiatorType === "img").length
      },
      clsApprox: Number(clsEntry.toFixed(4)),
      concierge: {
        hasApp: !!document.querySelector("[data-ftc-app]"),
        hasGoTimeRail: !!document.querySelector("[data-ftc-go-time-spline]"),
        hasService3d: !!document.querySelector(".ftc-service-3d"),
        routeAttr: document.querySelector("[data-ftc-app]")?.getAttribute("data-ftc-route") || ""
      }
    };
  });

  await context.close();
  return {
    route: route.key,
    profile,
    url,
    screenshot: screenshotPath,
    elapsedMs: Date.now() - startedAt,
    ...metrics
  };
}

async function runProfile(browser, profile, contextOptions) {
  const rows = [];
  for (const route of ROUTES) {
    rows.push(await captureRoute(browser, profile, contextOptions, route));
  }
  return rows;
}

const browser = await chromium.launch({ headless: true });
try {
  const desktop = await runProfile(browser, "desktop", { viewport: { width: 1440, height: 960 } });
  const mobile = await runProfile(browser, "iphone14", { ...devices["iPhone 14"], isMobile: true });
  const summary = {
    generatedAt: new Date().toISOString(),
    baseUrl: BASE_URL,
    outDir: OUT_DIR,
    rows: desktop.concat(mobile)
  };
  fs.writeFileSync(path.join(OUT_DIR, "baseline-summary.json"), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify(summary, null, 2));
} finally {
  await browser.close();
}
