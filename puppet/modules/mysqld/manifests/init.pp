class mysqld {

    package { [
                "mysql",
                "mysql-server",
                "perl-DBD-MySQL",
              ]:
        ensure  => installed,
        require => Package['php54'],
    }

    service { "mysqld":
        enable     => true,
        ensure     => running,
        subscribe  => Package["mysql"],
    }

    exec { 'set root password':
        unless  => "mysql -u root --password='${mysql_root_password}'",
        command => "mysqladmin -u root password '${mysql_root_password}'",
        require => Service['mysqld'],
    }

    file { "/etc/my.cnf":
        source => "puppet:///modules/mysqld/etc/my.cnf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }
}