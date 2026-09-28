#!/usr/bin/env python3
"""
Hostile Analysis Content Linter

Mechanical linter for Phase 1 Hostile Analysis content.
Checks that the analysis references at least one real repository file.
Does NOT evaluate semantic quality - only mechanical presence of real file references.

Exit codes:
  0 = PASS (at least one valid file reference found)
  1 = WARNING (no valid file references found)
  2 = ERROR (usage/internal error)
"""

import sys
import os
import re
import subprocess
from pathlib import Path

# Patterns that look like file references in hostile analysis
FILE_REF_PATTERNS = [
    # Standard paths
    r'(?:scripts|lib|aes|docs|tests|bin|\.aes)/[\w\-./]+\.(?:py|sh|md|yml|yaml|json|txt|dot|toml|cfg|ini)',
    r'(?:scripts|lib|aes|docs|tests|bin|\.aes)/[\w\-./]+',
    # Makefile
    r'Makefile',
    # File with line number: file.py:42 or file.sh:10-20
    r'[\w\-./]+\.(?:py|sh|md|yml|yaml|json):\d+(?:-\d+)?',
    # Bare filenames with extension in known dirs
    r'(?:scripts|lib|aes|docs|tests|bin|\.aes)/[\w\-]+\.(?:py|sh|md|yml|yaml|json|txt|dot|toml|cfg|ini)',
]

# Compile regexes
COMPILED_PATTERNS = [re.compile(p) for p in FILE_REF_PATTERNS]

# Get all tracked files in the repo
def get_repo_files():
    """Get set of all tracked files in the git repository.
    Falls back to filesystem scan if git is unavailable."""
    try:
        result = subprocess.run(
            ['git', 'ls-files'],
            capture_output=True,
            text=True,
            cwd=os.getcwd()
        )
        if result.returncode == 0:
            files = set(result.stdout.strip().split('\n'))
            if files and files != {''}:
                return files
    except Exception:
        pass
    
    # Fallback: scan filesystem for real files
    print("WARNING: git ls-files unavailable — falling back to filesystem scan", file=sys.stderr)
    files = set()
    for f in Path('.').rglob('*'):
        if f.is_file() and not f.name.startswith('.git'):
            rel = f.relative_to('.')
            files.add(str(rel))
    return files

REPO_FILES = get_repo_files()

def extract_candidate_refs(text):
    """Extract all candidate file references from text."""
    candidates = set()
    for pattern in COMPILED_PATTERNS:
        for match in pattern.finditer(text):
            ref = match.group(0)
            # Clean up trailing punctuation
            ref = ref.rstrip('.,;:)]}"\'')
            candidates.add(ref)
    return candidates

def normalize_ref(ref):
    """Normalize a reference for comparison with repo files."""
    # Remove line numbers
    ref = re.sub(r':\d+(?:-\d+)?$', '', ref)
    # Remove trailing slash
    ref = ref.rstrip('/')
    return ref

def is_valid_ref(ref):
    """Check if a reference corresponds to a real repository file."""
    normalized = normalize_ref(ref)
    
    # Direct match
    if normalized in REPO_FILES:
        return True
    
    # For directory paths, check if it's a tracked directory
    # (git doesn't track empty dirs, so check if any file starts with this path)
    if os.path.isdir(normalized):
        # Check if any tracked file is under this directory
        for f in REPO_FILES:
            if f.startswith(normalized + '/'):
                return True
    
    return False

def lint_hostile_analysis(text):
    """
    Lint hostile analysis text.
    Returns (passed: bool, message: str, valid_refs: list)
    """
    if not text or not text.strip():
        return False, "Empty hostile analysis text", []
    
    candidates = extract_candidate_refs(text)
    
    if not candidates:
        return False, "No file references found in hostile analysis", []
    
    valid_refs = []
    invalid_refs = []
    
    for ref in candidates:
        if is_valid_ref(ref):
            valid_refs.append(ref)
        else:
            invalid_refs.append(ref)
    
    if valid_refs:
        return True, f"PASS: {len(valid_refs)} valid file reference(s) found", valid_refs
    else:
        return False, f"WARNING: No valid repository file references found. Found {len(invalid_refs)} candidate(s) but none match tracked files.", []

def main():
    if len(sys.argv) > 1 and sys.argv[1] in ('-h', '--help'):
        print("Usage: hostile-analysis-lint.py [FILE]")
        print("Reads hostile analysis text from FILE or stdin.")
        print("Exit codes: 0=PASS, 1=WARNING (no valid refs), 2=ERROR")
        return 0
    
    # Read input
    if len(sys.argv) > 1:
        input_file = sys.argv[1]
        try:
            with open(input_file, 'r') as f:
                text = f.read()
        except Exception as e:
            print(f"ERROR: Cannot read {input_file}: {e}", file=sys.stderr)
            return 2
    else:
        text = sys.stdin.read()
    
    passed, message, valid_refs = lint_hostile_analysis(text)
    
    if passed:
        print(f"PASS: {message}")
        if valid_refs:
            for ref in valid_refs:
                print(f"  ✓ {ref}")
        return 0
    else:
        print(f"WARNING: {message}", file=sys.stderr)
        return 1

if __name__ == '__main__':
    sys.exit(main())