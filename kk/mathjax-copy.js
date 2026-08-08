// Return closest <mjx-container> element containing node, or null if not found.
function closestMjxContainer(node) {
  const element = (node instanceof Element ? node : node.parentElement);
  return element && element.closest('mjx-container');
}

// Replace each <mjx-container> in fragment with its raw TeX source text
// (read from its data-mjx-copy-tex attribute, set just before cloning).
function mjxReplaceWithTex(fragment) {
  fragment.querySelectorAll('mjx-container').forEach((container) => {
    container.replaceWith(document.createTextNode(container.getAttribute('data-mjx-copy-tex') || ''));
  });
  return fragment;
}

// Extract rendered text so <br> and block boundaries remain line breaks.
function fragmentToPlainText(fragment) {
  const wrapper = document.createElement('div');
  wrapper.style.cssText = 'position:fixed;left:-100000px;top:0;width:max-content;';
  wrapper.appendChild(fragment);
  document.body.appendChild(wrapper);
  const text = wrapper.innerText;
  wrapper.remove();
  return text;
}

// Clone a range after tagging rendered formulas with their original TeX.
function cloneRangeWithMathJaxSource(range) {
  const sourceRange = range.cloneRange();
  const startContainer = closestMjxContainer(sourceRange.startContainer);
  if (startContainer) {
    sourceRange.setStartBefore(startContainer);
  }

  const endContainer = closestMjxContainer(sourceRange.endContainer);
  if (endContainer) {
    sourceRange.setEndAfter(endContainer);
  }

  const doc = window.MathJax && window.MathJax.startup && window.MathJax.startup.document;
  const taggedContainers = [];
  if (doc && doc.math) {
    try {
      for (const math of doc.math) {
        const container = math.typesetRoot;
        if (container && sourceRange.intersectsNode(container)) {
          container.setAttribute('data-mjx-copy-tex', math.start.delim + math.math.trim() + math.end.delim);
          taggedContainers.push(container);
        }
      }
    } catch(e) {}
  }

  const fragment = sourceRange.cloneContents();
  taggedContainers.forEach((container) => container.removeAttribute('data-mjx-copy-tex'));
  return fragment;
}

// Public serializer used by copy handling and selected-fragment quoting.
function rangeToPlainTextWithMathJax(range) {
  return fragmentToPlainText(mjxReplaceWithTex(cloneRangeWithMathJaxSource(range)));
}

// Global copy handler to modify behavior on/within mjx-container elements.
document.addEventListener('copy', function (event) {
  const selection = window.getSelection();
  if (!selection || selection.isCollapsed || !event.clipboardData) {
    return; // default action OK if selection is empty or unchangeable
  }
  const clipboardData = event.clipboardData;
  const range = selection.getRangeAt(0);
  const fragment = cloneRangeWithMathJaxSource(range);

  if (!fragment.querySelector('mjx-container')) {
    return; // default action OK if no math elements
  }

  const htmlContents = Array.prototype.map.call(fragment.childNodes,
    (el) => (el instanceof Text ? el.textContent : el.outerHTML)
  ).join('');

  // Preserve usual HTML copy/paste behavior.
  clipboardData.setData('text/html', htmlContents);
  // Rewrite plain-text version, replacing rendered math with its raw TeX source.
  clipboardData.setData('text/plain', fragmentToPlainText(mjxReplaceWithTex(fragment)));
  // Prevent normal copy handling.
  event.preventDefault();
});
