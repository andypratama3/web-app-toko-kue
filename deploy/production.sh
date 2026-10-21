#!/usr/bin/env bash
#
# deploy/production.sh — 1 perintah deploy aman ke production.
#
# Urutan: preflight -> backup DB (restore test) -> maintenance -> pull -> migrate
#         -> verifikasi data -> up -> smoke test. Gagal = situs dikembalikan normal,
#         data tidak dihapus, backup tetap aman, perintah rollback dicetak.
#
# Cara pakai:
#   bash deploy/production.sh                        # deploy normal
#   bash deploy/production.sh --dry-run              # hanya cek, tidak mengubah apa pun
#   bash deploy/production.sh --allow-destructive     # izinkan migration destruktif
#
# Env override: APP_DIR BRANCH BACKUP_DIR WORK_DIR BASE_URL

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$HOME/domains/kuepandanasli.com/public_html}"
BRANCH="${BRANCH:-Main-V-1-0-0}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/db-backups}"
WORK_DIR="${WORK_DIR:-$HOME/deploy}"
BASE_URL="${BASE_URL:-https://kuepandanasli.com}"
RUN_ID="$(date +%Y%m%d-%H%M%S)"
LOG_DIR="$WORK_DIR/logs"
STATE_DIR="$WORK_DIR/state"
LOG_FILE="$LOG_DIR/deploy-$RUN_ID.log"
LOCK_FILE="$WORK_DIR/deploy.lock"
MYCNF=""
MAINT=0
OLD_SHA=""
NEW_SHA=""
BACKUP_FILE=""
ALLOW_DESTRUCTIVE=0
DRY_RUN=0
CLEAN_LEGACY=0
NEED_COMPOSER=0
MOVED_ASIDE=()
DB_NAME=""; DB_USER=""; DB_HOST=""; DB_PORT=""

c_r=$'\033[0m'; c_o=$'\033[32m'; c_w=$'\033[33m'; c_e=$'\033[31m'; c_c=$'\033[36m'; c_b=$'\033[1m'

log() { printf '%s %s\n' "$(date '+%H:%M:%S')" "$*" | tee -a "$LOG_FILE" >&2; }
ok()  { log "${c_o}[OK]${c_r}   $*"; }
warn(){ log "${c_w}[WARN]${c_r} $*"; }
err() { log "${c_e}[ERR]${c_r}  $*"; }
step(){ log ""; log "${c_b}=== $* ===${c_r}"; }
die() { err "$*"; on_error 1 "die: $*"; }

cleanup() {
  [ -n "$MYCNF" ] && rm -f "$MYCNF"
  rm -f "$LOCK_FILE" 2>/dev/null || true
}
trap cleanup EXIT

on_error() {
  local code="${1:-1}" line="${2:-?}"
  err "Deploy GAGAL (exit $code, baris $line). Tidak ada data yang dihapus."
  if [ "${#MOVED_ASIDE[@]}" -gt 0 ]; then
    for f in "${MOVED_ASIDE[@]}"; do
      [ -e "$f" ] || continue
      mkdir -p "$(dirname "$f")"
      mv -f "$WORK_DIR/untracked-$RUN_ID/$(echo "$f" | tr '/' '_')" "$f" 2>/dev/null \
        && warn "File '$f' dikembalikan seperti semula."
    done
  fi
  if [ "$MAINT" = "1" ]; then
    (cd "$APP_DIR" && php artisan up >>"$LOG_FILE" 2>&1) || true
    ok "Maintenance mode dimatikan, situs online kembali."
  fi
  err "Rollback manual (backup tetap aman di ${BACKUP_FILE:-<tidak ada>}) :"
  cat <<EOF
  cd $APP_DIR
  git reset --hard ${OLD_SHA:-<sha-sebelum>}
  php artisan migrate:rollback --force
  php artisan optimize:clear
  php artisan route:cache
  # pulihkan data (hanya bila perlu; data baru akan hilang):
  # gunzip -c ${BACKUP_FILE:-<backup>} | mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME
EOF
  exit "$code"
}
trap 'on_error $? $LINENO' ERR

