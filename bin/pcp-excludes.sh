#!/usr/bin/env bash
#
# Plugin Check (PCP) の除外フラグを .distignore から生成する。
# .distignore を「配布されないもの = チェックしないもの」の単一の真実とし、
# 各エントリをファイル/ディレクトリに振り分けて以下を出力する:
#
#   --exclude-directories=dir1,dir2 --exclude-files=path/to/file1,file2
#
# 使い方: bash bin/pcp-excludes.sh [path-to-.distignore]
#
set -euo pipefail

distignore="${1:-.distignore}"
[ -f "$distignore" ] || { echo ""; exit 0; }

dirs=()
files=()

while IFS= read -r raw || [ -n "$raw" ]; do
	line="${raw%%#*}"                          # コメント除去
	line="${line#"${line%%[![:space:]]*}"}"    # 前方の空白除去
	line="${line%"${line##*[![:space:]]}"}"    # 後方の空白除去
	[ -z "$line" ] && continue

	path="${line#/}"                           # 先頭スラッシュ除去（root相対）
	if [ -d "$path" ]; then
		dirs+=("$(basename "$path")")          # PCPはディレクトリ名で一致
	elif [ -f "$path" ]; then
		files+=("$path")                       # ファイルはroot相対パス
	else
		# 実在しないエントリは末尾/・拡張子で推測（不在なら除外対象が無く無害）
		case "$line" in
			*/) dirs+=("$(basename "$path")") ;;
			*.*) files+=("$path") ;;
			*) dirs+=("$(basename "$path")") ;;
		esac
	fi
done < "$distignore"

args=""
[ ${#dirs[@]} -gt 0 ]  && args="--exclude-directories=$(IFS=,; echo "${dirs[*]}")"
[ ${#files[@]} -gt 0 ] && args="$args --exclude-files=$(IFS=,; echo "${files[*]}")"
echo "$args"
