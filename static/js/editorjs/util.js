function mergeObjects(target, source) {
	if (!target || typeof target !== 'object') target = {};
	if (!source || typeof source !== 'object') return target;

	for (let key of Object.keys(source)) {
		if (key === '__proto__' || key === 'constructor' || key === 'prototype') continue;

		if (target[key] && typeof target[key] === 'object' &&
		    source[key] && typeof source[key] === 'object') {
			mergeObjects(target[key], source[key]);
		} else {
			target[key] = source[key];
		}
	}
	return target;
}