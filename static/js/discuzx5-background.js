(function() {
	function newSVGElem(type, attrs) {
		var el = document.createElementNS("http://www.w3.org/2000/svg", type);
		for(var k in attrs) { el.setAttribute(k, attrs[k]); }
		return el;
	}
	var size = 1024;
	var outputWidth = Math.max(size, Math.ceil(window.innerWidth || size));
	var freqX = 2 / size;
	var freqY = 5 / size;
	var seed = Math.floor(Math.random() * 255);

	var oSvg = newSVGElem("svg", { xmlns: "http://www.w3.org/2000/svg", width: size, height: size, viewBox: "0 0 " + size + " " + size });
	var oDefs = newSVGElem("defs");
	var oFilter = newSVGElem("filter", { id: "seamless", x: "-30%", y: "-30%", width: "160%", height: "160%" });
	oFilter.appendChild(newSVGElem("feTurbulence", { type: "fractalNoise", baseFrequency: freqX + " " + freqY, numOctaves: "5", seed: seed, stitchTiles: "stitch" }));
	oFilter.appendChild(newSVGElem("feColorMatrix", { type: "matrix", values: "0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 7 -3.5" }));
	oDefs.appendChild(oFilter);
	oSvg.appendChild(oDefs);
	oSvg.appendChild(newSVGElem("rect", { x: "-30%", width: "160%", height: "100%", fill: "white", filter: "url(#seamless)", opacity: "0.8" }));
	var svgString = (new XMLSerializer()).serializeToString(oSvg);
	var svgDataUrl = "data:image/svg+xml;base64," + btoa(svgString);
	var img = new Image();
	img.onload = function() {
		try {
			var canvas = document.createElement('canvas');
			canvas.width = outputWidth;
			canvas.height = size;
			var context = canvas.getContext('2d');
			context.drawImage(img, 0, 0, size, size, 0, 0, outputWidth, size);
			document.body.style.setProperty('--cloud-bg', 'url("' + canvas.toDataURL('image/png') + '")');
		} catch(e) {
			document.body.style.setProperty('--cloud-bg', 'url("' + svgDataUrl + '")');
		}
	};
	img.onerror = function() {
		document.body.style.setProperty('--cloud-bg', 'url("' + svgDataUrl + '")');
	};
	img.src = svgDataUrl;

})();
