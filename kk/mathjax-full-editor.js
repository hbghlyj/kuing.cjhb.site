function initFullEditorMathEntry() {
	var textarea = document.querySelector('.edt textarea[id$="_textarea"]');
	if (!textarea || !textarea.id.endsWith('_textarea')) return;

	var editorid = textarea.id.slice(0, -'_textarea'.length);
	var button = document.getElementById(editorid + '_button');
	if (!button || document.getElementById('post_math_button')) return;

	var labels = window.MATH_EDITOR_LABELS || {};
	var entryGroup = document.createElement('div');
	entryGroup.id = 'post_math_button';
	entryGroup.className = 'b2r';
	var entry = document.createElement('a');
	entry.href = 'javascript:;';
	entry.className = 'mathfx';
	entry.innerHTML = '<i>f</i>x';
	entry.title = labels.title || 'Insert/Edit Math';
	entry.onclick = function(event) {
		if (event) event.preventDefault();
		showFullEditorMathDialog(labels, getSelectedMathEquation());
		return false;
	};
	entryGroup.appendChild(entry);
	button.insertBefore(entryGroup, button.firstChild);
	renderMathEditorContent();
}

function escapeMathHtml(value) {
	return String(value).replace(/[&<>"']/g, function(character) {
		return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
	});
}

function getMathDialogValue(value) {
	var display = /^\$\$([\s\S]*)\$\$$/.exec(value);
	if (display) return { equation: display[1], wrap: 'display' };
	var inline = /^\$([^$][\s\S]*?)\$$/.exec(value);
	if (inline) return { equation: inline[1], wrap: 'inline' };
	return { equation: value, wrap: 'inline' };
}

function getSelectedMathEquation() {
	return wysiwyg && editdoc ? editdoc.querySelector('.math-editor-rendered.math-editor-selected') : null;
}

function createMathEditorCaret() {
	var caret = editdoc.createElement('span');
	caret.className = 'math-editor-caret';
	caret.setAttribute('data-math-caret', '1');
	caret.setAttribute('aria-hidden', 'true');
	caret.textContent = '\u200B';
	return caret;
}

function ensureMathEditorCarets(rendered) {
	if (!rendered.parentNode) return;
	if (!rendered.previousSibling || rendered.previousSibling.nodeType !== 1 || !rendered.previousSibling.hasAttribute('data-math-caret')) {
		rendered.parentNode.insertBefore(createMathEditorCaret(), rendered);
	}
	if (!rendered.nextSibling || rendered.nextSibling.nodeType !== 1 || !rendered.nextSibling.hasAttribute('data-math-caret')) {
		rendered.parentNode.insertBefore(createMathEditorCaret(), rendered.nextSibling);
	}
}

function placeMathEditorCaret(rendered, after) {
	ensureMathEditorCarets(rendered);
	var caret = after ? rendered.nextSibling : rendered.previousSibling;
	if (!caret || !caret.firstChild) return;
	var range = editdoc.createRange();
	range.setStart(caret.firstChild, after ? caret.firstChild.nodeValue.length : 0);
	range.collapse(true);
	var selection = editwin.getSelection();
	selection.removeAllRanges();
	selection.addRange(range);
}

function selectMathEquation(rendered) {
	if (!editdoc) return;
	clearMathEquationSelection();
	rendered.classList.add('math-editor-selected');
	var container = rendered.querySelector('mjx-container');
	if (container) container.classList.add('math-editor-selected');
	var button = document.querySelector('#post_math_button .mathfx');
	if (button) button.classList.add('hover');
	if (typeof setEditorTip === 'function') {
		setEditorTip((window.MATH_EDITOR_LABELS && window.MATH_EDITOR_LABELS.selected) || 'Double-click the formula to edit it. Press Escape to deselect.');
	}
}

function clearMathEquationSelection() {
	if (!editdoc) return;
	var selected = editdoc.querySelectorAll('.math-editor-rendered.math-editor-selected, mjx-container.math-editor-selected');
	for (var i = 0; i < selected.length; i++) {
		selected[i].classList.remove('math-editor-selected');
	}
	if (selected.length) {
		var button = document.querySelector('#post_math_button .mathfx');
		if (button) button.classList.remove('hover');
		if (typeof setEditorTip === 'function') setEditorTip('');
	}
}

