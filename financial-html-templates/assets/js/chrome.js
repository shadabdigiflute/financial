/* Shared site chrome for the Meridian Bootstrap templates.
   Each page includes <div data-chrome="header"></div> and
   <div data-chrome="footer"></div>; this script fills them so the
   masthead, nav, market board, ticker and footer stay in one place. */

const NAV = [
  ["Home", "index.html"],
  ["World Markets", "category.html?s=world-markets"],
  ["Equities", "category.html?s=equities"],
  ["Bonds", "category.html?s=bonds"],
  ["Commodities", "category.html?s=commodities"],
  ["FX", "category.html?s=fx"],
  ["Central Banks", "category.html?s=central-banks"],
  ["Companies", "category.html?s=companies"],
  ["Deals & M&A", "category.html?s=deals-m-a"],
  ["Economy", "category.html?s=economy"],
  ["Tech", "category.html?s=tech"],
  ["Energy", "category.html?s=energy"],
  ["Crypto", "category.html?s=crypto"],
  ["Wealth", "category.html?s=wealth"],
  ["Opinion", "category.html?s=opinion"],
];

const SUBNAV = [
  "Americas", "Europe", "Asia-Pacific", "Middle East", "Emerging Markets",
  "Rates", "Credit", "ETFs", "Private Equity", "Sustainable Finance",
];

const MARKETS = [
  ["S&P 500", "5,642.18", "+0.74%", 1], ["NASDAQ", "18,204.7", "+1.12%", 1],
  ["DOW", "41,388.1", "+0.31%", 1], ["FTSE 100", "8,312.4", "-0.18%", 0],
  ["DAX", "18,720.9", "+0.42%", 1], ["NIKKEI 225", "38,451.2", "-0.63%", 0],
  ["HANG SENG", "17,982.3", "+1.05%", 1], ["EUR/USD", "1.0872", "-0.09%", 0],
  ["USD/JPY", "146.21", "+0.24%", 1], ["US 10Y", "3.94%", "-4 bps", 0],
  ["GOLD", "$2,528", "+0.6%", 1], ["WTI CRUDE", "$74.62", "-1.3%", 0],
  ["BTC", "$63,410", "-1.8%", 0],
];

const SECTORS = [
  ["Financials", "+1.24%", 1], ["Technology", "+0.98%", 1], ["Energy", "-0.72%", 0],
  ["Health Care", "+0.41%", 1], ["Industrials", "+0.55%", 1], ["Materials", "-0.19%", 0],
  ["Cons. Disc.", "+0.63%", 1], ["Cons. Staples", "-0.08%", 0], ["Utilities", "+0.87%", 1],
  ["Real Estate", "+1.02%", 1], ["Comm. Svcs.", "+0.34%", 1],
];

const CLOCKS = [
  ["New York", "11:42", "open"], ["London", "16:42", "closing"],
  ["Tokyo", "00:42", "closed"], ["Hong Kong", "23:42", "closed"],
];

const TICKER = [
  "Fed officials signal a September cut is on the table as labour market cools",
  "ECB holds rates, trims growth forecast for the euro area",
  "Dollar softens against the yen ahead of the BOJ decision",
  "Oil slips as OPEC+ weighs unwinding voluntary output curbs",
  "Sovereign bond auction draws strongest demand in six months",
  "Gold holds near record as investors hedge policy uncertainty",
];

const EDITIONS = ["US", "UK", "Asia"];

const FOOTER = {
  Markets: ["World Markets", "Equities", "Bonds", "FX", "Commodities", "Crypto", "Rates", "Markets Data"],
  Sectors: ["Financials", "Technology", "Energy", "Healthcare", "Industrials", "Consumer", "Real Estate", "Utilities"],
  Regions: ["Americas", "Europe", "Asia-Pacific", "Middle East", "Africa", "Emerging Markets", "UK", "China"],
  Analysis: ["Opinion", "Lex", "Big Read", "Markets Insight", "The Week Ahead", "Newsletters", "Podcasts", "Video"],
  Products: ["Terminal", "Data Feeds", "API", "Research", "Alerts", "e-Paper", "Mobile App", "Print"],
  Company: ["About", "Advertise", "Careers", "Terms of Use", "Privacy Policy", "Editorial Code", "Contact", "Sitemap"],
};

const slugify = (s) => s.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
const activeSlug = new URLSearchParams(location.search).get("s") || "";
const page = location.pathname.split("/").pop() || "index.html";

