class nodejs {

	package { 'nodejs':
        ensure => present,
        require => Yumrepo['ius'],
    }

    package { 'npm':
        ensure => present,
        require => Package['nodejs'],
    }

    exec { "lessc":
        command => "npm install -g less@1.3.3",
        require => [Package['npm']],
    }

}