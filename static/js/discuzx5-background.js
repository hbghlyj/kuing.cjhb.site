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

	var oceanSvg = newSVGElem("svg", { xmlns: "http://www.w3.org/2000/svg", width: size, height: size, viewBox: "0 0 " + size + " " + size });
	oceanSvg.innerHTML = '<defs>' +
		'<linearGradient id="sea" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="1024"><stop stop-color="#5da8c2"/><stop offset=".14" stop-color="#2b8eae"/><stop offset=".38" stop-color="#0d6d91"/><stop offset=".68" stop-color="#07516d"/><stop offset="1" stop-color="#022f47"/></linearGradient>' +
		'<linearGradient id="refl" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="360"><stop stop-color="#fff" stop-opacity=".18"/><stop offset=".6" stop-color="#eaf4f8" stop-opacity=".07"/><stop offset="1" stop-color="#eaf4f8" stop-opacity="0"/></linearGradient>' +
		'<linearGradient id="farMask" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="360"><stop stop-color="#fff"/><stop offset=".55" stop-color="#fff" stop-opacity=".45"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>' +
		'<linearGradient id="nearMask" gradientUnits="userSpaceOnUse" x1="0" y1="360" x2="0" y2="1024"><stop stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#fff"/></linearGradient>' +
		'<mask id="mFar"><rect x="-40" y="0" width="1104" height="360" fill="url(#farMask)"/></mask><mask id="mNear"><rect x="-40" y="360" width="1104" height="664" fill="url(#nearMask)"/></mask>' +
		'<filter id="sway" x="-8%" y="-10%" width="116%" height="120%"><feTurbulence type="fractalNoise" baseFrequency=".003 .018" numOctaves="1" seed="' + (seed + 17) + '" result="n"/><feDisplacementMap in="SourceGraphic" in2="n" scale="15" xChannelSelector="R" yChannelSelector="G"/></filter>' +
		'<filter id="swell" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".0025 .009" numOctaves="2" seed="' + (seed + 23) + '" result="n"/><feGaussianBlur in="n" stdDeviation="2" result="b"/><feDiffuseLighting in="b" surfaceScale="3" diffuseConstant=".9" lighting-color="#fff" result="l"><feDistantLight azimuth="300" elevation="55"/></feDiffuseLighting><feComposite in="l" in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="ripFar" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".014 .12" numOctaves="2" seed="' + (seed + 31) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".35" result="b"/><feDiffuseLighting in="b" surfaceScale="1.3" diffuseConstant=".85" lighting-color="#f4faff"><feDistantLight azimuth="300" elevation="60"/></feDiffuseLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="ripNear" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".009 .06" numOctaves="2" seed="' + (seed + 33) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".5" result="b"/><feDiffuseLighting in="b" surfaceScale="2.4" diffuseConstant=".95" lighting-color="#f4faff"><feDistantLight azimuth="300" elevation="55"/></feDiffuseLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="specFar" x="-4%" y="-5%" width="108%" height="112%"><feTurbulence type="fractalNoise" baseFrequency=".010 .09" numOctaves="2" seed="' + (seed + 42) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".5" result="b"/><feSpecularLighting in="b" surfaceScale="1.6" specularConstant="1.15" specularExponent="16" lighting-color="#f0f8ff"><feDistantLight azimuth="300" elevation="58"/></feSpecularLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="specNear" x="-4%" y="-5%" width="108%" height="112%"><feTurbulence type="fractalNoise" baseFrequency=".006 .05" numOctaves="2" seed="' + (seed + 77) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".7" result="b"/><feSpecularLighting in="b" surfaceScale="2.6" specularConstant="1.05" specularExponent="13" lighting-color="#f2f9ff"><feDistantLight azimuth="300" elevation="52"/></feSpecularLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
	'</defs><g filter="url(#sway)"><rect x="-60" y="-60" width="1144" height="1144" fill="url(#sea)"/><rect x="-60" y="0" width="1144" height="360" fill="url(#refl)"/></g>' +
	'<rect x="-40" y="0" width="1104" height="1024" fill="#d9f2f8" filter="url(#swell)" opacity=".35" style="mix-blend-mode:soft-light"/>' +
		'<rect x="-40" y="0" width="1104" height="360" fill="#f4faff" filter="url(#ripFar)" mask="url(#mFar)" opacity=".45" style="mix-blend-mode:overlay"/><rect x="-40" y="360" width="1104" height="664" fill="#f4faff" filter="url(#ripNear)" mask="url(#mNear)" opacity=".38" style="mix-blend-mode:overlay"/>' +
		'<rect x="-40" y="0" width="1104" height="360" fill="#f0f8ff" filter="url(#specFar)" mask="url(#mFar)" opacity=".75" style="mix-blend-mode:screen"/><rect x="-40" y="360" width="1104" height="664" fill="#f2f9ff" filter="url(#specNear)" mask="url(#mNear)" opacity=".4" style="mix-blend-mode:screen"/>' +
		'<rect x="0" y="0" width="1024" height="18" fill="url(#refl)" opacity=".8"/>';
	var oceanString = (new XMLSerializer()).serializeToString(oceanSvg);
	document.body.style.setProperty('--ocean-bg', 'url("data:image/svg+xml;base64,' + btoa(oceanString) + '")');
})();
