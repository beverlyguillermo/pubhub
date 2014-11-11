class nodejs {

	package { 'nodejs':
        ensure => present,
        require => Yumrepo['ius'],
    }

    package { 'npm':
        ensure => present,
        require => Package['nodejs'],
    }

}