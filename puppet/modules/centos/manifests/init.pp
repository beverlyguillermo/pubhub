# Custom type: line for adding/removing lines from files
include line

# Default actions for CentOS development boxes (do not use these for production!)
class centos {
    line { 'disable-ipv6-all':
        file => "/etc/sysctl.conf",
        line => "net.ipv6.conf.all.disable_ipv6 = 1",
    }

    line { 'disable-ipv6-default':
        file => "/etc/sysctl.conf",
        line => "net.ipv6.conf.default.disable_ipv6 = 1",
    }

    service { 'iptables':
        ensure => 'stopped',
    }
}