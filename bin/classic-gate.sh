#!/usr/bin/env bash
set -euo pipefail

TAG=v4.1.4
cd "$(git rev-parse --show-toplevel)"

if ! git rev-parse --verify --quiet "refs/tags/$TAG^{commit}" >/dev/null; then
    echo "Tag $TAG is missing. Fetch tags first: git fetch --tags" >&2
    exit 2
fi

failed=0

changed=$(
    git ls-tree -r --name-only "$TAG" tests \
        | grep -E '(Test\.php$|^tests/Fixtures/)' \
        | while IFS= read -r path; do
            git diff --name-only "$TAG" -- "$path"
        done
)

if [ -n "$changed" ]; then
    echo "Released tests or fixtures changed since $TAG:" >&2
    echo "$changed" | sed 's/^/  /' >&2
    failed=1
fi

assets=$(
    awk '/function getAssets\(/{f=1} f{print} f&&/^    }/{exit}' src/FlowforgeServiceProvider.php \
        | grep -cE '::make\(' || true
)

if [ "$assets" != "1" ]; then
    echo "FlowforgeServiceProvider::getAssets() must list exactly one asset, found $assets." >&2
    failed=1
fi

if [ "$failed" -eq 0 ]; then
    echo "Classic gate passed: released tests and fixtures match $TAG."
fi

exit "$failed"