function headerHTML() {
  const clocks = CLOCKS.map(([c, t, st]) =>
    `<li class="d-flex align-items-center gap-1 flex-shrink-0">
       <span class="dot dot-${st}"></span>
       <span class="fw-semibold">${c}</span>
       <span class="text-faint tabular">${t}</span>
     </li>`).join("");

  const editions = EDITIONS.map((e, i) =>
    `<button type="button" class="btn btn-link p-0 px-1 fw-semibold ${i === 0 ? "text-accent text-decoration-underline" : "text-soft"}" style="font-size:11px" data-edition>${e}</button>`).join("");

  const nav = NAV.map(([label, href]) => {
    const isActive = (page === "index.html" && label === "Home") ||
      (page === "category.html" && slugify(label) === activeSlug);
    return `<li><a class="${isActive ? "active" : ""}" href="${href}">${label}</a></li>`;
  }).join("");

  const subnav = SUBNAV.map((s) =>
    `<li><a class="px-2 py-1 text-soft" style="font-size:12px;font-weight:500;white-space:nowrap" href="category.html?s=${slugify(s)}">${s}</a></li>`).join("");

  const board = MARKETS.map(([n, v, c, up]) =>
    `<li class="quote d-flex align-items-baseline gap-2 flex-shrink-0">
       <span class="name">${n}</span><span class="fw-bold tabular">${v}</span>
       <span class="fw-semibold tabular ${up ? "up" : "down"}">${up ? "▲" : "▼"} ${c}</span>
     </li>`).join("");

  const sectors = SECTORS.map(([n, c, up]) =>
    `<li class="s d-flex align-items-baseline gap-1 flex-shrink-0">
       <span class="fw-semibold text-soft">${n}</span>
       <span class="fw-bold tabular ${up ? "text-up" : "text-down"}">${c}</span>
     </li>`).join("");

  const ticker = TICKER.map((t) => `${t} <span class="text-accent2 mx-3">●</span>`).join(" ");

  return `
  <a href="#main" class="visually-hidden-focusable position-fixed top-0 start-0 m-2 btn btn-sm text-white bg-accent" style="z-index:1060">Skip to main content</a>

  <!-- Utility bar -->
  <div class="bg-paper-alt border-bottom rule text-soft">
    <div class="container-x mx-auto d-flex align-items-center justify-content-between gap-3 px-3 py-1 util-bar">
      <div class="d-flex align-items-center gap-3 overflow-x-auto no-scrollbar">
        <span class="text-uppercase flex-shrink-0">Thursday, 3 September 2026</span>
        <span class="d-none d-lg-inline" style="width:1px;height:12px;background:var(--rule-strong)"></span>
        <ul class="d-none d-lg-flex align-items-center gap-3 list-unstyled m-0">${clocks}</ul>
      </div>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <div class="d-none d-sm-flex align-items-center gap-1"><span class="text-faint">Edition:</span>${editions}</div>
        <a href="markets-data.html" class="d-none d-md-inline text-soft">Markets Data</a>
        <a href="#" class="d-none d-md-inline text-soft">e-Paper</a>
        <a href="#" class="fw-semibold text-accent">Sign In</a>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm text-white bg-accent px-2 py-1 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.04em">Subscribe</a>
      </div>
    </div>
  </div>

  <!-- Masthead -->
  <header class="border-bottom rule-strong">
    <div class="container-x mx-auto d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 px-3 py-4">
      <a href="index.html" class="text-center text-md-start">
        <span class="masthead-logo d-block">MERIDIAN</span>
        <span class="masthead-sub d-block mt-1">Global Finance</span>
      </a>
      <div class="d-flex align-items-center gap-3 w-100 w-md-auto">
        <form role="search" class="d-flex align-items-center border-bottom rule-ink pb-1 w-100" style="max-width:260px" action="search.html" method="get">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-faint flex-shrink-0">
            <circle cx="9" cy="9" r="6"></circle><path d="M14 14l4 4" stroke-linecap="round"></path>
          </svg>
          <input name="q" type="search" class="form-control border-0 bg-transparent shadow-none px-2 py-0" style="font-size:13px" placeholder="Search markets, companies…">
        </form>
        <div class="d-none d-lg-block"><div class="ad" style="max-width:468px;aspect-ratio:468/60"><span class="lbl">Advertisement</span><span class="sz">468 × 60</span></div></div>
      </div>
    </div>
  </header>

  <!-- Primary nav -->
  <nav class="mnav" aria-label="Primary">
    <div class="container-x mx-auto px-2">
      <ul class="d-flex list-unstyled m-0 overflow-x-auto no-scrollbar">${nav}</ul>
    </div>
  </nav>

  <!-- Secondary nav -->
  <div class="bg-paper-alt border-bottom rule">
    <div class="container-x mx-auto px-2">
      <ul class="d-flex align-items-center gap-1 list-unstyled m-0 py-1 overflow-x-auto no-scrollbar">${subnav}</ul>
    </div>
  </div>

  <!-- Market board -->
  <div class="board" aria-label="Market snapshot">
    <div class="container-x mx-auto d-flex align-items-center gap-3 px-2">
      <div class="d-none d-lg-flex align-items-center gap-2 flex-shrink-0 py-2 pe-3" style="border-right:1px solid rgba(255,255,255,.15)">
        <span class="live-dot green"></span>
        <span class="fw-bold text-uppercase tracking-wider" style="color:#3ddc97;font-size:11px">Markets Open</span>
        <span class="text-uppercase" style="color:rgba(255,255,255,.45);font-size:10px">as of 11:42 EDT</span>
      </div>
      <ul class="d-flex align-items-center gap-4 list-unstyled m-0 py-2 overflow-x-auto no-scrollbar flex-grow-1">${board}</ul>
    </div>
  </div>

  <!-- Sector strip -->
  <div class="sectors" aria-label="S&P 500 sector performance">
    <div class="container-x mx-auto d-flex align-items-center gap-4 px-2">
      <span class="d-none d-md-inline text-faint fw-bold text-uppercase tracking-wider py-2 flex-shrink-0" style="font-size:10px">Sectors</span>
      <ul class="d-flex align-items-center gap-4 list-unstyled m-0 py-2 overflow-x-auto no-scrollbar flex-grow-1">${sectors}</ul>
    </div>
  </div>

  <!-- Ticker -->
  <div class="ticker d-flex align-items-center" role="region" aria-label="Breaking news">
    <span class="tag flex-shrink-0 align-self-stretch">Live</span>
    <div class="position-relative flex-grow-1 overflow-hidden"><div class="marquee text-ink">${ticker}</div></div>
  </div>`;
}

