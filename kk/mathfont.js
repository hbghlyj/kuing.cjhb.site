const mathfont_list = {
    "Default":  "Default fonts",
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
    "XITS": "XITS"};
const localFonts = ["Georgia","Arial", "Verdana", "Helvetica", "Tahoma", "TrebuchetMS"];


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
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

document.addEventListener("DOMContentLoaded", () => {
	let mathfont_link = document.createElement("link");
	mathfont_link.setAttribute("rel", "stylesheet");
	mathfont_link.setAttribute("type", "text/css");
	document.head.appendChild(mathfont_link);

	const mathfont_select = document.querySelector("select.mathfont");
	const fontFamilyCookie = getCookie('fontFamily');

	function updateCookies() {
		if (mathfont_select) {
			if (mathfont_select.value !== "Default") {
			  setCookie('fontFamily',  mathfont_select.value, 30);
			} else {
			  setCookie('fontFamily', '', -1); // Delete cookie
			}
		}
	}

	if (mathfont_select) {
		function updateMathFont() {
			let mathfont = mathfont_select.value;
			if (mathfont == "Default" || localFonts.includes(mathfont))
				mathfont_link.removeAttribute("href");
			else
				mathfont_link.setAttribute("href",
										   `/static/MathFonts/${mathfont}/mathfonts.css`);
			if (localFonts.includes(mathfont)) {
				let style = document.createElement('style');
				style.textContent = `body, input, button, select, textarea, .xst, .ts, #thread_subject { font-family: ${mathfont}, "Twemoji Country Flags",zh,"Noto Colr Emoji Glyf"; }`;
				document.head.appendChild(style);
			}
			updateCookies();
		}
		for (let font in mathfont_list) {
			let option = document.createElement("option");
			option.value = font;
			option.innerText = mathfont_list[font];
			if (font === fontFamilyCookie) {
			  option.selected = true;
			}
			mathfont_select.appendChild(option);
		}
		for (let font of localFonts) {
			let option = document.createElement("option");
			option.value = font;
			option.innerHTML = '<span style="font-family: ' + font + '">' + font + '</span>';
			mathfont_select.appendChild(option);
			if (font === fontFamilyCookie) {
			  option.selected = true;
			}
		}

		updateMathFont();
		mathfont_select.addEventListener("change", updateMathFont);
	}

	const commonChineseFonts = [
		// Simplified
		"SimSun","NSimSun","Microsoft YaHei","SimHei","KaiTi","FangSong",
		"PingFang SC","Heiti SC","STHeiti","STXihei","Hiragino Sans GB",
		"Source Han Sans SC","Noto Sans SC","WenQuanYi Zen Hei","WenQuanYi Micro Hei",
		"Droid Sans Fallback",
		// Traditional
		"MingLiU","PMingLiU","Microsoft JhengHei","DFKai-SB",
		"PingFang TC","PingFang HK","Heiti TC","LiHei Pro Medium","LiSong Pro Light",
		"Source Han Sans TC","Noto Sans TC","Noto Sans HK"
	];
	async function getAvailableChineseFonts() {
		if (!('queryLocalFonts' in window)) {
			console.warn('Local Font Access API not supported');
			return [];
		}
		try {
			const available = await window.queryLocalFonts();
			const found = new Set();
			for (const { family, fullName } of available) {
				const fam = family.toLowerCase(), full = fullName.toLowerCase();
				for (const name of commonChineseFonts) {
					const key = name.toLowerCase();
					if (fam.includes(key) || full.includes(key)) {
						found.add(name);
						break;
					}
				}
			}
			return Array.from(found);
		} catch (e) {
			console.error('Error querying local fonts:', e);
			return [];
		}
	}
	const chinesefont_select = document.querySelector("select.chinesefont");
	const chineseFontCookie  = getCookie("chineseFont");
	
	const populateChineseFonts = (initial = false) => {
		if (!chinesefont_select) return;
		chinesefont_select.innerHTML = ''; // Clear existing options
		
		function updateChineseCookies() {
			if (chinesefont_select.value !== "Default")
				setCookie("chineseFont", chinesefont_select.value, 30);
			else
				setCookie("chineseFont", "", -1);
		}
		function updateChineseFont() {
			const v = chinesefont_select.value;
			document.body.style.removeProperty("font-family");
			const computedStyle = window.getComputedStyle(document.body);
			let currentFontFamily = computedStyle.fontFamily;
			if (v != "Default") {
				let zhIndex = currentFontFamily.indexOf(", zh");
				currentFontFamily = (zhIndex > 0 ? currentFontFamily.substring(0, zhIndex) : "") + `,"${v}", zh` + currentFontFamily.substring(zhIndex + 4);
				document.body.style.fontFamily = currentFontFamily;
			}
			updateChineseCookies();
		}
		const populateOptions = (installed) => {
			// Default option
			const d = document.createElement("option");
			d.value = "Default";
			d.innerText = "Default fonts";
			chinesefont_select.append(d);
			// Add each installed font
			installed.forEach(f => {
				const o = document.createElement("option");
				o.value = f;
				o.innerText = f;
				if (f === chineseFontCookie) o.selected = true;
				chinesefont_select.append(o);
			});
			updateChineseFont();
		};
		
		if (initial) {
			// Initial population with common fonts (no API call yet)
			chinesefont_select.addEventListener("change", updateChineseFont);
			populateOptions(commonChineseFonts);
		} else {
			getAvailableChineseFonts().then(installed => {
				populateOptions(installed);
			});
		}
	};
	
	if (chinesefont_select) {
		populateChineseFonts(true); // Populate on page load with initial list
		chinesefont_select.addEventListener('click', () => {
			populateChineseFonts();
		}, { once: true });
	}
});