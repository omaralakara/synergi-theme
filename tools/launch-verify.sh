#!/usr/bin/env bash
# Launch verification for synergi.ae — read-only HTTP checks from outside the server.
#
# Run from the repo root after tools/launch-after-activation.php:
#   bash tools/launch-verify.sh [base-url]
#
# Reads tools/launch-urls-before.txt (every URL published before launch — none
# may break, CLAUDE.md §2.8) and tools/launch-redirect-origins.txt (every
# redirect origin — each must be one 301 to a 200). Prints a line per failure
# and a summary; exits 1 if anything failed. Changes nothing anywhere.

set -u

BASE="${1:-https://synergi.ae}"
DIR="$(cd "$(dirname "$0")" && pwd)"
fails=0
checks=0

ok()   { checks=$((checks + 1)); }
fail() { checks=$((checks + 1)); fails=$((fails + 1)); echo "FAIL  $*"; }

# One request, redirects NOT followed: prints "<code> <location>".
# Read from the response headers (-I) rather than curl's -w: the Windows
# (Schannel) curl in Git Bash exits 43 on any -w write-out, which on 10 Sep
# turned every status check into a false "000".
probe() {
	local head code loc
	head=$(curl -sI --max-time 20 "$1" | tr -d '\r')
	code=$(printf '%s\n' "$head" | awk 'toupper($1) ~ /^HTTP\// { c = $2 } END { print (c == "" ? "000" : c) }')
	loc=$(printf '%s\n' "$head" | awk 'tolower($1) == "location:" { print $2; exit }')
	case "$loc" in /*) loc="$BASE$loc" ;; esac
	echo "$code $loc"
}

# Healthy = 200, or exactly one 301 that lands on a 200. $2 = "must-redirect" for origins.
check_url() {
	local url="$1" mode="${2:-}" first code loc second
	first=$(probe "$url")
	code=${first%% *}
	loc=${first#* }
	if [ "$code" = "200" ]; then
		if [ "$mode" = "must-redirect" ]; then fail "$url answers 200 — the redirect is not being served"; else ok; fi
		return
	fi
	if [ "$code" != "301" ]; then fail "$url -> $code"; return; fi
	second=$(probe "$loc")
	if [ "${second%% *}" = "200" ]; then ok; else fail "$url -> 301 $loc -> ${second%% *} (chain or dead target)"; fi
}

# Page body, cache-busted so LiteSpeed cannot answer with a pre-launch copy.
page() { curl -s --max-time 20 "$BASE$1?synverify=$(date +%s%N)"; }

has()     { if printf '%s' "$2" | grep -qiE "$3"; then ok; else fail "$1: expected /$3/"; fi; }
hasnt()   { if printf '%s' "$2" | grep -qiE "$3"; then fail "$1: must not contain /$3/"; else ok; fi; }
one_h1()  { local n; n=$(printf '%s' "$2" | grep -o '<h1' | wc -l | tr -d ' '); if [ "$n" = "1" ]; then ok; else fail "$1: $n <h1> elements (want 1)"; fi; }

echo "== Pre-launch URLs ($DIR/launch-urls-before.txt): each 200, or one 301 to a 200"
while IFS= read -r path; do
	[ -z "$path" ] && continue
	check_url "$BASE$path"
done < "$DIR/launch-urls-before.txt"

echo "== Redirect origins ($DIR/launch-redirect-origins.txt): each one 301 to a 200"
while IFS= read -r origin; do
	[ -z "$origin" ] && continue
	case "$origin" in \#*) continue ;; esac
	check_url "$BASE/$origin/" must-redirect
done < "$DIR/launch-redirect-origins.txt"

echo "== Homepage"
home=$(page /)
has    "home" "$home" "<title>BPO Services in UAE &(amp;)? the Gulf \| Synergi Business Solutions</title>"
hasnt  "home" "$home" "<meta name=.robots. content=.[^'\"]*noindex"
has    "home" "$home" "rel=\"canonical\" href=\"$BASE/\""
one_h1 "home" "$home"
has    "home" "$home" "G-F8BHKGB935"
hasnt  "home" "$home" "staging\\.synergi\\.ae"
# The new theme's own markup, not its HTML comments: a cache-level minifier may strip comments.
has    "home" "$home" "class=\"syn-"

echo "== Golden pages"
for p in /our-services/procurement/ /our-solutions/shared-services/ /markets/saudi-arabia/ /about-us/ /contact-us/ /blog/ /case-studies/ /automating-hr-operations/ /synergi-partners-with-the-international-customer-experience-institute-icxi/; do
	body=$(page "$p")
	if [ -z "$body" ]; then fail "$p: empty response"; continue; fi
	hasnt  "$p" "$body" "<meta name=.robots. content=.[^'\"]*noindex"
	hasnt  "$p" "$body" "staging\\.synergi\\.ae"
	one_h1 "$p" "$body"
done
contact=$(page /contact-us/)
has "/contact-us/" "$contact" "wpforms-form"
has "/contact-us/" "$contact" "Phone"
blog=$(page /blog/)
has "/blog/" "$blog" "automating-hr-operations|workflow-automation-reducing-workflow-chaos"

echo "== /connect/ stays up and out of the index"
connect=$(page /connect/)
has "/connect/" "$connect" "linkedin\\.com/company/synergi-ae"
has "/connect/" "$connect" "<meta name=.robots. content=.noindex"

# Decided 10 Sep: case studies and their service archives stay out of search for now.
# A 404 page is noindexed too, so the 200 is checked first or this would pass on a dead URL.
echo "== Case-study archives answer, and stay out of the index"
check_url "$BASE/case-studies/service/human-resources/"
archive=$(page /case-studies/service/human-resources/)
has "/case-studies/service/human-resources/" "$archive" "<meta name=.robots. content=.noindex"

echo "== Search engines"
robots=$(curl -s --max-time 20 "$BASE/robots.txt")
hasnt "robots.txt" "$robots" "^Disallow: /[[:space:]]*$"
idx=$(curl -s --max-time 20 "$BASE/sitemap_index.xml")
has   "sitemap_index" "$idx" "page-sitemap\\.xml"
has   "sitemap_index" "$idx" "post-sitemap\\.xml"
hasnt "sitemap_index" "$idx" "syn_case_study-sitemap"
pages=$(curl -s --max-time 20 "$BASE/page-sitemap.xml")
has   "page-sitemap" "$pages" "$BASE/our-solutions/shared-services/"
has   "page-sitemap" "$pages" "$BASE/markets/saudi-arabia/"
hasnt "page-sitemap" "$pages" "/connect/|/our-leadership/|/our-approach/|/procurement-readiness/|/synergi-uae-2-2/"

echo
echo "$checks checks, $fails failed."
[ "$fails" -eq 0 ]