function initMathEditorSelection() {
	if (!editdoc || editdoc._mathEditorSelectionInitialized) return;
	editdoc._mathEditorSelectionInitialized = true;
	editdoc.addEventListener('click', function(event) {
		var rendered = event.target.closest ? event.target.closest('.math-editor-rendered') : null;
		if (!rendered) {
			clearMathEquationSelection();
		} else if (!rendered._mathEditorInitialized) {
			event.preventDefault();
			selectMathEquation(rendered);
			var bounds = rendered.getBoundingClientRect();
			placeMathEditorCaret(rendered, event.clientX >= bounds.left + bounds.width / 2);
		}
	});
	editdoc.addEventListener('dblclick', function(event) {
		var rendered = event.target.closest ? event.target.closest('.math-editor-rendered') : null;
		if (!rendered || rendered._mathEditorInitialized) return;
		event.preventDefault();
		showFullEditorMathDialog(window.MATH_EDITOR_LABELS || {}, rendered);
	});
	editdoc.addEventListener('keydown', function(event) {
		if (event.key === 'Escape') clearMathEquationSelection();
	});
}

function syncMathJaxEditorStyles() {
	if (!editdoc || !editdoc.head) return;

	var sources = document.querySelectorAll('style[id^="MJX-"], link[id^="MJX-"]');
	for (var i = 0; i < sources.length; i++) {
		var source = sources[i];
		var id = 'math_editor_' + source.id;
		var target = editdoc.getElementById(id);
		if (!target) {
			target = editdoc.createElement(source.tagName.toLowerCase());
			target.id = id;
			editdoc.head.appendChild(target);
		}
		if (source.tagName === 'LINK') {
			target.rel = source.rel;
			target.href = source.href;
		} else {
			var cssText = source.textContent;
			if (source.sheet && source.sheet.cssRules) {
				cssText = Array.from(source.sheet.cssRules, function(rule) { return rule.cssText; }).join('\n');
			}
			target.textContent = cssText;
		}
	}
}

var pendingMathEditorEquations = [];
var mathEditorLoaderListening = false;
var mathEditorLoaderTimer = null;
var mathEditorLoaderAttempts = 0;

function flushPendingMathEditorEquations() {
	if (typeof MathJax === 'undefined' || typeof MathJax.typeset !== 'function') return;
	mathEditorLoaderListening = false;
	mathEditorLoaderAttempts = 0;
	if (mathEditorLoaderTimer) {
		clearTimeout(mathEditorLoaderTimer);
		mathEditorLoaderTimer = null;
	}
	var pending = pendingMathEditorEquations;
	pendingMathEditorEquations = [];
	for (var i = 0; i < pending.length; i++) {
		if (pending[i].rendered.isConnected) renderMathEquation(pending[i].rendered, pending[i].math);
	}
}

function waitForMathEditorLoader() {
	if (typeof MathJax !== 'undefined' && typeof MathJax.typeset === 'function') {
		flushPendingMathEditorEquations();
		return;
	}
	if (++mathEditorLoaderAttempts >= 600) {
		mathEditorLoaderListening = false;
		mathEditorLoaderAttempts = 0;
		mathEditorLoaderTimer = null;
		return;
	}
	mathEditorLoaderTimer = setTimeout(waitForMathEditorLoader, 100);
}

function queueMathEditorEquation(rendered, math) {
	var queued = false;
	for (var i = 0; i < pendingMathEditorEquations.length; i++) {
		if (pendingMathEditorEquations[i].rendered === rendered) {
			pendingMathEditorEquations[i].math = math;
			queued = true;
			break;
		}
	}
	if (!queued) pendingMathEditorEquations.push({ rendered: rendered, math: math });
	if (mathEditorLoaderListening) return;
	mathEditorLoaderListening = true;
	var loader = document.querySelector('script[src*="/mathjax@4/tex-chtml.js"]');
	if (loader) loader.addEventListener('load', flushPendingMathEditorEquations, { once: true });
	window.addEventListener('load', flushPendingMathEditorEquations, { once: true });
	waitForMathEditorLoader();
}

