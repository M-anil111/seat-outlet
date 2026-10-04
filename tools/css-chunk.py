#!/usr/bin/env python3
"""Split a minified stylesheet into files of at most LIMIT bytes, cutting only between top level rules (an @media block stays whole).

    css-chunk.py <in.css> <out prefix> <limit bytes>

Writes <prefix>.min.css when the input fits, else <prefix>.1.min.css, <prefix>.2.min.css ... Loaded in that order they are
exactly the input, so the cascade is unchanged.
"""
import sys, os, glob

src, prefix, limit = sys.argv[1], sys.argv[2], int(sys.argv[3])
css = open(src, encoding='utf-8').read()
for old in glob.glob(prefix + '.min.css') + glob.glob(prefix + '.[0-9]*.min.css'):
    os.remove(old)

def top_level_ends(text):
    ends, depth, in_str, q = [], 0, False, ''
    i, n = 0, len(text)
    while i < n:
        c = text[i]
        if in_str:
            if c == '\\': i += 1
            elif c == q: in_str = False
        elif c in '"\'':
            in_str, q = True, c
        elif c == '{': depth += 1
        elif c == '}':
            depth -= 1
            if depth == 0: ends.append(i + 1)
        elif c == ';' and depth == 0:
            ends.append(i + 1)   # @import / @charset
        i += 1
    return ends

data = css.encode('utf-8')
if len(data) <= limit:
    open(prefix + '.min.css', 'w', encoding='utf-8').write(css)
    print(prefix + '.min.css', len(data))
    sys.exit(0)

ends = top_level_ends(css)
parts, start = [], 0
total = len(css)
want = -(-len(data) // limit)            # number of files needed
target = -(-len(data) // want)           # aim for equal sizes
last = 0
for e in ends:
    if len(css[start:e].encode('utf-8')) >= target and len(parts) < want - 1:
        parts.append(css[start:e]); start = e
parts.append(css[start:])
for k, p in enumerate(parts, 1):
    path = '%s.%d.min.css' % (prefix, k)
    open(path, 'w', encoding='utf-8').write(p)
    print(path, len(p.encode('utf-8')))
