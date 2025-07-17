'use strict';
/* jshint esversion: 9, node: true */

function findTeX(text, options = {}) {
  const defaultOptions = {
    inlineMath: [['\\(', '\\)'], ['$', '$']],
    displayMath: [['$$', '$$'], ['\\[', '\\]']],
    processEscapes: true,
    processEnvironments: true,
    processRefs: true,
  };
  options = { ...defaultOptions, ...options };

  const startPatterns = [];
  const endPatterns = {};

  function quotePattern(pattern) {
    return pattern.replace(/([.*+?^${}()|\[\]\/\\])/g, '\\$1');
  }

  function addPattern(starts, delims, display) {
    const [open, close] = delims;
    starts.push(quotePattern(open));
    let regex = `${quotePattern(close)}|\\\\(?:[a-zA-Z]|.)|[{}]`;
    if (open === '$$') {
      regex = `${quotePattern(close)}|\\\\(?:begin|end)\\s*\\{[^}]*\\}|\\\\(?:[a-zA-Z]|.)|[{}]`;
    }
    endPatterns[open] = [close, display, new RegExp(regex, 'g')];
  }

  options.inlineMath.forEach(delims => addPattern(startPatterns, delims, 0));
  options.displayMath
    .sort((a, b) => (a[0] === '$$' ? -1 : b[0] === '$$' ? 1 : 0))
    .forEach(delims => addPattern(startPatterns, delims, 1));

  const parts = [];
  if (startPatterns.length) {
    startPatterns.sort((a, b) => {
      if (a === '\\$\\$') return -1;
      if (b === '\\$\\$') return 1;
      return b.length - a.length;
    });
    parts.push(startPatterns.join('|'));
  }
  if (options.processEnvironments) {
    parts.push('\\\\begin\\s*\\{([^}]*)\\}');
  }
  const startRegex = new RegExp(parts.join('|'), 'g');

  function findEnd(start, end) {
    const [close, display, pattern] = end;
    let i = (pattern.lastIndex = start.index + start[0].length);
    let match;
    let braces = 0;
    const envStack = [];
    while ((match = pattern.exec(text))) {
      const token = match[0];
      if (token === close && braces === 0 && envStack.length === 0) {
        return {
          open: start[0],
          math: text.slice(i, match.index),
          close: token,
          display,
          startIndex: start.index,
          endIndex: match.index + token.length,
        };
      } else if (/^\\begin/.test(token)) {
        const m = token.match(/^\\begin\\s*\{([^}]*)\}/);
        if (m) envStack.push(m[1]);
      } else if (/^\\end/.test(token)) {
        const m = token.match(/^\\end\\s*\{([^}]*)\}/);
        if (m && envStack.length && envStack[envStack.length - 1] === m[1]) {
          envStack.pop();
        }
      } else if (token === '{') {
        braces++;
      } else if (token === '}' && braces) {
        braces--;
      }
    }
    return null;
  }

  const math = [];
  let match;
  startRegex.lastIndex = 0;
  while ((match = startRegex.exec(text))) {
    const end = endPatterns[match[0]];
    if (end) {
      const found = findEnd(match, end);
      if (found) {
        math.push(found);
        startRegex.lastIndex = found.endIndex;
      }
    }
  }
  return math;
}

if (require.main === module) {
  const input = process.argv.slice(2).join(' ') || '$$\\begin{bmatrix}a&b\\\\c&d\\end{bmatrix}$$';
  console.log(JSON.stringify(findTeX(input), null, 2));
}

module.exports = { findTeX };
