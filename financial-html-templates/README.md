# Meridian — Bootstrap HTML Templates

A standalone, framework-free port of the Meridian Global Finance portal, built on
**Bootstrap 5.3** (via CDN) with the same "pink paper" premium-finance theme as the
React app. No build step — open any `.html` file in a browser or serve the folder.

## Pages

| File | Purpose |
|------|---------|
| `index.html` | Home / front page (lead, markets wrap, 12 section blocks, opinion, video) |
| `category.html?s=<slug>` | Category / section landing page (e.g. `?s=equities`, `?s=opinion`) |
| `article.html?a=<slug>` | News detail page (dropcap, pull-quote, related, share rail) |
| `markets-data.html` | Benchmarks + sector performance data tables |
| `search.html?q=<term>` | Client-side search over the story corpus |
| `404.html` | Not-found page |

## Structure

```
html-templates/
├── index.html · category.html · article.html · markets-data.html · search.html · 404.html
└── assets/
    ├── css/meridian.css   Theme tokens + component styles layered over Bootstrap
    └── js/
        ├── chrome.js       Shared header, nav, market board, ticker, footer (injected)
        ├── content.js      Reusable ad units, headline rows, and sidebar rail
        └── data.js         Content model + getCategory()/getArticle()/search corpus
```

Shared chrome is injected into `<div data-chrome="header">` / `<div data-chrome="footer">`
so it lives in one place. Each page's own script renders its main column from `data.js`.

## Ad placement (viewability-first)

- **Sticky rail units** (`.ad-sticky`, `300×250` + `300×600`) travel with the reader.
- **In-content leaderboards** (`728×90`) sit high — right after the lead, not buried.
- **In-feed / in-article MPUs** (`300×250`) interleave into story grids and after the
  2nd article paragraph, where dwell time peaks.

All slots are labelled placeholder boxes ready to swap for a real ad tag.
