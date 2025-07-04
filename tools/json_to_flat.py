import json
from pathlib import Path

SRC = Path('json')
DST = Path('flat')

for path in SRC.glob('*/*.json'):
    rel_dir = path.parent.relative_to(SRC)
    out_dir = DST / rel_dir
    out_dir.mkdir(parents=True, exist_ok=True)
    out_file = out_dir / (path.stem + '.md')
    with path.open('r', encoding='utf-8') as f:
        data = json.load(f)
    lines = []
    for block in data:
        key = block.get('key')
        text = block.get('v1', '')
        if not text:
            continue
        if key == 'title':
            lines.append('# ' + text)
        elif key == 'markdown':
            lines.append(text)
    content = '\n\n'.join(lines) + '\n'
    with out_file.open('w', encoding='utf-8') as f:
        f.write(content)
