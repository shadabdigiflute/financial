/* Content data for the Meridian templates — mirrors the React data model. */

window.DATA = (function () {
  const LEAD = {
    kicker: "Monetary Policy",
    title: "Fed lays groundwork for rate cut as inflation cools and labour market loses steam",
    blurb: "Minutes from the latest meeting show a growing consensus that borrowing costs have peaked, with officials weighing the pace of easing against still-sticky services inflation. Futures markets now price a quarter-point move as near-certain.",
    time: "9 min ago",
    byline: "By Eleanor Voss · Washington",
  };

  const LEAD_LIST = [
    { title: "Treasury yields slide to a four-month low as traders add to easing bets", kicker: "Rates", time: "22 min ago", badge: "Live" },
    { title: "Dollar index falls for a fifth session; emerging-market currencies rally", kicker: "FX", time: "38 min ago" },
    { title: "Bank stocks lead the tape higher as net-interest-margin fears ease", kicker: "Equities", time: "51 min ago" },
    { title: "Corporate issuers rush to lock in funding before month-end", kicker: "Credit", time: "1 hr ago", badge: "Exclusive" },
    { title: "Analysis: What a shift to easing means for the 60/40 portfolio", kicker: "Strategy", time: "1 hr ago", badge: "Analysis" },
  ];

  const MARKETS_WRAP = [
    { title: "Wall Street closes higher as tech leads broad advance", kicker: "US Close", time: "12 min ago", badge: "Live" },
    { title: "European shares mixed as luxury names weigh on the index", kicker: "Europe", time: "30 min ago" },
    { title: "Asian markets steady ahead of key China activity data", kicker: "Asia", time: "44 min ago" },
    { title: "Volatility gauge drops to lowest level since the spring", kicker: "Volatility", time: "1 hr ago" },
    { title: "Small caps outperform as breadth improves across sectors", kicker: "Equities", time: "1 hr ago", badge: "Premium" },
    { title: "Muni bond funds see largest weekly inflow of the year", kicker: "Fixed Income", time: "2 hrs ago" },
  ];

  const SECTIONS = [
    { name: "Equities", items: [
      { title: "Megacap tech extends gains as AI capex outlook brightens", kicker: "Technology", time: "18 min ago", badge: "Live" },
      { title: "Retailer beats on margins, lifts full-year guidance", kicker: "Earnings", time: "48 min ago" },
      { title: "Activist investor builds stake in industrial conglomerate", kicker: "Activism", time: "1 hr ago" },
      { title: "Dividend aristocrats back in favour as yields peak", kicker: "Income", time: "2 hrs ago" },
      { title: "IPO pipeline swells as issuers eye a friendlier window", kicker: "Listings", time: "3 hrs ago" } ] },
    { name: "Bonds & Rates", items: [
      { title: "Curve steepens as front-end yields lead the decline", kicker: "Treasuries", time: "26 min ago" },
      { title: "Investment-grade spreads tighten to post-2021 lows", kicker: "Credit", time: "1 hr ago" },
      { title: "Record demand at 10-year auction signals dovish tilt", kicker: "Auctions", time: "2 hrs ago" },
      { title: "High-yield issuance rebounds as risk appetite returns", kicker: "High Yield", time: "3 hrs ago" },
      { title: "Emerging-market debt draws inflows on softer dollar", kicker: "EM Debt", time: "4 hrs ago" } ] },
    { name: "FX & Central Banks", items: [
      { title: "BOJ seen holding as officials watch wage momentum", kicker: "Japan", time: "35 min ago" },
      { title: "ECB trims forecasts; markets look to October meeting", kicker: "Euro Area", time: "1 hr ago" },
      { title: "Sterling firms as UK services data beats expectations", kicker: "GBP", time: "2 hrs ago" },
      { title: "Swiss franc weakens after surprise inflation miss", kicker: "CHF", time: "3 hrs ago" },
      { title: "Yuan steadies as authorities signal support for growth", kicker: "China", time: "4 hrs ago" } ] },
    { name: "Commodities", items: [
      { title: "Oil slips as OPEC+ weighs return of barrels to market", kicker: "Crude", time: "20 min ago" },
      { title: "Gold holds near record on safe-haven and central-bank demand", kicker: "Metals", time: "1 hr ago" },
      { title: "Copper rallies on tightening mine supply and green demand", kicker: "Base Metals", time: "2 hrs ago" },
      { title: "Natural gas eases as storage builds ahead of winter", kicker: "Gas", time: "3 hrs ago" },
      { title: "Grain prices firm on adverse weather in key exporters", kicker: "Agriculture", time: "4 hrs ago" } ] },
    { name: "Companies", items: [
      { title: "Bank posts record trading revenue, hikes buyback plan", kicker: "Financials", time: "16 min ago" },
      { title: "Carmaker cuts EV price targets amid softening demand", kicker: "Autos", time: "1 hr ago" },
      { title: "Pharma group secures approval for blockbuster therapy", kicker: "Healthcare", time: "2 hrs ago" },
      { title: "Airline lifts profit outlook on resilient premium travel", kicker: "Travel", time: "3 hrs ago" },
      { title: "Chip designer warns of near-term inventory correction", kicker: "Semis", time: "4 hrs ago" } ] },
    { name: "Deals & M&A", items: [
      { title: "Private equity group nears $18bn take-private of software firm", kicker: "Buyouts", time: "24 min ago", badge: "Exclusive" },
      { title: "Two regional lenders agree all-stock merger", kicker: "Banking", time: "1 hr ago" },
      { title: "Energy major to spin off renewables unit in listing", kicker: "Spin-offs", time: "2 hrs ago" },
      { title: "Cross-border chip deal clears final antitrust hurdle", kicker: "Antitrust", time: "3 hrs ago" },
      { title: "Sovereign fund takes minority stake in data-centre operator", kicker: "Infrastructure", time: "4 hrs ago" } ] },
    { name: "Economy", items: [
      { title: "Payrolls cool but wage growth keeps services inflation sticky", kicker: "Jobs", time: "40 min ago" },
      { title: "Global PMI points to fragile, uneven manufacturing recovery", kicker: "Activity", time: "1 hr ago" },
      { title: "Housing starts rebound as mortgage rates retreat", kicker: "Housing", time: "2 hrs ago" },
      { title: "Trade surplus widens on stronger export orders", kicker: "Trade", time: "3 hrs ago" },
      { title: "Consumer sentiment ticks up as price pressures ease", kicker: "Consumer", time: "4 hrs ago" } ] },
    { name: "Energy & Climate", items: [
      { title: "Grid operators race to add storage as demand records fall", kicker: "Power", time: "30 min ago" },
      { title: "Green-bond issuance on track for a record year", kicker: "Sustainable", time: "1 hr ago" },
      { title: "Refiners squeezed as margins compress on weak diesel", kicker: "Refining", time: "2 hrs ago" },
      { title: "Carbon prices climb as tighter caps take effect", kicker: "Carbon", time: "3 hrs ago" },
      { title: "Solar equipment costs fall to fresh lows on oversupply", kicker: "Renewables", time: "4 hrs ago" } ] },
    { name: "Technology", items: [
      { title: "Cloud providers ramp capex as AI workloads surge", kicker: "Cloud", time: "22 min ago" },
      { title: "Data-centre power demand reshapes utility investment", kicker: "Infrastructure", time: "1 hr ago" },
      { title: "Payments firm expands into embedded-finance tools", kicker: "Fintech", time: "2 hrs ago" },
      { title: "Regulators draft framework for AI in capital markets", kicker: "Policy", time: "3 hrs ago" },
      { title: "Cybersecurity spend rises as breach costs mount", kicker: "Security", time: "4 hrs ago" } ] },
    { name: "Crypto & Digital Assets", items: [
      { title: "Bitcoin slips as ETF flows turn negative for the week", kicker: "Bitcoin", time: "28 min ago" },
      { title: "Stablecoin supply hits record as settlement use grows", kicker: "Stablecoins", time: "1 hr ago" },
      { title: "Tokenised treasuries cross $2bn as institutions dip in", kicker: "Tokenisation", time: "2 hrs ago" },
      { title: "Exchange secures licence to expand in European market", kicker: "Regulation", time: "3 hrs ago" },
      { title: "Miners diversify into AI compute to offset halving", kicker: "Mining", time: "4 hrs ago" } ] },
    { name: "Wealth & Investing", items: [
      { title: "Family offices tilt toward private credit and infrastructure", kicker: "Allocation", time: "34 min ago" },
      { title: "Fee pressure drives another wave of ETF launches", kicker: "Funds", time: "1 hr ago" },
      { title: "Tax-planning strategies for a lower-rate environment", kicker: "Planning", time: "2 hrs ago" },
      { title: "Alternatives allocations reach a new high among endowments", kicker: "Alternatives", time: "3 hrs ago" },
      { title: "Advisers rethink cash as money-market yields peak", kicker: "Cash", time: "4 hrs ago" } ] },
    { name: "Emerging Markets", items: [
      { title: "EM equities rally as a weaker dollar revives inflows", kicker: "Equities", time: "26 min ago" },
      { title: "India bonds draw record foreign buying after index inclusion", kicker: "India", time: "1 hr ago" },
      { title: "Latin American central banks continue cautious easing", kicker: "LatAm", time: "2 hrs ago" },
      { title: "Gulf funds accelerate diversification into global assets", kicker: "Middle East", time: "3 hrs ago" },
      { title: "Frontier markets lure yield-seeking credit investors", kicker: "Frontier", time: "4 hrs ago" } ] },
  ];

  const OPINION = [
    { title: "The soft landing is not yet won — complacency is the real risk", kicker: "Editorial", time: "1 hr ago", badge: "Analysis" },
    { title: "Why private credit's boom deserves closer scrutiny", kicker: "Markets Insight", time: "2 hrs ago", badge: "Premium" },
    { title: "Central banks should not declare victory on inflation", kicker: "Column", time: "3 hrs ago" },
    { title: "The hidden cost of the great capital-expenditure race", kicker: "Analysis", time: "4 hrs ago" },
  ];

  const VIDEOS = [
    { title: "Watch: Decoding the Fed minutes in three charts", kicker: "4:12", time: "1 hr ago" },
    { title: "The week ahead in global markets", kicker: "6:30", time: "2 hrs ago" },
    { title: "Inside the private-credit boom", kicker: "8:05", time: "3 hrs ago" },
    { title: "How tokenisation could reshape settlement", kicker: "5:44", time: "4 hrs ago" },
  ];

  const slugify = (s) => s.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "").slice(0, 80);

  const ALIASES = {
    "world-markets": "Markets Wrap", equities: "Equities", bonds: "Bonds & Rates",
    commodities: "Commodities", fx: "FX & Central Banks", "central-banks": "FX & Central Banks",
    companies: "Companies", "deals-m-a": "Deals & M&A", economy: "Economy", tech: "Technology",
    energy: "Energy & Climate", crypto: "Crypto & Digital Assets", wealth: "Wealth & Investing",
    opinion: "Opinion", home: "Home",
  };

  const STANDFIRSTS = {
    "Equities": "Stocks, earnings and the flows driving global equity markets.",
    "Bonds & Rates": "Yields, auctions and credit across sovereign and corporate debt.",
    "FX & Central Banks": "Currencies and the policymakers steering the global rate cycle.",
    "Commodities": "Energy, metals and agriculture from wellhead to warehouse.",
    "Companies": "Corporate results, strategy and leadership across the world's largest firms.",
    "Deals & M&A": "Buyouts, mergers and the bankers financing them.",
    "Economy": "Growth, jobs, trade and the data shaping the macro outlook.",
    "Energy & Climate": "The transition, power markets and the money behind them.",
    "Technology": "Cloud, chips, fintech and the capital fuelling the AI build-out.",
    "Crypto & Digital Assets": "Digital assets, tokenisation and the rails of on-chain finance.",
    "Wealth & Investing": "Allocation, funds and planning for private and institutional capital.",
    "Emerging Markets": "Growth, debt and flows across the developing world.",
    "Markets Wrap": "A cross-asset read on where global markets closed and why.",
    "Opinion": "Editorials, columns and market insight from the Meridian desk.",
  };

  function getCategory(slug) {
    const name = ALIASES[slug] || (SECTIONS.find((s) => slugify(s.name) === slug) || {}).name;
    if (!name) return null;
    if (name === "Opinion") {
      return { slug, name: "Opinion & Analysis", standfirst: STANDFIRSTS.Opinion,
        lead: OPINION[0], items: OPINION.slice(1).concat(MARKETS_WRAP) };
    }
    const sec = SECTIONS.find((s) => s.name === name);
    const items = sec ? sec.items : MARKETS_WRAP;
    const extra = SECTIONS.filter((s) => s.name !== name).flatMap((s) => s.items).slice(0, 8);
    return { slug, name, standfirst: STANDFIRSTS[name] || "The latest across global finance.",
      lead: items[0], items: items.slice(1).concat(extra) };
  }

  const ALL = [LEAD].concat(LEAD_LIST, MARKETS_WRAP, SECTIONS.flatMap((s) => s.items), OPINION);

  function bodyFor(title) {
    const t = title.replace(/\.$/, "");
    return [
      `${t} — a development that traders and policymakers alike had been bracing for as the second half of the year gets underway. The move landed after weeks of speculation and reshapes the near-term calculus for investors positioning across asset classes.`,
      "Analysts were quick to parse the implications. \"The signal here is clearer than the market had assumed,\" said one strategist at a large asset manager, pointing to the consistency of recent data. Others cautioned that a single print rarely settles a debate that has divided desks for months.",
      "For markets, the reaction was immediate. Rates repriced, the dollar found a fresh footing and risk assets caught a bid, though the breadth of the move suggested conviction remains fragile beneath the surface.",
      "The policy backdrop remains the dominant variable. Officials have been careful to keep their options open, wary of declaring victory too early while also mindful of the lag with which their decisions feed through to the real economy.",
      "Beneath the headline number, the internals told a more nuanced story. Composition mattered as much as the aggregate, and the details are likely to feature prominently in the next round of forecasts from the sell side.",
      "What happens next depends on the data. A run of softer readings would harden the emerging consensus; a single upside surprise could just as easily unwind it. For now, the balance of risks has tilted, and portfolios are being adjusted accordingly.",
    ];
  }

  function getArticle(slug) {
    const base = ALL.find((it) => slugify(it.title) === slug) ||
      { title: "Global markets in focus as the policy cycle turns", kicker: "Markets", time: "1 hr ago" };
    return {
      slug, title: base.title, kicker: base.kicker || "Markets", badge: base.badge, time: base.time,
      byline: "By Eleanor Voss and Marcus Reed",
      standfirst: "What the shift means for rates, currencies and the portfolios positioned for a turn in the cycle — and where strategists see the balance of risk from here.",
      body: bodyFor(base.title), related: MARKETS_WRAP.slice(0, 4),
    };
  }

  return {
    LEAD, LEAD_LIST, MARKETS_WRAP, SECTIONS, OPINION, VIDEOS,
    slugify, getCategory, getArticle,
    CORPUS: LEAD_LIST.concat(MARKETS_WRAP, SECTIONS.flatMap((s) => s.items), OPINION),
  };
})();
