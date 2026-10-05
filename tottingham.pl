#!/usr/bin/perl
use strict;
use warnings;

# tottingham.pl
# Calculates and reports whether it is St. Totteringham's Day: the day Arsenal
# become mathematically certain to finish above Tottenham in the Premier League.
# (see http://www.chiark.greenend.org.uk/~mikepitt/totteringham.html)
#
# Data: ESPN's public standings JSON. Core Perl modules only.
#
# Usage: tottingham.pl [--mail|--dry-run --to ADDR [--from ADDR]] [--file standings.json]
#   --mail   send the report via sendmail if St. Totteringham's Day has arrived
#   --dry-run  like --mail, but print the email (or why none would be sent) instead of
#              sending it; the state file is left untouched
#   --to     recipient address (required with --mail/--dry-run)
#   --from   sender address (default tottingham@localhost)
#   --file   read standings from a local JSON file instead of fetching (testing)
#   --state  state file recording the season already mailed (default ~/.tottingham_state)
#            so --mail sends at most once per season

use JSON::PP qw(decode_json);
use HTTP::Tiny;
use Getopt::Long;

### Configuration ###
my $standings_url  = 'https://site.api.espn.com/apis/v2/sports/soccer/eng.1/standings';
my $season_games   = 38;
my $points_for_win = 3;
my $mail_from      = 'tottingham@localhost';
my $mail_to;
my $sendmail       = $ENV{TOTTINGHAM_SENDMAIL} || '/usr/sbin/sendmail';
my $state_file     = ($ENV{HOME} || '.') . '/.tottingham_state';

my ($do_mail, $dry_run, $file);
GetOptions('mail' => \$do_mail, 'dry-run' => \$dry_run, 'to=s' => \$mail_to, 'from=s' => \$mail_from,
           'file=s' => \$file, 'state=s' => \$state_file)
  or die "usage: $0 [--mail|--dry-run --to ADDR [--from ADDR]] [--file F] [--state F]\n";
$do_mail = 1 if $dry_run;
die "--mail/--dry-run requires --to ADDR\n" if $do_mail && !$mail_to;

### Fetch ###
sub fetch_standings {
  if ($file) {
    open(my $fh, '<', $file) or die "Can't read $file: $!\n";
    local $/;
    return decode_json(<$fh>);
  }
  my $res = HTTP::Tiny->new(timeout => 20)->get($standings_url);
  die "Failed to fetch standings: $res->{status} $res->{reason}\n" unless $res->{success};
  return decode_json($res->{content});
}

my $data = fetch_standings();
my $entries = $data->{children}[0]{standings}{entries}
  or die "Unexpected standings format: no entries\n";

my %table;    # short team name => { Pos P Pts GD W L }
my %want = (Arsenal => qr/^Arsenal$/, Tottenham => qr/^Tottenham/);
for my $e (@$entries) {
  my %s = map { $_->{name} => $_->{value} } @{ $e->{stats} };
  my $name = $e->{team}{displayName};
  for my $key (keys %want) {
    next unless $name =~ $want{$key};
    $table{$key} = {
      Pos => $s{rank}, P => $s{gamesPlayed}, Pts => $s{points},
      GD  => $s{pointDifferential}, W => $s{wins}, L => $s{losses},
    };
  }
}
for my $key (keys %want) {
  die "Team '$key' not found in standings\n" unless $table{$key};
}

### Compute ###
for my $t (values %table) {
  $t->{Games_left} = $season_games - $t->{P};
  $t->{Pts_left}   = $t->{Games_left} * $points_for_win;
  $t->{Max_pts}    = $t->{Pts} + $t->{Pts_left};
}
my ($ars, $tot) = @table{qw(Arsenal Tottenham)};

my $status;
if ($ars->{Pts} > $tot->{Max_pts}) {
  $status = "Happy St. Totteringham's Day!";
} elsif ($ars->{Pts} == $tot->{Max_pts}) {
  $status = 'Spurs can only draw level on points; goal difference decides';
} elsif ($tot->{Pts} > $ars->{Max_pts}) {
  $status = 'Arsenal cannot catch Spurs: no St. Totteringham\'s Day this year';
} else {
  $status = 'Not yet';
}
my $clinched = $ars->{Pts} > $tot->{Max_pts};

### Report ###
my $fmt = "%-10s %3s %2s %4s %4s %5s %5s %5s\n";
my $out = sprintf($fmt, 'TEAM', 'Pos', 'P', 'Pts', 'GD', 'Left', 'PtsL', 'Max');
for my $name (sort { $table{$a}{Pos} <=> $table{$b}{Pos} } keys %table) {
  my $t = $table{$name};
  $out .= sprintf($fmt, $name, @{$t}{qw(Pos P Pts GD Games_left Pts_left Max_pts)});
}
$out .= "\n$status\n";
print $out;

### Mail ###
my $season = $data->{seasons}[0]{year} // 'unknown';
my $mailed_season = '';
if (open(my $sf, '<', $state_file)) {
  chomp($mailed_season = <$sf> // '');
  close($sf);
}
if ($do_mail && $dry_run) {
  if (!$clinched) {
    print "\n[dry-run] No email: St. Totteringham's Day has not arrived.\n";
  } elsif ($mailed_season eq $season) {
    print "\n[dry-run] No email: already sent for season $season.\n";
  } else {
    print "\n[dry-run] Would send via $sendmail:\n"
        . "From: $mail_from\nTo: $mail_to\nSubject: Happy St. Totteringham's Day!\n\n$out";
  }
} elsif ($do_mail && $clinched && $mailed_season ne $season) {
  open(my $mail, '|-', $sendmail, '-oi', '-t') or die "Can't run $sendmail: $!\n";
  print $mail "From: $mail_from\nTo: $mail_to\nSubject: Happy St. Totteringham's Day!\n\n$out";
  close($mail) or die "sendmail failed: $?\n";
  open(my $sf, '>', $state_file) or die "Can't write $state_file: $!\n";
  print $sf "$season\n";
  close($sf);
}
