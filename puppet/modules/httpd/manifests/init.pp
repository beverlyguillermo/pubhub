class httpd {

    package { ["httpd", "mod_ssl", "openssl"]:
        ensure => present,
    }

    service { "httpd":
        enable     => true,
        ensure     => running,
        hasrestart => true,
        subscribe  => [Package["httpd", "mod_ssl"], File["/etc/httpd/conf/httpd.conf"]],
    }

    exec { "/etc/init.d/httpd reload": 
        command      => "/etc/init.d/httpd reload", 
        refreshonly  => true, 
    }

    file { ["/usr/local/apache", "/usr/local/apache/htdocs"]:
        ensure => "directory",
    }

    file { [ "/var/cache/apache", "/var/cache/apache/wsdl_cache", "/var/tmp/php" ]:
        ensure  => "directory",
        owner   => "apache",
        group   => "apache",
        require => Package["httpd"], # httpd package creates apache user
    }

    file { "/var/log/httpd":
        ensure => "directory",
        owner => "root",
        group => "root",
        mode => 711, # allow loggers to write to logs
    }

    # users allowed to write to log files
    group { "loggers":
        ensure => "present",
    }

    # When a log is written from a CLI program
    user { "vagrant":
        ensure => "present",
        groups => "loggers",
    }

    # When a log is written from the server (like PHP)
    user { "apache":
        ensure => "present",
        groups => "loggers",
    }

    file { "/etc/httpd":
        ensure => "directory",
    }

    file { [ "/etc/httpd/conf", "/etc/httpd/conf.d", "/etc/httpd/vhosts"]:
        ensure => "directory",
        recurse => true,
    }

    file { "/etc/httpd/vhosts.d" :
        ensure => "directory",
        recurse => true,
        notify => Service["httpd"],
    }

    file { "/etc/httpd/conf/httpd.conf":
        source => "puppet:///modules/httpd/etc/httpd/conf/httpd.conf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/hosts":
        source => "puppet:///modules/httpd/etc/hosts",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/httpd/conf.d/ssl.conf":
        source => "puppet:///modules/httpd/etc/httpd/conf.d/ssl.conf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/pki/tls/certs/ca.crt":
        source => "puppet:///modules/httpd/etc/pki/tls/certs/ca.crt",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/pki/tls/private/ca.csr":
        source => "puppet:///modules/httpd/etc/pki/tls/private/ca.csr",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/pki/tls/private/ca.key":
        source => "puppet:///modules/httpd/etc/pki/tls/private/ca.key",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }
    
}