function removeMathEditorDisplayBreaks(rendered) {
	if (!rendered.querySelector('mjx-container[display="true"]')) return;
	for (var direction = -1; direction <= 1; direction += 2) {
		var sibling = direction < 0 ? rendered.previousSibling : rendered.nextSibling;
		while (sibling && ((sibling.nodeType === 1 && sibling.hasAttribute('data-math-caret')) || (sibling.nodeType === 3 && !sibling.nodeValue.trim()))) {
			sibling = direction < 0 ? sibling.previousSibling : sibling.nextSibling;
		}
		if (sibling && sibling.nodeType === 1 && sibling.matches('br')) sibling.remove();
	}
}

function renderMathEquation(rendered, math) {
	ensureMathEditorCarets(rendered);
	if (typeof MathJax !== 'undefined' && typeof MathJax.typesetClear === 'function') MathJax.typesetClear([rendered]);
	rendered.setAttribute('data-math-source', math);
	rendered.textContent = math;
	if (typeof MathJax === 'undefined' || typeof MathJax.typeset !== 'function') {
		queueMathEditorEquation(rendered, math);
		return;
	}
	if (!rendered.isConnected) return;
	var host = document.createElement('span');
	host.className = 'tex2jax_process';
	host.style.cssText = 'position:fixed;left:-100000px;top:0;visibility:hidden;';
	var sourceNode = document.createElement('span');
	sourceNode.textContent = math;
	host.appendChild(sourceNode);
	document.body.appendChild(host);
	try {
		MathJax.typeset([host]);
		if (!rendered.isConnected) return;
		rendered.textContent = '';
		while (sourceNode.firstChild) {
			rendered.appendChild(editdoc.importNode(sourceNode.firstChild, true));
			sourceNode.firstChild.remove();
		}
		rendered._mathEditorTypesetRetries = 0;
		syncMathJaxEditorStyles();
		removeMathEditorDisplayBreaks(rendered);
		if (rendered.classList.contains('math-editor-selected')) selectMathEquation(rendered);
	} catch (error) {
		var retries = rendered._mathEditorTypesetRetries || 0;
		if (rendered.isConnected && retries < 50) {
			rendered._mathEditorTypesetRetries = retries + 1;
			setTimeout(function() { renderMathEquation(rendered, math); }, 100);
		}
	} finally {
		if (typeof MathJax.typesetClear === 'function') MathJax.typesetClear([host]);
		host.remove();
	}
}

function initRenderedMathEquation(rendered) {
	if (rendered._mathEditorInitialized) return;
	rendered._mathEditorInitialized = true;
	rendered.addEventListener('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		selectMathEquation(rendered);
		var bounds = rendered.getBoundingClientRect();
		placeMathEditorCaret(rendered, event.clientX >= bounds.left + bounds.width / 2);
	});
	rendered.addEventListener('dblclick', function(event) {
		event.preventDefault();
		event.stopPropagation();
		showFullEditorMathDialog(window.MATH_EDITOR_LABELS || {}, rendered);
	});
}

function isEscapedMathDelimiter(text, index) {
	var slashCount = 0;
	while (index > 0 && text[--index] === '\\') slashCount++;
	return slashCount % 2 === 1;
}

function findMathDelimiter(text, start, opening, closing, standaloneDollar) {
	var index = text.indexOf(closing, start + opening.length);
	while (index !== -1) {
		if (!isEscapedMathDelimiter(text, index) && (!standaloneDollar || (text[index - 1] !== '$' && text[index + 1] !== '$'))) return index + closing.length;
		index = text.indexOf(closing, index + closing.length);
	}
	return -1;
}

