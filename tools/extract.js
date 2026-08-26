// Local analysis helper (not part of the theme, not a build step).
// Prints every rule whose selector matches a pattern, with its @media context,
// in source order — so the last-declared (winning) value is visible.
const fs = require('fs');
const path = require('path');
const SOURCE = path.join(__dirname, '..', 'design-source', 'assets', 'css', 'main.min.css');
const css = fs.readFileSync(SOURCE, 'utf8');
const re = new RegExp(process.argv[2], 'i');

let i = 0, ctx = [];
const out = [];
while (i < css.length) {
  const open = css.indexOf('{', i);
  if (open === -1) break;
  const prelude = css.slice(i, open).trim();
  if (prelude.startsWith('@media') || prelude.startsWith('@supports')) {
    ctx.push(prelude);
    i = open + 1;
    continue;
  }
  // find matching close for a declaration block
  let close = css.indexOf('}', open);
  const body = css.slice(open + 1, close).trim();
  if (re.test(prelude)) {
    out.push((ctx.length ? ctx.join(' AND ') + '\n' : '') + prelude + ' {\n  ' + body.split(';').filter(Boolean).join(';\n  ') + ';\n}\n');
  }
  i = close + 1;
  // pop contexts whose closing brace follows immediately
  while (ctx.length && css.slice(i).match(/^\s*}/)) {
    ctx.pop();
    i = css.indexOf('}', i) + 1;
  }
}
console.log(out.join('\n'));
