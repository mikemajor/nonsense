# tottingham.pl

Reports whether it's St. Totteringham's Day: the day Arsenal become mathematically certain to finish above Tottenham in the Premier League. See the [original explainer](http://www.chiark.greenend.org.uk/~mikepitt/totteringham.html).

## Requirements

Perl with core modules only (`JSON::PP`, `HTTP::Tiny`, `Getopt::Long`). Fetching over HTTPS needs `IO::Socket::SSL` and `Net::SSLeay`, which most Perl installs include.

Standings come from ESPN's public JSON endpoint. No API key is needed.

## Usage

```
perl tottingham.pl [--mail|--dry-run --to ADDR [--from ADDR]] [--file standings.json] [--state FILE]
```

| Option | Effect |
|---|---|
| `--mail` | Email the report if St. Totteringham's Day has arrived (see below). |
| `--dry-run` | Like `--mail`, but print the email it would send (or why it wouldn't) and send nothing. The state file is not touched. |
| `--to ADDR` | Recipient address. Required with `--mail` or `--dry-run`. |
| `--from ADDR` | Sender address. Default `tottingham@localhost`. |
| `--file F` | Read standings from a local JSON file instead of fetching. Useful for testing. |
| `--state F` | State file recording the season already mailed. Default `~/.tottingham_state`. |

Example output:

```
TEAM       Pos  P  Pts   GD  Left  PtsL   Max
Arsenal      2  5   12    4    33    99   111
Tottenham   20  5    2   -6    33    99   101

Not yet
```

`Left` is games left, `PtsL` is points still available, `Max` is the most points a team can finish with.

Possible status lines:

- **Happy St. Totteringham's Day!** Arsenal's points exceed Tottenham's maximum possible total.
- **Spurs can only draw level on points; goal difference decides.** Tottenham's maximum equals Arsenal's points.
- **Arsenal cannot catch Spurs.** Tottenham's points exceed Arsenal's maximum.
- **Not yet.** Anything else.

The calculation assumes a 38-game season and 3 points per win. Both are variables at the top of the script.

## Email

`--mail` sends the report only when Arsenal have clinched, and at most once per season. The season is recorded in the state file after a successful send. If sending fails, nothing is recorded and the next run retries.

Setup:

1. Pass `--to` (and optionally `--from`) with `--mail`.
2. Make sure `/usr/sbin/sendmail` exists and can deliver mail, or change `$sendmail`. The `TOTTINGHAM_SENDMAIL` environment variable also overrides the path.

Local sendmail delivers directly, so it only works where outbound port 25 is open and the sending host is trusted by the recipient's mail provider. Home and office networks often block port 25, and large providers such as Gmail may reject or spam-filter mail from a machine without proper DNS. If mail does not arrive, check `mailq`, or configure your local MTA to relay through an authenticated SMTP server (Postfix `relayhost`, msmtp, and similar).

To check delivery without waiting for a clinch, send a message through the same path:

```
printf 'To: you@example.com\nSubject: test\n\ntest\n' | /usr/sbin/sendmail -oi -t
```

## Running daily with cron

```
0 8 * * * /usr/bin/perl /path/to/tottingham.pl --mail --to you@example.com >> $HOME/.tottingham.log 2>&1
```

The state file keeps `--mail` from sending again on later days.

## Tests

```
prove t/
```

The tests run the script against generated standings, so they need no network access.
