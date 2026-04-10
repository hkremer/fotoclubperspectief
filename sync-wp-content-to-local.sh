#!/usr/bin/env bash
set -euo pipefail

# Sync wp-content from this git repo to Local site.
# Default behavior is safe: no --delete and uploads excluded.

SRC_DEFAULT="/Users/herman.kremer/wordpress/wp-content"
DEST_DEFAULT="/Users/herman.kremer/Local Sites/fotoclub-perspectief-hilversum/app/public/wp-content"
DEST_FALLBACK="/Users/herman.kremer/Local Sites/fotoclub-perspectief/app/public/wp-content"

SRC="${1:-$SRC_DEFAULT}"
DEST="${2:-$DEST_DEFAULT}"

DELETE_FLAG=""
INCLUDE_UPLOADS="0"

for arg in "${@:3}"; do
  case "$arg" in
    --delete)
      DELETE_FLAG="--delete"
      ;;
    --with-uploads)
      INCLUDE_UPLOADS="1"
      ;;
    *)
      echo "Onbekende optie: $arg"
      echo "Gebruik: $0 [src] [dest] [--delete] [--with-uploads]"
      exit 1
      ;;
  esac
done

if [[ ! -d "$SRC" ]]; then
  echo "Bronmap bestaat niet: $SRC"
  exit 1
fi

if [[ ! -d "$DEST" ]]; then
  if [[ "$DEST" == "$DEST_DEFAULT" && -d "$DEST_FALLBACK" ]]; then
    DEST="$DEST_FALLBACK"
    echo "Doelmap niet gevonden op standaardpad, fallback gebruikt:"
    echo "  $DEST"
  else
    echo "Doelmap bestaat niet: $DEST"
    exit 1
  fi
fi

echo "Sync gestart"
echo "  Van: $SRC"
echo "  Naar: $DEST"
[[ -n "$DELETE_FLAG" ]] && echo "  Optie: --delete AAN"
[[ "$INCLUDE_UPLOADS" == "1" ]] && echo "  Optie: uploads meenemen"

RSYNC_ARGS=(
  -avh
  --progress
  "$DELETE_FLAG"
  --exclude ".git"
  --exclude ".git/"
  --exclude ".DS_Store"
  --exclude "cache/"
)

if [[ "$INCLUDE_UPLOADS" != "1" ]]; then
  RSYNC_ARGS+=(--exclude "uploads/")
fi

# Remove empty element if --delete not set.
RSYNC_ARGS=("${RSYNC_ARGS[@]/#/}")

rsync "${RSYNC_ARGS[@]}" "$SRC/" "$DEST/"

echo "Klaar."
