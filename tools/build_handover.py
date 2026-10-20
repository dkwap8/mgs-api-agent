"""Build a source-only handover; never include runtime data or secrets."""
from pathlib import Path
import hashlib
import json
import zipfile

root = Path(__file__).resolve().parents[1]
allowed_dirs = {'app', 'bootstrap', 'config', 'database', 'docs', 'public', 'resources', 'routes', 'tests', 'tools'}
allowed_root = {'.env.example', '.gitignore', '.gitattributes', 'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'phpunit.xml', 'vite.config.js', 'README.md', 'server.php'}
excluded_parts = {'node_modules', 'vendor', '.git', '.codex', '.agents', '__pycache__', 'storage', 'release', 'cache', 'uploads', 'activations'}
files = []
for path in root.rglob('*'):
    relative = path.relative_to(root)
    if any(part in excluded_parts for part in relative.parts) or path.is_symlink() or not path.is_file():
        continue
    if relative.parts[0] not in allowed_dirs and relative.as_posix() not in allowed_root:
        continue
    if path.name == '.env' or path.suffix.lower() in {'.sqlite', '.db', '.sql', '.log', '.zip', '.bak'}:
        continue
    if relative.as_posix() in {'tools/test_database_status.php', 'tools/verify_preserved_document.php', 'docs/review/completion-mysql.xml'}:
        continue
    if relative.parts[0] == 'bootstrap' and relative.parts[1:2] == ('cache',):
        continue
    files.append((path, relative.as_posix()))
release = root / 'release'
release.mkdir(exist_ok=True)
archive = release / 'mgs-api-staging-handover-20261004.zip'
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as output:
    for path, relative in sorted(files, key=lambda item: item[1]):
        output.write(path, relative)
    for directory in ['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs']:
        output.writestr(directory + '/.gitignore', '*\n!.gitignore\n')
with zipfile.ZipFile(archive) as output:
    assert output.testzip() is None
    assert not any(name == '.env' or name.startswith(('vendor/', 'node_modules/', 'public/storage/')) for name in output.namelist())
manifest = {'archive': archive.name, 'sha256': hashlib.sha256(archive.read_bytes()).hexdigest(), 'source_files': len(files), 'excluded': ['.env', 'database contents', 'uploads', 'dependencies', 'logs', 'runtime cache']}
(release / 'manifest.json').write_text(json.dumps(manifest, indent=2))
print(json.dumps(manifest))
