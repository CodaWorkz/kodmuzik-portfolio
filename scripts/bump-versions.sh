#!/usr/bin/env bash
# Sets ?v= on every /css/*.css and /js/*.js link to a fingerprint of that file
# (first 8 hex characters of its SHA-256). A changed file gets a new URL, an
# unchanged file keeps its URL. Browsers revalidate CSS/JS anyway (no-cache),
# but Google's renderer caches them for up to 30 days whatever the headers say,
# so a new URL is what makes Google fetch a changed file.
# Run from the repo root before a deploy:   bash scripts/bump-versions.sh
# Check only, exit 1 if a page is stale:    bash scripts/bump-versions.sh --check

set -u
cd "$(dirname "$0")/.." || exit 1

mode="write"
[ "${1:-}" = "--check" ] && mode="check"

pages=()
while IFS= read -r f; do pages+=("$f"); done \
  < <(git grep -lE '(href|src)="/(css|js)/' -- '*.html' '*.php')

perl -e '
  use strict; use warnings; use Digest::SHA;
  my $mode = shift @ARGV;
  my (%fp, @stale, $missing);
  sub fingerprint {
    my $f = shift;
    return $fp{$f} //= (-f $f ? substr(Digest::SHA->new(256)->addfile($f, "b")->hexdigest, 0, 8) : undef);
  }
  for my $page (@ARGV) {
    open my $in, "<:raw", $page or die "$page: $!";
    my $src = do { local $/; <$in> };
    close $in;
    (my $out = $src) =~ s{((?:href|src)="/((?:css|js)/[^"?]+\.(?:css|js)))(?:\?v=[^"]*)?"}{
      my ($link, $file) = ($1, $2);
      my $v = fingerprint($file);
      if (defined $v) { "$link?v=$v\"" } else { warn "MISSING  $file (linked from $page)\n"; $missing++; $& }
    }ge;
    next if $out eq $src;
    push @stale, $page;
    next if $mode eq "check";
    open my $o, ">:raw", $page or die "$page: $!";
    print $o $out;
    close $o;
  }
  if ($mode eq "check") {
    print "STALE    $_ (run scripts/bump-versions.sh)\n" for @stale;
    print scalar(@ARGV) . " pages checked, " . scalar(@stale) . " with an outdated ?v=.\n";
  } else {
    printf "  %-34s ?v=%s\n", $_, $fp{$_} for grep { defined $fp{$_} } sort keys %fp;
    if (@stale) {
      print "Updated " . scalar(@stale) . " page(s). Upload the changed CSS/JS first, then:\n";
      print "  $_\n" for @stale;
    } else {
      print "All " . scalar(@ARGV) . " pages already carry the current fingerprints.\n";
    }
  }
  exit(($missing || ($mode eq "check" && @stale)) ? 1 : 0);
' "$mode" "${pages[@]}"
