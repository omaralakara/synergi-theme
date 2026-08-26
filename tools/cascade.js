// Local analysis helper. Not theme code, not a build step.
// Resolves main.min.css's eight override layers into the values that actually
// win, for a described element at a described viewport. Same job the :root
// audit did by hand in Stage 2, done by machine because this section has ~180
// overlapping rules.
const fs = require('fs');
const path = require('path');
const SOURCE = path.join(__dirname, '..', 'design-source', 'assets', 'css', 'main.min.css');
const css = fs.readFileSync(SOURCE, 'utf8');

/* ---------- parse ---------- */
const rules = [];
(function parse(text, media) {
  let i = 0;
  while (i < text.length) {
    const open = text.indexOf('{', i);
    if (open === -1) return;
    const prelude = text.slice(i, open).trim();
    // find matching close brace
    let depth = 1, j = open + 1;
    while (j < text.length && depth > 0) {
      if (text[j] === '{') depth++;
      else if (text[j] === '}') depth--;
      j++;
    }
    const body = text.slice(open + 1, j - 1);
    if (prelude.startsWith('@media') || prelude.startsWith('@supports')) {
      parse(body, media.concat(prelude.replace(/^@\w+\s*/, '')));
    } else if (!prelude.startsWith('@')) {
      const decls = [];
      body.split(';').forEach((d) => {
        const c = d.indexOf(':');
        if (c > 0) decls.push([d.slice(0, c).trim(), d.slice(c + 1).trim()]);
      });
      prelude.split(',').forEach((sel) => {
        rules.push({ media, sel: sel.trim(), decls, index: rules.length });
      });
    }
    i = j;
  }
})(css, []);

/* ---------- selector matching ---------- */
function parseCompound(part) {
  const out = { tag: null, classes: [], attrs: [], pseudos: [], pseudoEl: null, not: [] };
  part.replace(/::([\w-]+)/g, (m, n) => { out.pseudoEl = n; return ''; });
  let s = part.replace(/::[\w-]+/g, '');
  s = s.replace(/:not\(([^)]*)\)/g, (m, inner) => { out.not.push(inner); return ''; });
  s = s.replace(/\[([^\]]+)\]/g, (m, a) => { out.attrs.push(a); return ''; });
  s = s.replace(/:([\w-]+)/g, (m, n) => { out.pseudos.push(n); return ''; });
  s.split('.').forEach((piece, idx) => {
    if (!piece) return;
    if (idx === 0) out.tag = piece;
    else out.classes.push(piece);
  });
  return out;
}

function compoundMatches(c, el) {
  if (c.tag && c.tag !== '*' && c.tag !== el.tag) return false;
  if (!c.classes.every((k) => (el.classes || []).includes(k))) return false;
  if (c.pseudoEl && c.pseudoEl !== el.pseudoEl) return false;
  if (!c.pseudoEl && el.pseudoEl) return false;
  for (const p of c.pseudos) if (!(el.states || []).includes(p)) return false;
  for (const a of c.attrs) {
    const m = a.match(/^([\w-]+)(?:=["']?([^"']*)["']?)?$/);
    if (!m) return false;
    const have = (el.attrs || {})[m[1]];
    if (have === undefined) return false;
    if (m[2] !== undefined && String(have) !== m[2]) return false;
  }
  for (const n of c.not) {
    if (compoundMatches(parseCompound(n.trim()), { ...el, pseudoEl: el.pseudoEl })) return false;
  }
  return true;
}

// el is a chain: [rootmost, ..., target]. Supports descendant and > combinators.
function matches(sel, chain) {
  const parts = sel.trim().split(/\s+(?![^(]*\))/).flatMap((p) =>
    p.includes('>') ? p.split('>').flatMap((x, i, arr) => (i ? ['>', x] : [x])) : [p]
  ).filter(Boolean);
  let ci = chain.length - 1;
  let pi = parts.length - 1;
  if (!compoundMatches(parseCompound(parts[pi]), chain[ci])) return false;
  pi--; ci--;
  let child = false;
  while (pi >= 0) {
    if (parts[pi] === '>') { child = true; pi--; continue; }
    const c = parseCompound(parts[pi]);
    if (child) {
      if (ci < 0 || !compoundMatches(c, chain[ci])) return false;
      ci--; child = false; pi--;
    } else {
      let found = false;
      while (ci >= 0) {
        if (compoundMatches(c, chain[ci])) { found = true; ci--; break; }
        ci--;
      }
      if (!found) return false;
      pi--;
    }
  }
  return true;
}

