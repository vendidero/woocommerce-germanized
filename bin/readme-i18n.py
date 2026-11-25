#!/usr/bin/env python3
"""
Helper for the wordpress.org "Stable Readme" translations (translate.wordpress.org).

wordpress.org splits readme.txt into translatable strings (headers, paragraphs, list items) and converts
the markdown to HTML. This script mimics that split to find strings which are not translated yet and to
validate PO files before importing them.

Usage:
  bin/readme-i18n.py missing [--locale de|de-formal|all] [--no-changelog]
      Compare readme.txt against the current translations on translate.wordpress.org and write the
      untranslated strings (incl. changelog entries) to bin/readme-i18n/<locale>.po. Existing msgstr
      values within that file are kept, entries which are translated on wordpress.org in the meantime
      are dropped. Changelog entries are prefilled with the English original (as on wordpress.org),
      pass --no-changelog to skip them.

  bin/readme-i18n.py check [file.po ...]
      Make sure every msgid exists in readme.txt (so the import will actually match) and run msgfmt -c.
      Defaults to all PO files within bin/readme-i18n/.

Import the PO files via translate.wordpress.org -> Germanized for WooCommerce -> Stable Readme ->
German / German (Formal) -> Import Translations. Strings only exist on wordpress.org after the readme
has been deployed (e.g. via the wp.org deploy workflow, assets-only mode with sync_readme).
"""

import argparse
import glob
import os
import re
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
README = os.path.join(ROOT, 'readme.txt')
PO_DIR = os.path.join(ROOT, 'bin', 'readme-i18n')
SLUG = 'woocommerce-germanized'
LOCALES = {
    'de': 'de/default',
    'de-formal': 'de/formal',
}
EXPORT_URL = 'https://translate.wordpress.org/projects/wp-plugins/%s/stable-readme/%s/export-translations/?format=po'


# --- Markdown -> HTML (the subset used by readme.txt) ------------------------------------------------

def escape(text):
    text = re.sub(r'&(?!(?:[a-zA-Z]+|#\d+);)', '&amp;', text)
    return text.replace('<', '&lt;').replace('>', '&gt;')


def inline(text):
    codes = []

    def keep_code(m):
        codes.append('<code>%s</code>' % escape(m.group(1)))
        return '\x00%d\x00' % (len(codes) - 1)

    text = re.sub(r'`([^`]+)`', keep_code, text)
    # readme.txt contains no raw HTML: escape everything before adding markup (e.g. "WP < 6.9").
    text = escape(text)
    text = re.sub(r'\[([^\]]+)\]\(([^)\s]+)\s+"([^"]*)"\)', r'<a href="\2" title="\3">\1</a>', text)
    text = re.sub(r'\[([^\]]+)\]\(([^)\s]+)\)', r'<a href="\2">\1</a>', text)
    text = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', text)
    text = re.sub(r'\*(.+?)\*', r'<em>\1</em>', text)
    return re.sub('\x00(\\d+)\x00', lambda m: codes[int(m.group(1))], text)


def extract(changelog=True):
    """Returns a list of (comment, msgid) in readme order."""
    lines = open(README, encoding='utf-8').read().split('\n')
    strings = []
    seen = set()

    def add(comment, msgid):
        if msgid and msgid not in seen:
            seen.add(msgid)
            strings.append((comment, msgid))

    header = re.match(r'^===\s*(.+?)\s*===$', lines[0])
    if header:
        add('Plugin name.', header.group(1))

    i = 1
    # Skip header fields, the short description is the first paragraph afterwards.
    while i < len(lines) and lines[i].strip() and not lines[i].startswith('=='):
        i += 1
    while i < len(lines) and not lines[i].strip():
        i += 1
    if i < len(lines) and not lines[i].startswith('=='):
        add('Short description.', inline(lines[i].strip()))

    section = None
    paragraph = []

    def flush():
        para = list(paragraph)
        paragraph.clear()
        # Code-only lines (e.g. shortcode examples) are not translatable.
        para = [l for l in para if not re.fullmatch(r'`[^`]+`', l.strip())]
        if para and section in ('description', 'installation', 'faq'):
            add('Found in %s paragraph.' % section, inline('\n'.join(l.strip() for l in para)))

    for line in lines[i:]:
        stripped = line.strip()
        sec = re.match(r'^==\s*(.+?)\s*==$', stripped)
        if sec:
            flush()
            section = {
                'description': 'description',
                'installation': 'installation',
                'frequently asked questions': 'faq',
                'screenshots': 'screenshots',
                'changelog': 'changelog',
            }.get(sec.group(1).lower())
            continue
        head = re.match(r'^=\s*(.+?)\s*=$', stripped)
        if head:
            flush()
            if section in ('description', 'installation', 'faq'):
                add('Found in %s header.' % section, inline(head.group(1)))
            continue
        if not stripped:
            flush()
            continue
        if section == 'screenshots':
            m = re.match(r'^\d+\.\s+(.+)$', stripped)
            if m:
                add('Screenshot description.', inline(m.group(1)))
            continue
        item = re.match(r'^[*-]\s+(.+)$', stripped)
        if item:
            flush()
            if section == 'changelog':
                if changelog:
                    add('Found in changelog list item.', inline(item.group(1)))
            elif section:
                add('Found in %s list item.' % section, inline(item.group(1)))
            continue
        paragraph.append(line)
    flush()
    return strings


