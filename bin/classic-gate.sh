#!/usr/bin/env bash
set -euo pipefail

TAG=v4.1.4
cd "$(git rev-parse --show-toplevel)"

if ! git rev-parse --verify --quiet "refs/tags/$TAG^{commit}" >/dev/null; then
    echo "Tag $TAG is missing. Fetch tags first: git fetch --tags" >&2
    exit 2
fi

paths=()
while IFS= read -r path; do
    paths+=("$path")
done < <(
    git ls-tree -r --name-only "$TAG" tests \
        | grep -E '(Test\.php$|^tests/Fixtures/|^tests/database/migrations/|^tests/StandaloneTestCase\.php$)'
)

changed=$(
    {
        git diff --name-only "$TAG" -- "${paths[@]}"
        git diff --cached --name-only "$TAG" -- "${paths[@]}"
    } | sort -u
)

if [ -n "$changed" ]; then
    echo "Released tests, fixtures or test schema changed since $TAG:" >&2
    echo "$changed" | sed 's/^/  /' >&2
    exit 1
fi

echo "Classic gate passed: released tests, fixtures and test schema match $TAG."
