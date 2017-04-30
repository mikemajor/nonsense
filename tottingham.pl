#! /usr/bin/perl -w

# tottingham.pl         mike@mmajor.com
# This is a simple web client which grabs data from ESPNSoccernet.com to calculate
# and report the date on which St. Totteringham's Day occurs each year
# (see http://www.chiark.greenend.org.uk/~mikepitt/totteringham.html for more info).

# last modified 2017-04-30 by mike@mmajor.com


### Configuration ###
###my $standings_link = 'http://soccernet.espn.go.com/tables?league=eng.1';
##my $standings_link = 'http://news.bbc.co.uk/sport2/hi/football/eng_prem/table/default.stm';
#my $standings_link = 'http://www.espnfc.com/barclays-premier-league/23/table';
my $standings_link = 'http://www.bbc.com/sport/football/premier-league/table';
my @teams_to_report = ('Arsenal', 'Tottenham');
my $length_of_season_in_games = 38;
my $points_for_win = 3;
my $points_for_draw = 1;

# these stats arrays should represent the order in which stats are reported on the standings_link page
# [ note: no prefix = overall, h_ = home, a_ = away ]
##my @stats = qw( Pos TEAM P W D L GS GA h_W h_D h_L h_GS h_GA a_W a_D a_L a_GS a_GA GD Pts );
##my @stats_to_report = qw( Pos P W D L Pts );
#my @stats = qw( Pos TEAM P h_W h_D h_L h_GS h_GA a_W a_D a_L a_GS a_GA GD Pts );
#my @stats_to_report = qw( Pos P Pts GD Games_left Pts_back Pts_left Max_pts );
my @stats = qw( Pos TEAM P W D L GS GA GD Pts );
my @stats_to_report = qw( Pos P Pts GD Games_left Pts_back Pts_left Max_pts );


### Initialization ###
use LWP::Simple qw(!head);
use HTML::Parse;
use HTML::Element;


### Main Routine ###
my $out_text= '';

### Retrieve the content of the page at $standings_link
########### using lynx -dump to get text

# example of the dump output:
##
##      Barclays Premier League Table
##
##         CAPTION: This table charts the Barclays Premier League teams
##
##         Position Team P W D L F A GD Pts Last 10 games results Match status
##         Last updated 8 days ago
##         No movement 1 [95]Chelsea 29 20 7 2 61 25 36 67
##         No movement 2 [97]Man City 30 18 7 5 62 28 34 61
##         No movement 3 [99]Arsenal 30 18 6 6 58 31 27 60
##


# Capture the output from a lynx dump of the page
use IPC::System::Simple qw(capture);
my $standings_dump = capture("lynx -dump $standings_link");
#my $standings_dump = capture("curl $standings_link");
# Check that some content was returned
if (!defined($standings_dump)) {
  print "ERROR: Failed to dump text from the standings link\n";
  exit;
}

#print "\n------ DUMP----- \n";
#print "$standings_dump\n";
#print "\n------ DUMP----- \n";


# Create an array containing each dumped line of text
my @dump_lines = split(/\n+/, $standings_dump);

# Grab just the table portion of the dump and put the table rows into an array
my @table_lines;
foreach (@dump_lines) {
  next if /^\s*$/;
  next if /^\s*_+/;
  #print "$_\n" if m/(No movement|Moving up|Moving down)/;
  push(@table_lines, $_) if m/(No movement|Moving up|Moving down)/;
}
## Clean up the array elements
foreach (@table_lines) {
  #next unless /^\s*\d+\s+\[.*$/;
  s/(No movement|Moving up|Moving down)//;      # remove movement notes
  s/^\s+//;                                     # remove leading whitespace
  s/\s+$//;                                     # remove trailing whitespace
  s/\[[0-9]+\]//;                               # remove weird numbers from team names
  s/\s(United|Utd|City|Ham|Hotspur|Villa|Wednesday|\&|Hove|Albion|Brom|Town)/$1/;       # consider team names that have spaces in them
  push(@cleaned_lines, $_);
}


# ### DEBUG
# print "\n\n\n----- CLEANED LINES ----- \n";
# foreach (@cleaned_lines) {
#   print "$_\n";
# }
# print "----- CLEANED LINES ----- \n";
# ### END DEBUG

