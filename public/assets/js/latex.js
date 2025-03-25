/**
 * Replaces LaTeX formulas by pictures using MathJax FindTeX function
 */

(function (w, d) {
  /**
 * Function to find TeX expressions within a string.
 * @param {string} text - The string to search for TeX expressions.
 * @param {Object} options - The configuration options.
 * @returns {Array} - An array of found TeX expressions.
 */
    function findTeX(text, options = {}) {
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
      let envIndex = 0;
      let subIndex = 0;
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
      options.inlineMath.forEach(delims => addPattern(startPatterns, delims, false));
      options.displayMath.forEach(delims => addPattern(startPatterns, delims, true));

      const parts = [];
      if (startPatterns.length) {
        parts.push(startPatterns.sort((a, b) => b.length - a.length).join('|'));
      }
      if (options.processEnvironments) {
        parts.push('\\\\begin\\s*\\{([^}]*)\\}');
        envIndex = parts.length;
      }
      if (options.processEscapes) {
        subPatterns.push('\\\\([\\\\$])');
      }
      if (options.processRefs) {
        subPatterns.push('(\\\\(?:eq)?ref\\s*\\{[^}]*\\})');
      }
      if (subPatterns.length) {
        parts.push(`(${subPatterns.join('|')})`);
        subIndex = parts.length;
      }

      const startRegex = new RegExp(parts.join('|'), 'g');

      // Function to find the end delimiter
      function findEnd(text, start, end) {
        const [close, display, pattern] = end;
        let i = pattern.lastIndex = start.index + start[0].length;
        let match;
        let braces = 0;
        while ((match = pattern.exec(text))) {
          if ((match[1] || match[0]) === close && braces === 0) {
            return {
              open: start[0],
              math: text.slice(i, match.index),
              close: match[0],
              display,
              startIndex: start.index,
              endIndex: match.index + match[0].length,
            };
          } else if (match[0] === '{') {
            braces++;
          } else if (match[0] === '}' && braces) {
            braces--;
          }
        }
        return null;
      }

      const math = [];
      let match;
      startRegex.lastIndex = 0;
      while ((match = startRegex.exec(text))) {
        let found;
        if (match[envIndex] !== undefined && envIndex) {
          const end = new RegExp(`\\\\end\\s*\\{${quotePattern(match[envIndex])}\\}`, 'g');
          found = findEnd(text, match, ['{' + match[envIndex] + '}', true, end]);
          if (found) {
            found.math = found.open + found.math + found.close;
            found.open = found.close = '';
          }
        } else if (match[subIndex] !== undefined && subIndex) {
          const mathStr = match[subIndex];
          const end = match.index + match[subIndex].length;
          found = {
            open: '',
            math: mathStr.length === 2 ? mathStr.substr(1) : mathStr,
            close: '',
            display: false,
            startIndex: match.index,
            endIndex: end,
          };
        } else {
          found = findEnd(text, match, endPatterns[match[0]]);
        }
        if (found) {
          math.push(found);
          startRegex.lastIndex = found.endIndex;
        }
      }

      return math;
    }
    var prtcl = location.protocol,
        ntwPath = '//i.upmath.me',
        url = (prtcl === 'http:' || prtcl === 'https:') ? ntwPath : 'http:' + ntwPath,
        im = d.implementation,
        ext = im && im.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1") ? 'svg' : 'png';

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
                } catch (e) {
                }
                if (top) {
                    poll();
                }
            }
            d[add](pre + 'DOMContentLoaded', init, !1);
            d[add](pre + 'readystatechange', init, !1);
            w[add](pre + 'load', init, !1);
        }
    })(function () {
        processTree(d.body);
    });

    var imgQueue = {}, aSizes = {};

    function trackLoading(eImg, path, isCentered) {
        if (!imgQueue[path]) {
            imgQueue[path] = [[], []];

            fetch(path)
                .then(function (resp) {
                    return resp.text();
                })
                .then(function (text) {
                    var m = text.match(/postMessage\((?:&quot;|")([\d\|\.\-eE]*)(?:&quot;|")/), s;

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
        eImg.style.opacity = '1';
        eImg.style.width = 'calc(var(--latex-zoom, 1)*' + x + 'pt)';
        eImg.style.height = 'calc(var(--latex-zoom, 1)*' + y + 'pt)';
        eImg.style.verticalAlign = (isCentered ? 'top' : 'calc(var(--latex-zoom, 1)*' + (-shift) + 'pt)');
    }

    function setSizes(path, shift, x, y) {
        aSizes[path] = [shift, x, y];
        for (var isCentered = 0; isCentered < 2; isCentered++) {
            var ao = imgQueue[path][isCentered], i = ao.length;

            for (; i--;) {
                setImgSize(ao[i], isCentered, shift, x, y);
            }
        }
    }

    function createImgNode(formula, isCentered) {
        var i = d.createElement('img'),
            path = url + '/' + ext + '/' + encodeURIComponent(formula);

        i.setAttribute('src', path);
        i.setAttribute('class', 'latex-' + ext);
        i.setAttribute('style', 'vertical-align:middle; border:0; opacity:0;');
        i.setAttribute('alt', formula);

        isCentered && (i.style.margin = '0 0 0 auto');

        try {
            trackLoading(i, path, isCentered);
        } catch (e) {
            i.style.opacity = '1';
        }

        return i;
    }

    var processTree = function (eItem) {
        var eNext = eItem.firstChild;

        // Function to process text nodes and replace TeX with images
        function processTextNode(textNode) {
            const mathItems = findTeX(textNode.nodeValue);

            let lastIndex = 0;
            mathItems.forEach(item => {
                // Create the image node for the found TeX
                let imgNode = createImgNode(item.math, item.display);

                if (item.display) {
                  const wrapperDiv = d.createElement('div');
                  wrapperDiv.setAttribute('align', 'center');
                  wrapperDiv.appendChild(imgNode);
                  imgNode = wrapperDiv;
                }
                // Insert the image node before the text node
                textNode.parentNode.insertBefore(imgNode, textNode);

                // Create a text node for the text before the TeX
                const beforeText = textNode.nodeValue.slice(lastIndex, item.startIndex);
                const beforeTextNode = d.createTextNode(beforeText);
                textNode.parentNode.insertBefore(beforeTextNode, imgNode);

                lastIndex = item.endIndex;
            });

            // Create a text node for the remaining text
            const remainingText = textNode.nodeValue.slice(lastIndex);
            const remainingTextNode = d.createTextNode(remainingText);
            textNode.parentNode.insertBefore(remainingTextNode, textNode);

            textNode.parentNode.removeChild(textNode);
        }

        // Process each node in the tree
        while (eNext) {
            var eCur = eNext, sNn = eCur.nodeName;
            eNext = eNext.nextSibling;

            if (eCur.nodeType === 1 && sNn !== 'SCRIPT' && sNn !== 'TEXTAREA' && sNn !== 'OBJECT') {
                processTree(eCur);
                continue;
            }

            if (eCur.nodeType === 3) {
                processTextNode(eCur);
            }
        }
    };

    w.S2Latex = {processTree: processTree};
})(window, document);