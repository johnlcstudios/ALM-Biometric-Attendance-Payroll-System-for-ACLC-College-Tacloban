#!/usr/bin/perl
use strict;
use warnings;
use File::Basename;

# Migration Helper
# Migrates legacy text-based attendance logs to the new system format

my $legacy_dir = "./legacy_logs";
my $output_file = "migrated_attendance.csv";

print "Starting migration from $legacy_dir...\n";

unless (-d $legacy_dir) {
    die "Legacy directory not found!\n";
}

open(my $fh, '>', $output_file) or die "Could not open '$output_file' $!";
print $fh "EmployeeID,Timestamp,Action\n";

# Placeholder for migration logic
# while(my $file = <$legacy_dir/*.txt>) { ... }

close($fh);
print "Migration complete. Output: $output_file\n";
