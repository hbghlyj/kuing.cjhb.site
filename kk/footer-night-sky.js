(function () {
  var footer = document.querySelector('.dz_footc.cl');
  if (!footer) return;

  var w = footer.offsetWidth || 1600;
  var h = 250;

  // Generate random stars
  var stars = '';
  var count = 40;
  for (var i = 0; i < count; i++) {
    var x = Math.floor(Math.random() * w);
    var y = Math.floor(Math.random() * h);
    var size = (Math.random() > 0.7 ? 1.5 : 1).toFixed(1);
    var brightness = ['#eee', '#fff', '#ddd', '#fff', '#eee'][Math.floor(Math.random() * 5)];
    stars += 'radial-gradient(' + size + 'px ' + size + 'px at ' + x + 'px ' + y + 'px, ' + brightness + ', rgba(0,0,0,0))';
    if (i < count - 1) stars += ',\n      ';
  }

  Object.assign(footer.style, {
    'background-image': stars + ',\n      linear-gradient(180deg, #1a1a2e 0%, #16213e 100%)',
    'background-size': (Array(count).fill(w + 'px ' + h + 'px').join(', ') + ', 100% 100%'),
    'position': 'relative',
    'min-height': '250px',
    'overflow': 'hidden',
    'border': 'none'
  });

  // Crescent Moon
  var moon = document.createElement('div');
  Object.assign(moon.style, {
    'position': 'absolute',
    'top': '10%',
    'right': '10%',
    'width': '60px',
    'height': '60px',
    'background': 'transparent',
    'border-radius': '50%',
    'box-shadow': '15px 15px 0 0 #fdfcf0',
    'filter': 'drop-shadow(0 0 10px rgba(253, 252, 240, 0.5))',
    'transform': 'rotate(-45deg)',
    'z-index': '0'
  });
  footer.appendChild(moon);

  // Dunes
  var dune1 = document.createElement('div');
  Object.assign(dune1.style, {
    'position': 'absolute', 'bottom': '0', 'left': '0', 'width': '100%', 'height': '60%',
    'background': '#243447', 'clip-path': 'polygon(0% 100%, 0% 70%, 15% 55%, 35% 80%, 55% 40%, 75% 75%, 100% 50%, 100% 100%)',
    'z-index': '1'
  });
  footer.appendChild(dune1);

  var dune2 = document.createElement('div');
  Object.assign(dune2.style, {
    'position': 'absolute', 'bottom': '0', 'right': '0', 'width': '100%', 'height': '50%',
    'background': '#1b262c', 'clip-path': 'polygon(0% 100%, 0% 85%, 20% 70%, 45% 90%, 70% 60%, 85% 80%, 100% 75%, 100% 100%)',
    'z-index': '2'
  });
  footer.appendChild(dune2);

  // Foreground Elements
  var fgColor = '#0a1120';
  function addFG(style) {
    var el = document.createElement('div');
    Object.assign(el.style, { position: 'absolute', background: fgColor, 'z-index': '4' });
    Object.assign(el.style, style);
    footer.appendChild(el);
  }

  var cactusPath = 'polygon(40% 100%, 60% 100%, 60% 40%, 90% 40%, 90% 30%, 60% 30%, 60% 0%, 40% 0%, 40% 30%, 10% 30%, 10% 40%, 40% 40%)';

  addFG({ bottom: '20px', left: '20%', width: '40px', height: '80px', 'clip-path': cactusPath });
  addFG({ bottom: '15px', left: '10%', width: '40px', height: '80px', transform: 'scale(0.6) scaleX(-1)', 'clip-path': cactusPath });
  addFG({ bottom: '25px', left: '75%', width: '40px', height: '80px', transform: 'scale(0.8)', 'clip-path': cactusPath });

  addFG({ bottom: '10px', left: '40%', width: '30px', height: '15px', 'clip-path': 'polygon(10% 90%, 30% 20%, 60% 10%, 90% 40%, 95% 95%)' });
  addFG({ bottom: '5px', left: '60%', width: '45px', height: '20px', 'clip-path': 'polygon(5% 100%, 20% 40%, 50% 10%, 80% 30%, 95% 100%)' });

  // Footer Content
  var ftInner = footer.querySelector('#ft');
  if (ftInner) {
    Object.assign(ftInner.style, {
      'position': 'relative', 'z-index': '10', 'color': '#fff', 'text-shadow': '1px 1px 4px rgba(0,0,0,0.8)'
    });
  }
})();
