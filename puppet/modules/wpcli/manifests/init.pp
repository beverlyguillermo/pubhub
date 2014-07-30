class wpcli {

    file { '/usr/share/wp-cli':
        ensure => directory,
        before => File['wpcli installer'],
    }

    file { 'wpcli installer':
        path   => '/usr/share/wp-cli/install.sh',
        ensure => present,
        source => "puppet:///modules/wpcli/install.sh",
        owner  => $owner,
        mode   => '0744',
        before => Exec['install wpcli'],
    }

    exec { 'install wpcli':
        command => "install.sh",
        path    => "/usr/share/wp-cli",
    }

    file { '/usr/bin/wp':
        ensure  => link,
        target  => "/usr/share/wp-cli/bin/wp",
        require => Exec['install wpcli'],
    }

}