#!/usr/bin/env bash
# Build an installable WordPress plugin ZIP in the project root.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="sikora-custom-login"
MAIN_FILE="${ROOT_DIR}/${PLUGIN_SLUG}.php"

if [[ ! -f "${MAIN_FILE}" ]]; then
	echo "error: missing main plugin file: ${MAIN_FILE}" >&2
	exit 1
fi

# Read Version and Plugin Name from the plugin header.
VERSION="$(
	grep -E '^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*' "${MAIN_FILE}" \
		| head -n 1 \
		| sed -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*//' \
		| tr -d '[:space:]'
)"

if [[ -z "${VERSION}" ]]; then
	echo "error: could not read Version from ${MAIN_FILE}" >&2
	exit 1
fi

PLUGIN_NAME="$(
	grep -E '^[[:space:]]*\*[[:space:]]*Plugin Name:[[:space:]]*' "${MAIN_FILE}" \
		| head -n 1 \
		| sed -E 's/^[[:space:]]*\*[[:space:]]*Plugin Name:[[:space:]]*//' \
		| sed -E 's/[[:space:]]+$//'
)"

if [[ -z "${PLUGIN_NAME}" ]]; then
	echo "error: could not read Plugin Name from ${MAIN_FILE}" >&2
	exit 1
fi

# Slugify the display name for the ZIP filename (e.g. "Sikora Custom Login" → sikora-custom-login.zip).
ZIP_SLUG="$(
	printf '%s' "${PLUGIN_NAME}" \
		| tr '[:upper:]' '[:lower:]' \
		| sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//'
)"
ZIP_NAME="${ZIP_SLUG}.zip"
ZIP_PATH="${ROOT_DIR}/${ZIP_NAME}"
BUILD_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${PLUGIN_SLUG}-build.XXXXXX")"
STAGE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

cleanup() {
	rm -rf "${BUILD_DIR}"
}
trap cleanup EXIT

mkdir -p "${STAGE_DIR}/assets"

# Only the files required for the distributable plugin.
INCLUDE_FILES=(
	"${PLUGIN_SLUG}.php"
	"uninstall.php"
	"readme.txt"
	"assets/admin.js"
	"assets/login.css"
)

for path in "${INCLUDE_FILES[@]}"; do
	src="${ROOT_DIR}/${path}"
	if [[ ! -f "${src}" ]]; then
		echo "error: required file missing: ${src}" >&2
		exit 1
	fi
	cp "${src}" "${STAGE_DIR}/${path}"
done

rm -f "${ZIP_PATH}"

(
	cd "${BUILD_DIR}"
	zip -r "${ZIP_PATH}" "${PLUGIN_SLUG}" >/dev/null
)

echo "Created ${ZIP_PATH} (version ${VERSION})"
echo
echo "Files included:"
unzip -Z1 "${ZIP_PATH}" | sort | while IFS= read -r entry; do
	# Skip directory-only entries for a cleaner file list.
	[[ "${entry}" == */ ]] && continue
	echo "  ${entry}"
done
FILE_COUNT="$(unzip -Z1 "${ZIP_PATH}" | grep -cv '/$' || true)"
echo
echo "Total files: ${FILE_COUNT}"
