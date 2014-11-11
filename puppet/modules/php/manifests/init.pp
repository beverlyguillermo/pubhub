class php {

    package { 'php54':
        ensure => present,
        require => Yumrepo['ius'],
        notify  => Service['httpd'],
    }

    package { [
            'php54-cli',
            'php54-common',
            'php54-devel',
            'php54-gd',
            'php54-ldap',
            'php54-mbstring',
            'php54-mcrypt',
            'php54-mysql',
            'php54-pdo',
            'php54-pear',
            'php54-pecl-apc',
            'php54-xml',
            'php54-pecl-xdebug',
            'php-pear-phing',
        ]:
        ensure => present,
        require => Package['php54'],
    }

    file { "/etc/php.d/xdebug.ini":
        source => "puppet:///modules/php/etc/php.d/xdebug.ini",
        owner => "root",
        group => "root",
        mode => 644,
        require => Package['php54'],
        notify  => Service['httpd'],
    }

    exec { "version control - git":
        command => "/usr/bin/wget http://download.pear.php.net/package/VersionControl_Git-0.4.4.tgz && /usr/bin/pear install VersionControl_Git-0.4.4.tgz && /bin/sh -c 'rm VersionControl_Git-0.4.4.tgz'",
        unless => "pear info VersionControl_Git",
        require => Package['php54-pear'],
    }
}