function specificity(sel) {
  const s = sel.replace(/::[\w-]+/g, ' ');
  const ids = (s.match(/#[\w-]+/g) || []).length;
  const cls = (s.match(/\.[\w-]+/g) || []).length + (s.match(/\[[^\]]+\]/g) || []).length + (s.match(/:(?!:)[\w-]+/g) || []).length;
  const tags = (s.match(/(^|[\s>+~])([a-z][\w-]*)/g) || []).length;
  return ids * 10000 + cls * 100 + tags;
}

/* ---------- media evaluation ---------- */
function mediaApplies(list, env) {
  return list.every((q) => {
    const maxes = [...q.matchAll(/max-width:\s*([\d.]+)rem/g)].map((m) => parseFloat(m[1]));
    const mins = [...q.matchAll(/min-width:\s*([\d.]+)rem/g)].map((m) => parseFloat(m[1]));
    if (maxes.some((v) => env.width > v)) return false;
    if (mins.some((v) => env.width < v)) return false;
    if (/prefers-reduced-motion/.test(q) && !env.reducedMotion) return false;
    if (/pointer:\s*coarse/.test(q) && !env.coarse) return false;
    if (/hover:\s*hover/.test(q) && env.coarse) return false;
    return true;
  });
}


/* Shorthands that reset longhands. Without this, "margin:0" in layer 8 loses to
   "margin-bottom:1.1rem" in layer 2 and every card measurement comes out wrong. */
const SHORTHANDS = {
  margin: ['margin-top','margin-right','margin-bottom','margin-left','margin-block','margin-inline','margin-block-start','margin-block-end','margin-inline-start','margin-inline-end'],
  padding: ['padding-top','padding-right','padding-bottom','padding-left','padding-block','padding-inline'],
  inset: ['top','right','bottom','left','inset-block','inset-inline'],
  border: ['border-width','border-style','border-color','border-top','border-right','border-bottom','border-left'],
  'border-radius': ['border-top-left-radius','border-top-right-radius','border-bottom-right-radius','border-bottom-left-radius'],
  background: ['background-color','background-image','background-position','background-size','background-repeat','background-attachment','background-origin','background-clip'],
  font: ['font-size','font-family','font-weight','font-style','line-height','font-variant'],
  gap: ['row-gap','column-gap'],
  overflow: ['overflow-x','overflow-y'],
  flex: ['flex-grow','flex-shrink','flex-basis'],
  'grid-area': ['grid-row','grid-column'],
  transition: ['transition-property','transition-duration','transition-timing-function','transition-delay'],
  'place-items': ['align-items','justify-items'],
  'place-content': ['align-content','justify-content'],
};

function resolve(chain, env) {
  const applicable = [];
  rules.forEach((r) => {
    if (!mediaApplies(r.media, env)) return;
    if (!matches(r.sel, chain)) return;
    const spec = specificity(r.sel);
    r.decls.forEach(([prop, val], di) => {
      const important = /!important/.test(val);
      applicable.push({ prop, val, rank: (important ? 1e9 : 0) + spec, index: r.index, di, sel: r.sel, media: r.media });
    });
  });
  applicable.sort((a, b) => a.rank - b.rank || a.index - b.index || a.di - b.di);
  const won = {};
  applicable.forEach((d) => {
    (SHORTHANDS[d.prop] || []).forEach((long) => { delete won[long]; });
    won[d.prop] = d;
  });
  return won;
}

module.exports = { resolve, rules };

/* ---------- CLI ---------- */
if (require.main === module) {
  const spec = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
  spec.elements.forEach((e) => {
    console.log('==== ' + e.name + ' ====');
    spec.envs.forEach((env) => {
      const won = resolve(e.chain, env);
      console.log('-- ' + env.name);
      Object.keys(won).sort().forEach((p) => {
        console.log('   ' + p + ': ' + won[p].val + ';   /* ' + won[p].sel + (won[p].media.length ? ' @ ' + won[p].media.join(' ') : '') + ' */');
      });
    });
  });
}
