const fs = require('fs');
const path = require('path');
const { resolve } = require(process.argv[3] || path.join(__dirname, 'cascade.js'));
const spec = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const skip = new Set(['box-sizing']);
spec.elements.forEach((e) => {
  console.log('\n==== ' + e.name + ' ====');
  let prev = null;
  spec.envs.forEach((env) => {
    const won = resolve(e.chain, env);
    const flat = {};
    Object.keys(won).forEach((p) => { if (!skip.has(p)) flat[p] = won[p].val; });
    if (!prev) {
      console.log('  [base]');
      Object.keys(flat).sort().forEach((p) => console.log('    ' + p + ': ' + flat[p] + ';'));
    } else {
      const changed = Object.keys(flat).filter((p) => flat[p] !== prev[p]).sort();
      const gone = Object.keys(prev).filter((p) => !(p in flat)).sort();
      if (changed.length || gone.length) {
        console.log('  [' + env.name + ']');
        changed.forEach((p) => console.log('    ' + p + ': ' + flat[p] + ';'));
        gone.forEach((p) => console.log('    /* dropped: ' + p + ' */'));
      }
    }
    prev = flat;
  });
});
