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

// Global copy handler to modify behavior on/within mjx-container elements.
document.addEventListener('copy', function (event) {
  const selection = window.getSelection();
  if (!selection || selection.isCollapsed || !event.clipboardData) {
    return; // default action OK if selection is empty or unchangeable
  }
  const clipboardData = event.clipboardData;
  const range = selection.getRangeAt(0);

  // When start point is within a formula, expand to entire formula.
  const startContainer = closestMjxContainer(range.startContainer);
  if (startContainer) {
    range.setStartBefore(startContainer);
  }

  // Similarly, when end point is within a formula, expand to entire formula.
  const endContainer = closestMjxContainer(range.endContainer);
  if (endContainer) {
    range.setEndAfter(endContainer);
  }

  // Tag each live mjx-container intersecting the range with its original
  // TeX source (the same MathItem.math string MathJax's own "Copy Original"
  // menu command uses), so it survives into the cloned fragment below.
  const doc = window.MathJax && window.MathJax.startup && window.MathJax.startup.document;
  const taggedContainers = [];
  if (doc) {
    for (const math of doc.math) {
      const container = math.typesetRoot;
      if (container && range.intersectsNode(container)) {
        container.setAttribute('data-mjx-copy-tex', math.math.trim());
        taggedContainers.push(container);
      }
    }
  }

  const fragment = range.cloneContents();
  taggedContainers.forEach((container) => container.removeAttribute('data-mjx-copy-tex'));

  if (!fragment.querySelector('mjx-container')) {
    return; // default action OK if no math elements
  }

  const htmlContents = Array.prototype.map.call(fragment.childNodes,
    (el) => (el instanceof Text ? el.textContent : el.outerHTML)
  ).join('');

  // Preserve usual HTML copy/paste behavior.
  clipboardData.setData('text/html', htmlContents);
  // Rewrite plain-text version, replacing rendered math with its raw TeX source.
  clipboardData.setData('text/plain', mjxReplaceWithTex(fragment).textContent);
  // Prevent normal copy handling.
  event.preventDefault();
});
