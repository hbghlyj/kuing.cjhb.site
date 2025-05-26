const mathfont_list = {
    "Default": "Default",
    "Asana": "Asana",
    "Cambria": "Cambria (local only)",
    "DejaVu": "DejaVu",
    "Euler": "Euler Math",
    "FiraMath": "FiraMath",
    "Garamond": "Garamond",
    "GFS_NeoHellenic": "GFS NeoHellenic",
    "LatinModern": "Latin Modern",
    "LeteSansMath": "Lete Sans Math",
    "Libertinus": "Libertinus",
    "LucidaBright": "Lucida Bright (local only)",
    "Minion": "Minion (local only)",
    "NewComputerModern": "New Computer Modern",
    "NewComputerModernSans": "New Computer Modern Sans",
    "NotoSans": "Noto Sans",
    "Plex": "IBM Plex",
    "STIX": "STIX",
    "TeXGyreBonum": "TeX Gyre Bonum",
    "TeXGyrePagella": "TeX Gyre Pagella",
    "TeXGyreSchola": "TeX Gyre Schola",
    "TeXGyreTermes": "TeX Gyre Termes",
    "XITS": "XITS"
};
const localFonts = ["Georgia", "Arial", "Verdana", "Helvetica", "Tahoma", "TrebuchetMS"];
const localChineseFonts = [
	// Simplified
	"SimSun", "STZhongsong", "STSong", "STFangsong",
	"SimHei", "STHeiti", "STXihei", "HeitiSC",
	"Microsoft YaHei", "DengXian", "PingFangSC",
	"KaiTi", "FangSong", "STKaiti", "STXingkai",
	"STCaiyun", "STHupo", "STXinwei",
	"STLiti", "LiSu", "YouYuan",
	// Traditional
	"MingLiU", "PMingLiU", "Microsoft JhengHei", "DFKai-SB",
	"PingFang TC", "PingFang HK", "Heiti TC", "LiHei Pro Medium", "LiSong Pro Light", "Source Han Sans TC"
];
const googleChineseFonts = [
	"Noto Sans SC", "Noto Serif TC", "Noto Serif HK"
];
const boldChineseFonts = new Map([
	["Microsoft YaHei", "Microsoft YaHei Bold"],
	["Microsoft JhengHei", "Microsoft JhengHei Bold"],
	["DengXian", "DengXian-Bold"],
	["MingLiU", "MingLiU Bold"],
	["PingFangSC", "PingFangSC-Semibold"]
]);

function setCookie(name, value, days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toUTCString();
	}
	document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(";");
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == " ") c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function addOptions(select, fonts) {
	select.innerHTML = "";
	for (let font of fonts) {
		let option = document.createElement("option");
		option.value = option.innerText = font;
		select.appendChild(option);
	}
}

const en_font_style = document.getElementById("en_font_style");
default_en_font_style = en_font_style.textContent;
document.head.appendChild(en_font_style);
const zh_font_style = document.getElementById("zh_font_style");
default_zh_font_style = zh_font_style.textContent;
const zh_bold_style = document.getElementById("zh_bold_style");
default_zh_bold_style = zh_bold_style.textContent;
const en_font_select = document.getElementById("en_font_select");
const zh_font_select = document.getElementById("zh_font_select");
const zh_bold_select = document.getElementById("zh_bold_select");
const en_font_cookie = getCookie("en_font");
const zh_font_cookie = getCookie("zh_font");
const zh_bold_cookie = getCookie("zh_bold");

for (let font in mathfont_list) {
	let option = document.createElement("option");
	option.value = font;
	option.innerText = mathfont_list[font];
	en_font_select.appendChild(option);
}
for (let font of localFonts) {
	let option = document.createElement("option");
	option.value = option.innerText = font;
	en_font_select.appendChild(option);
}
addOptions(zh_font_select, ["Default", ...localChineseFonts, ...googleChineseFonts]);
addOptions(zh_bold_select, ["Default", ...new Set(boldChineseFonts.values())]);

if (en_font_cookie) {
	en_font_select.value = en_font_cookie;
	update_en_font();
}
if (zh_font_cookie) {
	zh_font_select.value = zh_font_cookie;
	update_zh_font();
}
if (zh_bold_cookie) {
	zh_bold_select.style.display = "";
	zh_bold_select.value = zh_bold_cookie;
	update_zh_bold();
}

en_font_select.addEventListener("change", () => {
	if (en_font_select.value !== "Default") {
		setCookie("en_font", en_font_select.value, 365);
	} else {
		setCookie("en_font", "", -1);
	}
	update_en_font();
});

