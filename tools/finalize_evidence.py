from pathlib import Path
import datetime
import json
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
review = root / 'docs/review'
def read(path):
    data = path.read_bytes()
    if data.startswith((b'\xff\xfe', b'\xfe\xff')):
        text = data.decode('utf-16')
        path.write_text(text, encoding='utf-8')
        return text
    return data.decode('utf-8-sig')
for path in review.rglob('*'):
    if path.is_file() and path.suffix in {'.json', '.txt'}:
        read(path)
def suite(name):
    element = ET.parse(review / name).getroot().find('testsuite')
    result = {key: int(element.get(key, '0')) for key in ['tests', 'assertions', 'errors', 'failures', 'skipped']}
    assert result['tests'] > 0 and result['errors'] == result['failures'] == 0
    return result
browser = json.loads(read(review / 'browser/results.json'))
assert len(browser['findings']) == 8 and not browser['pageErrors']
assert all(item['result'] == 'passed' for item in browser['findings'])
composer = json.loads(read(review / 'composer-audit-final.json'))
npm = json.loads(read(review / 'npm-audit-final.json'))
assert not composer['advisories'] and npm['metadata']['vulnerabilities']['total'] == 0
assert not json.loads(read(review / 'schema-columns-final.json'))['missing_columns']
assert 'PASS:' in read(review / 'concurrency-final.txt')
lint = json.loads(read(review / 'php-lint-final.json'))
assert lint['result'] == 'passed'
assert 'built in' in read(review / 'frontend-build-final.txt')
assert 'Risky:' not in read(review / 'phpunit-mysql-final.txt')
evidence = {
    'verified_at_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(),
    'sqlite': suite('completion-sqlite-final.xml'),
    'mariadb_upgraded_schema': suite('completion-mysql-final.xml'),
    'mariadb_two_process_withdrawal': 'passed',
    'legacy_schema_missing_columns': [],
    'browser_checks': 8,
    'browser_authenticated_data': 'synthetic API fixtures, built frontend; backend authorization and 404s tested by PHPUnit',
    'php_lint': lint,
    'frontend_build': 'passed',
    'composer_known_advisories': 0,
    'npm_known_advisories': 0,
    'preservation': json.loads(read(review / 'preservation-final.json')),
    'original_document': json.loads(read(review / 'preserved-document.json')),
    'provider_certified': False,
    'production_acceptance_complete': False,
}
(review / 'final-verification.json').write_text(json.dumps(evidence, indent=2), encoding='utf-8')
print(json.dumps(evidence))