function findMathRanges(text) {
	var ranges = [];
	for (var index = 0; index < text.length; index++) {
		if (isEscapedMathDelimiter(text, index)) continue;
		var end = -1;
		if (text.slice(index, index + 2) === '$$') {
			end = findMathDelimiter(text, index, '$$', '$$');
		} else if (text[index] === '$' && text[index - 1] !== '$' && text[index + 1] !== '$') {
			end = findMathDelimiter(text, index, '$', '$', true);
		} else if (text.slice(index, index + 2) === '\\[') {
			end = findMathDelimiter(text, index, '\\[', '\\]');
		} else if (text.slice(index, index + 2) === '\\(') {
			end = findMathDelimiter(text, index, '\\(', '\\)');
		} else if (text.slice(index, index + 7) === '\\begin{') {
			var environment = /^\\begin\{([A-Za-z0-9*]+)\}/.exec(text.slice(index));
			if (environment) end = findMathDelimiter(text, index, environment[0], '\\end{' + environment[1] + '}');
		}
		if (end !== -1) {
			ranges.push({ start: index, end: end });
			index = end - 1;
		}
	}
	return ranges;
}

function renderMathEditorContent() {
	if (!wysiwyg || !editdoc || !editdoc.body) return;
	initMathEditorSelection();
	var renderedEquations = editdoc.querySelectorAll('.math-editor-rendered');
	for (var renderedIndex = 0; renderedIndex < renderedEquations.length; renderedIndex++) {
		var existing = renderedEquations[renderedIndex];
		initRenderedMathEquation(existing);
		ensureMathEditorCarets(existing);
		if (!existing.querySelector('mjx-container')) {
			var source = existing.getAttribute('data-math-source') || existing.textContent;
			if (source) renderMathEquation(existing, source);
		}
	}

	var nodes = [];
	var walker = editdoc.createTreeWalker(editdoc.body, 4);
	var node;
	while ((node = walker.nextNode())) {
		if (node.parentNode.closest('.math-editor-rendered, code, pre, script, style')) continue;
		if (findMathRanges(node.nodeValue).length) nodes.push(node);
	}

	for (var i = 0; i < nodes.length; i++) {
		var text = nodes[i].nodeValue;
		var ranges = findMathRanges(text);
		var fragment = editdoc.createDocumentFragment();
		var formulas = [];
		var index = 0;
		for (var rangeIndex = 0; rangeIndex < ranges.length; rangeIndex++) {
			var range = ranges[rangeIndex];
			if (range.start > index) fragment.appendChild(editdoc.createTextNode(text.slice(index, range.start)));
			var math = text.slice(range.start, range.end);
			var rendered = editdoc.createElement('span');
			rendered.className = 'math-editor-rendered';
			rendered.setAttribute('data-math-source', math);
			rendered.setAttribute('contenteditable', 'false');
			initRenderedMathEquation(rendered);
			fragment.appendChild(rendered);
			formulas.push({ rendered: rendered, math: math });
			index = range.end;
		}
		if (index < text.length) fragment.appendChild(editdoc.createTextNode(text.slice(index)));
		nodes[i].parentNode.replaceChild(fragment, nodes[i]);
		for (var j = 0; j < formulas.length; j++) {
			renderMathEquation(formulas[j].rendered, formulas[j].math);
		}
	}
}

function insertMathEquation(math) {
	if (!wysiwyg || !editdoc) {
		insertText(math, false);
		return;
	}

	var id = 'math_editor_render_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
	var formula = '<span id="' + id + '" class="math-editor-rendered" data-math-source="' + escapeMathHtml(math) + '" contenteditable="false">' + escapeMathHtml(math) + '</span>';
	insertText(formula, false);
	var rendered = editdoc.getElementById(id);
	if (!rendered) return;
	rendered.removeAttribute('id');
	initRenderedMathEquation(rendered);
	renderMathEquation(rendered, math);
}

