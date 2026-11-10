#!/usr/bin/perl
use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use File::Spec;
use FindBin qw($Bin);
use JSON::PP qw(encode_json);

# Runs tottingham.pl against generated standings (--file) and checks the verdict.

my $script = "$Bin/../tottingham.pl";
my $dir    = tempdir(CLEANUP => 1);

sub entry {
  my ($name, $rank, $played, $pts) = @_;
  return { team  => { displayName => $name },
           stats => [ { name => 'rank',              value => $rank },
                      { name => 'gamesPlayed',       value => $played },
                      { name => 'points',            value => $pts },
                      { name => 'pointDifferential', value => 0 },
                      { name => 'wins',              value => 0 },
                      { name => 'losses',            value => 0 } ] };
}

# standings(ars => [played, pts], tot => [played, pts]) -> path to JSON file
my $n = 0;
sub standings {
  my ($ars, $tot) = @_;
  my @e = (entry('Arsenal', 1, @$ars), entry('Tottenham Hotspur', 2, @$tot));
  my $path = File::Spec->catfile($dir, 'standings' . $n++ . '.json');
  open(my $fh, '>', $path) or die $!;
  print $fh encode_json({ seasons => [ { year => 2026 } ],
                          children => [ { standings => { entries => \@e } } ] });
  close($fh);
  return $path;
}

sub run {
  my ($file, @args) = @_;
  my $out = `perl $script --file $file @args 2>&1`;
  return $out;
}

my $clinched = qr/Happy St\. Totteringham's Day!/;
my $level    = qr/goal difference decides/;
my $cannot   = qr/Arsenal cannot catch Spurs/;
my $not_yet  = qr/Not yet/;

# With 36 games played, 2 are left: Spurs can add at most 6 points.
my @cases = (
  # name,                                  ars [P, Pts], tot [P, Pts], expected
  ['Arsenal one point above Spurs max',    [36, 27], [36, 20], $clinched],
  ['Arsenal level with Spurs max',         [36, 26], [36, 20], $level],
  ['Arsenal one point below Spurs max',    [36, 25], [36, 20], $not_yet],
  ['Spurs ahead but Arsenal can catch',    [36, 20], [36, 22], $not_yet],
  ['Spurs points above Arsenal max',       [36, 10], [36, 17], $cannot],
  ['Spurs level with Arsenal max',         [36, 10], [36, 16], $not_yet],
  ['Early season, nothing settled',        [5, 12],  [5, 2],   $not_yet],
  ['Different games played: Spurs in hand',[34, 60],  [33, 50], $not_yet],
  ['Clinch with Spurs having more games left', [36, 60], [30, 20], $clinched],
);

for my $c (@cases) {
  my ($name, $ars, $tot, $want) = @$c;
  like(run(standings($ars, $tot)), $want, $name);
}

# Tottenham ahead of Arsenal in the table must never be reported as clinched.
{
  my $path = standings([36, 20], [36, 30]);
  unlike(run($path), $clinched, 'Spurs above Arsenal is not a clinch');
}

# Mail: sent once per season, only when clinched; --dry-run sends nothing.
{
  my $log  = "$dir/mail.log";
  my $fake = "$dir/fake-sendmail";
  open(my $fh, '>', $fake) or die $!;
  print $fh "#!/bin/sh\ncat >> $log\n";
  close($fh);
  chmod 0755, $fake;
  local $ENV{TOTTINGHAM_SENDMAIL} = $fake;
  my $state = "$dir/state";
  my $won   = standings([36, 80], [36, 20]);
  my $lost  = standings([36, 20], [36, 80]);
  my $mails = sub {
    return 0 unless -e $log;
    open(my $l, '<', $log) or die $!;
    return scalar grep { /^Subject:/ } <$l>;
  };
  my @mail = ('--to', 'a@example.com', '--state', $state);

  run($lost, '--mail', @mail);
  is($mails->(), 0, 'no mail when not clinched');
  ok(!-e $state, 'no state written when not clinched');

  run($won, '--dry-run', @mail);
  is($mails->(), 0, '--dry-run sends nothing');
  ok(!-e $state, '--dry-run leaves state untouched');

  run($won, '--mail', @mail);
  is($mails->(), 1, 'mail sent when clinched');
  run($won, '--mail', @mail);
  is($mails->(), 1, 'mail not repeated for the same season');

  like(run($won, '--mail'), qr/requires --to/, '--mail without --to is rejected');
}

done_testing;
