import os
import re

folders = ['admin', 'ci', 'cso']
marker = '// Shared bootstrap compatibility for static analysis'
block = """// Shared bootstrap compatibility for static analysis
if (!isset($conn)) {
    $conn = null;
}
if (!isset($session_id)) {
    $session_id = '';
}

"""

changed = []
for folder in folders:
    for dirpath, _, filenames in os.walk(folder):
        for filename in filenames:
            if not filename.endswith('.php'):
                continue
            path = os.path.join(dirpath, filename)
            with open(path, 'r', encoding='utf-8', errors='ignore') as fh:
                text = fh.read()
            if '$conn' not in text and '$session_id' not in text:
                continue
            if marker in text:
                continue
            if text.lstrip().startswith('<?php'):
                text = re.sub(r'<\?php\b', '<?php\n' + block, text, count=1)
            else:
                text = '<?php\n' + block + text
            with open(path, 'w', encoding='utf-8') as fh:
                fh.write(text)
            changed.append(path)

print('\n'.join(changed))
print(f'CHANGED={len(changed)}')