var mathSymbolRecentKey = 'Discuz_math_symbol_recent';
var mathSymbolRecentLimit = 10;
var mathSymbolCategories = [
	{ key: 'greek', preview: 'α β γ', symbols: ['\\alpha', '\\beta', '\\gamma', '\\delta', '\\epsilon', '\\varepsilon', '\\zeta', '\\eta', '\\theta', '\\vartheta', '\\iota', '\\kappa', '\\lambda', '\\mu', '\\nu', '\\xi', '\\pi', '\\varpi', '\\rho', '\\sigma', '\\tau', '\\upsilon', '\\phi', '\\varphi', '\\chi', '\\psi', '\\omega', '\\Gamma', '\\Delta', '\\Theta', '\\Lambda', '\\Xi', '\\Pi', '\\Sigma', '\\Upsilon', '\\Phi', '\\Psi', '\\Omega'] },
	{ key: 'operators', preview: '∑ ∫ ×', symbols: ['+', '-', '\\pm', '\\mp', '\\times', '\\div', '\\cdot', '\\ast', '\\star', '\\circ', '\\bullet', '\\oplus', '\\ominus', '\\otimes', '\\sum', '\\prod', '\\coprod', '\\int', '\\iint', '\\iiint', '\\oint', '\\partial', '\\nabla', '\\infty'] },
	{ key: 'relations', preview: '= ≈ ≤', symbols: ['=', '\\ne', '\\approx', '\\equiv', '\\sim', '\\simeq', '\\cong', '<', '>', '\\leqslant', '\\geqslant', '\\ll', '\\gg', '\\propto', '\\in', '\\notin', '\\ni', '\\subset', '\\subseteq', '\\supset', '\\supseteq', '\\perp', '\\parallel', '\\mid', '\\nmid'] },
	{ key: 'arrows', preview: '→ ⇒ ↦', symbols: ['\\leftarrow', '\\rightarrow', '\\leftrightarrow', '\\Leftarrow', '\\Rightarrow', '\\Leftrightarrow', '\\mapsto', '\\longmapsto', '\\hookleftarrow', '\\hookrightarrow', '\\uparrow', '\\downarrow', '\\updownarrow', '\\nearrow', '\\searrow', '\\swarrow', '\\nwarrow'] },
	{ key: 'logic', preview: '∀ ∃ ∈', symbols: ['\\forall', '\\exists', '\\nexists', '\\neg', '\\land', '\\lor', '\\implies', '\\iff', '\\therefore', '\\because', '\\emptyset', '\\mathbb{N}', '\\mathbb{Z}', '\\mathbb{Q}', '\\mathbb{R}', '\\mathbb{C}', '\\cup', '\\cap', '\\setminus'] },
	{ key: 'geometry', preview: '∠ △ ⟂', symbols: ['\\angle', '\\measuredangle', '\\triangle', '\\square', '\\diamond', '\\perp', '\\parallel', '\\cong', '\\sim', '\\odot', '\\circ', '^\\circ', '\\overline{}', '\\vec{}'] }
];

function getMathSymbolCatalog() {
	var catalog = {};
	for (var i = 0; i < mathSymbolCategories.length; i++) {
		for (var j = 0; j < mathSymbolCategories[i].symbols.length; j++) catalog[mathSymbolCategories[i].symbols[j]] = true;
	}
	return catalog;
}

function loadRecentMathSymbols() {
	var catalog = getMathSymbolCatalog();
	try {
		var stored = JSON.parse(localStorage.getItem(mathSymbolRecentKey) || '[]');
		if (!Array.isArray(stored)) return [];
		return stored.filter(function(symbol) { return typeof symbol === 'string' && catalog[symbol]; }).slice(-mathSymbolRecentLimit);
	} catch (error) {
		return [];
	}
}

function saveRecentMathSymbols(symbols) {
	try {
		localStorage.setItem(mathSymbolRecentKey, JSON.stringify(symbols));
	} catch (error) {}
}

function insertMathDialogSymbol(equation, symbol) {
	var start = equation.selectionStart;
	var end = equation.selectionEnd;
	if (typeof start !== 'number' || typeof end !== 'number') start = end = equation.value.length;
	equation.value = equation.value.slice(0, start) + symbol + equation.value.slice(end);
	equation.focus();
	equation.setSelectionRange(start + symbol.length, start + symbol.length);
	equation.dispatchEvent(new Event('input', { bubbles: true }));
}