# Put the table lines into arrays, then into stats hashes
foreach (@cleaned_lines) {
  next unless /^\s*\d+\s+\[.*$/;
  s/^\s+//;             # remove leading whitespace
  s/\s+$//;             # remove trailing whitespace
  s/\[[0-9]+\]//;       # remove weird numbers from team names
  push(@cleaned_lines, $_);
}


# Retrieve the desired stats for the desired teams and store them as hashes in another hash
my %teams_stats = ();
if (@teams_to_report) {
  if (@stats_to_report) {

    #stats:             Pos TEAM P h_W h_D h_L h_GS h_GA a_W a_D a_L a_GS a_GA GD Pts
    #stats_to_report:   Pos P Pts GD Games_left Pts_back Pts_left Max_pts
    #output format:       TEAM  Pos  P  Pts  GD g_l p_b p_l max BACK
    $out_text .= sprintf("\n%16s\t%3s %2s %3s %3s %10s %8s %8s %7s %10s\n", 'TEAM', @stats_to_report, ' ');



    foreach my $team (@teams_to_report) {

        # Create individual team stats hashes by mapping the table rows with the header-rows as arrays using hashslices
        my %team_stats = ();
        #whuh? my @team_line = grep { $_ = $team } @cleaned_lines;
        foreach (@cleaned_lines) {
          if (m/ $team/) {
            my @team_line = split(/\s+/, $_);
            #foreach(@team_line) { print "[$_] "; } print "\n"; # DEBUG
            @team_stats{ @stats } = @team_line;

            foreach my $stat (@stats) {
              $teams_stats{$team}{$stat} = $team_stats{$stat};
            }
            # Add some computed stats
            if($team_stats{$team}{'h_W'})  {$teams_stats{$team}{'W'}  = $team_stats{'h_W'}  + $team_stats{'a_W'}  };
            if($team_stats{$team}{'h_D'})  {$teams_stats{$team}{'D'}  = $team_stats{'h_D'}  + $team_stats{'a_D'}  };
            if($team_stats{$team}{'h_L'})  {$teams_stats{$team}{'L'}  = $team_stats{'h_L'}  + $team_stats{'a_L'}  };
            if($team_stats{$team}{'h_GS'}) {$teams_stats{$team}{'GS'} = $team_stats{'h_GS'} + $team_stats{'a_GS'} };
            if($team_stats{$team}{'h_GA'}) {$teams_stats{$team}{'GA'} = $team_stats{'h_GA'} + $team_stats{'a_GA'} };

            # #DEBUG
            # for my $key ( keys %teams_stats ) {
            #     print "$key: ";
            #     for $skey ( keys %{ $teams_stats{$key} } ) {
            #          print "$skey=$teams_stats{$key}{$skey} ";
            #     }
            #     print "\n";
            # }

          }
        }

    }
  } else { $out_text .= print "\nNo stats to look up\n\n"; }
} else { $out_text .= print "\nNo teams to report\n\n"; }



## Determine the comparative rankings of the teams to report, and
##   calculate comparison of points behind, games left, & possible points left.
## Also calculate comparative "games back" for teams following the leaders
##   note:   GB = (number of fewer wins + number of extra losses) / 2
my ($GB, $leader_wins, $leader_losses, $games_back);
my ($leader_played, $leader_games_left, $leader_points, $leader_points_left, $leader_max_points);

# Loop through the teams to report with a comparative sort which orders
#   them by league position (from highest-placed to lowest)
my $rank_order = 1;
my $totterinham_clinch = '';
foreach my $rteam ( sort { $teams_stats{$a}->{'Pos'} <=> $teams_stats{$b}->{'Pos'} } keys %teams_stats){
  # If the team's rank_order is 1, that team is in the lead in the league among the teams to report.
  if ($rank_order == 1) {
    $leader_wins = $teams_stats{$rteam}{'W'};
    $leader_losses = $teams_stats{$rteam}{'L'};
    $leader_played = $teams_stats{$rteam}{'P'};
    $leader_points = $teams_stats{$rteam}{'Pts'};
    $leader_games_left = $length_of_season_in_games - $teams_stats{$rteam}{'P'};
    $leader_points_left = $leader_games_left * $points_for_win;
    $leader_max_points = $leader_points + $leader_points_left;
  }

  $games_back = (($leader_wins - $teams_stats{$rteam}{'W'}) + ( $teams_stats{$rteam}{'L'} - $leader_losses)) / 2;

  if ($games_back == 0) {
    $GB = "---";
  } else {
    $GB = sprintf("%3.1f", $games_back);
  }

  # Compute the comparative stats
  my $points_back = $leader_points - $teams_stats{$rteam}{'Pts'};
  my $games_left = $length_of_season_in_games - $teams_stats{$rteam}{'P'};
  my $points_left = $games_left * $points_for_win;
  my $max_points = $teams_stats{$rteam}{'Pts'} + $points_left;

  # Determine St. Totteringham's day status
  $totteringham_clinch = '';
  if ($rteam =~ /Tottenham/ ) {
    if ( $max_points < $leader_points ) {
      $totteringham_clinch = 'Eliminated';
    } elsif ( $max_points == $leader_points ) {
      $totteringham_clinch = 'Need a miracle';
    }
  } elsif ($rteam =~ /Arsenal/ ) {
    if ($points_back > $points_left) {
      # don't bother coding it; this will never happen
    }
  }

  #output format:       TEAM  Pos  P  Pts  GD g_l p_b p_l max  -----
  $out_text .= sprintf("%16s\t%3s %2s %3s %3s %10s %8s %8s %7s %10s\n",
                $rteam,
                $teams_stats{$rteam}{'Pos'},
                $teams_stats{$rteam}{'P'},
                $teams_stats{$rteam}{'Pts'},
                $teams_stats{$rteam}{'GD'},
                $games_left,
                $points_back,
                $points_left,
                $max_points,
                $totteringham_clinch
  );
  $rank_order++;
}


# Display results
print "$out_text\n";

# Email mike if it's St. Totteringham's Day
if ($totteringham_clinch) {
  #mailsendmail('mikeokb@gmail.com', 'mmajor@localhost', "Happy St. Totteringham's Day!", "\n$out_text\n", 'mike@mmajor.com');
  mailMimeLite('mikeokb@gmail.com', 'mmajor@localhost', "Happy St. Totteringham's Day!", "\n$out_text\n", 'mike@mmajor.com');
}


### Local subroutines ###
# Used if called from another script
sub show_output {
        return "\n<pre>\n$out_text\n</pre>\n";
}

# Find the number position of an item in a list
# (requires that the '~' character doesn't appear in the data)
# Usage: array_rank($item, @list);
sub array_rank {
  my $value = shift;
  local($_) = '~' . join('~', @_) . '~';
  return unless /^(.*?~$value)~/;
  my $chunk = $1;
  $chunk =~ tr/~//;
}


sub hashValueAscendingNum {
  $hash{$a} <=> $hash{$b};
}

sub hashValueDescendingNum {
  $hash{$b} <=> $hash{$a};
}


#       Simple Email Function

#       ($to, $from, $subject, $message)

sub sendmail_Email {
#sendmail_Email( TO Email, FROM email, SUBJECT of email, BODY of email );
        my ($to, $from, $subject, $message) = @_;
        my $sendmail = '/usr/lib/sendmail';
        open(MAIL, "|$sendmail -oi -t");
                print MAIL "From: $from\n";
                print MAIL "To: $to\n";
                print MAIL "Subject: $subject\n\n";
                print MAIL "$message\n";
        close(MAIL);
        print "Email sent.\n"
}

sub mailMimeLite {
  my ($to, $from, $subject, $message, $cc) = @_;

  use MIME::Lite;
  my $msg = MIME::Lite->new(
     To      => $to,
     From    => $from,
     Subject => $subject,
     Type    => "text/html",
     Data    => "<pre>$message</pre>"
  );

  $msg->send();
}


sub mailsendmail {
  use Mail::Sendmail;
  my ($to, $from, $subject, $message, $cc) = @_;

  %mail = ( To          => $to,
            Cc          => $cc,
            From        => $from,
            Subject     => $subject,
            Message     => $message
           );
  sendmail(%mail) or die $Mail::Sendmail::error;
  #print "mail sent ok:\n", $Mail::Sendmail::log;
}
