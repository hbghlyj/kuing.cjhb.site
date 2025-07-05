/**
 * Replaces LaTeX formulas by pictures using MathJax FindTeX function
 */
'use strict';
(function (w, d) {
  /**
   * Function to find TeX expressions within a string.
   * @param {string} text - The string to search for TeX expressions.
   * @param {Object} options - The configuration options.
   * @returns {range: string, math: Array} - The range text and an array of TeX expressions.
   */
  function findTeX(old_range, options = {}) {
    let text = old_range.toString();
    // Default options
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
    let envIndex = 0; //The index of the \begin...\end pattern in the regex match array
    let subIndex = 0; //The index of the \ref and escaped character patterns in the regex match array
    const subPatterns = [];

    // Function to quote a pattern for regex
    function quotePattern(pattern) {
      return pattern.replace(/([.*+?^${}()|\[\]\/\\])/g, '\\$1');
    }

    // Function to add patterns for a pair of delimiters
    function addPattern(starts, delims, display) {
      const [open, close] = delims;
      starts.push(quotePattern(open));
      endPatterns[open] = [close, display, new RegExp(`${quotePattern(close)}|\\\\(?:[a-zA-Z]|.)|[{}]`, 'g')];
    }

    // Add inline and display math patterns
    options.inlineMath.forEach(delims => addPattern(startPatterns, delims, 0));
    options.displayMath.forEach(delims => addPattern(startPatterns, delims, 1));

    const parts = [];
    if (startPatterns.length) {
      parts.push(startPatterns.sort((a, b) => b.length - a.length).join('|'));
    }
    if (options.processEnvironments) {
      envIndex = parts.length;
      parts.push('\\\\begin\\s*\\{([^}]*)\\}');
    }
    if (options.processEscapes) {
      subPatterns.push('\\\\[\\\\$]');
    }
    if (options.processRefs) {
      subPatterns.push('(\\\\(?:eq)?ref\\s*\\{[^}]*\\})');
    }
    if (subPatterns.length) {
      subIndex = parts.length;
      parts.push(`(${subPatterns.join('|')})`);
    }

    const startRegex = new RegExp(parts.join('|'), 'g');

    // Function to find the end delimiter
    function findEnd(old_range, start, end) {
      const [close, display, pattern] = end;
      let i = pattern.lastIndex = start.index + start[0].length;
      let match, found;
      let braces = 0;
      while (match = pattern.exec(text)) {
        if ((match[1] || match[0]) === close && braces === 0) {
          found = {
            open: start[0],
            math: text.slice(i, match.index),
            close: match[0],
            display,
            startIndex: start.index,
            endIndex: match.index + match[0].length,
          };
          found.range = shiftRangeStart(shiftRangeEnd(old_range, found.endIndex), found.startIndex);
          return found;
        } else if (match[0] === '{') {
          braces++;
        } else if (match[0] === '}' && braces) {
          braces--;
        }
      }
      return null;
    }

    const mathItem = [];
    let match, pending_startOffset = text.length;
    startRegex.lastIndex = 0;
    while (match = startRegex.exec(text)) {
      let found;
      if (envIndex && match[envIndex] !== undefined) {
        const end = new RegExp(`\\\\end\\s*(\\{${quotePattern(match[envIndex])}\\})`, 'g');
        found = findEnd(old_range, match, ['{' + match[envIndex] + '}', 1, end]);
        if (found) {
          const m = found.close.match(/\{(?:equation|align|gather|alignat|multline|flalign)(\*?)\}$/);
          if (m) {
            const group = m[1];
            if (group === '') {
              found.open = found.open.replace(/\{(.*?)\}$/, '{$1*}');
              found.close = found.close.replace(/\{(.*?)\}$/, '{$1*}');
            }
          }
          found.math = found.open + found.math + found.close;
        } else {
          // not found, so move \begin{env}... to the start of next text node
          pending_startOffset = match.index;
        }
      } else if (subIndex && match[subIndex] !== undefined) {
        const mathStr = match[subIndex];
        const end = match.index + mathStr.length;
        if (mathStr.length == 2){
          shiftRangeStart(shiftRangeEnd(old_range, match.index+1), match.index).deleteContents();
          text = old_range.toString();
        }else{
          found = {
            open: '',
            math: mathStr,
            close: '',
            display: 0,
            startIndex: match.index,
            endIndex: end,
          };
          found.range = shiftRangeStart(shiftRangeEnd(old_range, found.endIndex), found.startIndex);
        }
      } else {
        found = findEnd(old_range, match, endPatterns[match[0]]);
      }
      if (found) {
        mathItem.push(found);
        startRegex.lastIndex = found.endIndex;
      }
    }

    return [pending_startOffset, mathItem];
  }

  var prtcl = location.protocol,
    ntwPath = '//i.upmath.me',
    url = (prtcl === 'http:' || prtcl === 'https:') ? ntwPath : 'http:' + ntwPath,
    ext = typeof SVGElement !== 'undefined' ? 'svg' : 'png';

  (function (fn) {
    var done = !1,
      top = !0,
      root = d.documentElement,
      w3 = !!d.addEventListener,

      add = w3 ? 'addEventListener' : 'attachEvent',
      rem = w3 ? 'removeEventListener' : 'detachEvent',
      pre = w3 ? '' : 'on',

      init = function (e) {
        if (e.type === 'readystatechange' && d.readyState !== 'complete') {
          return;
        }
        (e.type === 'load' ? w : d)[rem](pre + e.type, init, false);
        if (!done && (done = !0)) {
          fn.call(w, e.type || e);
        }
      },

      poll = function () {
        try {
          root.doScroll('left');
        } catch (e) {
          setTimeout(poll, 50);
          return;
        }
        init('poll');
      };

    if (d.readyState === 'complete') {
      fn.call(w, 'lazy');
    } else {
      if (d.createEventObject && root.doScroll) {
        try {
          top = !w.frameElement;
        } catch (e) { }
        if (top) {
          poll();
        }
      }
      d[add](pre + 'DOMContentLoaded', init, !1);
      d[add](pre + 'readystatechange', init, !1);
      w[add](pre + 'load', init, !1);
    }
  })(function () {
    d.body.querySelectorAll('td.markdown-col').forEach(function (e) {
      processTree(e);
    });
  });

  var imgQueue = {},
    aSizes = {};

  function trackLoading(eImg, path, isCentered) {
    if (!imgQueue[path]) {
      imgQueue[path] = [[], []];

      fetch(path)
        .then(function (resp) {
          return resp.text();
        })
        .then(function (text) {
          var m = text.match(/postMessage\((?:&quot;|")([\d\|\.\-eE]*)(?:&quot;|")/),
            s;

          if (m && m[1]) {
            s = m[1].split('|');
            setSizes(path, s.shift(), s.shift(), s.shift());
          }
        });
    }
    if (!aSizes[path]) {
      imgQueue[path][isCentered].push(eImg);
    } else {
      setImgSize(eImg, isCentered, aSizes[path][0], aSizes[path][1], aSizes[path][2]);
    }
  }

  function setImgSize(eImg, isCentered, shift, x, y) {
    eImg.style.width = x + 'pt';
    eImg.style.height = y + 'pt';
    eImg.style.verticalAlign = isCentered ? 'top' : -shift + 'pt';
  }

  function setSizes(path, shift, x, y) {
    aSizes[path] = [shift, x, y];
    for (var isCentered = 0; isCentered < 2; isCentered++) {
      var ao = imgQueue[path][isCentered],
        i = ao.length;

      for (; i--;) {
        setImgSize(ao[i], isCentered, shift, x, y);
      }
    }
  }

  function createImgNode(formula, isCentered) {
    const i = d.createElement('img'),
      tagLabel = formula.includes('\\tag*')
        ? formula.match(/\\tag\*\{(.*?)\}/)[1]
        : formula.includes('\\tag')
          ? '(' + formula.match(/\\tag\{(.*?)\}/)[1] + ')'
          : '',
      cleanedFormula = formula.replace(/\\tag\{.*?\}/g, '').replace(/\\tag\*\{.*?\}/g, ''),
      path = url + '/' + ext + '/' + encodeURIComponent(cleanedFormula);

    i.setAttribute('src', path);
    i.setAttribute('class', 'latex-' + ext);
    i.setAttribute('style', 'vertical-align:middle; border:0;');
    i.setAttribute('alt', cleanedFormula);
    i.setAttribute('loading', 'lazy');

    isCentered && (i.style.margin = '0 0 0 auto');

    trackLoading(i, path, isCentered);

    return [i, tagLabel];
  }

  function shiftRangeStart(range, startIndex) {
    let remainingOffset = startIndex;
    const newRange = range.cloneRange();
    if (remainingOffset == 0) return newRange;
    let currentNode = range.startContainer;
    let currentOffset = range.startOffset;
    // Traverse nodes to find the new start container and offset
    while (currentNode && remainingOffset > 0) {
      while (currentNode.firstChild) {
        currentNode = currentNode.firstChild;
      }
      if (currentNode.nodeType === Node.TEXT_NODE && currentNode.textContent) {
        const textLength = currentNode.textContent.length;
        if (currentOffset + remainingOffset <= textLength) {
          newRange.setStart(currentNode, currentOffset + remainingOffset);
          return newRange;
        } else {
          remainingOffset -= (textLength - currentOffset);
          currentOffset = 0;
        }
      }

      // If no next sibling, move up the DOM tree
      while (!currentNode.nextSibling) {
        currentNode = currentNode.parentNode;
      }
      currentNode = currentNode.nextSibling;
    }

    throw new Error("New position is out of bounds.");
  }

  function shiftRangeEnd(range, endIndex) {
    let remainingOffset = range.toString().length - endIndex;
    const newRange = range.cloneRange();
    if (remainingOffset == 0) return newRange;
    let currentNode = range.endContainer;
    let currentOffset = range.endOffset;

    // Traverse nodes to find the new end container and offset
    while (currentNode && remainingOffset > 0) {
      while (currentNode.lastChild) {
        currentNode = currentNode.lastChild;
      }
      currentOffset = currentNode.data.length;
      if (currentNode.nodeType === Node.TEXT_NODE && currentNode.textContent) {
        if (currentOffset >= remainingOffset) {
          newRange.setEnd(currentNode, currentOffset - remainingOffset);
          return newRange;
        } else {
          remainingOffset -= currentOffset;
        }
      }

      // If no previous sibling, move up the DOM tree
      while (!currentNode.previousSibling) {
        currentNode = currentNode.parentNode;
      }
      currentNode = currentNode.previousSibling;
    }

    throw new Error("New position is out of bounds.");
  }

  function convertToList(frag,ol) {
    let currentLi = null;
    // Iterate through child nodes to find and process \item
    Array.from(frag.childNodes).forEach(child => {
      if (child.nodeType === Node.TEXT_NODE && child.textContent) {
        const items = child.textContent.split(/\\item\s+/);
        items.forEach((item, index) => {
          if (index == 0) {
            currentLi && currentLi.appendChild(d.createTextNode(item));
          } else {
            currentLi = d.createElement("li");
            currentLi.textContent = item;
            ol.appendChild(currentLi);
          }
        });
      } else {
        currentLi.appendChild(child);
      }
    });
  }
  // Function to process text nodes and replace TeX with images
  function processRange(old_range) {
    let [pending_startOffset, mathItem] = findTeX(old_range),
      pending = pending_startOffset == old_range.toString().length ? null : shiftRangeStart(old_range, pending_startOffset);
    mathItem.forEach(item => {
      // Check for other environments
      const environments = [
        'definition',
        'theorem',
        'lemma',
        'corollary',
        'proposition',
        'proof'
      ];

      if (environments.includes(item.open.replace('\\begin{', '').replace('}', ''))) {
        // Extract the content inside the environment
        const envName = item.open.replace('\\begin{', '').replace('}', '');
        // Convert the environment to an HTML div with a class
        const div = d.createElement('div');
        div.className = envName == 'definition' ? 'alert alert-success' : envName == 'theorem' ? 'alert alert-primary' :
            envName == 'proof' ? 'alert border bg-white' : 'alert alert-info';
        {
          const helperRange = item.range.cloneRange();
          helperRange.setStart(item.range.startContainer, item.range.startOffset + item.open.length);
          helperRange.setEnd(item.range.endContainer, item.range.endOffset - item.close.length);
          div.append(helperRange.extractContents());
        }
        item.range.deleteContents();
        item.range.insertNode(div);
        // Add a title span
        const strong = d.createElement('strong');
        strong.innerHTML = envName.charAt(0).toUpperCase() + envName.slice(1);
        div.prepend(strong);
        processTree(div);
      } else if (item.open === '\\begin{enumerate}' || item.open === '\\begin{itemize}') {
        const ol = d.createElement(item.open === '\\begin{enumerate}' ? 'ol' : 'ul');
        {
          const helperRange = item.range.cloneRange();
          helperRange.setStart(item.range.startContainer, item.range.startOffset + item.open.length);
          helperRange.setEnd(item.range.endContainer, item.range.endOffset - item.close.length);
          convertToList(helperRange.extractContents(),ol);
        }
        item.range.deleteContents();
        item.range.insertNode(ol);
        processTree(ol);
      } else if (item.startIndex <= pending_startOffset) {
        // Create the image node for the found TeX
        const [imgNode, tagLabel] = createImgNode(item.math, item.display);

        const wrapperDiv = item.display ? d.createElement('div') : imgNode;
        if (item.display) {
          wrapperDiv.setAttribute('align', 'center');
          wrapperDiv.appendChild(imgNode);
          if (tagLabel) {
            const labelSpan = d.createElement('span');
            labelSpan.setAttribute('style', 'float: right; margin-left: 10px;');
            labelSpan.textContent = tagLabel;
            wrapperDiv.appendChild(labelSpan);
          }
        }
        // Insert the image node before the text node
        item.range.insertNode(wrapperDiv);
        item.range.setStartAfter(wrapperDiv);
        item.range.deleteContents();
      }
    });
    return pending;
  }
  var pending;
  function processTree (eItem) {
    var eNext = eItem.firstChild;
    // Process each node in the tree
    while (eNext) {
      var eCur = eNext,
        sNn = eCur.nodeName;
      eNext = eNext.nextSibling;
      const excludedTags = ['SCRIPT', 'TEXTAREA', 'OBJECT', 'CODE', 'PRE'];
      if (eCur.nodeType === 1 && !excludedTags.includes(sNn)) {
        pending = processTree(eCur);
      } else if (eCur.nodeType === 3) {
        const range = d.createRange();
        range.selectNodeContents(eCur);
        if(pending){
          range.setStart(pending.startContainer,pending.startOffset);
        }
        pending = processRange(range);
    }
  }
  return pending;
};
}) (window, document);