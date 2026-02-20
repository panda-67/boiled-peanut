#!/usr/bin/env bash
set -euo pipefail

###############################################################################
# CONFIG
###############################################################################
# FTP credentials
FTP_KACANG_USER="kacang@api.notivra.com"
FTP_PUBLIC_KACANG_USER="frontend@api.notivra.com"
FTP_PUBLIC_APP_KACANG_USER="frontend@app.notivra.com"

FTP_HOST="ftp.notivra.com"
FTP_DIR="/"

# Local directories
DIR_KACANG="root_kacang"
DIR_PUBLIC_KACANG="public_html/kacang"
DIR_KACANG_APP="../kacang-frontend"

###############################################################################
# Functions
###############################################################################
ask_password() {
	local __varname=$1
	read -rsp "🔐  Enter password for $2: " "$__varname"
	echo
}

ftp_mirror() {
	local local_dir=$1
	local host=$2
	local user=$3
	local pass=$4
	local remote_dir=$5

	lftp -c "
    set ssl:verify-certificate no
    set ftp:ssl-force true
    set ftp:ssl-protect-data true
    set ftp:sync-mode yes
    set net:timeout 20
    set net:max-retries 2
    set mirror:use-pget-n 4
    open -u ${user},${pass} ${host}
    mirror -R \
      --only-newer \
      --parallel=4 \
      --no-symlinks \
      --exclude-glob=.ftpquota \
      --exclude-glob=.git \
      --exclude-glob=.github \
      --exclude-glob=*.log \
      --exclude=node_modules \
      --exclude=.env \
      --exclude=.env.testing \
      --exclude=tests \
      --exclude=notivra/ \
      --exclude=staging/ \
      --exclude=samsulmuarrif.my.id \
      \"${local_dir}\" \"${remote_dir}\"
    bye
  "
}

###############################################################################
# Main
###############################################################################
clear
echo "📦 Kacang FTP Deploy"
echo "-----------------------------------------"
echo "1) Deploy API kacang (FTP)"
echo "2) Deploy app kacang (FTP)"
echo "q) Quit"
echo "-----------------------------------------"
read -rp "Select an option: " choice

case "$choice" in
1)
	ask_password FTP_KACANG_PASS "$FTP_KACANG_USER"
	cd "$DIR_KACANG"
	composer install --no-dev --optimize-autoloader --classmap-authoritative
	php artisan optimize:clear

	echo "🚀 Uploading kacang..."
	ftp_mirror "." "$FTP_HOST" "$FTP_KACANG_USER" "$FTP_KACANG_PASS" "$FTP_DIR"
	echo "✅ FTP upload kacang completed."
	cd - >/dev/null

	echo "🚀 Uploading public kacang..."
	cd "$DIR_PUBLIC_KACANG"
	ftp_mirror "." "$FTP_HOST" "$FTP_PUBLIC_KACANG_USER" "$FTP_KACANG_PASS" "$FTP_DIR"
	cd - >/dev/null

	echo "✅ FTP upload public kacang completed."

	;;
2)
	ask_password FTP_PUBLIC_APP_KACANG_PASS "$FTP_PUBLIC_APP_KACANG_USER"
	echo "📦 Buliding app kacang frontend."
	cd "$DIR_KACANG_APP"
	pnpm run build

	echo "🚀 Uploading app kacang..."
	ftp_mirror "./out/" "$FTP_HOST" "$FTP_PUBLIC_APP_KACANG_USER" "$FTP_PUBLIC_APP_KACANG_PASS" "$FTP_DIR"
	cd - >/dev/null

	echo "✅ FTP upload app kacang completed."
	;;
q | Q)
	echo "👋 Cancelled."
	exit 0
	;;
*)
	echo "⚠️ Invalid choice."
	exit 1
	;;
esac

echo "🎉 Done."
