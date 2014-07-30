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
            'php-pear-phing',
        ]:
        ensure => present,
        require => Package['php54'],
    }

    # Install various PEAR packages
    # exec { "pear auto-discover":
    #     command => "pear config-set auto_discover 1 system",
    #     unless => "pear config-get auto_discover system | grep 1",
    #     require => Package['php54-pear'],
    # }
    # exec { "pear upgrade":
    #     command => "pear upgrade",
    #     unless => "pear info pear | grep PEAR.PHP.NET/PEAR-1.9.4",
    #     require => [Package['php54-cli'], Package['php54-pear']],
    # }
    # exec { "pear update-channels":
    #     command => "pear update-channels",
    #     unless => "pear info pear | grep PEAR.PHP.NET/PEAR-1.9.4",
    #     require => Exec['pear upgrade'],
    # }
    # exec { "pear-phpunit":
    #     command => "pear install --alldeps pear.phpunit.de/PHPUnit",
    #     unless => "pear info pear.phpunit.de/PHPUnit",
    #     require => Exec['pear update-channels'],
    #     returns => [0, '', ' '],
    # }
    # exec { "pear-phing":
    #     command => "pear install pear.phing.info/phing-2.5.0",
    #     unless => "pear info pear.phing.info/phing",
    #     require => Exec['pear update-channels'],
    #     returns => [0, '', ' '],
    # }
    # # exec { "pear-phpcpd":
    # #     command => "pear install pear.phpunit.de/phpcpd",
    # #     unless => "pear info pear.phpunit.de/phpcpd",
    # #     require => Exec['pear update-channels'],
    # #     returns => [0, '', ' '],
    # # }
    # # exec { "pear-phploc":
    # #     command => "pear install pear.phpunit.de/phploc",
    # #     unless => "pear info pear.phpunit.de/phploc",
    # #     require => Exec['pear update-channels'],
    # #     returns => [0, '', ' '],
    # # }
    # # exec { "pear-phpmd":
    # #     command => "pear install --alldeps pear.phpmd.org/PHP_PMD",
    # #     unless => "pear info pear.phpmd.org/PHP_PMD",
    # #     require => Exec['pear update-channels'],
    # #     returns => [0, '', ' '],
    # # }
    # # exec { "pear-phpdoc":
    # #     command => "pear install pear.phpdoc.org/phpDocumentor-alpha",
    # #     unless => "pear info pear.phpdoc.org/phpDocumentor-alpha",
    # #     require => Exec['pear update-channels'],
    # #     returns => [0, '', ' '],
    # # }
    # # exec { "pear-code-coverage":
    # #     command => "pear install pear.phpunit.de/PHP_CodeCoverage",
    # #     unless => "pear info pear.phpunit.de/PHP_CodeCoverage",
    # #     require => Exec['pear update-channels'],
    # #     returns => [0, '', ' '],
    # # }
    # does not install via pear command; must do this manually.
    exec { "version control - git":
        command => "/usr/bin/wget http://download.pear.php.net/package/VersionControl_Git-0.4.4.tgz && /usr/bin/pear install VersionControl_Git-0.4.4.tgz && /bin/sh -c 'rm VersionControl_Git-0.4.4.tgz'",
        unless => "pear info VersionControl_Git",
        require => Package['php54-pear'],
    }

}