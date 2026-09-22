/* Reusable content fragments for the Meridian templates:
   ad units, headline rows, and the shared sidebar rail. */

const IN_BRIEF = [
  "Jobless claims fall to 219k, below the 230k consensus estimate.",
  "Eurozone consumer confidence flash reading edges up to -12.8.",
  "Nvidia supplier lifts quarterly guidance, citing AI demand.",
  "Brent crude settles below $78 as OPEC+ signals more supply.",
  "Two-year Treasury yield touches a fresh four-month low of 3.58%.",
];

const MOST_READ = [
  "Live markets: Stocks, bonds and the dollar react to the Fed minutes",
  "Explained: What a September rate cut means for mortgages and savers",
  "The 10 charts that define the second-half outlook",
  "Big Read: Inside the $2tn private-credit machine",
  "Lex: Why the chip cycle isn't over yet",
  "Currencies: Where strategists see the dollar heading next",
];

const CALENDAR = [
  ["08:30", "US Initial Jobless Claims", "Weekly"],
  ["10:00", "Eurozone Consumer Confidence", "Flash"],
  ["14:00", "Fed Speakers — Regional Presidents", "3 events"],
  ["19:50", "Japan Q2 GDP (revised)", "QoQ"],
];

const TRENDING = [
  "Fed Minutes", "Rate Cut", "US 10Y Yield", "Dollar Index", "OPEC+",
  "Gold Record", "Private Credit", "AI Capex", "Bitcoin ETF", "Yen",
];

const artSlug = (t) => t.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "").slice(0, 80);

// Global helpers ------------------------------------------------------------

window.ad = function (w, h, sticky) {
  return `<div class="${sticky ? "ad-sticky" : ""}"><div class="ad" style="max-width:${w}px;aspect-ratio:${w}/${h}">
    <span class="lbl">Advertisement</span><span class="sz">${w} × ${h}${sticky ? " · sticky" : ""}</span></div></div>`;
};

window.badge = function (b) {
  if (!b) return "";
  if (b === "Live") return `<span class="badge-live"><span class="live-dot" style="width:6px;height:6px"></span> Live</span>`;
  if (b === "Premium") return `<span class="badge-premium">◆ Premium</span>`;
  return `<span class="badge-plain ${b === "Exclusive" ? "excl" : ""}">${b}</span>`;
};

// item = { title, kicker, time, badge }
window.headline = function (it, big) {
  const meta = (it.kicker || it.badge)
    ? `<span class="d-inline-flex flex-wrap align-items-center gap-2">
         ${it.kicker ? `<span class="kicker">${it.kicker}</span>` : ""}
         ${it.kicker && it.badge ? `<span style="color:var(--rule-strong)">·</span>` : ""}
         ${window.badge(it.badge)}
       </span>` : "";
  return `<article class="headline">
    <a href="article.html?a=${artSlug(it.title)}">
      ${meta}
      <h3 class="${big ? "big" : ""}">${it.title}</h3>
      <span class="time">${it.time}</span>
    </a>
  </article>`;
};

window.sectionHead = function (name, href) {
  return `<div class="section-head">
    ${href ? `<a class="font-serif" href="${href}" style="font-size:18px;font-weight:700;color:var(--ink)">${name}</a>`
           : `<h2>${name}</h2>`}
    ${href ? `<a class="see-all" href="${href}">See all</a>` : ""}
  </div>`;
};

window.sidebar = function (compact) {
  const inBrief = `<section class="card-box">
    <div class="hd d-flex align-items-center justify-content-between">In Brief
      <span class="text-faint fw-semibold text-uppercase tracking-wider" style="font-size:10px">The 5-minute wrap</span></div>
    <ul class="list-unstyled m-0 px-3 py-1">
      ${IN_BRIEF.map((t) => `<li class="d-flex gap-2 py-2 border-bottom rule"><span class="flex-shrink-0 mt-2" style="width:6px;height:6px;border-radius:50%;background:var(--accent-2)"></span><span class="text-soft" style="font-size:13px;line-height:1.35">${t}</span></li>`).join("")}
    </ul></section>`;

  const mostRead = `<section>
    ${window.sectionHead("Most Read")}
    <ol class="list-unstyled most-read m-0">
      ${MOST_READ.map((t, i) => `<li><span class="rank">${i + 1}</span><a href="article.html?a=${artSlug(t)}">${t}</a></li>`).join("")}
    </ol></section>`;

  const calendar = `<section class="card-box">
    <div class="hd dark">Today's Calendar</div>
    <ul class="list-unstyled m-0">
      ${CALENDAR.map(([t, e, n]) => `<li class="d-flex align-items-baseline gap-3 px-3 py-2 border-bottom rule">
        <span class="text-accent fw-bold tabular" style="width:44px;font-size:12px">${t}</span>
        <span><span class="d-block fw-semibold text-ink" style="font-size:13px;line-height:1.3">${e}</span>
        <span class="text-faint text-uppercase tracking-wide" style="font-size:11px">${n}</span></span></li>`).join("")}
    </ul></section>`;

  const trending = `<section>
    ${window.sectionHead("Trending")}
    <div class="d-flex flex-wrap gap-2">
      ${TRENDING.map((t) => `<a class="chip" href="search.html?q=${encodeURIComponent(t)}">#${t.replace(/\s+/g, "")}</a>`).join("")}
    </div></section>`;

  const newsletter = `<section class="card-box p-4">
    <h2 class="font-serif fw-bold m-0" style="font-size:17px">The Opening Bell</h2>
    <p class="text-soft mt-1" style="font-size:13px;line-height:1.5">Global markets, macro and the day's must-reads — in your inbox before the open.</p>
    <form onsubmit="return false" class="d-flex flex-column gap-2 mt-2">
      <input type="email" required placeholder="you@example.com" class="form-control rounded-0 border rule-strong" style="font-size:14px">
      <button class="btn text-white bg-accent rounded-0 fw-semibold text-uppercase tracking-wide" style="font-size:14px">Subscribe</button>
    </form></section>`;

  return `<aside class="d-flex flex-column gap-4">
    <div class="ad-sticky">${window.ad(300, 250)}</div>
    ${compact ? "" : inBrief}
    ${mostRead}
    ${compact ? "" : calendar}
    ${newsletter}
    ${compact ? "" : trending}
    ${window.ad(300, 600, true)}
  </aside>`;
};