function typesetMathSymbolContainer(container) {
	if (typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function') return;
	MathJax.typesetPromise([container]).catch(function() {});
}

function createMathSymbolButton(symbol, equation, onInsert) {
	var button = document.createElement('button');
	button.type = 'button';
	button.className = 'math-symbol-cell';
	button.title = symbol;
	button.textContent = '$' + symbol + '$';
	button.addEventListener('click', function(event) {
		event.preventDefault();
		insertMathDialogSymbol(equation, symbol);
		onInsert(symbol);
	});
	return button;
}

function initMathSymbolPicker(container, equation, labels) {
	var recentSymbols = loadRecentMathSymbols();
	var initialCategory = null;
	var initialCategoryButton = null;
	var picker = document.createElement('div');
	picker.className = 'math-symbol-picker';
	var toggle = document.createElement('button');
	toggle.type = 'button';
	toggle.className = 'math-symbol-toggle';
	toggle.setAttribute('aria-expanded', 'false');
	toggle.innerHTML = '<span>' + escapeMathHtml(labels.symbols || 'Symbols') + '</span><span aria-hidden="true">▾</span>';
	var menu = document.createElement('div');
	menu.className = 'math-symbol-menu';
	var categories = document.createElement('div');
	categories.className = 'math-symbol-categories';
	var grid = document.createElement('div');
	grid.className = 'math-symbol-grid';
	grid.id = 'math_symbol_grid';
	menu.appendChild(categories);
	menu.appendChild(grid);
	picker.appendChild(toggle);
	picker.appendChild(menu);
	container.appendChild(picker);

	var recentLabel = document.createElement('div');
	recentLabel.className = 'math-symbol-recent-label';
	recentLabel.textContent = labels.recent || 'Recently used';
	var recent = document.createElement('div');
	recent.className = 'math-symbol-recent';
	container.appendChild(recentLabel);
	container.appendChild(recent);

	var closePicker = function() {
		picker.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
	};
	var rememberSymbol = function(symbol) {
		if (recentSymbols.length >= mathSymbolRecentLimit) recentSymbols.shift();
		recentSymbols.push(symbol);
		saveRecentMathSymbols(recentSymbols);
		renderRecent();
		closePicker();
	};
	var renderRecent = function() {
		if (typeof MathJax !== 'undefined' && typeof MathJax.typesetClear === 'function') MathJax.typesetClear([recent]);
		recent.replaceChildren();
		for (var i = 0; i < mathSymbolRecentLimit; i++) {
			if (recentSymbols[i]) {
				recent.appendChild(createMathSymbolButton(recentSymbols[i], equation, rememberSymbol));
			} else {
				var empty = document.createElement('button');
				empty.type = 'button';
				empty.className = 'math-symbol-cell is-empty';
				empty.disabled = true;
				recent.appendChild(empty);
			}
		}
		typesetMathSymbolContainer(recent);
	};
	var selectCategory = function(category, categoryButton) {
		var active = categories.querySelector('.is-active');
		if (active) active.classList.remove('is-active');
		categoryButton.classList.add('is-active');
		if (typeof MathJax !== 'undefined' && typeof MathJax.typesetClear === 'function') MathJax.typesetClear([grid]);
		grid.replaceChildren();
		for (var i = 0; i < category.symbols.length; i++) grid.appendChild(createMathSymbolButton(category.symbols[i], equation, rememberSymbol));
		typesetMathSymbolContainer(grid);
	};

	for (var i = 0; i < mathSymbolCategories.length; i++) {
		(function(category, index) {
			var categoryButton = document.createElement('button');
			categoryButton.type = 'button';
			categoryButton.className = 'math-symbol-category';
			categoryButton.setAttribute('aria-controls', grid.id);
			var name = document.createElement('span');
			name.className = 'math-symbol-category-name';
			name.textContent = labels[category.key] || category.key;
			var preview = document.createElement('span');
			preview.className = 'math-symbol-category-preview';
			preview.textContent = category.preview;
			var chevron = document.createElement('span');
			chevron.className = 'math-symbol-category-chevron';
			chevron.setAttribute('aria-hidden', 'true');
			chevron.textContent = '›';
			categoryButton.appendChild(name);
			categoryButton.appendChild(preview);
			categoryButton.appendChild(chevron);
			categoryButton.addEventListener('mouseenter', function() { selectCategory(category, categoryButton); });
			categoryButton.addEventListener('focus', function() { selectCategory(category, categoryButton); });
			categoryButton.addEventListener('click', function(event) {
				event.preventDefault();
				selectCategory(category, categoryButton);
			});
			categories.appendChild(categoryButton);
			if (index === 0) {
				initialCategory = category;
				initialCategoryButton = categoryButton;
			}
		})(mathSymbolCategories[i], i);
	}

	toggle.addEventListener('click', function(event) {
		event.preventDefault();
		var open = !picker.classList.contains('is-open');
		picker.classList.toggle('is-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open && !grid.children.length && initialCategory) selectCategory(initialCategory, initialCategoryButton);
	});
	if (!document._mathSymbolPickerOutsideClick) {
		document._mathSymbolPickerOutsideClick = true;
		document.addEventListener('click', function(event) {
			var openPicker = document.querySelector('.math-symbol-picker.is-open');
			if (openPicker && !openPicker.contains(event.target)) {
				openPicker.classList.remove('is-open');
				var openToggle = openPicker.querySelector('.math-symbol-toggle');
				if (openToggle) openToggle.setAttribute('aria-expanded', 'false');
			}
		});
	}
	renderRecent();
}

function showFullEditorMathDialog(labels, rendered) {
	var selected = getMathDialogValue(rendered ? rendered.getAttribute('data-math-source') : (getSel() || ''));
	var title = labels.title || 'Insert/Edit Math';
	var equationLabel = labels.equation || 'Math equation';
	var wrapLabel = labels.wrap || 'Text wrap';
	var inlineLabel = (labels.inline || 'Inline') + ' ($...$)';
	var displayLabel = (labels.display || 'Display') + ' ($$...$$)';
	var content = '<div class="math-editor-dialog">' +
		'<p><label for="math_editor_equation">' + escapeMathHtml(equationLabel) + '</label>' +
		'<textarea id="math_editor_equation" class="pt" rows="8"></textarea></p>' +
		'<div id="math_symbol_tools" class="math-symbol-tools"></div>' +
		'<p><label for="math_editor_wrap">' + escapeMathHtml(wrapLabel) + '</label>' +
		'<select id="math_editor_wrap"><option value="inline">' + escapeMathHtml(inlineLabel) + '</option>' +
		'<option value="display">' + escapeMathHtml(displayLabel) + '</option></select></p>' +
		'<div id="math_editor_preview" class="math-editor-preview" aria-live="polite"></div>' +
		'</div>';

	showDialog(content, 'confirm', title, function() {
		var equation = document.getElementById('math_editor_equation');
		var wrap = document.getElementById('math_editor_wrap');
		if (!equation || !wrap || equation.value === '') return;
		var math = wrap.value === 'display' ? '$$' + equation.value + '$$' : '$' + equation.value + '$';
		if (rendered) {
			renderMathEquation(rendered, math);
		} else {
			insertMathEquation(math);
		}
	}, 1, null, '', labels.save || 'Save', labels.cancel || 'Cancel');

	setTimeout(function() {
		var equation = document.getElementById('math_editor_equation');
		var wrap = document.getElementById('math_editor_wrap');
		var preview = document.getElementById('math_editor_preview');
		var symbolTools = document.getElementById('math_symbol_tools');
		if (!equation || !wrap || !preview || !symbolTools) return;
		var previewVersion = 0;
		var updatePreview = function() {
			var math = wrap.value === 'display' ? '$$' + equation.value + '$$' : '$' + equation.value + '$';
			preview.textContent = math;
			if (typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function') return;
			if (typeof MathJax.typesetClear === 'function') MathJax.typesetClear([preview]);
			var version = ++previewVersion;
			MathJax.typesetPromise([preview]).catch(function() {
				if (version === previewVersion) preview.textContent = math;
			});
		};
		equation.value = selected.equation;
		wrap.value = selected.wrap;
		initMathSymbolPicker(symbolTools, equation, labels);
		equation.addEventListener('input', updatePreview);
		wrap.addEventListener('change', updatePreview);
		updatePreview();
		equation.focus();
		equation.select();
	}, 0);
}

initFullEditorMathEntry();
window.renderMathEditorContent = renderMathEditorContent;
document.addEventListener('discuz:editor-mode-changed', function(event) {
	if (!event.detail || !event.detail.wysiwyg) return;
	requestAnimationFrame(renderMathEditorContent);
});
// The editor may have initialized before this deferred script exposes the renderer.
if (typeof wysiwyg !== 'undefined' && typeof editdoc !== 'undefined') renderMathEditorContent();
