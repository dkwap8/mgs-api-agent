from pathlib import Path
import json
import subprocess

root = Path(__file__).resolve().parents[1]
paths = [p for directory in ['app', 'bootstrap', 'config', 'database', 'routes', 'tests', 'tools'] for p in (root / directory).rglob('*.php') if 'cache' not in p.relative_to(root).parts]
paths.extend([root / 'server.php'])
errors = []
for path in paths:
    result = subprocess.run(['php', '-l', str(path)], capture_output=True, text=True)
    if result.returncode:
        errors.append({'file': str(path.relative_to(root)), 'error': result.stdout + result.stderr})
evidence = {'result': 'failed' if errors else 'passed', 'php_files': len(paths), 'errors': errors}
(root / 'docs/review/php-lint-final.json').write_text(json.dumps(evidence, indent=2))
print(json.dumps(evidence))
raise SystemExit(bool(errors))