function footerHTML() {
  const cols = Object.entries(FOOTER).map(([head, links]) => `
    <div class="col-6 col-sm-4 col-lg-2 mb-3">
      <h4 class="text-ink fw-bold text-uppercase tracking-wider mb-3" style="font-size:12px">${head}</h4>
      <ul class="list-unstyled d-flex flex-column gap-2 m-0">
        ${links.map((l) => `<li><a class="text-soft" style="font-size:13px" href="category.html?s=${slugify(l)}">${l}</a></li>`).join("")}
      </ul>
    </div>`).join("");

  return `
  <footer class="bg-paper-alt" style="border-top:2px solid var(--ink)">
    <div class="container-x mx-auto px-3 py-5">
      <div class="mb-4">
        <span class="font-serif d-block fw-bold" style="font-size:26px;letter-spacing:-.02em">MERIDIAN</span>
        <span class="masthead-sub d-block">Global Finance</span>
      </div>
      <div class="row">${cols}</div>
      <div class="border-top rule-strong pt-4 mt-4 text-faint" style="font-size:11px;line-height:1.6">
        <p class="mb-1">Data is indicative and delayed. Nothing on this page constitutes investment advice. Figures shown are illustrative for design purposes.</p>
        <span>© 2026 Meridian Global Finance. All rights reserved.</span>
      </div>
    </div>
  </footer>`;
}

window.MARKET_DATA = { MARKETS, SECTORS };

document.addEventListener("DOMContentLoaded", () => {
  const h = document.querySelector('[data-chrome="header"]');
  const f = document.querySelector('[data-chrome="footer"]');
  if (h) h.innerHTML = headerHTML();
  if (f) f.innerHTML = footerHTML();

  // Edition switcher
  document.querySelectorAll("[data-edition]").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll("[data-edition]").forEach((b) => {
        b.classList.remove("text-accent", "text-decoration-underline");
        b.classList.add("text-soft");
      });
      btn.classList.remove("text-soft");
      btn.classList.add("text-accent", "text-decoration-underline");
    });
  });
});