zh_font_select.addEventListener("change", () => {
	if (zh_font_select.value !== "Default") {
		setCookie("zh_font", zh_font_select.value, 365);
	} else {
		setCookie("zh_font", "", -1);
	}
	update_zh_font();
});

zh_bold_select.addEventListener("change", () => {
	if (zh_bold_select.value !== "Default") {
		setCookie("zh_bold", zh_bold_select.value, 365);
	} else {
		setCookie("zh_bold", "", -1);
	}
	update_zh_bold();
});

function update_en_font() {
	const v = en_font_select.value;
	if (v == "Default") {
		en_font_style.textContent = default_en_font_style;
	} else if (localFonts.includes(v)) {
		en_font_style.textContent = `:root { --common-font: "${v}"; }`;
	} else if (mathfont_list[v]) {
		en_font_style.textContent = `@import url(/static/MathFonts/${v}/mathfonts.css);:root { --common-font: "${v}"; }`;
	} else {
		en_font_style.textContent = default_en_font_style;
	}
}

function update_zh_font() {
	const v = zh_font_select.value;
	if (v == "Default") {
		zh_font_style.textContent = default_zh_font_style;
	} else if (localChineseFonts.includes(v)) {
		zh_font_style.textContent = `@font-face {font-family: zh; src: local("${v}"); font-weight: normal;}`;
	} else if (googleChineseFonts.includes(v)) {
		const fontStack = getComputedStyle(document.body).fontFamily.replace(/\bzh\b/g, `"${v}"`);
		zh_font_style.textContent = `@import url('https://fonts.googleapis.com/css2?family=${v.replace(/ /g, "+")}:wght@400;700&display=swap');\nbody, input, button, select, .xst, .ts,#thread_subject { font-family: ${fontStack}; }`;
	} else {
		zh_font_style.textContent = default_zh_font_style;
	}
}

function update_zh_bold() {
	const bold = zh_bold_select.value;
	if (bold == "Default") {
		zh_bold_style.textContent = default_zh_bold_style;
	} else {
		zh_bold_style.textContent = `@font-face {font-family: zh; src: local("${bold}"); font-weight: bold;}`;
	}
}

zh_font_select.addEventListener("click", async () => {
	try {
		const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
		svg.setAttribute("width", "500");
		svg.setAttribute("height", "200");
		svg.style.visibility = "hidden";

		const textElementSVG = document.createElementNS("http://www.w3.org/2000/svg", "text");
		textElementSVG.setAttribute("x", "0");
		textElementSVG.setAttribute("y", "100");
		textElementSVG.textContent = "你好";
		textElementSVG.style.fontSize = "72px";
		textElementSVG.style.setProperty("font-synthesis-weight", "none");
		svg.appendChild(textElementSVG);
		document.body.appendChild(svg);

		textElementSVG.setAttribute("font-family", "serif");
		const defaultWidth = textElementSVG.getComputedTextLength();
		const currentZhFont = zh_font_select.value;
		const currentZhBold = zh_bold_select.value;
		const availableLocalFonts = [];
		const availableBoldFonts = [];

		for (const fontName of localChineseFonts) {
			textElementSVG.setAttribute("font-family", fontName);
			const testWidth = textElementSVG.getComputedTextLength();
			if (testWidth === defaultWidth) {
				continue;
			}
			availableLocalFonts.push(fontName);

			if (!boldChineseFonts.has(fontName)) {
				continue;
			}
			const normalExtent = textElementSVG.getExtentOfChar(0);
			const boldFontName = boldChineseFonts.get(fontName);
			textElementSVG.setAttribute("font-family", boldFontName);
			const boldExtent = textElementSVG.getExtentOfChar(0);
			if (boldExtent.width !== normalExtent.width || boldExtent.height !== normalExtent.height) {
				availableBoldFonts.push(boldFontName);
			}
		}

		document.body.removeChild(svg);
		addOptions(zh_font_select, ["Default", ...availableLocalFonts, ...googleChineseFonts]);
		addOptions(zh_bold_select, ["Default", ...availableBoldFonts]);
		zh_font_select.value = zh_font_cookie || currentZhFont || "Default";
		if (!zh_font_select.value) {
			zh_font_select.value = "Default";
		}
		zh_bold_select.value = zh_bold_cookie || currentZhBold || "Default";
		if (!zh_bold_select.value) {
			zh_bold_select.value = "Default";
		}
		zh_bold_select.style.display = "";
	} catch (e) {
		showError("Error checking local fonts:", e);
	}
}, { once: true });