while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY_RUN=1 ;;
    --allow-destructive) ALLOW_DESTRUCTIVE=1 ;;
    --clean-legacy) CLEAN_LEGACY=1 ;;
    --branch) BRANCH="$2"; shift ;;
    -h|--help) sed -n '2,18p' "$0"; exit 0 ;;
    *) die "Argumen tidak dikenal: $1" ;;
  esac
  shift
done

mkdir -p "$LOG_DIR" "$STATE_DIR" "$BACKUP_DIR"
exec 9>"$LOCK_FILE"
flock -n 9 || die "Ada deploy lain yang sedang jalan (lock: $LOCK_FILE)."

step "PREFLIGHT"
[ -d "$APP_DIR" ] || die "APP_DIR tidak ditemukan: $APP_DIR"
cd "$APP_DIR"
for c in git php mysqldump mysql curl gzip flock sed awk; do
  command -v "$c" >/dev/null 2>&1 || die "Perintah '$c' tidak ada di server."
done
[ -f .env ] || die ".env tidak ditemukan di $APP_DIR"
[ -d .git ] || die "Bukan repository git: $APP_DIR"

setup_mysql() {
  local pw
  pw=$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '\r')
  pw=${pw#\'}; pw=${pw%\'}; pw=${pw#\"}; pw=${pw%\"}
  DB_NAME=$(grep -m1 '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '\r')
  DB_USER=$(grep -m1 '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '\r')
  DB_HOST=$(grep -m1 '^DB_HOST=' .env | cut -d= -f2- | tr -d '\r')
  DB_PORT=$(grep -m1 '^DB_PORT=' .env | cut -d= -f2- | tr -d '\r')
  DB_HOST=${DB_HOST:-127.0.0.1}; DB_PORT=${DB_PORT:-3306}
  MYCNF=$(mktemp /tmp/deploy-my.XXXXXX.cnf)
  chmod 600 "$MYCNF"
  printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' \
    "$DB_HOST" "$DB_PORT" "$DB_USER" "$pw" > "$MYCNF"
}
setup_mysql
db() { mysql --defaults-extra-file="$MYCNF" -N -B -e "$1"; }

db "SELECT 1" >/dev/null 2>&1 || die "Tidak bisa konek ke database $DB_NAME di $DB_HOST:$DB_PORT"
ok "Database terhubung: $DB_NAME @ $DB_HOST:$DB_PORT"

AVAIL_GB=$(df -BG --output=avail "$APP_DIR" | tail -1 | tr -dc '0-9')
[ "${AVAIL_GB:-0}" -ge 2 ] || die "Disk tersisa < 2GB, deploy dibatalkan."
ok "Disk tersisa ${AVAIL_GB}GB"

OLD_SHA=$(git rev-parse --short HEAD)
CUR_BRANCH=$(git rev-parse --abbrev-ref HEAD)
ok "Repo: branch $CUR_BRANCH @ $OLD_SHA"
[ "$CUR_BRANCH" = "$BRANCH" ] || warn "Branch aktif '$CUR_BRANCH' bukan '$BRANCH' (pull tetap ke $BRANCH)."

DIRTY=$(git status --porcelain | grep -v '^ D public/storage/.gitignore' | grep -v '^?? \.env\.bak' || true)
if [ -n "$DIRTY" ]; then
  warn "File tak terlacak / berubah lokal di server (dicek bentrok di bawah):"
  printf '%s\n' "$DIRTY" | head -20 | tee -a "$LOG_FILE" >&2
fi

if [ "$CLEAN_LEGACY" = "1" ]; then
  step "BERSIHKAN FILE LIAR (dipindah, tidak dihapus)"
  LEGACY_DIR="$WORK_DIR/legacy-bak/$RUN_ID"
  FIND_OUT="$STATE_DIR/legacy-find-$RUN_ID.txt"
  find . -maxdepth 6 -type f \
      \( -name '*.bak-*' -o -name '*.bak_*' -o -name 'fix_migrations_records.php' -o -path './public/diag-payload.json' \) \
      -not -path './vendor/*' -not -path './.git/*' -not -path './node_modules/*' > "$FIND_OUT" 2>/dev/null || true
  if [ "$DRY_RUN" = "1" ]; then
    ok "DRY RUN: $(wc -l < "$FIND_OUT" | tr -dc '0-9') file akan dipindah ke $LEGACY_DIR (belum dipindah)."
    rm -f "$FIND_OUT"
  else
    mkdir -p "$LEGACY_DIR"
    LEGACY_N=0
    while IFS= read -r f; do
      [ -n "$f" ] || continue
      rel=$(echo "$f" | sed 's|^\./||; s|/|__|g')
      if mv -f "$f" "$LEGACY_DIR/$rel" 2>/dev/null; then
        LEGACY_N=$((LEGACY_N + 1))
        printf '%s -> %s\n' "$f" "$LEGACY_DIR/$rel" >> "$LEGACY_DIR/MANIFEST.txt"
      fi
    done < "$FIND_OUT"
    rm -f "$FIND_OUT"
    ok "$LEGACY_N file liar dipindah ke $LEGACY_DIR (tidak dihapus, ada MANIFEST.txt)."
  fi
fi

git fetch origin "$BRANCH" >>"$LOG_FILE" 2>&1
NEW_SHA=$(git rev-parse --short "origin/$BRANCH")
N_COMMITS=$(git rev-list --count "HEAD..origin/$BRANCH" 2>/dev/null || echo 0)
ok "Target: origin/$BRANCH @ $NEW_SHA ($N_COMMITS commit menunggu)"

MERGE_BASE=$(git merge-base HEAD "origin/$BRANCH")
CHANGED=$(git diff --name-only "$MERGE_BASE" "origin/$BRANCH" || true)

MODIFIED=$(git diff --name-only | grep -v '^public/storage/.gitignore' || true)
STAGED=$(git diff --cached --name-only | grep -v '^public/storage/.gitignore' || true)
UNTRACKED=$(git ls-files --others --exclude-standard || true)
CONFLICT=0
for f in $MODIFIED $STAGED; do
  if printf '%s\n' "$CHANGED" | grep -qxF "$f"; then
    err "Konflik: '$f' diubah lokal dan juga berubah di commit baru."
    CONFLICT=1
  fi
done
[ "$CONFLICT" = "0" ] || die "Selesaikan konflik lokal (commit/stash) lalu deploy ulang."
ok "Tidak ada bentrok antara perubahan lokal dan commit baru."

if printf '%s\n' "$CHANGED" | grep -qE '^(composer\.json|composer\.lock)$'; then
  NEED_COMPOSER=1
  ok "composer berubah -> composer install akan dijalankan."
else
  ok "composer tidak berubah -> tidak perlu composer install."
fi
if printf '%s\n' "$CHANGED" | grep -qE '^package\.json$'; then
  warn "package.json berubah tapi node/npm tidak ada di server -> build frontend manual."
fi
HAS_PROC_OPEN=$(php -r 'echo function_exists("proc_open") ? "yes" : "no";' 2>/dev/null || echo "no")
if [ "$HAS_PROC_OPEN" = "no" ]; then
  warn "PHP server menonaktifkan proc_open/exec (hosting) -> composer tidak bisa dijalankan di server."
  if [ "$NEED_COMPOSER" = "1" ]; then
    die "composer berubah tapi server tidak bisa menjalankan composer. Jalankan 'composer install' di lokal lalu salin vendor/ secara manual."
  fi
fi

step "CEK KEAMANAN MIGRASI (hanya method up())"
PENDING_MIG=$(printf '%s\n' "$CHANGED" | grep '^database/migrations/' || true)
if [ -n "$PENDING_MIG" ]; then
  for m in $PENDING_MIG; do
    UP_BODY=$(git show "origin/$BRANCH:$m" 2>/dev/null | awk '/function up\(/{f=1} /function down\(/{f=0} f' || true)
    if printf '%s' "$UP_BODY" | grep -qiE "dropColumn|dropTable|truncate\(|renameColumn|->delete\(|DB::statement\(.?DROP|dropForeign|dropUnique|dropPrimary"; then
      err "Migration destruktif terdeteksi: $m"
      printf '%s\n' "$UP_BODY" | grep -inE "dropColumn|dropTable|truncate\(|renameColumn|->delete\(|DB::statement\(.?DROP" | head -5 | tee -a "$LOG_FILE" >&2
      [ "$ALLOW_DESTRUCTIVE" = "1" ] || die "Migrasi destruktif diblokir. Tinjau manual, lalu jalankan ulang dengan --allow-destructive."
      warn "Tetap lanjut karena --allow-destructive."
    else
      ok "Aman (aditif): $m"
    fi
  done
else
  ok "Tidak ada migration baru pada commit yang akan diambil."
fi

if [ "$DRY_RUN" = "1" ]; then
  step "DRY RUN — tidak ada yang diubah"
  log "Rencana: backup+restore-test -> maintenance -> pull $NEW_SHA -> optimize:clear -> migrate -> verifikasi data -> up -> smoke test"
  exit 0
fi

step "BACKUP DATA"
SNAP_BEFORE="$STATE_DIR/counts-before-$RUN_ID.tsv"
snapshot_counts() {
  local out="$1" q="" t tables
  tables=$(db "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_type='BASE TABLE' ORDER BY table_name")
  : > "$out"
  printf '%s\n' "$tables" | grep -v '^$' | sort > "${out%.tsv}.tbl"
  for t in $tables; do
    [ -n "$q" ] && q="$q UNION ALL "
    q="$qSELECT '$t' AS t, COUNT(*) AS c FROM \`$t\`"
  done
  db "$q" > "$out"
}
snapshot_counts "$SNAP_BEFORE"
TABLE_COUNT=$(wc -l < "$SNAP_BEFORE" | tr -dc '0-9')
[ "${TABLE_COUNT:-0}" -gt 0 ] || die "Snapshot tabel kosong — abort."
ok "Snapshot baris $TABLE_COUNT tabel -> $SNAP_BEFORE"

cp -a .env "$STATE_DIR/env-$RUN_ID.backup" && chmod 600 "$STATE_DIR/env-$RUN_ID.backup"
ok "Salinan .env disimpan (600)."

BACKUP_FILE="$BACKUP_DIR/pre-deploy-$RUN_ID.sql.gz"
mysqldump --defaults-extra-file="$MYCNF" --single-transaction --quick --default-character-set=utf8mb4 \
  --hex-blob --routines --triggers --events "$DB_NAME" 2>>"$LOG_FILE" | gzip -c > "$BACKUP_FILE"
[ -s "$BACKUP_FILE" ] || die "Backup DB kosong."
gzip -t "$BACKUP_FILE" || die "Backup DB korup (gzip test gagal)."
ok "Backup DB: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

step "VERIFIKASI BACKUP (restore test nyata)"
TMP_SQL="$STATE_DIR/verify-$RUN_ID.sql"
gunzip -c "$BACKUP_FILE" > "$TMP_SQL"
DUMP_TABLES=$(grep -c '^CREATE TABLE' "$TMP_SQL" || true)
[ "$DUMP_TABLES" = "$TABLE_COUNT" ] || die "Jumlah tabel di dump ($DUMP_TABLES) != DB ($TABLE_COUNT)."
ok "Struktur dump lengkap: $DUMP_TABLES tabel."

RESTORE_OK=1
for t in orders customers whatsapp_conversations users products; do
  grep -q "^CREATE TABLE \`$t\`" "$TMP_SQL" || continue
  live_rows=$(db "SELECT COUNT(*) FROM \`$t\`")
  live_sum=$(db "CHECKSUM TABLE \`$t\`" | awk '{print $2}')
  db "DROP TABLE IF EXISTS \`__deploy_rt_$t\`" >/dev/null
  db "CREATE TABLE \`__deploy_rt_$t\` LIKE \`$t\`" >/dev/null
  sed "s/^INSERT INTO \`$t\` VALUES/INSERT INTO \`__deploy_rt_$t\` VALUES/" "$TMP_SQL" \
    | grep "^INSERT INTO \`__deploy_rt_$t\`" \
    | mysql --defaults-extra-file="$MYCNF" "$DB_NAME" >>"$LOG_FILE" 2>&1 || true
  rt_rows=$(db "SELECT COUNT(*) FROM \`__deploy_rt_$t\`")
  rt_sum=$(db "CHECKSUM TABLE \`__deploy_rt_$t\`" | awk '{print $2}')
  if [ "$rt_rows" != "$live_rows" ]; then
    err "Restore test '$t' GAGAL: baris live=$live_rows hasil restore=$rt_rows"
    RESTORE_OK=0
  elif [ "$rt_sum" != "$live_sum" ]; then
    warn "Restore test '$t': jumlah baris sama ($rt_rows) tapi checksum beda (live=$live_sum restore=$rt_sum)."
  else
    ok "Restore test '$t' identik (checksum $live_sum, $rt_rows baris)."
  fi
  db "DROP TABLE IF EXISTS \`__deploy_rt_$t\`" >/dev/null
done
[ "$RESTORE_OK" = "1" ] || die "Backup tidak terbukti bisa dipulihkan — deploy dibatalkan."
rm -f "$TMP_SQL"
ok "Backup terbukti bisa dipulihkan."

step "MAINTENANCE MODE"
php artisan down --retry=60 >>"$LOG_FILE" 2>&1 || true
MAINT=1
ok "Situs Mode Maintenance (503) sampai deploy selesai."

step "TARIK KODE"
CONFLICT2=0
for f in $UNTRACKED; do
  if git cat-file -e "origin/$BRANCH:$f" 2>/dev/null; then
    if git show "origin/$BRANCH:$f" | cmp -s - "$f"; then
      mkdir -p "$WORK_DIR/untracked-$RUN_ID"
      mv -f "$f" "$WORK_DIR/untracked-$RUN_ID/$(echo "$f" | tr '/' '_')"
      MOVED_ASIDE+=("$f")
      ok "'$f' identik dengan versi repo -> dipindah aside (aman, tidak hilang)."
    else
      err "'$f' ada di server dan versi repo berbeda -> deploy diblokir, periksa manual."
      CONFLICT2=1
    fi
  fi
done
[ "$CONFLICT2" = "0" ] || die "Ada file bentrok."
git pull --ff-only origin "$BRANCH" 2>&1 | tee -a "$LOG_FILE"
ok "Kode diperbarui: $OLD_SHA -> $(git rev-parse --short HEAD)"

if [ "$NEED_COMPOSER" = "1" ]; then
  step "COMPOSER INSTALL"
  composer install --no-dev --no-interaction --optimize-autoloader 2>&1 | tee -a "$LOG_FILE"
  ok "Dependency diperbarui."
fi

step "CACHE"
HAD_ROUTE=0; [ -f bootstrap/cache/routes-v7.php ] && HAD_ROUTE=1
HAD_CONFIG=0; [ -f bootstrap/cache/config.php ] && HAD_CONFIG=1
php artisan optimize:clear 2>&1 | tee -a "$LOG_FILE"
ok "Cache dibersihkan (route/config/view/event)."

step "MIGRASI"
php artisan migrate --pretend --force 2>&1 | tee -a "$LOG_FILE" || true
php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"
ok "Migrasi selesai."

step "VERIFIKASI DATA (tidak boleh ada data hilang)"
SNAP_AFTER="$STATE_DIR/counts-after-$RUN_ID.tsv"
snapshot_counts "$SNAP_AFTER"
FAIL=0
while IFS=$'\t' read -r t c; do
  [ -n "$t" ] || continue
  c2=$(awk -F'\t' -v k="$t" '$1==k{print $2}' "$SNAP_AFTER")
  if [ -z "$c2" ]; then err "Tabel '$t' hilang setelah migrasi!"; FAIL=1; continue; fi
  if [ "$c2" -lt "$c" ]; then err "Baris tabel '$t' turun: $c -> $c2"; FAIL=1; fi
done < "$SNAP_BEFORE"
NEW_TABLES=$(comm -13 "$STATE_DIR/counts-before-$RUN_ID.tbl" "$STATE_DIR/counts-after-$RUN_ID.tbl" | tr '\n' ' ')
[ -n "$(echo "$NEW_TABLES" | tr -d ' ')" ] && ok "Tabel baru: $NEW_TABLES"
[ "$FAIL" = "0" ] || die "Verifikasi data gagal — ada data yang hilang/berkurang."
ok "Jumlah baris semua tabel utuh (tidak ada data hilang)."

MUTATED=$(db "SELECT COUNT(*) FROM \`orders\` WHERE \`delivery_method\` IS NOT NULL" 2>/dev/null || echo "n/a")
if [ "$MUTATED" = "0" ]; then
  ok "Order lama tidak tersentuh migrasi (delivery_method masih NULL)."
else
  warn "Ada $MUTATED order dengan delivery_method terisi."
fi
REG_OK=$(db "SELECT COUNT(*) FROM \`regions\` WHERE \`latitude\` IS NOT NULL AND \`longitude\` IS NOT NULL" 2>/dev/null || echo 0)
if [ "${REG_OK:-0}" -ge 1 ]; then ok "Koordinat region ter-backfill: $REG_OK baris."; else warn "Koordinat region belum terisi."; fi
ZONES=$(db "SELECT COUNT(*) FROM \`delivery_zones\`" 2>/dev/null || echo 0)
ok "Zona pengiriman utuh: $ZONES zona."

PENDING_AFTER=$(php artisan migrate:status 2>/dev/null | grep -c 'Pending' || true)
[ "${PENDING_AFTER:-0}" = "0" ] || die "Masih ada migration Pending setelah migrate."
ok "Semua migration sudah Ran."

step "PULIHKAN KONFIGURASI CACHE"
if [ "$HAD_ROUTE" = "1" ]; then
  if php artisan route:cache 2>&1 | tee -a "$LOG_FILE"; then
    ok "Route cache dibuat ulang."
  else
    warn "route:cache gagal — situs tetap jalan (route dibaca dari file)."
  fi
fi
if [ "$HAD_CONFIG" = "1" ]; then
  php artisan config:cache 2>&1 | tee -a "$LOG_FILE" || warn "config:cache gagal."
fi

step "SITUS ONLINE"
php artisan up >>"$LOG_FILE" 2>&1 || true
MAINT=0
ok "Maintenance mode dimatikan."

step "SMOKE TEST"
sleep 2
SMOKE_FAIL=0
for path in / /login; do
  code=$(curl -s -o /dev/null -w '%{http_code}' -m 25 "$BASE_URL$path" || echo 000)
  if [ "$code" = "200" ]; then ok "$path -> $code"; else err "$path -> $code (harapan 200)"; SMOKE_FAIL=1; fi
done
ADMIN_CODE=$(curl -s -o /dev/null -w '%{http_code}' -m 25 "$BASE_URL/admin/regions" || echo 000)
case "$ADMIN_CODE" in 200|302) ok "/admin/regions -> $ADMIN_CODE (butuh auth)" ;; *) warn "/admin/regions -> $ADMIN_CODE" ;; esac
php artisan --version 2>&1 | tee -a "$LOG_FILE" | head -3
php artisan env 2>&1 | tee -a "$LOG_FILE" | grep -E "^APP_ENV|^APP_DEBUG|^APP_URL" | head -5 || true
[ "$SMOKE_FAIL" = "0" ] || die "Smoke test gagal."

if [ -f "$APP_DIR/deploy/production.sh" ]; then
  install -m 700 "$APP_DIR/deploy/production.sh" "$WORK_DIR/production.sh" 2>/dev/null || true
fi

step "SELESAI"
log "Commit   : $OLD_SHA -> $(git rev-parse --short HEAD) (branch $BRANCH)"
log "Backup   : $BACKUP_FILE"
log "Snapshot : $SNAP_BEFORE | $SNAP_AFTER"
log "Log      : $LOG_FILE"
log "Rollback : git reset --hard $OLD_SHA && php artisan migrate:rollback --force && php artisan optimize:clear"
ok "Deploy sukses, tidak ada data yang hilang."