# --- PO helpers ---------------------------------------------------------------------------------------

def po_unquote(chunks):
    s = ''.join(chunks)
    return re.sub(r'\\(.)', lambda m: {'n': '\n', 't': '\t', '"': '"', '\\': '\\'}.get(m.group(1), m.group(0)), s)


def po_parse(text):
    """Returns {msgid: msgstr} (header skipped)."""
    entries = {}
    msgid, msgstr, cur = None, None, None
    for line in text.split('\n') + ['']:
        line = line.strip()
        if line.startswith('msgid '):
            if msgid is not None:
                entries[po_unquote(msgid)] = po_unquote(msgstr or [])
            msgid, msgstr, cur = [], None, 'id'
            line = line[6:]
        elif line.startswith('msgstr '):
            msgstr, cur = [], 'str'
            line = line[7:]
        elif not line.startswith('"'):
            continue
        val = line[1:-1]
        (msgid if cur == 'id' else msgstr).append(val)
    if msgid is not None:
        entries[po_unquote(msgid)] = po_unquote(msgstr or [])
    entries.pop('', None)
    return entries


def po_quote(s):
    s = s.replace('\\', '\\\\').replace('"', '\\"')
    parts = s.split('\n')
    if len(parts) == 1:
        return '"%s"' % s
    return '""\n' + '\n'.join('"%s%s"' % (p, '\\n' if n < len(parts) - 1 else '') for n, p in enumerate(parts))


def po_write(path, locale, entries):
    out = [
        '# German (%s) translations for the %s Stable Readme, see bin/readme-i18n.py.' % ('formal' if 'formal' in locale else 'informal', SLUG),
        'msgid ""',
        'msgstr ""',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        '"Plural-Forms: nplurals=2; plural=n != 1;\\n"',
        '"Language: de\\n"',
        '"Project-Id-Version: Plugins - Germanized for WooCommerce - Stable Readme (latest release)\\n"',
        '',
    ]
    for comment, msgid, msgstr in entries:
        out += ['#. %s' % comment, 'msgid ' + po_quote(msgid), 'msgstr ' + po_quote(msgstr), '']
    with open(path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(out))


# --- Commands -----------------------------------------------------------------------------------------

def cmd_missing(args):
    locales = list(LOCALES) if args.locale == 'all' else [args.locale]
    strings = extract(not args.no_changelog)
    os.makedirs(PO_DIR, exist_ok=True)

    for locale in locales:
        url = EXPORT_URL % (SLUG, LOCALES[locale])
        # curl instead of urllib: python.org builds on macOS ship without CA certificates.
        res = subprocess.run(['curl', '-sfL', '--max-time', '30', url], check=True, stdout=subprocess.PIPE)
        remote = po_parse(res.stdout.decode('utf-8'))

        path = os.path.join(PO_DIR, '%s.po' % locale)
        local = po_parse(open(path, encoding='utf-8').read()) if os.path.exists(path) else {}

        # Changelog entries are kept in English on wordpress.org (msgstr = msgid) - prefill them accordingly.
        entries = [
            (c, m, local.get(m) or (m if 'changelog' in c else ''))
            for c, m in strings if not remote.get(m)
        ]
        po_write(path, locale, entries)

        todo = sum(1 for e in entries if not e[2])
        print('%s: %d untranslated string(s), %d still without translation -> %s' % (locale, len(entries), todo, os.path.relpath(path, ROOT)))


def cmd_check(args):
    files = args.files or sorted(glob.glob(os.path.join(PO_DIR, '*.po')))
    known = {m for _, m in extract()}
    ok = True

    for path in files:
        entries = po_parse(open(path, encoding='utf-8').read())
        for msgid, msgstr in entries.items():
            if msgid not in known:
                ok = False
                print('%s: msgid not found in readme.txt: %s' % (path, msgid[:100]))
            elif not msgstr:
                print('%s: missing translation: %s' % (path, msgid[:100]))
        try:
            subprocess.run(['msgfmt', '-c', '-o', os.devnull, path], check=True, stderr=subprocess.PIPE)
        except FileNotFoundError:
            print('msgfmt not found (brew install gettext), skipping syntax check')
        except subprocess.CalledProcessError as e:
            ok = False
            print(e.stderr.decode())
        print('%s: %d entries checked' % (os.path.relpath(path, ROOT), len(entries)))

    sys.exit(0 if ok else 1)


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = parser.add_subparsers(dest='command', required=True)

    missing = sub.add_parser('missing', help='write untranslated readme strings to bin/readme-i18n/<locale>.po')
    missing.add_argument('--locale', choices=list(LOCALES) + ['all'], default='all')
    missing.add_argument('--no-changelog', action='store_true', help='skip changelog entries')
    missing.set_defaults(func=cmd_missing)

    check = sub.add_parser('check', help='validate PO files against readme.txt')
    check.add_argument('files', nargs='*')
    check.set_defaults(func=cmd_check)

    args = parser.parse_args()
    args.func(args)


if __name__ == '__main__':
    main()
