class postfix {

	package { ["postfix"]:
        ensure => present,
    }

    service { "postfix":
        require   => Package["postfix"],
	    enable    => true,
	    ensure    => running,
	    hasstatus => true,
    }

}


