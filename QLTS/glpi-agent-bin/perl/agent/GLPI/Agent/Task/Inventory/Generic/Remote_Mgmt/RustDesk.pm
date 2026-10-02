package GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk;

# Based on the work done by Ilya published on no more existing https://fusioninventory.userecho.com site

use strict;
use warnings;

use parent 'GLPI::Agent::Task::Inventory::Module';

use English qw(-no_match_vars);

use GLPI::Agent::Tools;

# Every place a RustDesk.toml is known to live. The LocalService path is the
# one a service-mode install uses, but an agent running as a plain user cannot
# read it - which is why a single hardcoded path made isEnabled() always false
# and skipped doInventory() entirely.
sub _getConfigPaths {
    my (%params) = @_;

    my $osname = $params{osname} // OSNAME;

    if ($osname eq 'MSWin32') {
        my @paths = (
            'C:\Windows\ServiceProfiles\LocalService\AppData\Roaming\RustDesk\config\RustDesk.toml',
        );
        push @paths, $ENV{APPDATA}.'\RustDesk\config\RustDesk.toml'
            if $ENV{APPDATA};
        push @paths, $ENV{ProgramData}.'\RustDesk\config\RustDesk.toml'
            if $ENV{ProgramData};
        return @paths;
    }

    return (
        '/root/.config/rustdesk/RustDesk.toml',
        '/etc/rustdesk/RustDesk.toml',
    );
}

sub _getExecutable {
    my (%params) = @_;

    my $osname = $params{osname} // OSNAME;

    return 'rustdesk' unless $osname eq 'MSWin32';

    GLPI::Agent::Tools::Win32->require();
    my $installLocation = GLPI::Agent::Tools::Win32::getRegistryValue(
        path   => "HKEY_LOCAL_MACHINE/SOFTWARE/Microsoft/Windows/CurrentVersion/Uninstall/RustDesk/InstallLocation",
        logger => $params{logger}
    );

    return (empty($installLocation) ? 'C:\Program Files\RustDesk' : $installLocation)
        . '\rustdesk.exe';
}

# --get-id only exists since RustDesk 1.2.2. The version gate is kept on
# purpose: passing an unknown flag to an older GUI build can pop a window on
# the user's screen, which is unacceptable on a staff workstation.
sub _supportsGetId {
    my (%params) = @_;

    my $version = $params{version};
    unless (defined($version)) {
        return 0 unless $params{command};
        $version = getFirstLine(
            command => $params{command}." --version",
            logger  => $params{logger}
        );
    }

    return 0 unless $version && $version =~ /^(\d+)\.(\d+)\.(\d+)/;

    my ($major, $minor, $patch) = (int($1), int($2), int($3));

    return $major > 1                                       ? 1
         : $major == 1 && $minor > 2                         ? 1
         : $major == 1 && $minor == 2 && $patch >= 2         ? 1
         :                                                     0;
}

sub isEnabled {
    foreach my $path (_getConfigPaths()) {
        return 1 if has_file($path);
    }

    return canRun(_getExecutable()) ? 1 : 0;
}

# Ask the binary first: it is the only reliable source since RustDesk 1.3
# stores an encrypted enc_id instead of a plain id in the config file.
sub _getID {
    my (%params) = @_;

    my $osname  = $params{osname} // OSNAME;
    my $logger  = $params{logger};
    my $command = $params{command} // _getExecutable(
        osname => $osname,
        logger => $logger
    );

    if (canRun($command)) {
        my $quoted = $osname eq 'MSWin32' ? '"'.$command.'"' : $command;
        if (_supportsGetId(command => $quoted, logger => $logger)) {
            my $id = getFirstMatch(
                command => $quoted." --get-id",
                logger  => $logger,
                pattern => qr/^(\d+)$/
            );
            return $id if defined($id) && length($id);
            $logger->debug("RustDesk --get-id gave nothing, RustDesk may not be running")
                if $logger;
        } else {
            $logger->debug("RustDesk too old for --get-id, reading config file instead")
                if $logger;
        }
    }

    my @files = $params{files} ? @{$params{files}} : _getConfigPaths(osname => $osname);
    foreach my $file (@files) {
        next unless has_file($file);
        my $id = getFirstMatch(
            file    => $file,
            logger  => $logger,
            pattern => qr/^id\s*=\s*'?(\d+)'?\s*$/
        );
        return $id if defined($id) && length($id);
    }

    return;
}

sub doInventory {
    my (%params) = @_;

    my $inventory = $params{inventory};
    my $logger    = $params{logger};

    my $RustDeskID = _getID(logger => $logger);

    unless (defined($RustDeskID) && length($RustDeskID)) {
        $logger->debug('RustDesk ID not found') if $logger;
        return;
    }

    $logger->debug('Found RustDesk ID : ' . $RustDeskID) if $logger;

    $inventory->addEntry(
        section => 'REMOTE_MGMT',
        entry   => {
            ID   => $RustDeskID,
            TYPE => 'rustdesk'
        }
    );
}

1;
